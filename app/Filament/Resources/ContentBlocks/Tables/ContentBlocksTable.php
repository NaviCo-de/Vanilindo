<?php

namespace App\Filament\Resources\ContentBlocks\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContentBlocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->searchable(),
                TextColumn::make('page')->sortable(),
                TextColumn::make('heading')->limit(48),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->defaultSort('page')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
