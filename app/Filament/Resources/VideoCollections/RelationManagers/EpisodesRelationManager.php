<?php

namespace App\Filament\Resources\VideoCollections\RelationManagers;

use App\Models\Episode;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EpisodesRelationManager extends RelationManager
{
    protected static string $relationship = 'episodes';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return in_array($ownerRecord->source_type ?? 'manual', ['manual', 'hybrid'], true);
    }

    public function getTableHeading(): ?string
    {
        /** @var \App\Models\VideoCollection $collection */
        $collection = $this->getOwnerRecord();

        if (($collection->source_type ?? 'manual') === 'hybrid') {
            return 'Sabitlenen Videolar';
        }

        return 'Koleksiyondaki Videolar';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')
                    ->label('Video / Bölüm Adı')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('program.name')
                    ->label('Program')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('duration')
                    ->label('Süre')
                    ->sortable(),

                TextColumn::make('aired_at')
                    ->label('Yayın Tarihi')
                    ->date('d.m.Y')
                    ->sortable(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('+ Video Ekle')
                    ->modalHeading('Koleksiyona Video Ekle')
                    ->recordSelect(
                        fn (Select $select) => $select
                            ->placeholder('Koleksiyona eklenecek videoyu arayın veya seçin...')
                            ->searchable()
                            ->options(function () {
                                /** @var \App\Models\VideoCollection $ownerRecord */
                                $ownerRecord = $this->getOwnerRecord();
                                $attachedIds = $ownerRecord->episodes()->pluck('episodes.id')->all();

                                return Episode::query()
                                    ->with(['program'])
                                    ->where('is_active', true)
                                    ->where('show_on_public', true)
                                    ->where('status', 'published')
                                    ->when(! empty($attachedIds), fn ($q) => $q->whereNotIn('episodes.id', $attachedIds))
                                    ->where(function ($q) {
                                        $q->where(function ($sub) {
                                            $sub->whereNotNull('youtube_url')
                                                ->where('youtube_url', '!=', '');
                                        })->orWhere(function ($sub) {
                                            $sub->whereNotNull('video_path')
                                                ->where('video_path', '!=', '');
                                        });
                                    })
                                    ->orderByDesc('aired_at')
                                    ->orderByDesc('created_at')
                                    ->orderByDesc('id')
                                    ->limit(100)
                                    ->get()
                                    ->mapWithKeys(function (Episode $record) {
                                        $programName = $record->program?->name;
                                        $label = $programName ? "{$programName} — {$record->title}" : $record->title;
                                        return [$record->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->getSearchResultsUsing(function (string $search): array {
                                /** @var \App\Models\VideoCollection $ownerRecord */
                                $ownerRecord = $this->getOwnerRecord();
                                $attachedIds = $ownerRecord->episodes()->pluck('episodes.id')->all();

                                return Episode::query()
                                    ->with(['program'])
                                    ->where('is_active', true)
                                    ->where('show_on_public', true)
                                    ->where('status', 'published')
                                    ->when(! empty($attachedIds), fn ($q) => $q->whereNotIn('episodes.id', $attachedIds))
                                    ->where(function ($q) {
                                        $q->where(function ($sub) {
                                            $sub->whereNotNull('youtube_url')
                                                ->where('youtube_url', '!=', '');
                                        })->orWhere(function ($sub) {
                                            $sub->whereNotNull('video_path')
                                                ->where('video_path', '!=', '');
                                        });
                                    })
                                    ->where(function ($q) use ($search) {
                                        $q->where('title', 'like', "%{$search}%")
                                            ->orWhereHas('program', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
                                    })
                                    ->orderByDesc('aired_at')
                                    ->orderByDesc('created_at')
                                    ->orderByDesc('id')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function (Episode $record) {
                                        $programName = $record->program?->name;
                                        $label = $programName ? "{$programName} — {$record->title}" : $record->title;
                                        return [$record->id => $label];
                                    })
                                    ->toArray();
                            })
                    ),
            ])
            ->recordActions([
                DetachAction::make()
                    ->label('Koleksiyondan Çıkar')
                    ->modalHeading('Videoyu Koleksiyondan Çıkar')
                    ->modalDescription('Bu işlem videoyu koleksiyondan çıkarır. Video sistemden silinmez.')
                    ->color('danger'),
            ])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50);
    }
}
