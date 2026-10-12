<?php

namespace App\Providers;

use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Nonce trusted template code before any user content is rendered.
        // Livewire and Vite already read the same per-request Vite nonce.
        Blade::precompiler(fn (string $template): string => preg_replace(
            '/<(script|style)\b(?![^>]*\bnonce\s*=)/i',
            '<$1 nonce="{{ \\Illuminate\\Support\\Facades\\Vite::cspNonce() }}"',
            $template,
        ));

        FileUpload::configureUsing(function (FileUpload $upload): void {
            $upload->preventFilePathTampering()
                ->rule('extensions:jpg,jpeg,png,webp');
        });
    }
}
