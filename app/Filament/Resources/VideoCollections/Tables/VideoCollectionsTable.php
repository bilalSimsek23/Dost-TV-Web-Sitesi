<?php

namespace App\Filament\Resources\VideoCollections\Tables;

use App\Models\VideoCollection;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VideoCollectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Koleksiyon Adı')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('source_type')
                    ->label('Kaynak Türü')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'manual' => 'Manuel',
                        'category' => 'Kategori',
                        'featured' => 'Öne Çıkan',
                        'active_period_program_videos' => 'Aktif Yayın Dönemi',
                        'hybrid' => 'Hibrit',
                        default => VideoCollection::SOURCE_TYPES[$state] ?? (is_string($state) ? $state : '—'),
                    })
                    ->color(fn ($state) => match ($state) {
                        'manual' => 'info',
                        'category' => 'warning',
                        'featured' => 'success',
                        'active_period_program_videos' => 'success',
                        'hybrid' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('category.name')
                    ->label('Bağlı Kategori')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('usage')
                    ->label('Kullanım')
                    ->badge()
                    ->state(function (VideoCollection $record) {
                        $usage = app(\App\Services\Home\CollectionUsageDetector::class)->getVideoCollectionUsage($record);
                        return $usage['labels'];
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Ana Sayfa' => 'danger',
                        'Video Merkezi' => 'info',
                        'Taslak' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('is_active')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'Aktif' : 'Pasif')
                    ->color(fn (bool $state) => $state ? 'success' : 'gray')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('source_type')
                    ->label('Kaynak Türü')
                    ->options(VideoCollection::SOURCE_TYPES),

                SelectFilter::make('sort_mode')
                    ->label('Sıralama Mantığı')
                    ->options(VideoCollection::SORT_MODES),

                TernaryFilter::make('is_active')
                    ->label('Aktif / Pasif'),

                SelectFilter::make('usage')
                    ->label('Kullanım')
                    ->options([
                        'published' => 'Ana Sayfa',
                        'video_center' => 'Video Merkezi',
                        'draft' => 'Taslak',
                        'unused' => 'Kullanılmıyor',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        if (! $value) {
                            return $query;
                        }
                        $detector = app(\App\Services\Home\CollectionUsageDetector::class);
                        if ($value === 'published') {
                            $ids = $detector->getPublishedVideoCollectionIds();
                            return empty($ids) ? $query->whereRaw('1 = 0') : $query->whereIn('id', $ids);
                        }
                        if ($value === 'video_center') {
                            return $query->where('is_active', true);
                        }
                        if ($value === 'draft') {
                            $ids = $detector->getDraftVideoCollectionIds();
                            return empty($ids) ? $query->whereRaw('1 = 0') : $query->whereIn('id', $ids);
                        }
                        if ($value === 'unused') {
                            $pubIds = $detector->getPublishedVideoCollectionIds();
                            $draftIds = $detector->getDraftVideoCollectionIds();
                            $allIds = array_values(array_unique(array_merge($pubIds, $draftIds)));
                            return $query->where('is_active', false)->whereNotIn('id', $allIds);
                        }
                        return $query;
                    }),
            ])
            ->recordActions([
                EditAction::make()->label('Düzenle'),
                DeleteAction::make()
                    ->label('Sil')
                    ->requiresConfirmation()
                    ->modalHeading('Video Koleksiyonunu Sil')
                    ->modalDescription(function (VideoCollection $record) {
                        $usage = app(\App\Services\Home\CollectionUsageDetector::class)->getVideoCollectionUsage($record);
                        $parts = [];
                        if ($usage['isPublished']) {
                            $parts[] = 'aktif Ana Sayfa düzeninde';
                        }
                        if ($usage['isVideoCenter']) {
                            $parts[] = 'Video Merkezi’nde';
                        }
                        if (! empty($parts)) {
                            $places = implode(' ve ', $parts);
                            return "Bu Video Koleksiyonu {$places} aktif olarak kullanılıyor. Silerseniz bağlı gösterimler kaldırılacaktır. Silmek istediğinize emin misiniz?";
                        }
                        if ($usage['isDraft']) {
                            return 'Bu koleksiyon taslak ana sayfa düzeninde kullanılıyor. Silmek istediğinize emin misiniz?';
                        }
                        return 'Bu koleksiyonu silmek istediğinize emin misiniz?';
                    }),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}
