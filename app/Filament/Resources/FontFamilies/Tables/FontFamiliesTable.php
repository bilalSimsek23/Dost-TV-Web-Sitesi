<?php

namespace App\Filament\Resources\FontFamilies\Tables;

use App\Models\FontFamily;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FontFamiliesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Font Adı')
                    ->searchable(),

                TextColumn::make('source_type')
                    ->label('Kaynak Türü')
                    ->badge()
                    ->formatStateUsing(fn ($state) => FontFamily::SOURCE_TYPES[$state] ?? $state),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                IconColumn::make('is_default')
                    ->label('Varsayılan')
                    ->boolean(),

                TextColumn::make('weights')
                    ->label('Ağırlıklar')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            return implode(', ', $state);
                        }
                        if (is_string($state) && filled($state)) {
                            $decoded = json_decode($state, true);
                            return is_array($decoded) ? implode(', ', $decoded) : $state;
                        }
                        return '—';
                    }),

                TextColumn::make('updated_at')
                    ->label('Güncellenme Tarihi')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('source_type')
                    ->label('Kaynak Türü')
                    ->options(FontFamily::SOURCE_TYPES),
                TernaryFilter::make('is_active')
                    ->label('Aktif/Pasif'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn (FontFamily $record) => $record->is_default)
                    ->tooltip(fn (FontFamily $record) => $record->is_default
                        ? 'Varsayılan font silinemez. Önce başka bir fontu varsayılan yapın.'
                        : null),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
