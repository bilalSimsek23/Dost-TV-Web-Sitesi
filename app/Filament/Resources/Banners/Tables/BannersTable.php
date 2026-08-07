<?php

namespace App\Filament\Resources\Banners\Tables;

use App\Models\Banner;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Görsel')
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl(asset('images/placeholder.jpg')),

                TextColumn::make('title')
                    ->label('Başlık')
                    ->searchable(['title', 'subtitle', 'description'])
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Banner $record) => $record->subtitle),

                TextColumn::make('content_type')
                    ->label('İçerik Türü')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Banner::CONTENT_TYPES[$state] ?? ($state ?: 'Hero Görseli'))
                    ->color(fn ($state) => match ($state) {
                        'hero' => 'primary',
                        'banner' => 'info',
                        'promotion' => 'amber',
                        default => 'primary',
                    }),

                TextColumn::make('display_date')
                    ->label('Gösterim Tarihi')
                    ->badge()
                    ->state(function (Banner $record) {
                        $now = now();
                        if ($record->ends_at && $record->ends_at->isPast()) {
                            return 'Süresi Doldu';
                        }
                        if ($record->starts_at || $record->ends_at) {
                            $start = $record->starts_at ? $record->starts_at->format('d.m.Y') : 'Başlangıçsız';
                            $end = $record->ends_at ? $record->ends_at->format('d.m.Y') : 'Süresiz';
                            return "{$start} - {$end}";
                        }
                        return 'Süresiz';
                    })
                    ->color(function (Banner $record) {
                        if ($record->ends_at && $record->ends_at->isPast()) {
                            return 'danger';
                        }
                        if ($record->starts_at || $record->ends_at) {
                            return 'info';
                        }
                        return 'gray';
                    }),

                ToggleColumn::make('is_active')
                    ->label('Aktif'),

                TextColumn::make('sort_order')
                    ->label('Sıralama')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('content_type')
                    ->label('İçerik Türü')
                    ->options(Banner::CONTENT_TYPES),

                TernaryFilter::make('is_active')
                    ->label('Aktiflik Durumu'),

                Filter::make('active_period')
                    ->label('Süresi Devam Edenler')
                    ->query(fn (Builder $query) => $query
                        ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                        ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))),

                Filter::make('expired_period')
                    ->label('Süresi Dolanlar')
                    ->query(fn (Builder $query) => $query->whereNotNull('ends_at')->where('ends_at', '<', now())),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label('Önizle')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->action(function (Banner $record) {
                        $statusText = $record->is_active ? 'Aktif' : 'Pasif';
                        $typeText = Banner::CONTENT_TYPES[$record->content_type] ?? 'Hero Görseli';
                        Notification::make()
                            ->title("Görsel Önizleme: {$record->title}")
                            ->body("Tür: {$typeText} | Durum: {$statusText} | Bağlantı: " . ($record->link_url ?? 'Yok'))
                            ->info()
                            ->send();
                    }),

                EditAction::make(),

                Action::make('replicate')
                    ->label('Kopyala')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('amber')
                    ->action(function (Banner $record) {
                        $replica = $record->replicate();
                        $replica->title = "{$record->title} (Kopya)";
                        $replica->is_active = false;
                        $replica->save();

                        Notification::make()
                            ->title('Görsel İçerik Kopyalandı')
                            ->body("'{$record->title}' kaydından pasif bir kopya oluşturuldu.")
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Görsel İçeriği Sil')
                    ->modalDescription('Bu görsel içeriği silmek istediğinizden emin misiniz? Public site tasarımı bundan etkilenmeyecektir.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
