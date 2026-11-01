#!/usr/bin/env python3
"""Deploy an immutable cPanel release over explicit FTPS, with guarded rollback."""
from __future__ import annotations

import argparse
from concurrent.futures import ThreadPoolExecutor
import ftplib
import io
import json
import os
from pathlib import Path, PurePosixPath
import signal
import ssl
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

from bundle import RELEASE_ID, allowed, digest, front_controller, migration_hash, safe_path


class DeploymentError(Exception):
    """An operator-readable error that never includes credentials/server replies."""


class Bundle:
    def __init__(self, root: Path):
        self.root = root.resolve()
        self.manifest = json.loads((self.root / "manifest.json").read_text())
        self.release = self.manifest["release_id"]
        if self.manifest.get("version") != 1 or not RELEASE_ID.fullmatch(self.release):
            raise DeploymentError("Invalid deployment manifest version/release")
        self.files: dict[tuple[str, str], Path] = {}
        migrations = {}
        for entry in self.manifest["files"]:
            area, name = entry["area"], safe_path(entry["path"])
            if not allowed(area, name) or (area, name) in self.files:
                raise DeploymentError("Bundle contains a protected or duplicate path")
            path = self.root / area / name
            if path.is_symlink() or self.root not in path.resolve().parents:
                raise DeploymentError("Bundle contains a symlink/path escape")
            data = path.read_bytes()
            if len(data) != entry["size"] or digest(data) != entry["sha256"]:
                raise DeploymentError("Bundle checksum/size mismatch")
            self.files[area, name] = path
            if area == "private" and name.startswith("database/migrations/") and name.endswith(".php"):
                migrations[name.removeprefix("database/migrations/")] = digest(data)
        actual = {p.relative_to(self.root).as_posix() for p in self.root.rglob("*") if p.is_file()}
        expected = {f"{area}/{name}" for area, name in self.files} | {"manifest.json"}
        if actual != expected or any(p.is_symlink() for p in self.root.rglob("*")):
            raise DeploymentError("Unlisted files/symlinks in bundle; refusing artifact")
        if (not migrations or migrations != self.manifest["migrations"]
                or migration_hash(migrations) != self.manifest["migration_hash"]):
            raise DeploymentError("Invalid migration fingerprint")
        if self.files["public", "index.php"].read_text() != front_controller(self.release):
            raise DeploymentError("Front controller does not match the private release layout")
        for name in ("vendor/autoload.php", "bootstrap/app.php", "composer.lock"):
            if ("private", name) not in self.files:
                raise DeploymentError("Incomplete production application")
        if ("public", "build/manifest.json") not in self.files:
            raise DeploymentError("Production frontend build is missing")


class FTPS:
    def __init__(self, host: str, user: str, password: str, port: int = 21):
        self.credentials = (host, user, password, port)
        self.ftp = None
        self.reconnect()

    def reconnect(self):
        self.close()
        host, user, password, port = self.credentials
        ftp = ftplib.FTP_TLS(context=ssl.create_default_context(), timeout=60)
        try:
            ftp.connect(host, port)
            ftp.login(user, password)  # FTP_TLS authenticates TLS before PASS.
            ftp.prot_p()  # Encrypt data connections too; never downgrade to FTP.
            ftp.voidcmd("TYPE I")
            self.ftp = ftp
        except BaseException:
            ftp.close()
            raise

    def close(self):
        if self.ftp is not None:
            self.ftp.close()
            self.ftp = None

    def entries(self, path: str) -> dict[str, str]:
        result = {}
        for name, facts in self.ftp.mlsd(path):
            if name in (".", "..") or facts.get("type") in ("cdir", "pdir"):
                continue
            if "/" in safe_path(name):
                raise DeploymentError("Invalid server directory entry")
            result[name] = facts.get("type", "unknown")
        return result

    def kind(self, path: str):
        path = PurePosixPath(path)
        if str(path) == "/":
            return "dir"
        return self.entries(str(path.parent)).get(path.name)

    def mkdir(self, path: str):
        self.ftp.mkd(path)
        self.ftp.sendcmd("SITE CHMOD 0755 " + path)

    def ensure_dir(self, path: str):
        current = PurePosixPath("/")
        for piece in PurePosixPath(path).parts[1:]:
            current /= piece
            kind = self.kind(str(current))
            if kind is None:
                self.mkdir(str(current))
            elif kind != "dir":
                raise DeploymentError("Destination parent is not a regular directory")

    def read(self, path: str, limit: int = 32 * 1024 * 1024) -> bytes:
        output = io.BytesIO()

        def consume(data):
            if output.tell() + len(data) > limit:
                raise DeploymentError("Remote file exceeds the expected size limit")
            output.write(data)

        self.ftp.retrbinary("RETR " + path, consume)
        return output.getvalue()

    def size(self, path: str):
        self.ftp.voidcmd("TYPE I")
        return self.ftp.size(path)

    def put(self, path: str, data: bytes):
        self.ftp.storbinary("STOR " + path, io.BytesIO(data))
        self.ftp.sendcmd("SITE CHMOD 0644 " + path)
        if self.size(path) != len(data):
            raise DeploymentError("Transfer size verification failed")

    def upload(self, files: list[tuple[Path, str]]):
        # Each worker owns a connection; ftplib control connections are not thread-safe.
        def worker(group):
            connection = FTPS(*self.credentials[:3], port=self.credentials[3])
            try:
                for local, remote in group:
                    connection.put(remote, local.read_bytes())
            finally:
                connection.close()

        groups = [files[i::3] for i in range(3)]
        with ThreadPoolExecutor(max_workers=3) as pool:
            list(pool.map(worker, [group for group in groups if group]))

    def rename(self, source: str, target: str):
        self.ftp.rename(source, target)

    def delete(self, path: str):
        self.ftp.delete(path)

    def rmdir(self, path: str):
        self.ftp.rmd(path)

    def remove_release_tree(self, path: str):
        parent = PurePosixPath(path)
        if (parent.parent.name not in ("releases", "backups")
                or parent.parent.parent.name != ".cd" or not RELEASE_ID.fullmatch(parent.name)):
            raise DeploymentError("Refusing cleanup outside an owned CD release")

        def remove(folder):
            for name, kind in self.entries(folder).items():
                child = folder + "/" + name
                if kind == "dir":
                    remove(child)
                elif kind == "file":
                    self.delete(child)
                else:
                    raise DeploymentError("Refusing to follow a symlink during CD cleanup")
            self.rmdir(folder)

        remove(path)


def validate_origin(base: str):
    origin = urllib.parse.urlsplit(base)
    if (origin.scheme != "https" or not origin.hostname or origin.username or origin.password
            or origin.query or origin.fragment or origin.path not in ("", "/")):
        raise DeploymentError("Health-check origin must be an HTTPS origin without credentials")
    return origin


def health_check(base: str, release: str | None, bundle: Bundle | None = None):
    origin = validate_origin(base)

    def fetch(path):
        request = urllib.request.Request(base.rstrip("/") + path,
                                         headers={"User-Agent": "Vanilindo-CD/1.0", "Cache-Control": "no-cache"})
        try:
            response = urllib.request.urlopen(request, timeout=30)
        except urllib.error.HTTPError as error:
            response = error
        with response:
            final = urllib.parse.urlsplit(response.url)
            if (final.scheme, final.netloc) != (origin.scheme, origin.netloc):
                raise DeploymentError("Health check redirected away from its HTTPS origin")
            return response.status, response.headers, response.read(32 * 1024 * 1024)

    # PHP's timestamp checks may briefly serve the previous front controller.
    for attempt in range(6):
        try:
            status, headers, _ = fetch("/up")
            if status != 200 or (release and headers.get("X-Vanilindo-Release") != release):
                raise DeploymentError("The expected release is not ready")
            break
        except (DeploymentError, urllib.error.URLError, TimeoutError):
            if attempt == 5:
                raise DeploymentError("Application readiness timed out") from None
            time.sleep(2)
    for path in ("/", "/admin/login"):
        status, headers, _ = fetch(path)
        if status != 200 or (release and headers.get("X-Vanilindo-Release") != release):
            raise DeploymentError("New release did not pass application/DB/login health checks")
    for path in ("/.env", "/vendor/autoload.php", "/composer.json", "/storage/", "/.git/config"):
        if fetch(path)[0] not in (403, 404):
            raise DeploymentError("A private path or media listing became accessible")
    if bundle:
        for (area, name), local in bundle.files.items():
            if area == "public" and name not in ("index.php", ".htaccess", "storage/.htaccess"):
                status, _, body = fetch("/" + urllib.parse.quote(name, safe="/"))
                if status != 200 or digest(body) != digest(local.read_bytes()):
                    raise DeploymentError("A public asset does not match the new release")


def deploy(remote, bundle: Bundle, base: str, root: str = "/", health=health_check):
    validate_origin(base)
    if not root.startswith("/") or any(p in (".", "..") for p in root.split("/")) or "\\" in root or any(ord(c) < 32 for c in root):
        raise DeploymentError("Invalid FTP root")
    root = root.rstrip("/")
    app, public = root + "/vanilindo", root + "/public_html"
    control = app + "/.cd"
    release = control + "/releases/" + bundle.release
    backup = control + "/backups/" + bundle.release
    state_file, lock = control + "/state.json", control + "/lock"
    maintenance = app + "/storage/framework/maintenance.php"
    maintenance_body = ("<?php /* Vanilindo CD " + bundle.release + " */\n"
                        "http_response_code(503); header('Retry-After: 60');\n"
                        "header('Cache-Control: no-store');\n"
                        "echo 'Website sedang diperbarui. Silakan coba sebentar lagi.'; exit;\n").encode()
    for path in (app, public, app + "/storage/framework"):
        if remote.kind(path) != "dir":
            raise DeploymentError("FTP account must see sibling vanilindo/public_html and shared runtime")
    if remote.kind(app + "/.env") != "file" or not remote.size(app + "/.env"):
        raise DeploymentError("Existing private production env is required; it will never be downloaded")
    if remote.kind(public + "/index.php") != "file":
        raise DeploymentError("This workflow updates an existing deployment, not a fresh installation")
    remote.ensure_dir(control)
    # Atomic directory creation serializes deployments, including other CI runners.
    remote.mkdir(lock)
    lock_owned = True
    changed = []
    journal = {}
    stage_created = False
    activation_started = False
    state_changed = False
    old_state_bytes = None
    serial = 0

    def atomic(path, data):
        nonlocal serial
        serial += 1
        temporary = backup + "/temporary-" + str(serial)
        remote.put(temporary, data)
        remote.rename(temporary, path)

    def clear_own_maintenance():
        if remote.kind(maintenance) is not None:
            if remote.read(maintenance, limit=8192) != maintenance_body:
                raise DeploymentError("Another operator changed maintenance mode; refusing to remove it")
            remote.delete(maintenance)

    try:
        state = None
        state_kind = remote.kind(state_file)
        if state_kind is not None:
            if state_kind != "file":
                raise DeploymentError("CD state must be a regular file")
            old_state_bytes = remote.read(state_file, limit=1024 * 1024)
            state = json.loads(old_state_bytes)
            if (state.get("version") != 1 or not isinstance(state.get("history"), list)
                    or not state["history"] or state["release_id"] != state["history"][0]
                    or not all(RELEASE_ID.fullmatch(item) for item in state["history"])):
                raise DeploymentError("Invalid existing CD state; manual inspection is required")
            if remote.read(public + "/index.php") != front_controller(state["release_id"]).encode():
                raise DeploymentError("Active front controller does not match CD state; inspect manual changes first")
            previous_migrations = state["migration_hash"]
        else:
            folder = app + "/database/migrations"
            files = remote.entries(folder)
            previous_migrations = migration_hash({name: digest(remote.read(folder + "/" + name))
                                                  for name, kind in files.items()
                                                  if kind == "file" and name.endswith(".php")})
        if previous_migrations != bundle.manifest["migration_hash"]:
            raise DeploymentError("Database migrations changed. Complete and verify a backed-up manual migration before enabling this release")
        if remote.kind(maintenance) or remote.kind(app + "/storage/framework/down"):
            raise DeploymentError("Existing maintenance mode detected; deployment will not override it")
        remote.ensure_dir(str(PurePosixPath(release).parent))
        remote.ensure_dir(str(PurePosixPath(backup).parent))
        if remote.kind(release) or remote.kind(backup):
            raise DeploymentError("Release ID already exists; use a new workflow run attempt")
        # On the 500 MB plan, staging a third unpacked release can exceed quota.
        # Drop only known inactive history; keep the live release for rollback.
        if state:
            for old in set(state["history"][1:]) - {state["release_id"]}:
                for directory in (control + "/releases/" + old, control + "/backups/" + old):
                    if remote.kind(directory) == "dir":
                        remote.remove_release_tree(directory)
        remote.mkdir(release)
        stage_created = True
        remote.mkdir(backup)
        private = [(local, release + "/" + name) for (area, name), local in bundle.files.items() if area == "private"]
        for folder in sorted({str(PurePosixPath(path).parent) for _, path in private}
                             | {release + "/bootstrap/cache"}):
            remote.ensure_dir(folder)
        print("Uploading an isolated private release; the active website is unchanged.", flush=True)
        remote.upload(private)
        for area, name in bundle.files:
            if area != "public":
                continue
            target = public + "/" + name
            remote.ensure_dir(str(PurePosixPath(target).parent))
            kind = remote.kind(target)
            if kind not in (None, "file"):
                raise DeploymentError("Public destination is not a regular file")
            journal[name] = kind == "file"
            if kind == "file":
                saved = backup + "/public/" + name
                remote.ensure_dir(str(PurePosixPath(saved).parent))
                remote.put(saved, remote.read(target))
        remote.put(backup + "/journal.json", json.dumps(journal).encode())
        if old_state_bytes is not None:
            remote.put(backup + "/previous-state.json", old_state_bytes)
        # Re-check after staging: a human may have enabled maintenance meanwhile.
        if remote.kind(maintenance) or remote.kind(app + "/storage/framework/down"):
            raise DeploymentError("Maintenance mode changed during upload; activation was stopped")
        activation_started = True  # Also handles a lost ACK after the atomic rename.
        atomic(maintenance, maintenance_body)
        print("Activating release under brief maintenance mode.", flush=True)
        public_names = sorted(name for area, name in bundle.files if area == "public" and name != "index.php") + ["index.php"]
        for name in public_names:
            changed.append(name)  # A lost rename ACK must still be rolled back.
            atomic(public + "/" + name, bundle.files["public", name].read_bytes())
        clear_own_maintenance()
        health(base, bundle.release, bundle)
        history = [bundle.release] + ([item for item in state["history"] if item != bundle.release][:1] if state else [])
        new_state = dict(version=1, release_id=bundle.release, history=history,
                         migration_hash=bundle.manifest["migration_hash"])
        state_changed = True
        atomic(state_file, json.dumps(new_state, indent=2).encode())
        print("Release and public assets passed HTTPS verification.", flush=True)
        # Keep current + previous managed release. Legacy source/runtime/media are never pruned.
        if state:
            for old in state["history"]:
                if old not in history:
                    for directory in (control + "/releases/" + old, control + "/backups/" + old):
                        try:
                            if remote.kind(directory) == "dir":
                                remote.remove_release_tree(directory)
                        except Exception:
                            print("Old managed release cleanup skipped; check hosting disk usage.", flush=True)
        return new_state
    except BaseException:
        recovered = False
        try:
            remote.reconnect()
            if activation_started:
                existing = remote.kind(maintenance)
                if existing is not None and remote.read(maintenance, limit=8192) != maintenance_body:
                    raise DeploymentError("Maintenance mode ownership changed")
                if existing is None:
                    atomic(maintenance, maintenance_body)
                for name in reversed(changed):
                    if journal[name]:
                        atomic(public + "/" + name, remote.read(backup + "/public/" + name))
                    elif remote.kind(public + "/" + name) is not None:
                        remote.delete(public + "/" + name)
                if state_changed:
                    if old_state_bytes is not None:
                        atomic(state_file, old_state_bytes)
                    elif remote.kind(state_file) is not None:
                        remote.delete(state_file)
                clear_own_maintenance()
                health(base, None, None)
                print("Previous application and public files restored; database/env/media unchanged.", flush=True)
            recovered = True
            if stage_created and remote.kind(release) == "dir":
                remote.remove_release_tree(release)
            if stage_created and remote.kind(backup) == "dir":
                remote.remove_release_tree(backup)
        except BaseException:
            lock_owned = False
            print("Automatic recovery incomplete. Keep the lock and inspect private .cd/backups; do not start another deployment.", file=sys.stderr)
        if not recovered:
            raise DeploymentError("Deployment/recovery incomplete; follow the documented recovery procedure") from None
        raise
    finally:
        if lock_owned:
            remote.rmdir(lock)


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--bundle", type=Path, required=True)
    parser.add_argument("--base", default="https://indonesiavanillas.com")
    parser.add_argument("--check", action="store_true", help="Validate the local artifact only; no connection or deployment")
    args = parser.parse_args()
    remote = None
    try:
        bundle = Bundle(args.bundle)
        if args.check:
            print(f"Bundle verified: {len(bundle.files)} files; release {bundle.release}.")
            return 0
        required = ("CPANEL_FTPS_HOST", "CPANEL_FTPS_USER", "CPANEL_FTPS_PASSWORD")
        validate_origin(args.base)
        if not all(os.environ.get(key) for key in required):
            raise DeploymentError("Set CPANEL_FTPS_HOST, CPANEL_FTPS_USER and CPANEL_FTPS_PASSWORD as environment secrets")
        remote = FTPS(*(os.environ[key] for key in required), port=int(os.environ.get("CPANEL_FTPS_PORT", "21")))
        deploy(remote, bundle, args.base, os.environ.get("CPANEL_FTP_ROOT", "/"))
        return 0
    except (Exception, KeyboardInterrupt) as error:
        detail = str(error) if isinstance(error, DeploymentError) else "Check the artifact, TLS certificate, FTP permissions and deployment recovery log."
        print("Deployment stopped: " + detail, file=sys.stderr)
        return 1
    finally:
        if remote is not None:
            remote.close()


if __name__ == "__main__":
    # A cooperative termination runs rollback; a hard runner kill still needs manual recovery.
    signal.signal(signal.SIGTERM, lambda *_: (_ for _ in ()).throw(KeyboardInterrupt()))
    raise SystemExit(main())
