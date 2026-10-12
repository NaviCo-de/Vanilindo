<?php

namespace Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CpanelStorageTest extends TestCase
{
    public function test_split_public_media_keeps_runtime_and_private_files_separate(): void
    {
        $directory = sys_get_temp_dir().'/vanilindo-storage-'.bin2hex(random_bytes(8));
        mkdir($directory.'/public_html/storage', 0755, true);
        mkdir($directory.'/vanilindo/storage/app/private', 0755, true);
        file_put_contents($directory.'/vanilindo/storage/app/private/secret.txt', 'private');
        config([
            'filesystems.disks.public.root' => $directory.'/public_html/storage',
            'filesystems.disks.public.url' => 'https://indonesiavanillas.com/storage',
        ]);
        Storage::forgetDisk('public');

        try {
            $this->assertTrue(Storage::disk('public')->put('products/bean.jpg', 'image-fixture'));
            $this->assertSame('image-fixture', file_get_contents($directory.'/public_html/storage/products/bean.jpg'));
            $this->assertFalse(Storage::disk('public')->exists('secret.txt'));
            $this->assertFileDoesNotExist($directory.'/vanilindo/storage/app/private/products/bean.jpg');
            $this->assertSame('https://indonesiavanillas.com/storage/products/bean.jpg', Storage::disk('public')->url('products/bean.jpg'));
            $this->assertSame('private', file_get_contents($directory.'/vanilindo/storage/app/private/secret.txt'));
        } finally {
            Storage::forgetDisk('public');
            (new Filesystem)->deleteDirectory($directory);
        }
    }
}
