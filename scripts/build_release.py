"""Create a clean Laravel release in a new temporary directory, including production vendor."""
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import zipfile

root = Path(__file__).resolve().parent.parent
stage = Path(tempfile.mkdtemp(prefix='vanilindo-release-'))
php = os.environ.get('PHP_BINARY', '/opt/homebrew/opt/php@8.3/bin/php')
composer = root / '.tools/composer.phar'
if not Path(php).is_file() or not composer.is_file():
    raise SystemExit('Set PHP_BINARY and provide the verified local .tools/composer.phar.')
for folder in ['app', 'config', 'resources', 'routes']:
    shutil.copytree(root / folder, stage / folder)
shutil.copytree(root / 'bootstrap', stage / 'bootstrap',
                ignore=lambda directory, names: [name for name in names if Path(directory).name == 'cache' and name.endswith('.php')])
shutil.copytree(root / 'database', stage / 'database', ignore=shutil.ignore_patterns('*.sqlite', '*.sqlite-*'))
shutil.copytree(root / 'public', stage / 'public', ignore=shutil.ignore_patterns('storage', 'hot'))
for filename in ['artisan', 'composer.json', 'composer.lock', '.env.example']:
    shutil.copy2(root / filename, stage / filename)
for folder in ['app/public', 'app/private', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs']:
    (stage / 'storage' / folder).mkdir(parents=True, exist_ok=True)
environment = os.environ.copy()
environment['PATH'] = str(Path(php).parent) + os.pathsep + environment['PATH']
subprocess.run([php, str(composer), 'install', '--no-dev', '--prefer-dist', '--optimize-autoloader', '--no-interaction'],
               cwd=stage, env=environment, check=True)
subprocess.run([php, str(composer), 'check-platform-reqs', '--no-dev'], cwd=stage, env=environment, check=True)
subprocess.run([php, 'artisan', 'route:list', '--except-vendor'], cwd=stage, env=environment, check=True)
for cache in (stage / 'bootstrap/cache').glob('*.php'):
    cache.unlink()
assert (stage / 'public/build/manifest.json').is_file()
assert (stage / 'public/.htaccess').is_file()
assert not (stage / 'vendor/phpunit').exists()
assert not (stage / '.env').exists()
assert not (stage / 'public/storage').exists()
release = root / 'dist/vanilindo-release-2026-10-10.zip'
release.parent.mkdir(exist_ok=True)
size = 0
with zipfile.ZipFile(release, 'w', zipfile.ZIP_DEFLATED, compresslevel=6) as archive:
    for item in sorted(stage.rglob('*')):
        assert not item.is_symlink(), f'Unexpected symlink: {item}'
        if item.is_file():
            name = str(item.relative_to(stage))
            assert name != '.env' and not name.endswith('.sqlite')
            archive.write(item, name)
            size += item.stat().st_size
    for folder in ['storage/app/public/', 'storage/app/private/', 'storage/framework/cache/data/',
                   'storage/framework/sessions/', 'storage/framework/views/', 'storage/logs/', 'bootstrap/cache/']:
        archive.writestr(folder, '')
with zipfile.ZipFile(release) as archive:
    assert archive.testzip() is None
    files = len(archive.namelist())
manifest = {'archive': release.name, 'sha256': hashlib.sha256(release.read_bytes()).hexdigest(),
            'compressed_bytes': release.stat().st_size, 'uncompressed_bytes': size,
            'entries': files, 'production_dependencies_only': True, 'contains_env_or_local_database': False,
            'staging_directory': str(stage)}
(release.parent / 'release-manifest.json').write_text(json.dumps(manifest, indent=2) + '\n')
print(json.dumps(manifest))
