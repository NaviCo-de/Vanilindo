"""Prepare split public/private files for a cPanel account without SSH.

Uses the already verified production release; includes no passwords or DB dumps.
The private patch is extracted in /home/indone57/vanilindo, and the public ZIP
in /home/indone57/public_html. Existing media is never included or deleted.
"""
import hashlib
import json
from pathlib import Path
import zipfile

root = Path(__file__).resolve().parent.parent
release = root / 'dist/vanilindo-release-2026-10-10.zip'
output = root / 'dist/cpanel'
output.mkdir(parents=True, exist_ok=True)
expected = '6c61b546ba9fd02abda913ab6a1a0ea5228e7639b71ea40cfdd81c7645fab08a'
assert hashlib.sha256(release.read_bytes()).hexdigest() == expected

index = '''<?php

use Illuminate\\Foundation\\Application;
use Illuminate\\Http\\Request;

define('LARAVEL_START', microtime(true));
$basePath = dirname(__DIR__).'/vanilindo';

if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $basePath.'/vendor/autoload.php';
/** @var Application $app */
$app = require_once $basePath.'/bootstrap/app.php';
$app->usePublicPath(__DIR__);
$app->handleRequest(Request::capture());
'''
media_protection = '''Options -Indexes -ExecCGI
RemoveHandler .php .php3 .php4 .php5 .php7 .php8 .phtml .phar .cgi .pl .py .sh

# Only uploaded raster images are public. Deny executable names, including php.jpg.
<FilesMatch "(?i)\\.(php[0-9]*|phtml|phar|cgi|pl|py|sh)(\\.|$)">
    Require all denied
</FilesMatch>
<FilesMatch "(?i)^(?!.*\\.(jpg|jpeg|png|webp)$).*$">
    Require all denied
</FilesMatch>
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
</IfModule>
'''

def write_public_file(archive, name, contents):
    # ZipFile.writestr(name, ...) defaults to 0600. PHP can read that as the
    # account owner, but Apache cannot serve assets or read rewrite rules.
    info = zipfile.ZipInfo(name)
    info.create_system = 3
    info.external_attr = (0o100644 << 16)
    info.compress_type = zipfile.ZIP_DEFLATED
    archive.writestr(info, contents)


with zipfile.ZipFile(release) as source, zipfile.ZipFile(output / 'public-html.zip', 'w', zipfile.ZIP_DEFLATED) as archive:
    assert source.testzip() is None
    for name in sorted(source.namelist()):
        if name.startswith('public/') and not name.endswith('/'):
            target = name.removeprefix('public/')
            assert not target.startswith('storage/')
            write_public_file(archive, target, index if target == 'index.php' else source.read(name))
    write_public_file(archive, 'storage/.htaccess', media_protection)

patch_files = ['config/filesystems.php', 'app/Console/Commands/ProductionPreflight.php']
with zipfile.ZipFile(output / 'private-patch.zip', 'w', zipfile.ZIP_DEFLATED) as archive:
    for name in patch_files:
        archive.write(root / name, name)

manifest = {}
for archive_path in sorted(output.glob('*.zip')):
    with zipfile.ZipFile(archive_path) as archive:
        assert archive.testzip() is None
        assert all(not name.startswith('/') and '..' not in Path(name).parts for name in archive.namelist())
        assert '.env' not in archive.namelist()
        if archive_path.name == 'public-html.zip':
            assert all((info.external_attr >> 16) & 0o777 == 0o644 for info in archive.infolist())
        manifest[archive_path.name] = {'sha256': hashlib.sha256(archive_path.read_bytes()).hexdigest(),
                                     'bytes': archive_path.stat().st_size, 'entries': len(archive.namelist())}
(output / 'manifest.json').write_text(json.dumps(manifest, indent=2) + '\n')
print(json.dumps(manifest))
