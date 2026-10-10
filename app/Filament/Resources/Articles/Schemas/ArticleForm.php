<?php

namespace App\Filament\Resources\Articles\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->label('Judul')->required()->maxLength(255)->columnSpanFull(),
                TextInput::make('external_url')
                    ->label('Link blog')
                    ->required()
                    ->url()
                    ->rule('url:http,https')
                    ->maxLength(2048)
                    ->placeholder('https://example.com/blog')
                    ->helperText('Pengunjung langsung membuka link ini saat kartu blog diklik.')
                    ->columnSpanFull(),
                FileUpload::make('cover_image_path')
                    ->label('Gambar')
                    ->required()
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->disk('public')
                    ->directory('articles')
                    ->visibility('public')
                    ->maxSize(4096)
                    ->helperText('JPG, PNG, atau WebP, maksimal 4 MB. Gunakan gambar horizontal.')
                    ->columnSpanFull(),
                Textarea::make('excerpt')->label('Deskripsi')->required()->rows(4)->maxLength(2000)->columnSpanFull(),
                Toggle::make('is_published')->label('Tampilkan di website')->default(true),
                DateTimePicker::make('published_at')->label('Jadwal tayang (opsional)')
                    ->timezone('Asia/Jakarta')->helperText('Kosongkan untuk langsung tampil. Waktu menggunakan WIB.'),
            ]);
    }
}
