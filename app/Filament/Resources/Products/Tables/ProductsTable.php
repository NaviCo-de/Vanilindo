<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('variety')->searchable(),
                IconColumn::make('is_published')->label('Published')->boolean(),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_published')->label('Published'),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
