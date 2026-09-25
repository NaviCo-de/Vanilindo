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
                TextInput::make('title')->required()->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(180)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->unique(ignoreRecord: true)
                    ->helperText('Use lowercase letters, numbers and hyphens. Changing this later changes the article URL.'),
                Textarea::make('excerpt')->rows(3),
                MarkdownEditor::make('body')->label('Article body')->required(),
                FileUpload::make('cover_image_path')
                    ->label('Cover image')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->disk('public')
                    ->directory('articles')
                    ->visibility('public')
                    ->maxSize(4096),
                TextInput::make('cover_image_alt')->label('Cover image description (alt text)')->maxLength(255),
                Toggle::make('is_published')->label('Visible on website')->default(false),
                DateTimePicker::make('published_at')->label('Publication date')->helperText('Optional; used as the article date.'),
                TextInput::make('seo_title')->label('SEO title')->maxLength(255),
                Textarea::make('seo_description')->label('SEO description')->rows(2)->maxLength(320),
            ]);
    }
}
