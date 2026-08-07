<?php

namespace App\Filament\Resources\Announcements\Tables;

use App\Models\Announcement;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(Announcement::adminOrdered())
            ->searchPlaceholder('Duyuru Ara...')
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->disk('public')
                    ->height(60)
                    ->width(45)
                    ->extraImgAttributes([
                        'class' => 'rounded-md object-cover shadow-sm border border-slate-700',
                        'style' => 'object-fit: cover; aspect-ratio: 3/4; width: 45px; height: 60px;',
                    ])
                    ->defaultImageUrl(asset('images/placeholder.svg')),

                TextColumn::make('title')
                    ->label('Başlık')
                    ->searchable(['title', 'message'])
                    ->weight('bold')
                    ->limit(50)
                    ->description(fn (Announcement $record) => ! empty(trim((string) $record->message)) ? \Illuminate\Support\Str::limit(strip_tags($record->message), 80) : null),

                TextColumn::make('status_badge')
                    ->label('Durum')
                    ->badge()
                    ->state(fn (Announcement $record) => $record->admin_status['label'])
                    ->color(fn (Announcement $record) => $record->admin_status['color']),

                TextColumn::make('formatted_date_range')
                    ->label('Gösterim Tarihi')
                    ->color('gray'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Düzenle'),

                DeleteAction::make()
                    ->label('Sil')
                    ->requiresConfirmation()
                    ->modalHeading('Duyuruyu silmek istediğinize emin misiniz?')
                    ->modalDescription('Bu işlem geri alınamaz.'),
            ]);
    }
}
