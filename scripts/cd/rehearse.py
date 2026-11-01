#!/usr/bin/env python3
"""Boot a real production bundle in a disposable cPanel layout on loopback only."""
from __future__ import annotations

import argparse
import base64
import os
from pathlib import Path
import shutil
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

from bundle import digest
from deploy import Bundle


def rehearse(bundle: Bundle, php: str):
    with tempfile.TemporaryDirectory(prefix="vanilindo-cd-rehearsal-") as temporary:
        root = Path(temporary)
        shared, public = root / "vanilindo", root / "public_html"
        release = shared / ".cd/releases" / bundle.release
        shutil.copytree(bundle.root / "private", release)
        shutil.copytree(bundle.root / "public", public)
        (release / "bootstrap/cache").mkdir(parents=True, exist_ok=True)
        for folder in ("app/private", "app/public", "framework/cache/data", "framework/sessions", "framework/views", "logs"):
            (shared / "storage" / folder).mkdir(parents=True, exist_ok=True)
        database = shared / "test.sqlite"
        database.touch()
        with socket.socket() as probe:
            probe.bind(("127.0.0.1", 0))
            port = probe.getsockname()[1]
        origin = f"http://127.0.0.1:{port}"
        # Generated fixture values only. Never read the developer's .env or production DB.
        key = base64.b64encode(os.urandom(32)).decode()
        (shared / ".env").write_text(
            f"APP_ENV=production\nAPP_DEBUG=false\nAPP_URL={origin}\nAPP_KEY=base64:{key}\n"
            f"DB_CONNECTION=sqlite\nDB_DATABASE={database}\nDB_URL=\n"
            f"PUBLIC_STORAGE_PATH={public}/storage\nCACHE_STORE=database\n"
            "SESSION_DRIVER=database\nSESSION_SECURE_COOKIE=false\nQUEUE_CONNECTION=sync\nLOG_CHANNEL=stderr\n")
        environment = {k: v for k, v in os.environ.items()
                       if not k.startswith(("APP_", "DB_", "SESSION_", "CACHE_", "PUBLIC_STORAGE_"))}
        bootstrap = root / "bootstrap-test.php"
        bootstrap.write_text('''<?php
require $argv[1].'/vendor/autoload.php';
$app = require $argv[1].'/bootstrap/app.php';
$app->useEnvironmentPath($argv[2]);
$app->useStoragePath($argv[2].'/storage');
$app->usePublicPath($argv[3]);
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
if (!$app->environment('production') || config('app.debug') ||
    config('database.connections.sqlite.database') !== $argv[2].'/test.sqlite' ||
    config('filesystems.disks.public.root') !== $argv[3].'/storage') {
    throw new RuntimeException('Shared environment/storage/public path mismatch');
}
foreach (['migrate', 'db:seed'] as $command) {
    if (Illuminate\\Support\\Facades\\Artisan::call($command, ['--force' => true]) !== 0) {
        throw new RuntimeException('Disposable test database setup failed');
    }
}
''')
        subprocess.run([php, str(bootstrap), str(release), str(shared), str(public)],
                       env=environment, check=True, stdout=subprocess.DEVNULL)
        router = root / "router.php"
        router.write_text('''<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file($_SERVER['DOCUMENT_ROOT'].$path)) { return false; }
require $_SERVER['DOCUMENT_ROOT'].'/index.php';
''')
        with (root / "http.log").open("w+") as log:
            server = subprocess.Popen([php, "-S", f"127.0.0.1:{port}", "-t", str(public), str(router)],
                                      cwd=root, env=environment, stdout=log, stderr=log)
            try:
                for attempt in range(40):
                    try:
                        with urllib.request.urlopen(origin + "/up", timeout=2) as response:
                            assert response.headers.get("X-Vanilindo-Release") == bundle.release
                        break
                    except urllib.error.URLError:
                        if server.poll() is not None or attempt == 39:
                            raise RuntimeError("Disposable PHP server did not become ready") from None
                        time.sleep(0.25)
                for path in ("/", "/admin/login"):
                    with urllib.request.urlopen(origin + path, timeout=30) as response:
                        body = response.read()
                        assert response.status == 200
                        assert response.headers.get("X-Vanilindo-Release") == bundle.release
                        assert len(body) > 1000
                assets = 0
                for (area, name), local in bundle.files.items():
                    if area == "public" and name not in ("index.php", ".htaccess", "storage/.htaccess"):
                        with urllib.request.urlopen(origin + "/" + urllib.parse.quote(name, safe="/"), timeout=30) as response:
                            assert digest(response.read()) == digest(local.read_bytes()), name
                        assets += 1
                assert any((shared / "storage/framework/views").glob("*.php"))
                print(f"Local production rehearsal passed: /up, /, /admin/login, {assets} assets; shared env, SQLite and runtime verified.")
            except BaseException:
                log.flush()
                log.seek(0)
                print(log.read()[-8000:])
                raise
            finally:
                server.terminate()
                server.wait(timeout=10)


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--bundle", type=Path, required=True)
    parser.add_argument("--php", default=os.environ.get("PHP_BINARY", "php"))
    args = parser.parse_args()
    rehearse(Bundle(args.bundle), args.php)
