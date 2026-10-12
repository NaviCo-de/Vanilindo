<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

#[Signature('vanilindo:preflight {--local : Allow loopback HTTP and SQLite for a local rehearsal}')]
#[Description('Check production configuration without printing secrets or changing data')]
class ProductionPreflight extends Command
{
    public function handle(): int
    {
        $local = (bool) $this->option('local');
        $checks = [];
        $checks['PHP >= 8.3'] = version_compare(PHP_VERSION, '8.3.0', '>=');
        foreach (['ctype', 'curl', 'dom', 'fileinfo', 'gd', 'intl', 'mbstring', 'openssl', 'pdo_mysql', 'xml', 'xmlreader', 'zip'] as $extension) {
            $checks['PHP extension '.$extension] = extension_loaded($extension);
        }
        $checks['APP_ENV=production'] = config('app.env') === 'production';
        $checks['APP_DEBUG=false'] = config('app.debug') === false;
        $key = (string) config('app.key');
        $key = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : $key;
        $checks['Valid application key'] = is_string($key) && strlen($key) === 32;
        $url = (string) config('app.url');
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $loopback = in_array($host, ['127.0.0.1', 'localhost', '[::1]'], true);
        $checks['HTTPS application URL'] = filter_var($url, FILTER_VALIDATE_URL)
            && ($scheme === 'https' || ($local && $loopback && $scheme === 'http'));
        $checks['Secure session cookies'] = config('session.secure') === true || ($local && $loopback);
        $checks['HttpOnly / SameSite session cookies'] = config('session.http_only') === true
            && in_array(config('session.same_site'), ['lax', 'strict'], true);
        $checks['Production MySQL connection'] = config('database.default') === 'mysql'
            || ($local && $loopback && config('database.default') === 'sqlite');
        $checks['Writable runtime directories'] = is_writable(storage_path()) && is_writable(base_path('bootstrap/cache'));
        $mediaPath = realpath(public_path('storage'));
        $diskPath = realpath((string) config('filesystems.disks.public.root'));
        $defaultMediaPath = realpath(storage_path('app/public'));
        $supportedMediaPath = is_link(public_path('storage'))
            ? $diskPath === $defaultMediaPath
            : $diskPath !== $defaultMediaPath;
        $checks['Public media storage location'] = $mediaPath !== false && $diskPath !== false
            && $mediaPath === $diskPath && is_writable($diskPath) && $supportedMediaPath;
        $checks['Production frontend build'] = is_file(public_path('build/manifest.json')) && ! is_file(public_path('hot'));
        $checks['Filament assets'] = is_file(public_path('css/filament/filament/app.css'))
            && is_file(public_path('js/filament/filament/app.js'));

        try {
            DB::connection()->getPdo();
            $checks['Database reachable'] = true;
            $checks['Application tables'] = collect(['users', 'products', 'articles', 'content_blocks', 'site_settings'])
                ->every(fn (string $table): bool => Schema::hasTable($table));
            $repository = app('migration.repository');
            $migrator = app('migrator');
            $checks['All migrations applied'] = $repository->repositoryExists()
                && array_diff(array_keys($migrator->getMigrationFiles(database_path('migrations'))), $repository->getRan()) === [];
            $checks['Database sessions ready'] = config('session.driver') !== 'database' || Schema::hasTable('sessions');
            $checks['Database cache ready'] = config('cache.default') !== 'database'
                || (Schema::hasTable('cache') && Schema::hasTable('cache_locks'));
            $checks['Administrator created'] = Schema::hasTable('users') && DB::table('users')->where('is_admin', true)->exists();
        } catch (Throwable) {
            $checks['Database reachable and initialized'] = false;
        }

        foreach ($checks as $label => $passed) {
            $this->line(($passed ? 'PASS' : 'FAIL').' '.$label);
        }
        if (in_array(false, $checks, true)) {
            $this->error('Preflight failed. Resolve failed checks before activating this deployment.');

            return self::FAILURE;
        }

        $this->info($local ? 'Local rehearsal preflight passed; hosting, TLS and capacity still require live checks.' : 'Server preflight passed; verify TLS, routing, login and uploads over HTTPS.');

        return self::SUCCESS;
    }
}
