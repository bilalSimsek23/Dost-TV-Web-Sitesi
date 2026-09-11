<?php

namespace App\Filament\Resources\ProgramCollections\Tables;

use App\Models\ProgramCollection;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProgramCollectionsTable
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
                        'active_period_schedule' => 'Güncel Programlar',
                        'all_programs' => 'Tüm Programlar',
                        'active_period_live_programs' => 'Öne Çıkan Canlılar',
                        'archive_programs' => 'Arşiv Programları',
                        'featured' => 'Öne Çıkan (Eski)',
                        'hybrid' => 'Hibrit',
                        default => is_string($state) ? $state : '—',
                    })
                    ->color(fn ($state) => match ($state) {
                        'manual' => 'info',
                        'category' => 'warning',
                        'active_period_schedule' => 'success',
                        'all_programs' => 'success',
                        'active_period_live_programs' => 'success',
                        'archive_programs' => 'success',
                        'featured' => 'gray',
                        'hybrid' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('category.name')
                    ->label('Bağlı Kategori')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('programs_count')
                    ->label('Program Sayısı')
                    ->counts('programs')
                    ->formatStateUsing(function ($state, ProgramCollection $record) {
                        if (in_array($record->source_type, ['category', 'active_period_schedule', 'all_programs', 'active_period_live_programs', 'archive_programs', 'featured'], true)) {
                            return 'Otomatik (' . $record->resolvePrograms()->count() . ')';
                        }
                        return $state . ' Program';
                    }),

                TextColumn::make('usage')
                    ->label('Kullanım')
                    ->badge()
                    ->state(function (ProgramCollection $record) {
                        $usage = app(\App\Services\Home\CollectionUsageDetector::class)->getProgramCollectionUsage($record);
                        return $usage['labels'];
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Ana Sayfa' => 'danger',
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
                    ->options(ProgramCollection::getFormSourceTypeOptions()),

                TernaryFilter::make('is_active')
                    ->label('Aktif / Pasif'),

                SelectFilter::make('usage')
                    ->label('Kullanım')
                    ->options([
                        'published' => 'Ana Sayfa',
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
                            $ids = $detector->getPublishedProgramCollectionIds();
                            return empty($ids) ? $query->whereRaw('1 = 0') : $query->whereIn('id', $ids);
                        }
                        if ($value === 'draft') {
                            $ids = $detector->getDraftProgramCollectionIds();
                            return empty($ids) ? $query->whereRaw('1 = 0') : $query->whereIn('id', $ids);
                        }
                        if ($value === 'unused') {
                            $pubIds = $detector->getPublishedProgramCollectionIds();
                            $draftIds = $detector->getDraftProgramCollectionIds();
                            $allIds = array_values(array_unique(array_merge($pubIds, $draftIds)));
                            return empty($allIds) ? $query : $query->whereNotIn('id', $allIds);
                        }
                        return $query;
                    }),
            ])
            ->recordActions([
                EditAction::make()->label('Düzenle'),
                DeleteAction::make()
                    ->label('Sil')
                    ->requiresConfirmation()
                    ->modalHeading('Program Koleksiyonunu Sil')
                    ->modalDescription(function (ProgramCollection $record) {
                        $usage = app(\App\Services\Home\CollectionUsageDetector::class)->getProgramCollectionUsage($record);
                        if ($usage['isPublished']) {
                            return 'Bu koleksiyon aktif Ana Sayfa düzeninde kullanılıyor. Silerseniz bağlı blok koleksiyon kaynağını kaybeder.';
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
