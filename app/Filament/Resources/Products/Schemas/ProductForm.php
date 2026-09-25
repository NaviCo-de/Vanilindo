<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->maxLength(160),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(180)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->unique(ignoreRecord: true)
                    ->helperText('Use lowercase letters, numbers and hyphens. Changing this later changes the product URL.'),
                TextInput::make('variety')->maxLength(100)->helperText('For example, Planifolia or Tahitensis.'),
                Textarea::make('summary')->rows(3),
                MarkdownEditor::make('description')->label('Description'),
                FileUpload::make('image_path')
                    ->label('Product image')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->disk('public')
                    ->directory('products')
                    ->visibility('public')
                    ->maxSize(4096),
                TextInput::make('image_alt')->label('Image description (alt text)')->maxLength(255),
                Toggle::make('is_published')->label('Visible on website')->default(false),
                Toggle::make('is_featured')->label('Featured product')->default(false),
                TextInput::make('sort_order')->integer()->minValue(0)->maxValue(65535)->default(0),
                TextInput::make('seo_title')->label('SEO title')->maxLength(255),
                Textarea::make('seo_description')->label('SEO description')->rows(2)->maxLength(320),
            ]);
    }
}
