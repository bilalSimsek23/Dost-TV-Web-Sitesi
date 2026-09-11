<?php

namespace App\Filament\Resources\InstagramVideos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InstagramVideosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_image_url')
                    ->label('Önizleme')
                    ->height(64)
                    ->width(36),

                TextColumn::make('speaker_name')
                    ->label('Hoca Adı')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('categories.name')
                    ->label('Kategoriler')
                    ->badge()
                    ->color('rose')
                    ->placeholder('-'),

                TextColumn::make('caption')
                    ->label('Açıklama / Başlık')
                    ->limit(60)
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label('Durum')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Eklenme Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
