<?php

namespace App\Filament\Resources\Khatms\RelationManagers;

use App\Models\JuzClaim;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class JuzClaimsRelationManager extends RelationManager
{
    protected static string $relationship = 'juzClaims';

    protected static ?string $title = '30 Cüz Yönetimi (Cüz Dağıtımı)';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('juz_number')
                    ->label('Cüz Numarası')
                    ->disabled(),

                Select::make('status')
                    ->label('Durum')
                    ->options(JuzClaim::STATUSES)
                    ->required(),

                TextInput::make('claimed_by_name')
                    ->label('Cüzü Alan Ad Soyad'),

                TextInput::make('claimed_by_phone')
                    ->label('Telefon Numarası'),

                TextInput::make('claimed_by_email')
                    ->label('E-posta Adresi')
                    ->email(),

                DateTimePicker::make('claimed_at')
                    ->label('Atanma / Alınma Tarihi'),

                Textarea::make('notes')
                    ->label('Not')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('juz_number')
                    ->label('Cüz No')
                    ->formatStateUsing(fn (int $state) => $state . '. Cüz')
                    ->sortable()
                    ->color('primary')
                    ->weight(FontWeight::Bold),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => JuzClaim::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'completed' => 'success',
                        'assigned' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('claimed_by_name')
                    ->label('Alan Kişi')
                    ->state(fn (JuzClaim $record) => $record->claimed_by_name ?: '—')
                    ->searchable(),

                TextColumn::make('claimed_by_phone')
                    ->label('Telefon')
                    ->state(fn (JuzClaim $record) => $record->claimed_by_phone ?: '—'),

                TextColumn::make('claimed_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('juz_number')
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(JuzClaim::STATUSES),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Düzenle / Ata')
                    ->modalHeading('Cüz Bilgilerini Düzenle / Kişiye Ata'),

                Action::make('reset_juz')
                    ->label('Boşalt (Sıfırla)')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cüzü Boşalt')
                    ->modalDescription('Bu cüzün kişisini ve atanma bilgisini sıfırlamak istediğinizden emin misiniz?')
                    ->action(function (JuzClaim $record) {
                        $record->update([
                            'status' => 'empty',
                            'claimed_by_name' => null,
                            'claimed_by_phone' => null,
                            'claimed_by_email' => null,
                            'claimed_at' => null,
                        ]);

                        Notification::make()
                            ->title($record->juz_number . '. Cüz başarıyla sıfırlandı.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
