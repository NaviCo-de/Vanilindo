<?php

namespace App\Filament\Resources\Articles\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_image_path')->label('Gambar')->disk('public')->imageWidth(100)->imageHeight(56),
                TextColumn::make('title')->label('Judul')->searchable()->sortable()->wrap(),
                TextColumn::make('external_url')->label('Link blog')->limit(45)->wrap(),
                IconColumn::make('is_published')->label('Tampil')->boolean(),
                TextColumn::make('published_at')->label('Jadwal tayang')->dateTime()->timezone('Asia/Jakarta')->sortable()->placeholder('Langsung'),
                TextColumn::make('updated_at')->label('Diubah')->dateTime()->timezone('Asia/Jakarta')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_published')->label('Tampil di website'),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
