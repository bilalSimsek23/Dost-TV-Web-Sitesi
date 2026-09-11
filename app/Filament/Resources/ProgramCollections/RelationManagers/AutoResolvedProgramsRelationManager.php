<?php

namespace App\Filament\Resources\ProgramCollections\RelationManagers;

use App\Filament\Resources\Programs\ProgramResource;
use App\Models\Program;
use App\Models\ProgramCollection;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AutoResolvedProgramsRelationManager extends RelationManager
{
    protected static string $relationship = 'programs';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return in_array($ownerRecord->source_type, ['category', 'active_period_schedule', 'all_programs', 'active_period_live_programs', 'archive_programs', 'featured', 'hybrid'], true);
    }

    public function getTableHeading(): ?string
    {
        /** @var ProgramCollection $collection */
        $collection = $this->getOwnerRecord();

        $resolvedPrograms = $collection->resolvePrograms();

        if ($collection->source_type === 'hybrid') {
            $pinnedIds = $collection->programs()->pluck('programs.id')->all();
            $autoPrograms = $resolvedPrograms->reject(fn ($p) => in_array($p->id, $pinnedIds, true));
            $count = $autoPrograms->count();
            return "Otomatik Gelen Programlar ({$count})";
        }

        $count = $resolvedPrograms->count();

        return "Otomatik Gelen Programlar ({$count})";
    }

    public function getTableDescription(): ?string
    {
        /** @var ProgramCollection $collection */
        $collection = $this->getOwnerRecord();

        return match ($collection->source_type) {
            'category' => $collection->category ? "{$collection->category->name} kategorisinden otomatik gelen programlar listelenir." : 'Seçilen kategoriden otomatik gelen programlar listelenir.',
            'active_period_schedule' => 'Aktif yayın dönemindeki tüm programlar otomatik listelenir.',
            'all_programs' => 'Sistemdeki tüm aktif programlar otomatik listelenir.',
            'active_period_live_programs' => 'Aktif yayın dönemindeki canlı yayınlanan programlar listelenir.',
            'archive_programs' => 'Arşiv kategorisine bağlı tüm programlar otomatik listelenir.',
            'featured' => 'Öne çıkan programlar otomatik listelenir.',
            'hybrid' => 'Sabitlenenler dışındaki otomatik gelen programlar listelenir.',
            default => 'Tanımlı kaynak kurallarına göre otomatik listelenir.',
        };
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    protected function getTableQuery(): Builder
    {
        /** @var ProgramCollection $collection */
        $collection = $this->getOwnerRecord();

        $resolvedPrograms = $collection->resolvePrograms();
        $resolvedIds = $resolvedPrograms->pluck('id')->all();

        if ($collection->source_type === 'hybrid') {
            $pinnedIds = $collection->programs()->pluck('programs.id')->all();
            $resolvedIds = array_values(array_diff($resolvedIds, $pinnedIds));
        }

        if (empty($resolvedIds)) {
            return Program::query()->whereRaw('1 = 0');
        }

        $cases = [];
        $params = [];
        foreach ($resolvedIds as $index => $id) {
            $cases[] = 'WHEN id = ? THEN ?';
            $params[] = (int) $id;
            $params[] = (int) $index;
        }
        $orderBySql = 'CASE ' . implode(' ', $cases) . ' ELSE ' . count($resolvedIds) . ' END';

        return Program::query()
            ->with(['categories'])
            ->whereIn('id', $resolvedIds)
            ->orderByRaw($orderBySql, $params);
    }

    public function reorderTable(array $order, string|int|null $draggedRecordKey = null): void
    {
        /** @var ProgramCollection $collection */
        $collection = $this->getOwnerRecord();

        $orderedIds = [];
        if (array_keys($order) === range(0, count($order) - 1)) {
            $orderedIds = array_map('intval', $order);
        } else {
            asort($order);
            $orderedIds = array_map('intval', array_keys($order));
        }

        $publicSettings = $collection->public_settings ?? [];
        $existingOrder = $publicSettings['custom_program_order'] ?? [];

        $newOrder = array_values(array_unique(array_merge($orderedIds, array_diff($existingOrder, $orderedIds))));

        $publicSettings['custom_program_order'] = $newOrder;
        $collection->public_settings = $publicSettings;
        $collection->save();
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label('Program Adı')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn (Program $record) => ProgramResource::getUrl('edit', ['record' => $record])),

                TextColumn::make('categories.name')
                    ->label('Kategoriler')
                    ->badge()
                    ->separator(', '),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Program::STATUSES[$state] ?? $state),

                TextColumn::make('source_badge')
                    ->label('Kaynak')
                    ->badge()
                    ->state(function (Program $record) {
                        /** @var ProgramCollection $collection */
                        $collection = $this->getOwnerRecord();
                        return match ($collection->source_type) {
                            'category' => 'Kategori',
                            'active_period_schedule' => 'Güncel Programlar',
                            'all_programs' => 'Tüm Programlar',
                            'active_period_live_programs' => 'Öne Çıkan Canlılar',
                            'archive_programs' => 'Arşiv Programları',
                            'featured' => 'Öne Çıkan',
                            default => 'Otomatik',
                        };
                    })
                    ->color(function () {
                        /** @var ProgramCollection $collection */
                        $collection = $this->getOwnerRecord();
                        return match ($collection->source_type) {
                            'category' => 'warning',
                            'active_period_schedule' => 'success',
                            'all_programs' => 'success',
                            'active_period_live_programs' => 'success',
                            'archive_programs' => 'success',
                            'featured' => 'gray',
                            default => 'info',
                        };
                    }),
            ])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50);
    }
}
