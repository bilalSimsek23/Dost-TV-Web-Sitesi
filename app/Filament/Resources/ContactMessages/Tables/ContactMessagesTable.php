<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use App\Models\ContactMessage;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('İletişim mesajlarında ara...')
            ->columns([
                TextColumn::make('name')
                    ->label('Ad Soyad')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->label('E-Posta')
                    ->searchable()
                    ->copyable()
                    ->color('sky'),

                TextColumn::make('subject')
                    ->label('Konu')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ContactMessage::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        ContactMessage::STATUS_NEW => 'warning',
                        ContactMessage::STATUS_READ => 'info',
                        ContactMessage::STATUS_REPLIED => 'success',
                        ContactMessage::STATUS_ARCHIVED => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(ContactMessage::STATUSES),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('İncele')
                    ->mutateRecordDataUsing(function (array $data, ContactMessage $record): array {
                        $record->markAsRead();
                        $data['status'] = $record->status;
                        return $data;
                    }),

                EditAction::make()
                    ->label('Durum Güncelle'),

                DeleteAction::make()
                    ->label('Sil')
                    ->requiresConfirmation()
                    ->modalHeading('Mesajı silmek istediğinize emin misiniz?')
                    ->modalDescription('Bu işlem geri alınamaz.'),
            ]);
    }
}
