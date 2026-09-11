<?php

namespace App\Filament\Resources\YoutubeChannels\Tables;

use App\Models\YoutubeChannel;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class YoutubeChannelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->state(fn (YoutubeChannel $record) => $record->avatar_url)
                    ->circular(),

                TextColumn::make('name')
                    ->label('Kanal Adı')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (YoutubeChannel $record) => $record->description),

                TextColumn::make('handle')
                    ->label('Handle')
                    ->searchable()
                    ->badge()
                    ->color('slate'),

                TextColumn::make('url')
                    ->label('YouTube URL')
                    ->searchable()
                    ->limit(40)
                    ->url(fn (YoutubeChannel $record) => $record->url, true),

                ToggleColumn::make('is_active')
                    ->label('Aktif'),

                TextColumn::make('sort_order')
                    ->label('Sıra')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Aktiflik Durumu'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Kanala Sil')
                    ->modalDescription('Bu YouTube kanalını silmek istediğinize emin misiniz?'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
