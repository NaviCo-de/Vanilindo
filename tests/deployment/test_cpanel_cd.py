"""Offline transaction tests: these never connect to hosting or use real secrets."""
from copy import deepcopy
import json
from pathlib import Path, PurePosixPath
import ssl
import sys
import tempfile
import unittest
from unittest.mock import patch

CD = Path(__file__).resolve().parents[2] / "scripts/cd"
sys.path.insert(0, str(CD))
from bundle import copy_clean, digest, front_controller, migration_hash
from deploy import Bundle, DeploymentError, FTPS, deploy, health_check

FIRST, SECOND, THIRD = "a" * 40, "b" * 40, "c" * 40


def make_bundle(root, release=FIRST, migration=b"initial schema"):
    contents = {
        ("private", "vendor/autoload.php"): b"<?php // production autoload",
        ("private", "bootstrap/app.php"): b"<?php // application",
        ("private", "composer.lock"): b"{}",
        ("private", "database/migrations/001_initial.php"): migration,
        ("public", "index.php"): front_controller(release).encode(),
        ("public", "build/manifest.json"): b'{"new":true}',
        ("public", "build/assets/new.css"): b"body{color:green}",
        ("public", "storage/.htaccess"): b"Require all denied",
    }
    entries = []
    for (area, name), data in contents.items():
        path = root / area / name
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_bytes(data)
        entries.append(dict(area=area, path=name, size=len(data), sha256=digest(data)))
    migrations = {"001_initial.php": digest(migration)}
    manifest = dict(version=1, release_id=release, files=entries,
                    migrations=migrations, migration_hash=migration_hash(migrations))
    (root / "manifest.json").write_text(json.dumps(manifest))
    return Bundle(root)


class MemoryRemote:
    """Filesystem model with injected network/rename failures, not a mock of deploy()."""
    def __init__(self):
        self.dirs = {"/"}
        self.files = {}
        self.reads = []
        self.operations = []
        self.failure = lambda *args: None
        for path in ("/vanilindo/storage/framework", "/vanilindo/database/migrations",
                     "/public_html/build", "/public_html/storage"):
            self.ensure_dir(path)
        self.files.update({
            "/vanilindo/.env": b"private environment that must never be read",
            "/vanilindo/storage/framework/session": b"existing authenticated session",
            "/vanilindo/database/migrations/001_initial.php": b"initial schema",
            "/vanilindo/app-legacy.php": b"existing private application",
            "/public_html/index.php": b"legacy index with private maintenance.php support",
            "/public_html/build/manifest.json": b"old manifest",
            "/public_html/storage/.htaccess": b"old media rules",
            "/public_html/storage/customer-photo.jpg": b"irreplaceable existing media",
            "/public_html/php.ini": b"hosting-owned settings",
        })

    def kind(self, path):
        return "dir" if path in self.dirs else "file" if path in self.files else None

    def entries(self, folder):
        if folder not in self.dirs:
            raise FileNotFoundError(folder)
        return {PurePosixPath(p).name: self.kind(p) for p in self.dirs | self.files.keys()
                if p != "/" and str(PurePosixPath(p).parent) == folder}

    def mkdir(self, path):
        if self.kind(path):
            raise FileExistsError(path)
        if str(PurePosixPath(path).parent) not in self.dirs:
            raise FileNotFoundError(path)
        self.dirs.add(path)

    def ensure_dir(self, path):
        parent = str(PurePosixPath(path).parent)
        if parent != path and parent not in self.dirs:
            self.ensure_dir(parent)
        if path not in self.dirs:
            self.mkdir(path)

    def read(self, path, limit=32 * 1024 * 1024):
        self.reads.append(path)
        if path == "/vanilindo/.env":
            raise AssertionError("Production environment must never be downloaded")
        data = self.files[path]
        if len(data) > limit:
            raise DeploymentError("size limit")
        return data

    def size(self, path):
        return len(self.files[path])

    def put(self, path, data):
        self.failure("put", path, data)
        if str(PurePosixPath(path).parent) not in self.dirs:
            raise FileNotFoundError(path)
        self.operations.append(("put", path))
        self.files[path] = data

    def upload(self, files):
        for local, path in files:
            self.put(path, local.read_bytes())

    def rename(self, source, target):
        self.failure("rename-before", target, self.files[source])
        self.files[target] = self.files.pop(source)
        self.operations.append(("rename", target))
        self.failure("rename-after", target, self.files[target])

    def delete(self, path):
        self.operations.append(("delete", path))
        del self.files[path]

    def rmdir(self, path):
        if self.entries(path):
            raise OSError("Directory not empty")
        self.dirs.remove(path)

    def reconnect(self):
        self.operations.append(("reconnect",))

    def remove_release_tree(self, path):
        for file in list(self.files):
            if file.startswith(path + "/"):
                del self.files[file]
        self.dirs = {d for d in self.dirs if d != path and not d.startswith(path + "/")}


class TransactionTests(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        self.bundle = make_bundle(self.root / "bundle")
        self.remote = MemoryRemote()
        self.original = deepcopy(self.remote.files)

    def assert_persistent_data_unchanged(self):
        for path in ("/vanilindo/.env", "/vanilindo/storage/framework/session",
                     "/vanilindo/app-legacy.php", "/public_html/storage/customer-photo.jpg", "/public_html/php.ini"):
            self.assertEqual(self.original[path], self.remote.files[path], path)
        self.assertNotIn("/vanilindo/.env", self.remote.reads)

    def test_success_activates_index_last_and_preserves_persistent_data(self):
        def healthy(base, release, bundle):
            self.assertEqual(FIRST, release)
            self.assertNotIn("/vanilindo/storage/framework/maintenance.php", self.remote.files)
            self.assertEqual(front_controller(FIRST).encode(), self.remote.files["/public_html/index.php"])
        state = deploy(self.remote, self.bundle, "https://example.test", health=healthy)
        self.assertEqual([FIRST], state["history"])
        changed = [op[1] for op in self.remote.operations if op[0] == "rename" and op[1].startswith("/public_html/")]
        self.assertEqual("/public_html/index.php", changed[-1])
        self.assertNotIn("/vanilindo/.cd/lock", self.remote.dirs)
        self.assert_persistent_data_unchanged()

    def test_changed_migrations_stop_before_application_upload(self):
        changed = make_bundle(self.root / "schema-change", SECOND, b"ALTER TABLE users")
        with self.assertRaisesRegex(DeploymentError, "migrations changed"):
            deploy(self.remote, changed, "https://example.test", health=lambda *a: None)
        self.assertEqual(self.original, self.remote.files)
        self.assertNotIn("/vanilindo/.cd/lock", self.remote.dirs)

    def test_first_deploy_lists_only_existing_parent_directories(self):
        original_kind = self.remote.kind
        def strict_kind(path):
            if path != "/" and str(PurePosixPath(path).parent) not in self.remote.dirs:
                raise FileNotFoundError("MLSD parent does not exist")
            return original_kind(path)
        self.remote.kind = strict_kind
        deploy(self.remote, self.bundle, "https://example.test", health=lambda *a: None)

    def test_invalid_health_origin_stops_before_remote_writes(self):
        for base in ("http://example.test", "https://user:password@example.test", "https://example.test/path"):
            with self.subTest(base=base), self.assertRaises(DeploymentError):
                deploy(self.remote, self.bundle, base, health=lambda *a: None)
        self.assertEqual(self.original, self.remote.files)
        self.assertNotIn("/vanilindo/.cd/lock", self.remote.dirs)

    def test_state_symlink_is_never_read(self):
        state = "/vanilindo/.cd/state.json"
        original_kind = self.remote.kind
        self.remote.kind = lambda path: "OS.unix=slink" if path == state else original_kind(path)
        with self.assertRaisesRegex(DeploymentError, "regular file"):
            deploy(self.remote, self.bundle, "https://example.test", health=lambda *a: None)
        self.assertNotIn(state, self.remote.reads)
        self.assertEqual(self.original, self.remote.files)

    def test_upload_failure_leaves_active_website_untouched(self):
        def fail(operation, path, data):
            if operation == "put" and "/releases/" in path:
                raise ConnectionError("simulated dropped data connection")
        self.remote.failure = fail
        with self.assertRaises(ConnectionError):
            deploy(self.remote, self.bundle, "https://example.test", health=lambda *a: self.fail("not activated"))
        self.assertEqual(self.original, self.remote.files)
        self.assert_persistent_data_unchanged()

    def test_failed_health_restores_all_original_files_and_removes_new_public_files(self):
        calls = []
        def health(base, release, bundle):
            calls.append(release)
            if release:
                raise DeploymentError("database unhealthy")
            for path, data in self.original.items():
                self.assertEqual(data, self.remote.files[path], path)
        with self.assertRaisesRegex(DeploymentError, "database unhealthy"):
            deploy(self.remote, self.bundle, "https://example.test", health=health)
        self.assertEqual([FIRST, None], calls)
        self.assertNotIn("/public_html/build/assets/new.css", self.remote.files)
        self.assertNotIn("/vanilindo/.cd/state.json", self.remote.files)
        self.assertNotIn("/vanilindo/.cd/lock", self.remote.dirs)
        self.assert_persistent_data_unchanged()

    def test_lost_activation_ack_still_restores_previous_index(self):
        failed = False
        def fail(operation, path, data):
            nonlocal failed
            if not failed and operation == "rename-after" and path == "/public_html/index.php":
                failed = True
                raise ConnectionError("rename committed but ACK lost")
        self.remote.failure = fail
        with self.assertRaises(ConnectionError):
            deploy(self.remote, self.bundle, "https://example.test", health=lambda *a: None)
        self.assertEqual(self.original["/public_html/index.php"], self.remote.files["/public_html/index.php"])
        self.assertNotIn("/vanilindo/storage/framework/maintenance.php", self.remote.files)
        self.assert_persistent_data_unchanged()

    def test_lost_state_commit_ack_rolls_back_state_as_well(self):
        failed = False
        def fail(operation, path, data):
            nonlocal failed
            if not failed and operation == "rename-after" and path == "/vanilindo/.cd/state.json":
                failed = True
                raise ConnectionError("state ACK lost")
        self.remote.failure = fail
        with self.assertRaises(ConnectionError):
            deploy(self.remote, self.bundle, "https://example.test", health=lambda *a: None)
        self.assertNotIn("/vanilindo/.cd/state.json", self.remote.files)
        self.assertEqual(self.original["/public_html/index.php"], self.remote.files["/public_html/index.php"])

    def test_incomplete_recovery_keeps_lock_and_maintenance(self):
        def fail(operation, path, data):
            if operation == "rename-before" and path == "/public_html/index.php" and data == self.original[path]:
                raise ConnectionError("server unreachable during recovery")
        self.remote.failure = fail
        with self.assertRaisesRegex(DeploymentError, "recovery incomplete"):
            deploy(self.remote, self.bundle, "https://example.test",
                   health=lambda *a: (_ for _ in ()).throw(DeploymentError("bad release")))
        self.assertIn("/vanilindo/.cd/lock", self.remote.dirs)
        self.assertIn("/vanilindo/storage/framework/maintenance.php", self.remote.files)
        self.assert_persistent_data_unchanged()

    def test_existing_operator_maintenance_is_never_overwritten(self):
        self.remote.files["/vanilindo/storage/framework/maintenance.php"] = b"operator maintenance"
        with self.assertRaisesRegex(DeploymentError, "Existing maintenance"):
            deploy(self.remote, self.bundle, "https://example.test", health=lambda *a: None)
        self.assertEqual(b"operator maintenance", self.remote.files["/vanilindo/storage/framework/maintenance.php"])
        self.assert_persistent_data_unchanged()

    def test_concurrent_deployment_cannot_steal_or_delete_lock(self):
        self.remote.ensure_dir("/vanilindo/.cd/lock")
        with self.assertRaises(FileExistsError):
            deploy(self.remote, self.bundle, "https://example.test", health=lambda *a: None)
        self.assertIn("/vanilindo/.cd/lock", self.remote.dirs)
        self.assertEqual(self.original, self.remote.files)

    def test_retention_only_removes_known_managed_releases(self):
        for name in ("one", "two"):
            bundle = make_bundle(self.root / name, SECOND if name == "one" else THIRD)
            deploy(self.remote, bundle, "https://example.test", health=lambda *a: None)
        deploy(self.remote, self.bundle, "https://example.test", health=lambda *a: None)
        state = json.loads(self.remote.files["/vanilindo/.cd/state.json"])
        self.assertEqual([FIRST, THIRD], state["history"])
        self.assertNotIn("/vanilindo/.cd/releases/" + SECOND, self.remote.dirs)
        self.assertIn("/vanilindo/.cd/releases/" + THIRD, self.remote.dirs)
        self.assert_persistent_data_unchanged()

    def test_older_inactive_release_is_pruned_before_staging_to_bound_disk_usage(self):
        deploy(self.remote, self.bundle, "https://example.test", health=lambda *a: None)
        second = make_bundle(self.root / "second", SECOND)
        deploy(self.remote, second, "https://example.test", health=lambda *a: None)
        third = make_bundle(self.root / "third", THIRD)
        def check_upload(operation, path, data):
            if operation == "put" and "/releases/" + THIRD + "/" in path:
                self.assertNotIn("/vanilindo/.cd/releases/" + FIRST, self.remote.dirs)
                self.assertIn("/vanilindo/.cd/releases/" + SECOND, self.remote.dirs)
                self.assertEqual(front_controller(SECOND).encode(), self.remote.files["/public_html/index.php"])
        self.remote.failure = check_upload
        deploy(self.remote, third, "https://example.test", health=lambda *a: None)
        self.assert_persistent_data_unchanged()

    def test_manual_index_change_stops_before_pruning_any_previous_release(self):
        deploy(self.remote, self.bundle, "https://example.test", health=lambda *a: None)
        second = make_bundle(self.root / "second", SECOND)
        deploy(self.remote, second, "https://example.test", health=lambda *a: None)
        self.remote.files["/public_html/index.php"] = b"manually changed controller"
        with self.assertRaisesRegex(DeploymentError, "does not match CD state"):
            deploy(self.remote, make_bundle(self.root / "third", THIRD), "https://example.test", health=lambda *a: None)
        self.assertIn("/vanilindo/.cd/releases/" + FIRST, self.remote.dirs)
        self.assertIn("/vanilindo/.cd/releases/" + SECOND, self.remote.dirs)

    def test_missing_env_stops_before_any_deploy_writes(self):
        del self.remote.files["/vanilindo/.env"]
        operations = list(self.remote.operations)
        with self.assertRaisesRegex(DeploymentError, "production env"):
            deploy(self.remote, self.bundle, "https://example.test", health=lambda *a: None)
        self.assertEqual(operations, self.remote.operations)

    def test_unsafe_ftp_root_is_rejected(self):
        for root in ("relative", "/../public_html", "/a\r\nDELE /file"):
            with self.subTest(root=root), self.assertRaises(DeploymentError):
                deploy(self.remote, self.bundle, "https://example.test", root=root)


class ArtifactTests(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        make_bundle(self.root)

    def alter_manifest(self, operation):
        path = self.root / "manifest.json"
        manifest = json.loads(path.read_text())
        operation(manifest)
        path.write_text(json.dumps(manifest))

    def test_tampered_asset_is_rejected_before_connecting(self):
        (self.root / "public/build/assets/new.css").write_text("tampered")
        with self.assertRaisesRegex(DeploymentError, "checksum"):
            Bundle(self.root)

    def test_env_runtime_media_and_path_traversal_are_rejected(self):
        for area, name in (("private", ".env"), ("private", "storage/logs/a.php"),
                           ("public", "storage/photo.jpg"), ("public", "../.env"),
                           ("private", "bootstrap/cache/config.php")):
            with self.subTest(path=name):
                manifest = json.loads((self.root / "manifest.json").read_text())
                manifest["files"].append(dict(area=area, path=name, size=0, sha256=digest(b"")))
                path = self.root / "manifest.json"
                original = path.read_bytes()
                path.write_text(json.dumps(manifest))
                try:
                    with self.assertRaises((DeploymentError, ValueError)):
                        Bundle(self.root)
                finally:
                    path.write_bytes(original)

    def test_unlisted_secret_would_not_enter_uploaded_artifact(self):
        (self.root / "private/.env").write_text("secret")
        with self.assertRaisesRegex(DeploymentError, "Unlisted"):
            Bundle(self.root)

    def test_symlink_to_file_outside_bundle_is_rejected(self):
        asset = self.root / "public/build/assets/new.css"
        asset.unlink()
        asset.symlink_to("/etc/hosts")
        with self.assertRaisesRegex(DeploymentError, "symlink"):
            Bundle(self.root)

    def test_duplicate_paths_and_invalid_release_ids_are_rejected(self):
        original = (self.root / "manifest.json").read_bytes()
        self.alter_manifest(lambda m: m["files"].append(m["files"][0]))
        with self.assertRaisesRegex(DeploymentError, "duplicate"):
            Bundle(self.root)
        (self.root / "manifest.json").write_bytes(original)
        self.alter_manifest(lambda m: m.update(release_id="../public_html"))
        with self.assertRaisesRegex(DeploymentError, "release"):
            Bundle(self.root)

    def test_migration_fingerprint_cannot_be_forged_by_manifest_only(self):
        self.alter_manifest(lambda m: m.update(migration_hash="0" * 64))
        with self.assertRaisesRegex(DeploymentError, "fingerprint"):
            Bundle(self.root)

    def test_clean_copy_excludes_env_and_local_database(self):
        source = self.root / "source"
        source.mkdir()
        for name in (".env", "database.sqlite", "app.php", "dump.sql"):
            (source / name).write_text("test fixture")
        target = self.root / "copy"
        copy_clean(source, target)
        self.assertEqual(["app.php"], [p.name for p in target.iterdir()])


class ProtocolTests(unittest.TestCase):
    @patch("deploy.time.sleep")
    @patch("deploy.urllib.request.urlopen")
    def test_stale_release_never_passes_readiness(self, request, sleep):
        from unittest.mock import MagicMock
        response = MagicMock()
        response.__enter__.return_value = response
        response.status, response.url = 200, "https://example.test/up"
        response.headers = {"X-Vanilindo-Release": SECOND}
        response.read.return_value = b"old release"
        request.return_value = response
        with self.assertRaisesRegex(DeploymentError, "readiness timed out"):
            health_check("https://example.test", FIRST)
        self.assertEqual(6, request.call_count)
        self.assertEqual(5, sleep.call_count)

    @patch("deploy.urllib.request.urlopen")
    def test_a_public_asset_with_wrong_contents_blocks_release(self, request):
        from unittest.mock import MagicMock
        with tempfile.TemporaryDirectory() as temporary:
            bundle = make_bundle(Path(temporary))
            def fetch(req, **kwargs):
                response = MagicMock()
                response.__enter__.return_value = response
                response.url = req.full_url
                private = req.full_url.split(".test", 1)[1] in (
                    "/.env", "/vendor/autoload.php", "/composer.json", "/storage/", "/.git/config")
                response.status = 404 if private else 200
                response.headers = {"X-Vanilindo-Release": FIRST}
                response.read.return_value = b"wrong asset content"
                return response
            request.side_effect = fetch
            with self.assertRaisesRegex(DeploymentError, "public asset"):
                health_check("https://example.test", FIRST, bundle)

    @patch("deploy.time.sleep")
    @patch("deploy.urllib.request.urlopen")
    def test_readiness_retries_previous_php_front_controller(self, request, sleep):
        from unittest.mock import MagicMock
        def response(status, release=None):
            item = MagicMock()
            item.__enter__.return_value = item
            item.status = status
            item.url = "https://example.test/up"
            item.headers = {"X-Vanilindo-Release": release}
            item.read.return_value = b"test"
            return item
        request.side_effect = [response(200, SECOND), response(200, FIRST),
                               response(200, FIRST), response(200, FIRST)] + [response(404)] * 5
        health_check("https://example.test", FIRST)
        sleep.assert_called_once_with(2)

    @patch("deploy.ftplib.FTP_TLS")
    def test_control_and_data_require_validated_tls(self, client):
        remote = FTPS("ftp.example.test", "test-user", "test-password")
        context = client.call_args.kwargs["context"]
        self.assertEqual(ssl.CERT_REQUIRED, context.verify_mode)
        self.assertTrue(context.check_hostname)
        client.return_value.login.assert_called_once_with("test-user", "test-password")
        client.return_value.prot_p.assert_called_once_with()
        remote.close()

    @patch("deploy.ftplib.FTP")
    @patch("deploy.ftplib.FTP_TLS")
    def test_tls_failure_never_falls_back_to_plain_ftp(self, tls, plain):
        tls.return_value.connect.side_effect = ssl.SSLError("test invalid certificate")
        with self.assertRaises(ssl.SSLError):
            FTPS("ftp.example.test", "test-user", "test-password")
        plain.assert_not_called()
        tls.return_value.close.assert_called_once()

    @patch("deploy.ftplib.FTP_TLS")
    def test_listing_timeout_reports_data_channel_without_server_details(self, client):
        remote = FTPS("ftp.example.test", "test-user", "test-password")
        client.return_value.mlsd.side_effect = TimeoutError("sensitive server response")
        with self.assertRaisesRegex(DeploymentError, "passive FTP data ports") as raised:
            remote.entries("/")
        self.assertNotIn("sensitive", str(raised.exception))
        client.return_value.mkd.assert_not_called()
        client.return_value.storbinary.assert_not_called()

    @patch("deploy.ftplib.FTP_TLS")
    def test_truncated_transfer_is_rejected(self, client):
        remote = FTPS("ftp.example.test", "test-user", "test-password")
        client.return_value.size.return_value = 2
        with self.assertRaisesRegex(DeploymentError, "Transfer size"):
            remote.put("/private/test.php", b"a full file")

    @patch("deploy.ftplib.FTP_TLS")
    def test_cleanup_rejects_runtime_and_symlinks(self, client):
        remote = FTPS("ftp.example.test", "test-user", "test-password")
        with self.assertRaisesRegex(DeploymentError, "owned CD"):
            remote.remove_release_tree("/vanilindo/storage")
        client.return_value.mlsd.return_value = [("link", {"type": "OS.unix=slink"})]
        with self.assertRaisesRegex(DeploymentError, "symlink"):
            remote.remove_release_tree("/vanilindo/.cd/releases/" + FIRST)
        client.return_value.delete.assert_not_called()


if __name__ == "__main__":
    unittest.main()
