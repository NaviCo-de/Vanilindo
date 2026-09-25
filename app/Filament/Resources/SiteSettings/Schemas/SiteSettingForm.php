<?php

namespace App\Filament\Resources\SiteSettings\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SiteSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('brand_name')->required()->maxLength(160),
                TextInput::make('tagline')->maxLength(255),
                FileUpload::make('logo_path')
                    ->label('Logo')
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/webp', 'image/jpeg'])
                    ->disk('public')
                    ->directory('branding')
                    ->visibility('public')
                    ->maxSize(2048),
                TextInput::make('logo_alt')->label('Logo description (alt text)')->maxLength(255),
                TextInput::make('contact_email')->email()->maxLength(255),
                TextInput::make('whatsapp_number')->tel()->maxLength(32)->helperText('Use international format, e.g. 62812…'),
                Textarea::make('address')->rows(3),
                TextInput::make('instagram_url')->url()->maxLength(255),
                Textarea::make('meta_description')->label('Default SEO description')->rows(3)->maxLength(320),
            ]);
    }
}
