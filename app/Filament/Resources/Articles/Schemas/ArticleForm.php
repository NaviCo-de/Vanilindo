<?php

namespace App\Filament\Resources\Articles\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
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
                FileUpload::make('cover_image_path')
                    ->label('Gambar utama')
                    ->required()
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->disk('public')
                    ->directory('articles')
                    ->visibility('public')
                    ->maxSize(4096)
                    ->helperText('JPG, PNG, atau WebP, maksimal 4 MB. Gunakan gambar horizontal.')
                    ->columnSpanFull(),
                TextInput::make('cover_image_alt')->label('Deskripsi gambar (opsional)')->maxLength(255)
                    ->helperText('Jelaskan isi foto untuk pembaca layar. Jika kosong, menggunakan judul blog.')->columnSpanFull(),
                Textarea::make('excerpt')->label('Ringkasan')->required()->rows(3)->maxLength(2000)
                    ->helperText('Tampil pada kartu blog di halaman utama. Disarankan 1–2 kalimat, sekitar 160–240 karakter.')
                    ->columnSpanFull(),
                MarkdownEditor::make('body')->label('Isi artikel')->required()->maxLength(100000)
                    ->toolbarButtons([
                        ['bold', 'italic', 'link'], ['heading'], ['blockquote', 'bulletList', 'orderedList'], ['undo', 'redo'],
                    ])
                    ->minHeight('360px')
                    ->helperText('Tulis artikel lengkap. Gunakan toolbar untuk subjudul, paragraf, daftar, dan kutipan. Halaman blog dibuat otomatis.')
                    ->columnSpanFull(),
                Toggle::make('is_published')->label('Tampilkan di website')->default(false)
                    ->helperText('Matikan untuk menyimpan sebagai draft.'),
                DateTimePicker::make('published_at')->label('Jadwal tayang (opsional)')
                    ->timezone('Asia/Jakarta')->helperText('Kosongkan untuk langsung tampil. Waktu menggunakan WIB.'),
            ]);
    }
}
