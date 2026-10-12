"""Bounded HTTP load test. Defaults to localhost; remote targets require an explicit flag."""
import argparse
import concurrent.futures
import http.client
import json
import math
import time
from collections import Counter
from pathlib import Path
from urllib.parse import urlsplit

parser = argparse.ArgumentParser()
parser.add_argument('--base-url', default='http://127.0.0.1:8177')
parser.add_argument('--concurrency', type=int, default=10)
parser.add_argument('--requests', type=int, default=500)
parser.add_argument('--p95-limit-ms', type=float, default=2000)
parser.add_argument('--allow-remote', action='store_true')
parser.add_argument('--output', required=True)
args = parser.parse_args()
target = urlsplit(args.base_url)
if target.scheme not in ('http', 'https') or not target.hostname:
    parser.error('A valid HTTP(S) target is required.')
if target.hostname not in ('localhost', '127.0.0.1', '::1') and not args.allow_remote:
    parser.error('Remote requests require --allow-remote and authorization to test that host.')
if not 1 <= args.concurrency <= 50 or not 1 <= args.requests <= 10000:
    parser.error('Allowed concurrency: 1..50; total requests: 1..10000.')
routes = [('/', 200, b'Indonesian Soil'), ('/?products_page=2', 200, b'Audit Vanilla 10'),
          ('/products/load-product-100', 200, b'Audit Vanilla 100'),
          ('/blog/load-article-100', 200, b'Audit Journal 100'),
          ('/admin/login', 200, b'Vanilindo Admin'), ('/about', 302, b'#about')]

def request(number):
    path, expected, marker = routes[number % len(routes)]
    started = time.perf_counter()
    connection = None
    try:
        cls = http.client.HTTPSConnection if target.scheme == 'https' else http.client.HTTPConnection
        connection = cls(target.hostname, target.port, timeout=15)
        connection.request('GET', target.path.rstrip('/') + path,
                           headers={'User-Agent': 'Vanilindo-authorized-local-load-test/1.0'})
        response = connection.getresponse()
        body = response.read(5_000_001)
        return {'ms': (time.perf_counter() - started) * 1000, 'status': response.status,
                'ok': response.status == expected and marker in body and len(body) <= 5_000_000,
                'bytes': len(body), 'path': path}
    except Exception as error:
        return {'ms': (time.perf_counter() - started) * 1000, 'status': type(error).__name__,
                'ok': False, 'bytes': 0, 'path': path}
    finally:
        if connection:
            connection.close()

started = time.perf_counter()
with concurrent.futures.ThreadPoolExecutor(max_workers=args.concurrency) as executor:
    results = list(executor.map(request, range(args.requests)))
elapsed = time.perf_counter() - started
latencies = sorted(result['ms'] for result in results)
p95 = latencies[max(0, math.ceil(len(latencies) * .95) - 1)]
failures = [result for result in results if not result['ok']]
summary = {'target': args.base_url, 'concurrency': args.concurrency, 'requests': args.requests,
           'elapsed_seconds': round(elapsed, 2), 'requests_per_second': round(args.requests / elapsed, 2),
           'p50_ms': round(latencies[len(latencies) // 2], 2), 'p95_ms': round(p95, 2),
           'max_ms': round(max(latencies), 2), 'errors': len(failures),
           'status_counts': dict(Counter(str(result['status']) for result in results)),
           'threshold': {'max_errors': 0, 'p95_limit_ms': args.p95_limit_ms},
           'passed': not failures and p95 <= args.p95_limit_ms, 'failure_samples': failures[:5]}
Path(args.output).write_text(json.dumps(summary, indent=2) + '\n')
print(json.dumps(summary))
raise SystemExit(0 if summary['passed'] else 1)
