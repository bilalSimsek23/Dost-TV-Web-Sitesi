<?php

namespace App\Filament\Resources\Khatms\Tables;

use App\Models\Khatm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KhatmsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Hatim Başlığı')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Khatm::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('claimed_progress')
                    ->label('Cüz Dağıtım Durumu')
                    ->state(fn (Khatm $record) => $record->claimed_count . ' / ' . $record->total_juz . ' Cüz Atandı')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('completed_progress')
                    ->label('Tamamlanan')
                    ->state(fn (Khatm $record) => $record->completed_count . ' / ' . $record->total_juz . ' Cüz Okundu')
                    ->badge()
                    ->color('success'),

                TextColumn::make('start_date')
                    ->label('Başlangıç')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('Bitiş')
                    ->date('d.m.Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(Khatm::STATUSES),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Yönet / Düzenle'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
