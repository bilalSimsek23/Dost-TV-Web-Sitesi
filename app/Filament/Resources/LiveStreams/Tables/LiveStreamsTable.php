<?php

namespace App\Filament\Resources\LiveStreams\Tables;

use App\Models\LiveStream;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class LiveStreamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Yayın Adı')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('stream_type')
                    ->label('Tür')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => LiveStream::STREAM_TYPES[$state] ?? $state)
                    ->color('info'),

                IconColumn::make('is_primary')
                    ->label('Ana Yayın')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedStar)
                    ->falseIcon(Heroicon::OutlinedMinus)
                    ->trueColor('amber')
                    ->falseColor('gray'),

                IconColumn::make('is_currently_live')
                    ->label('Canlıda')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedSignal)
                    ->falseIcon(Heroicon::OutlinedMinus)
                    ->trueColor('danger')
                    ->falseColor('gray'),

                ToggleColumn::make('is_active')
                    ->label('Aktif'),

                TextColumn::make('stream_url')
                    ->label('Akış URL\'si')
                    ->limit(30)
                    ->copyable()
                    ->searchable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('stream_type')
                    ->label('Platform Türü')
                    ->options(LiveStream::STREAM_TYPES),

                TernaryFilter::make('is_primary')
                    ->label('Ana Yayın'),

                TernaryFilter::make('is_currently_live')
                    ->label('Canlıda Olma Durumu'),

                TernaryFilter::make('is_active')
                    ->label('Aktiflik Durumu'),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('set_primary')
                    ->label('Ana Yayın Yap')
                    ->icon(Heroicon::OutlinedStar)
                    ->color('warning')
                    ->hidden(fn (LiveStream $record) => $record->is_primary)
                    ->action(function (LiveStream $record) {
                        $record->update(['is_primary' => true]);

                        Notification::make()
                            ->title('"' . $record->title . '" ana yayın olarak ayarlandı.')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
