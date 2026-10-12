<?php

namespace Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionPreflightTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_gate_rejects_debug_http_and_missing_administrator(): void
    {
        config(['app.env' => 'production', 'app.debug' => true, 'app.url' => 'http://example.com', 'session.secure' => false]);

        $this->artisan('vanilindo:preflight')
            ->expectsOutput('FAIL APP_DEBUG=false')
            ->expectsOutput('FAIL HTTPS application URL')
            ->expectsOutput('FAIL Secure session cookies')
            ->expectsOutput('FAIL Administrator created')
            ->assertFailed();
    }

    public function test_local_flag_does_not_allow_insecure_remote_configuration(): void
    {
        config(['app.env' => 'production', 'app.debug' => false, 'app.url' => 'http://example.com', 'session.secure' => false]);

        $this->artisan('vanilindo:preflight', ['--local' => true])
            ->expectsOutput('FAIL HTTPS application URL')
            ->expectsOutput('FAIL Secure session cookies')
            ->expectsOutput('FAIL Production MySQL connection')
            ->assertFailed();
    }

    public function test_cpanel_preflight_accepts_direct_public_media_and_rejects_a_mismatched_disk(): void
    {
        $directory = sys_get_temp_dir().'/vanilindo-preflight-'.bin2hex(random_bytes(8));
        mkdir($directory.'/storage', 0755, true);
        mkdir($directory.'/other-media', 0755, true);
        $previousPublicPath = public_path();
        $this->app->usePublicPath($directory);

        try {
            config(['filesystems.disks.public.root' => $directory.'/storage']);
            $this->artisan('vanilindo:preflight')
                ->expectsOutput('PASS Public media storage location')->assertFailed();

            config(['filesystems.disks.public.root' => $directory.'/other-media']);
            $this->artisan('vanilindo:preflight')
                ->expectsOutput('FAIL Public media storage location')->assertFailed();
        } finally {
            $this->app->usePublicPath($previousPublicPath);
            (new Filesystem)->deleteDirectory($directory);
        }
    }
}
