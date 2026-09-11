<?php

namespace App\Filament\Resources\SiteLayout\HomepageLayoutResource\Tables;

use App\Filament\Resources\SiteLayout\HomepageLayoutResource\HomepageLayoutResource;
use App\Models\HomepageLayout;
use App\Services\Page\PageDiscoveryService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HomepageLayoutsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('target_page')
                    ->label('SAYFA')
                    ->getStateUsing(fn (HomepageLayout $record): string => PageDiscoveryService::resolveTargetTitle($record))
                    ->weight('bold')
                    ->searchable(false),

                TextColumn::make('page_type')
                    ->label('TÜR')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'home' => 'Ana Sayfa',
                        'program_detail' => 'Şablon',
                        'program_index' => 'Sistem',
                        'schedule' => 'Sistem',
                        'live_tv' => 'Sistem',
                        'program_collection' => 'Program Koleksiyonu',
                        'video_collection' => 'Video Koleksiyonu',
                        default => 'Sistem',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'home' => 'primary',
                        'program_detail' => 'info',
                        'program_collection', 'video_collection' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('target_url')
                    ->label('URL')
                    ->getStateUsing(fn (HomepageLayout $record): string => PageDiscoveryService::resolveTargetUrl($record))
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status')
                    ->label('DURUM')
                    ->badge()
                    ->getStateUsing(function (HomepageLayout $record): string {
                        if ($record->is_active) {
                            return 'Canlı';
                        }
                        if ($record->published_at !== null) {
                            return 'Taslak / Pasif';
                        }

                        return 'Henüz Tasarlanmadı';
                    })
                    ->color(function (HomepageLayout $record): string {
                        if ($record->is_active) {
                            return 'success';
                        }
                        if ($record->published_at !== null) {
                            return 'warning';
                        }

                        return 'gray';
                    }),

                TextColumn::make('name')
                    ->label('DÜZEN ADI')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('published_at')
                    ->label('SON YAYINLAMA')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('builder')
                    ->label(fn (HomepageLayout $record): string => ($record->published_at === null && ! $record->is_active) ? "Visual Builder'da Tasarla" : "Visual Builder'da Düzenle")
                    ->icon(fn (HomepageLayout $record): string => ($record->published_at === null && ! $record->is_active) ? 'heroicon-o-paint-brush' : 'heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(fn (HomepageLayout $record): string => HomepageLayoutResource::getUrl('edit', ['record' => $record])),

                Action::make('duplicate')
                    ->label('Düzeni Kopyala')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Düzeni Kopyala')
                    ->modalDescription('Bu düzenin bir kopyası (taslak olarak) oluşturulacak. Kopyalanan düzen otomatik olarak canlı yapılmaz.')
                    ->action(function (HomepageLayout $record) {
                        $replica = $record->duplicate();
                        Notification::make()
                            ->title('Düzen Kopyalandı')
                            ->body("'{$replica->name}' başarıyla oluşturuldu.")
                            ->success()
                            ->send();
                    }),

                Action::make('activate')
                    ->label('Canlı Yap')
                    ->icon('heroicon-o-bolt')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Bu Düzeni Canlı Yap')
                    ->modalDescription('Bu düzen aktif edildiğinde, aynı sayfa türündeki canlı düzen otomatik olarak pasife alınacak.')
                    ->visible(fn (HomepageLayout $record) => ! $record->is_active && $record->published_at !== null)
                    ->action(function (HomepageLayout $record) {
                        $record->publish(true);
                        Notification::make()
                            ->title('Canlı Düzen Değiştirildi')
                            ->body('Bu düzen yayınlanarak canlı sayfa olarak ayarlandı.')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()
                    ->label('Sil')
                    ->modalHeading('Düzeni Sil')
                    ->modalDescription('Bu düzen kaydını silmek istediğinizden emin misiniz?')
                    ->before(function (HomepageLayout $record, DeleteAction $action) {
                        if ($record->is_active) {
                            Notification::make()
                                ->title('Aktif Düzen Silinemez')
                                ->body('Canlı yayında olan aktif düzen doğrudan silinemez. Lütfen önce başka bir düzeni canlı yapın.')
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->paginated([25, 50])
            ->defaultPaginationPageOption(25);
    }
}

