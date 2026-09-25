<?php

namespace App\Filament\Resources\ContentBlocks\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ContentBlockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')->disabled()->dehydrated(false),
                TextInput::make('eyebrow')->maxLength(160),
                TextInput::make('heading')->maxLength(255),
                Textarea::make('body')->rows(8)->helperText('Replace any [Temporary] copy before launch.'),
                FileUpload::make('image_path')
                    ->label('Section image')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->disk('public')
                    ->directory('sections')
                    ->visibility('public')
                    ->maxSize(4096),
                TextInput::make('image_alt')->label('Image description (alt text)')->maxLength(255),
            ]);
    }
}
