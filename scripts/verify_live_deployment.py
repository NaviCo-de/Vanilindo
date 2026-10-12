#!/usr/bin/env python3
"""Read-only, bounded checks of the deployed site; never records cookie values."""
import argparse
import hashlib
import json
import re
import time
import urllib.error
import urllib.request
from concurrent.futures import ThreadPoolExecutor
from datetime import datetime, timezone
from pathlib import Path
from zipfile import ZipFile

parser = argparse.ArgumentParser()
parser.add_argument('--base', default='https://indonesiavanillas.com')
parser.add_argument('--archive', default='dist/cpanel/public-html.zip')
parser.add_argument('--output', default='docs/audit-evidence/2026-10-12/live-http.json')
args = parser.parse_args()
base = args.base.rstrip('/')


def fetch(path, origin=base):
    started = time.monotonic()
    req = urllib.request.Request(origin + path, headers={'User-Agent': 'Vanilindo-Deployment-Verification/1.0'})
    try:
        response = urllib.request.urlopen(req, timeout=25)
    except urllib.error.HTTPError as error:
        response = error
    with response as response:
        body = response.read()
        cookies = response.headers.get_all('Set-Cookie') or []
        result = {
            'path': path, 'status': response.status, 'url': response.url,
            'bytes': len(body), 'elapsed_ms': round((time.monotonic() - started) * 1000),
            'headers': {key: response.headers.get(key) for key in
                ['Content-Type', 'X-Content-Type-Options', 'X-Frame-Options', 'Referrer-Policy',
                 'Content-Security-Policy', 'Cache-Control', 'Permissions-Policy', 'X-Powered-By']},
            'cookie_flags': [{'name': cookie.split('=', 1)[0],
                'secure': bool(re.search(r';\s*secure(?:;|$)', cookie, re.I)),
                'httponly': bool(re.search(r';\s*httponly(?:;|$)', cookie, re.I)),
                'samesite': re.findall(r';\s*samesite=([^;]+)', cookie, re.I)} for cookie in cookies],
        }
        return result, body


pages = []
for path in ['/', '/up', '/about', '/products', '/blog', '/contact', '/admin', '/admin/login']:
    result, body = fetch(path)
    result['title'] = re.findall(r'<title>(.*?)</title>', body.decode(errors='replace'), re.S)
    pages.append(result)

blocked = []
for path in ['/.env', '/vanilindo/.env', '/vendor/autoload.php', '/composer.json',
             '/storage/logs/laravel.log', '/.git/config', '/storage/probe.txt',
             '/storage/probe.php.jpg', '/storage/', '/php.ini', '/.user.ini']:
    result, _ = fetch(path)
    blocked.append(result)

redirects = []
for origin in ['http://indonesiavanillas.com', 'http://www.indonesiavanillas.com',
               'https://www.indonesiavanillas.com']:
    result, _ = fetch('/', origin)
    redirects.append(result)

with ZipFile(args.archive) as archive:
    expected = {name: hashlib.sha256(archive.read(name)).hexdigest()
                for name in archive.namelist() if not name.endswith('/')
                and name not in ['index.php', '.htaccess', 'storage/.htaccess']}


def verify_asset(item):
    name, expected_hash = item
    result, body = fetch('/' + name)
    return {'path': name, 'status': result['status'], 'bytes': len(body),
            'sha256': hashlib.sha256(body).hexdigest(),
            'matches_release': hashlib.sha256(body).hexdigest() == expected_hash}

with ThreadPoolExecutor(max_workers=3) as pool:
    assets = list(pool.map(verify_asset, expected.items()))

report = {'checked_at_utc': datetime.now(timezone.utc).isoformat(), 'base': base,
          'tls_validation': 'default trusted SSL validation enabled',
          'pages': pages, 'private_and_media_paths': blocked, 'redirects': redirects,
          'assets': assets, 'assets_all_match': all(asset['status'] == 200 and asset['matches_release'] for asset in assets)}
session_cookies = [cookie for page in pages for cookie in page['cookie_flags']
                   if cookie['name'].endswith(('_session', '-session'))]
report['checks'] = {
    'pages_accessible': all(page['status'] == 200 for page in pages),
    'private_paths_blocked': all(result['status'] in (403, 404) for result in blocked),
    'assets_match_release': report['assets_all_match'],
    'http_redirects_to_https': all(result['url'].startswith('https://') for result in redirects),
    'secure_session_cookies': bool(session_cookies) and all(cookie['secure'] and cookie['httponly']
        and cookie['samesite'] == ['lax'] for cookie in session_cookies),
    'public_csp_disallows_eval': 'unsafe-eval' not in (pages[0]['headers']['Content-Security-Policy'] or '')
        and bool(pages[0]['headers']['Content-Security-Policy']),
    'admin_responses_not_cached': all('no-store' in (page['headers']['Cache-Control'] or '')
        for page in pages if page['url'].endswith('/admin/login')),
    'php_version_header_hidden': all(not page['headers']['X-Powered-By'] for page in pages),
}
report['passed'] = all(report['checks'].values())
Path(args.output).write_text(json.dumps(report, indent=2) + '\n')
print(json.dumps({'pages': [(p['path'], p['status'], p['url']) for p in pages],
                  'blocked_paths': [(p['path'], p['status']) for p in blocked],
                  'assets_count': len(assets), 'assets_all_match': report['assets_all_match'],
                  'redirects': [(p['url'], p['status']) for p in redirects],
                  'checks': report['checks'], 'passed': report['passed']}, indent=2))
raise SystemExit(0 if report['passed'] else 1)
