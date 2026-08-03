<?php

namespace App\Filament\Resources\Episodes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EpisodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('')
                    ->disk('public'),
                TextColumn::make('program.name')
                    ->label('Program')
                    ->searchable(),
                TextColumn::make('title')
                    ->label('Başlık')
                    ->searchable(),
                TextColumn::make('video_source')
                    ->label('Kaynak')
                    ->badge(),
                TextColumn::make('aired_at')
                    ->label('Yayın Tarihi')
                    ->date()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Sıralama')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
