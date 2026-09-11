<?php

namespace App\Filament\Resources\VideoCollections\RelationManagers;

use App\Filament\Resources\Episodes\EpisodeResource;
use App\Models\Episode;
use App\Models\VideoCollection;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AutoResolvedEpisodesRelationManager extends RelationManager
{
    protected static string $relationship = 'episodes';

    protected ?\Illuminate\Support\Collection $memoizedResolvedEpisodes = null;

    protected function getResolvedEpisodes(): \Illuminate\Support\Collection
    {
        if ($this->memoizedResolvedEpisodes === null) {
            /** @var VideoCollection $collection */
            $collection = $this->getOwnerRecord();
            $this->memoizedResolvedEpisodes = $collection->resolveEpisodes();
        }

        return $this->memoizedResolvedEpisodes;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return in_array($ownerRecord->source_type, ['category', 'featured', 'active_period_program_videos', 'hybrid'], true);
    }

    public function getTableHeading(): ?string
    {
        /** @var VideoCollection $collection */
        $collection = $this->getOwnerRecord();

        $resolvedEpisodes = $this->getResolvedEpisodes();

        if ($collection->source_type === 'hybrid') {
            $pinnedIds = $collection->episodes()->pluck('episodes.id')->all();
            $autoEpisodes = $resolvedEpisodes->reject(fn ($ep) => in_array($ep->id, $pinnedIds, true));
            $count = $autoEpisodes->count();
            return "Otomatik Gelen Videolar ({$count})";
        }

        $count = $resolvedEpisodes->count();

        return "Otomatik Gelen Videolar ({$count})";
    }

    public function getTableDescription(): ?string
    {
        /** @var VideoCollection $collection */
        $collection = $this->getOwnerRecord();

        $resolvedEpisodes = $this->getResolvedEpisodes();
        $totalCount = $resolvedEpisodes->count();

        if ($collection->source_type === 'category') {
            $catName = $collection->category ? $collection->category->name : 'Seçilen';
            if ($totalCount === 0) {
                return 'Bu kategoriye bağlı public/published video bulunmuyor. Bu koleksiyon şu anda public sitede boş olacaktır.';
            }
            $formattedCount = number_format($totalCount, 0, ',', '.');
            return "{$catName} kategorisine bağlı {$formattedCount} public/published video bulundu.";
        }

        if ($collection->source_type === 'featured') {
            if ($totalCount === 0) {
                return 'Aktif yayın dönemindeki canlı programlara ait video bulunamadı. Bu koleksiyon şu anda public sitede boş olacaktır.';
            }
            $formattedCount = number_format($totalCount, 0, ',', '.');
            return "Aktif yayın dönemindeki canlı programlardan {$formattedCount} video bulundu.";
        }

        if ($collection->source_type === 'active_period_program_videos') {
            if ($totalCount === 0) {
                return 'Aktif yayın dönemindeki programlara ait video bulunamadı. Bu koleksiyon şu anda public sitede boş olacaktır.';
            }
            $formattedCount = number_format($totalCount, 0, ',', '.');
            return "Aktif yayın dönemindeki tüm programlardan {$formattedCount} video bulundu.";
        }

        if ($collection->source_type === 'hybrid') {
            $pinnedCount = $collection->episodes()->count();
            $pinnedIds = $collection->episodes()->pluck('episodes.id')->all();
            $autoEpisodes = $resolvedEpisodes->reject(fn ($ep) => in_array($ep->id, $pinnedIds, true));
            $autoCount = $autoEpisodes->count();

            $pinnedFormatted = number_format($pinnedCount, 0, ',', '.');
            $autoFormatted = number_format($autoCount, 0, ',', '.');

            if ($totalCount === 0) {
                return 'Bu koleksiyonda sabitlenen veya otomatik gelen video bulunmuyor. Bu koleksiyon şu anda public sitede boş olacaktır.';
            }

            return "{$pinnedFormatted} sabitlenen video, {$autoFormatted} otomatik gelen video bulundu.";
        }

        return 'Tanımlı kaynak kurallarına göre otomatik listelenir.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    protected function getTableQuery(): Builder
    {
        /** @var VideoCollection $collection */
        $collection = $this->getOwnerRecord();

        $resolvedEpisodes = $this->getResolvedEpisodes();
        $resolvedIds = $resolvedEpisodes->pluck('id')->all();

        if ($collection->source_type === 'hybrid') {
            $pinnedIds = $collection->episodes()->pluck('episodes.id')->all();
            $resolvedIds = array_values(array_diff($resolvedIds, $pinnedIds));
        }

        if (empty($resolvedIds)) {
            return Episode::query()->whereRaw('1 = 0');
        }

        return Episode::query()
            ->with(['program.categories'])
            ->whereIn('id', $resolvedIds);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Video / Bölüm Adı')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn (Episode $record) => EpisodeResource::getUrl('edit', ['record' => $record])),

                TextColumn::make('program.name')
                    ->label('Program')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('program.categories.name')
                    ->label('Kategoriler')
                    ->badge()
                    ->separator(', '),

                TextColumn::make('aired_at')
                    ->label('Yayın Tarihi')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Episode::STATUSES[$state] ?? $state),

                TextColumn::make('source_badge')
                    ->label('Kaynak')
                    ->badge()
                    ->state(function (Episode $record) {
                        /** @var VideoCollection $collection */
                        $collection = $this->getOwnerRecord();
                        return match ($collection->source_type) {
                            'category' => 'Kategori',
                            'featured' => 'Öne Çıkan',
                            'active_period_program_videos' => 'Yayın Dönemi',
                            default => 'Otomatik',
                        };
                    })
                    ->color(function () {
                        /** @var VideoCollection $collection */
                        $collection = $this->getOwnerRecord();
                        return match ($collection->source_type) {
                            'category' => 'warning',
                            'featured' => 'success',
                            'active_period_program_videos' => 'success',
                            default => 'info',
                        };
                    }),
            ])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50);
    }
}
