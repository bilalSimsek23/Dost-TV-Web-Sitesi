<?php

namespace App\Filament\Resources\Menus\Tables;

use App\Models\Menu;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MenusTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Ad')
                    ->searchable(),

                TextColumn::make('location')
                    ->label('Konum')
                    ->formatStateUsing(fn (string $state) => Menu::LOCATIONS[$state] ?? $state)
                    ->badge(),

                TextColumn::make('description')
                    ->label('Açıklama')
                    ->limit(50)
                    ->placeholder('—'),

                ToggleColumn::make('is_active')
                    ->label('Aktif'),

                TextColumn::make('items_count')
                    ->label('Menü Öğesi Sayısı')
                    ->counts('items'),

                TextColumn::make('updated_at')
                    ->label('Güncellenme Tarihi')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('location')
                    ->label('Konum')
                    ->options(Menu::LOCATIONS),
                TernaryFilter::make('is_active')
                    ->label('Aktif/Pasif'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(fn (Menu $record) => $record->items()->count() > 0
                        ? "Bu menünün {$record->items()->count()} öğesi var. Menü silindiğinde bu öğeler de silinir."
                        : 'Bu menüyü silmek istediğinize emin misiniz?'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
