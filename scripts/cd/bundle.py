#!/usr/bin/env python3
"""Build a portable, secret-free release for cPanel. No server connection."""
from __future__ import annotations

import argparse
import hashlib
import json
import os
from pathlib import Path, PurePosixPath
import re
import shutil
import subprocess
import tempfile

RELEASE_ID = re.compile(r"[a-f0-9]{40}(?:-[0-9]+-[0-9]+)?\Z")
PRIVATE_DIRS = ("app", "bootstrap", "config", "database", "resources", "routes", "vendor")
PUBLIC_DIRS = ("build", "css", "fonts", "images", "js")
PUBLIC_FILES = (".htaccess", "favicon.ico", "robots.txt")
MEDIA_RULES = r'''Options -Indexes -ExecCGI
RemoveHandler .php .php3 .php4 .php5 .php7 .php8 .phtml .phar .cgi .pl .py .sh
<FilesMatch "(?i)\.(php[0-9]*|phtml|phar|cgi|pl|py|sh)(\.|$)">
    Require all denied
</FilesMatch>
<FilesMatch "(?i)^(?!.*\.(jpg|jpeg|png|webp)$).*$">
    Require all denied
</FilesMatch>
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
</IfModule>
'''


def digest(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()


def safe_path(value: str) -> str:
    if (not isinstance(value, str) or not value or "\\" in value
            or any(ord(c) < 32 for c in value) or value.startswith("/")
            or any(p in ("", ".", "..") for p in value.split("/"))):
        raise ValueError("Unsafe relative release path")
    return value


def allowed(area: str, name: str) -> bool:
    path = PurePosixPath(safe_path(name))
    if any(p.startswith(".env") or p == ".git" for p in path.parts):
        return False
    if path.suffix in (".sqlite", ".sql", ".log", ".pyc"):
        return False
    if area == "private":
        return (name in ("artisan", "composer.json", "composer.lock")
                or (path.parts[0] in PRIVATE_DIRS and name != "bootstrap/cache/config.php"
                    and not name.startswith("bootstrap/cache/")))
    return area == "public" and (
        name in (*PUBLIC_FILES, "index.php", "storage/.htaccess")
        or path.parts[0] in PUBLIC_DIRS)


def migration_hash(files: dict[str, str]) -> str:
    return digest(json.dumps(files, sort_keys=True, separators=(",", ":")).encode())


def front_controller(release_id: str) -> str:
    if not RELEASE_ID.fullmatch(release_id):
        raise ValueError("Invalid release identifier")
    # Paths are resolved at request time. The existing env and runtime stay shared.
    return f'''<?php

use Illuminate\\Foundation\\Application;
use Illuminate\\Http\\Request;

define('LARAVEL_START', microtime(true));
$sharedPath = dirname(__DIR__).'/vanilindo';
$basePath = $sharedPath.'/.cd/releases/{release_id}';

if (file_exists($maintenance = $sharedPath.'/storage/framework/maintenance.php')) {{
    require $maintenance;
}}

require $basePath.'/vendor/autoload.php';
/** @var Application $app */
$app = require_once $basePath.'/bootstrap/app.php';
$app->useEnvironmentPath($sharedPath);
$app->useStoragePath($sharedPath.'/storage');
$app->usePublicPath(__DIR__);
header('X-Vanilindo-Release: {release_id}');
$app->handleRequest(Request::capture());
'''


def copy_clean(source: Path, destination: Path) -> None:
    if source.is_symlink():
        raise ValueError("Source directories must not be symlinks")
    for item in sorted(source.rglob("*")):
        if item.is_symlink():
            raise ValueError("Symlinks are not permitted in a deployment bundle")
        if not item.is_file():
            continue
        name = item.relative_to(source).as_posix()
        if any(p.startswith(".env") or p in (".git", "__pycache__") for p in item.relative_to(source).parts):
            continue
        if item.suffix in (".sqlite", ".sql", ".log", ".pyc"):
            continue
        target = destination / name
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(item, target)
        target.chmod(0o644)


def build(source: Path, output: Path, release_id: str, php: str, composer: list[str]) -> Path:
    if not RELEASE_ID.fullmatch(release_id):
        raise ValueError("Invalid release identifier")
    if output.exists():
        raise ValueError("Use a new output directory; existing output is never overwritten")
    with tempfile.TemporaryDirectory(prefix="vanilindo-cd-build-") as temporary:
        stage = Path(temporary)
        for folder in PRIVATE_DIRS:
            if folder == "vendor":
                continue
            copy_clean(source / folder, stage / folder)
        # Local cache files may contain secrets and must not reach Composer boot.
        for cache in (stage / "bootstrap/cache").glob("*.php"):
            cache.unlink()
        for name in ("artisan", "composer.json", "composer.lock"):
            shutil.copyfile(source / name, stage / name)
        for folder in (*PUBLIC_DIRS,):
            if (source / "public" / folder).exists():
                copy_clean(source / "public" / folder, stage / "public" / folder)
        for name in (*PUBLIC_FILES, "index.php"):
            if (source / "public" / name).exists():
                (stage / "public").mkdir(exist_ok=True)
                shutil.copyfile(source / "public" / name, stage / "public" / name)
        for name in ("app/private", "app/public", "framework/cache/data", "framework/sessions", "framework/views", "logs"):
            (stage / "storage" / name).mkdir(parents=True, exist_ok=True)
        environment = os.environ.copy()
        # Never pass production settings to this secret-free build process.
        environment.update(APP_ENV="production", APP_DEBUG="false", DB_CONNECTION="sqlite",
                           DB_DATABASE=":memory:", CACHE_STORE="array", SESSION_DRIVER="array",
                           QUEUE_CONNECTION="sync", LOG_CHANNEL="stderr", DB_URL="",
                           APP_KEY="", APP_CONFIG_CACHE=str(stage / "bootstrap/cache/config.php"))
        subprocess.run([*composer, "install", "--no-dev", "--prefer-dist", "--no-interaction",
                        "--optimize-autoloader"], cwd=stage, env=environment, check=True)
        subprocess.run([*composer, "check-platform-reqs", "--no-dev"], cwd=stage,
                       env=environment, check=True)
        subprocess.run([php, "artisan", "route:list", "--except-vendor"], cwd=stage,
                       env=environment, check=True)
        if not (stage / "public/build/manifest.json").is_file():
            raise ValueError("Run npm ci and npm run build before packaging")
        if (stage / "vendor/phpunit").exists():
            raise ValueError("Development dependencies found in production bundle")
        (output / "private").mkdir(parents=True)
        (output / "public").mkdir()
        for folder in PRIVATE_DIRS:
            for item in sorted((stage / folder).rglob("*")):
                if item.is_symlink():
                    raise ValueError("Unexpected symlink in production dependencies")
                if item.is_file():
                    name = item.relative_to(stage).as_posix()
                    if allowed("private", name):
                        target = output / "private" / name
                        target.parent.mkdir(parents=True, exist_ok=True)
                        shutil.copyfile(item, target)
        for name in ("artisan", "composer.json", "composer.lock"):
            shutil.copyfile(stage / name, output / "private" / name)
        copy_clean(stage / "public", output / "public")
        (output / "public/index.php").write_text(front_controller(release_id))
        (output / "public/storage").mkdir(exist_ok=True)
        (output / "public/storage/.htaccess").write_text(MEDIA_RULES)
        entries = []
        migrations = {}
        for area in ("private", "public"):
            for item in sorted((output / area).rglob("*")):
                if item.is_file():
                    name = item.relative_to(output / area).as_posix()
                    if not allowed(area, name):
                        raise ValueError("Unexpected file in deployment bundle")
                    item.chmod(0o644)
                    data = item.read_bytes()
                    entries.append(dict(area=area, path=name, size=len(data), sha256=digest(data)))
                    if area == "private" and name.startswith("database/migrations/") and name.endswith(".php"):
                        migrations[name.removeprefix("database/migrations/")] = digest(data)
        manifest = dict(version=1, release_id=release_id, files=entries,
                        migrations=migrations, migration_hash=migration_hash(migrations))
        (output / "manifest.json").write_text(json.dumps(manifest, indent=2) + "\n")
    return output


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--source", type=Path, default=Path(__file__).resolve().parents[2])
    parser.add_argument("--output", type=Path, required=True)
    parser.add_argument("--release-id", required=True)
    parser.add_argument("--php", default=os.environ.get("PHP_BINARY", "php"))
    parser.add_argument("--composer", default=os.environ.get("COMPOSER_BINARY", "composer"))
    args = parser.parse_args()
    composer = [args.php, args.composer] if args.composer.endswith(".phar") else [args.composer]
    build(args.source.resolve(), args.output.resolve(), args.release_id, args.php, composer)
    print("Production bundle built without environment files, runtime data or development dependencies.")


if __name__ == "__main__":
    main()
