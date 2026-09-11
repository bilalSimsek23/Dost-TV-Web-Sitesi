<?php

namespace App\Filament\Resources\SiteLayout\HomepageLayoutResource\Tables;

use App\Models\HomepageLayout;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HomepageLayoutsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Düzen Adı')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('page_type')
                    ->label('Sayfa Türü')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'home' => 'Ana Sayfa',
                        'program_detail' => 'Program Detay',
                        'program_index' => 'Programlar',
                        'schedule' => 'Yayın Akışı',
                        'live_tv' => 'Canlı TV',
                        'program_collection' => 'Program Koleksiyonu',
                        'video_collection' => 'Video Koleksiyonu',
                        default => $state ?? 'Ana Sayfa',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'home' => 'primary',
                        'program_detail' => 'info',
                        'program_collection', 'video_collection' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('target_url')
                    ->label('Hedef / URL')
                    ->getStateUsing(function (HomepageLayout $record): string {
                        $type = $record->page_type ?? 'home';
                        if ($type === 'program_collection' && $record->target_id) {
                            $col = \App\Models\ProgramCollection::find($record->target_id);
                            return $col ? "/program-koleksiyonlari/{$col->slug}" : "Koleksiyon #{$record->target_id}";
                        }
                        if ($type === 'video_collection' && $record->target_id) {
                            $col = \App\Models\VideoCollection::find($record->target_id);
                            return $col ? "/koleksiyonlar/{$col->slug}" : "Koleksiyon #{$record->target_id}";
                        }
                        return match ($type) {
                            'home' => '/',
                            'program_detail' => '/programlar/{slug}',
                            'program_index' => '/programlar',
                            'schedule' => '/yayin-akisi',
                            'live_tv' => '/canli-tv',
                            default => '/',
                        };
                    })
                    ->badge()
                    ->color('gray'),

                IconColumn::make('is_active')
                    ->label('Canlı Durum')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('published_at')
                    ->label('Son Yayınlanma')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Henüz Yayınlanmadı')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Son Güncelleme')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()->label('Düzenle'),

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

                Action::make('publish')
                    ->label('Yayınla')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Düzeni Yayınla')
                    ->modalDescription('Taslakta yaptığınız tüm blok değişiklikleri canlı yayındaki yayınlanmış sürüme kopyalanacak.')
                    ->action(function (HomepageLayout $record) {
                        $record->publish(false);
                        Notification::make()
                            ->title('Düzen Yayınlandı')
                            ->body('Taslak değişiklikleri canlı sürüme başarıyla aktarıldı.')
                            ->success()
                            ->send();
                    }),

                Action::make('activate')
                    ->label('Canlı Yap (Aktif Et)')
                    ->icon('heroicon-o-bolt')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Bu Düzeni Canlı Yap')
                    ->modalDescription('Bu düzen aktif edildiğinde, aynı sayfa türündeki canlı düzen otomatik olarak pasife alınacak.')
                    ->visible(fn (HomepageLayout $record) => ! $record->is_active)
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
                                ->body('Canlı yayında olan aktif düzen doğrudan silinemez. Lütfen önce başka bir düzeni canlı yapın (aktif edin).')
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
