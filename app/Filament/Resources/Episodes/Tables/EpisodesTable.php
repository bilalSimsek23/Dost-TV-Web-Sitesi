<?php

namespace App\Filament\Resources\Episodes\Tables;

use App\Models\Episode;
use App\Models\ProgramSeason;
use App\Models\ProgramSeries;
use App\Support\Youtube;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class EpisodesTable
{
    public static function configure(Table $table): Table
    {
        $livewire = $table->getLivewire();
        $programId = $livewire?->program_id ?? request()->query('program_id');
        $seasonParam = $livewire?->season_number ?? request()->query('season_number');
        $seasonYearParam = $livewire?->season_year ?? request()->query('season_year');
        $seriesParam = $livewire?->program_series_id ?? $livewire?->series_id ?? request()->query('program_series_id', request()->query('series_id'));

        if (filled($programId)) {
            return static::configureSeasonDetailTable($table, (int) $programId, $seasonParam, $seasonYearParam, $seriesParam);
        }

        return static::configureGroupedMainTable($table);
    }

    protected static function configureGroupedMainTable(Table $table): Table
    {
        return $table
            ->recordUrl(function (Episode $record) {
                $url = '/admin/episodes?program_id=' . $record->program_id . '&season_number=' . ($record->season_number ?? 'none');
                if (filled($record->season_year)) {
                    $url .= '&season_year=' . $record->season_year;
                }
                if (filled($record->program_series_id)) {
                    $url .= '&program_series_id=' . $record->program_series_id;
                }
                return url($url);
            })
            ->modifyQueryUsing(function ($query) {
                return $query
                    ->select(
                        'program_id',
                        'season_number',
                        'season_year',
                        'program_series_id',
                        DB::raw('COUNT(id) as episodes_count'),
                        DB::raw('COUNT(CASE WHEN video_source = "youtube" OR (youtube_url IS NOT NULL AND youtube_url != "") THEN 1 END) as youtube_episodes_count'),
                        DB::raw('MAX(aired_at) as last_aired_at'),
                        DB::raw('MIN(id) as id')
                    )
                    ->groupBy('program_id', 'season_number', 'season_year', 'program_series_id')
                    ->with(['program', 'programSeries']);
            })
            ->columns([
                TextColumn::make('program.name')
                    ->label('Program Adı')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('season_display')
                    ->label('Sezon')
                    ->state(function (Episode $record) {
                        if (blank($record->season_number)) {
                            return filled($record->season_year) ? "Sezonsuz ({$record->season_year})" : 'Sezonsuz';
                        }

                        return filled($record->season_year)
                            ? "Sezon {$record->season_number} ({$record->season_year})"
                            : "Sezon {$record->season_number}";
                    })
                    ->badge()
                    ->color(fn (Episode $record) => filled($record->season_number) ? 'primary' : 'gray'),

                TextColumn::make('series_display')
                    ->label('Seri')
                    ->state(function (Episode $record) {
                        return $record->programSeries?->name ?? '—';
                    })
                    ->badge()
                    ->color(fn (Episode $record) => $record->program_series_id ? 'warning' : 'gray'),

                TextColumn::make('episodes_count')
                    ->label('Bölüm Sayısı')
                    ->formatStateUsing(fn ($state) => "{$state} Bölüm")
                    ->sortable(),

                TextColumn::make('playlist')
                    ->label('Playlist')
                    ->badge()
                    ->state(function (Episode $record) {
                        if ($record->program_series_id && $record->programSeries) {
                            if (filled($record->programSeries->youtube_playlist_url)) {
                                return 'Playlist Bağlı';
                            }
                        } else {
                            $playlistUrl = ProgramSeason::resolvePlaylistUrl($record->program, $record->season_number, $record->season_year);
                            if (filled($playlistUrl)) {
                                return 'Playlist Bağlı';
                            }
                        }

                        if (($record->youtube_episodes_count ?? 0) > 0) {
                            return 'Playlistten Aktarıldı';
                        }
                        return 'Playlist Yok';
                    })
                    ->color(function (Episode $record) {
                        if ($record->program_series_id && $record->programSeries) {
                            if (filled($record->programSeries->youtube_playlist_url)) {
                                return 'success';
                            }
                        } else {
                            $playlistUrl = ProgramSeason::resolvePlaylistUrl($record->program, $record->season_number, $record->season_year);
                            if (filled($playlistUrl)) {
                                return 'success';
                            }
                        }

                        if (($record->youtube_episodes_count ?? 0) > 0) {
                            return 'info';
                        }
                        return 'gray';
                    }),

                TextColumn::make('last_aired_at')
                    ->label('Son Yayın Tarihi')
                    ->date('d.m.Y')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->actions([
                Action::make('edit_season')
                    ->label('Düzenle')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->modalHeading('Grup Bilgilerini Düzenle')
                    ->modalDescription(function (Episode $record) {
                        $programName = $record->program?->name ?? 'Program';
                        $seasonLabel = $record->season_number !== null
                            ? "Sezon {$record->season_number}" . (filled($record->season_year) ? " ({$record->season_year})" : '')
                            : 'Sezonsuz';
                        $seriesLabel = $record->program_series_id && $record->programSeries
                            ? " — {$record->programSeries->name}"
                            : '';
                        $count = $record->episodes_count ?? 1;

                        return "{$programName} — {$seasonLabel}{$seriesLabel}\nBu grupta toplam {$count} bölüm bulunmaktadır. Yapılan değişiklikler gruptaki tüm bölümlere ve bağlı sezon/seri kayıtlarına uygulanacaktır.";
                    })
                    ->modalSubmitActionLabel('Kaydet ve Güncelle')
                    ->modalWidth('lg')
                    ->form([
                        TextInput::make('program_name')
                            ->label('Program')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(fn (Episode $record) => $record->program?->name)
                            ->columnSpanFull(),

                        TextInput::make('season_number')
                            ->label('Sezon Numarası')
                            ->numeric()
                            ->nullable()
                            ->placeholder('Örn: 1 (Sezonsuz için boş bırakın)')
                            ->helperText('Sezonsuz kayıtlar için boş bırakılabilir.'),

                        TextInput::make('season_year')
                            ->label('Sezon Yılı')
                            ->nullable()
                            ->placeholder('Örn: 2017 veya 2022-2023')
                            ->helperText('Opsiyonel (Örn: 2017 veya 2022-2023)')
                            ->regex('/^\d{4}(-\d{4})?$/')
                            ->validationMessages([
                                'regex' => 'Sezon yılı YYYY (örn: 2017) veya YYYY-YYYY (örn: 2022-2023) formatında olmalıdır.',
                            ]),

                        TextInput::make('series_name')
                            ->label('Alt Seri / Seri Adı')
                            ->nullable()
                            ->placeholder('Örn: Lemalar, Sözler, 1-10. Söz (Opsiyonel)')
                            ->helperText('Varsa alt seri adını girin. Seri kullanmayan programlarda boş bırakabilirsiniz.')
                            ->columnSpanFull(),

                        TextInput::make('youtube_playlist_url')
                            ->label('YouTube Playlist URL')
                            ->nullable()
                            ->placeholder('https://www.youtube.com/playlist?list=...')
                            ->helperText('Bu sezon veya seriye bağlı YouTube oynatma listesi URL\'si.')
                            ->columnSpanFull(),

                        TextInput::make('episodes_count_display')
                            ->label('Bölüm Sayısı')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('last_sync_display')
                            ->label('Son Senkronizasyon')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->fillForm(function (Episode $record): array {
                        $seriesRecord = $record->program_series_id ? $record->programSeries : null;
                        $seasonRecord = ProgramSeason::findSeason($record->program_id, $record->season_number, $record->season_year);

                        $playlistUrl = null;
                        $lastSyncAt = null;

                        if ($seriesRecord) {
                            $playlistUrl = $seriesRecord->youtube_playlist_url;
                            $lastSyncAt = $seriesRecord->last_youtube_sync_at;
                        }

                        if (blank($playlistUrl) && $seasonRecord) {
                            $playlistUrl = $seasonRecord->youtube_playlist_url;
                            $lastSyncAt = $lastSyncAt ?? $seasonRecord->last_youtube_sync_at;
                        }

                        if (blank($playlistUrl) && $record->program) {
                            $playlistUrl = $record->program->youtube_playlist_url;
                            $lastSyncAt = $lastSyncAt ?? $record->program->last_youtube_sync_at;
                        }

                        $count = $record->episodes_count ?? Episode::where('program_id', $record->program_id)
                            ->when($record->season_number !== null, fn ($q) => $q->where('season_number', $record->season_number), fn ($q) => $q->whereNull('season_number'))
                            ->when(filled($record->season_year), fn ($q) => $q->where('season_year', $record->season_year), fn ($q) => $q->whereNull('season_year'))
                            ->when($record->program_series_id !== null, fn ($q) => $q->where('program_series_id', $record->program_series_id), fn ($q) => $q->whereNull('program_series_id'))
                            ->count();

                        return [
                            'program_name' => $record->program?->name,
                            'season_number' => $record->season_number,
                            'season_year' => $record->season_year,
                            'series_name' => $seriesRecord?->name,
                            'youtube_playlist_url' => $playlistUrl,
                            'episodes_count_display' => "{$count} Bölüm",
                            'last_sync_display' => $lastSyncAt ? $lastSyncAt->format('d.m.Y H:i') : '-',
                        ];
                    })
                    ->action(function (Episode $record, array $data) {
                        $oldProgramId = (int) $record->program_id;
                        $oldSeasonNumber = $record->season_number !== null ? (int) $record->season_number : null;
                        $oldSeasonYear = filled($record->season_year) ? (string) $record->season_year : null;
                        $oldSeriesId = $record->program_series_id ? (int) $record->program_series_id : null;
                        $oldSeries = $oldSeriesId ? ProgramSeries::find($oldSeriesId) : null;
                        $oldSeriesName = $oldSeries?->name;

                        $newSeasonNumber = filled($data['season_number']) ? (int) $data['season_number'] : null;
                        $newSeasonYear = filled($data['season_year']) ? trim((string) $data['season_year']) : null;
                        $newSeriesName = filled($data['series_name']) ? trim((string) $data['series_name']) : null;
                        $newPlaylistUrl = filled($data['youtube_playlist_url']) ? trim((string) $data['youtube_playlist_url']) : null;

                        $currentPlaylistUrl = $oldSeries?->youtube_playlist_url ?? ProgramSeason::resolvePlaylistUrl($record->program, $oldSeasonNumber, $oldSeasonYear);

                        if (
                            $oldSeasonNumber === $newSeasonNumber &&
                            $oldSeasonYear === $newSeasonYear &&
                            $oldSeriesName === $newSeriesName &&
                            $currentPlaylistUrl === $newPlaylistUrl
                        ) {
                            Notification::make()
                                ->title('Herhangi bir değişiklik yapılmadı.')
                                ->info()
                                ->send();

                            return;
                        }

                        // Conflict check: Does another season/series group exist for this program with the new values?
                        $targetSeries = filled($newSeriesName) ? ProgramSeries::findSeries($oldProgramId, null, $newSeriesName) : null;
                        $targetSeriesId = $targetSeries?->id;

                        $conflictQuery = Episode::where('program_id', $oldProgramId);
                        if ($newSeasonNumber !== null) {
                            $conflictQuery->where('season_number', $newSeasonNumber);
                        } else {
                            $conflictQuery->whereNull('season_number');
                        }

                        if ($newSeasonYear !== null) {
                            $conflictQuery->where('season_year', $newSeasonYear);
                        } else {
                            $conflictQuery->whereNull('season_year');
                        }

                        if ($targetSeriesId !== null) {
                            $conflictQuery->where('program_series_id', $targetSeriesId);
                        } elseif (filled($newSeriesName)) {
                            $conflictQuery->whereHas('programSeries', fn ($q) => $q->where('name', $newSeriesName));
                        } else {
                            $conflictQuery->whereNull('program_series_id');
                        }

                        // Exclude current group's episodes
                        $excludeQuery = Episode::where('program_id', $oldProgramId);
                        if ($oldSeasonNumber !== null) {
                            $excludeQuery->where('season_number', $oldSeasonNumber);
                        } else {
                            $excludeQuery->whereNull('season_number');
                        }

                        if ($oldSeasonYear !== null) {
                            $excludeQuery->where('season_year', $oldSeasonYear);
                        } else {
                            $excludeQuery->whereNull('season_year');
                        }

                        if ($oldSeriesId !== null) {
                            $excludeQuery->where('program_series_id', $oldSeriesId);
                        } else {
                            $excludeQuery->whereNull('program_series_id');
                        }

                        $hasConflict = $conflictQuery->whereNotIn('id', $excludeQuery->select('id'))->exists();

                        if ($hasConflict) {
                            $seasonLabel = ($newSeasonNumber !== null ? "Sezon {$newSeasonNumber}" : 'Sezonsuz')
                                . ($newSeasonYear !== null ? " ({$newSeasonYear})" : '');
                            $seriesLabel = filled($newSeriesName) ? " · {$newSeriesName}" : '';

                            Notification::make()
                                ->title("Bu programda {$seasonLabel}{$seriesLabel} grubu zaten mevcut.")
                                ->danger()
                                ->send();

                            return;
                        }

                        // Execute bulk update in transaction
                        $updatedCount = 0;
                        try {
                            DB::transaction(function () use (
                                $oldProgramId,
                                $oldSeasonNumber,
                                $oldSeasonYear,
                                $oldSeriesId,
                                $newSeasonNumber,
                                $newSeasonYear,
                                $newSeriesName,
                                $newPlaylistUrl,
                                &$updatedCount
                            ) {
                                // 1. ProgramSeason management
                                $newSeasonRecord = null;
                                if ($newSeasonNumber !== null || $newSeasonYear !== null) {
                                    $newSeasonRecord = ProgramSeason::firstOrCreate([
                                        'program_id' => $oldProgramId,
                                        'season_number' => $newSeasonNumber ?? 1,
                                        'season_year' => $newSeasonYear,
                                    ]);
                                }

                                // 2. ProgramSeries management
                                $resolvedSeriesId = null;
                                if (filled($newSeriesName)) {
                                    $seriesRecord = ProgramSeries::findOrCreateSeries(
                                        $oldProgramId,
                                        $newSeasonRecord?->id,
                                        $newSeriesName,
                                        $newPlaylistUrl
                                    );

                                    $resolvedSeriesId = $seriesRecord->id;

                                    $seriesUpdates = [];
                                    if ($seriesRecord->program_season_id !== $newSeasonRecord?->id) {
                                        $seriesUpdates['program_season_id'] = $newSeasonRecord?->id;
                                    }
                                    if ($seriesRecord->name !== $newSeriesName) {
                                        $seriesUpdates['name'] = $newSeriesName;
                                    }
                                    if ($newPlaylistUrl !== null && $seriesRecord->youtube_playlist_url !== $newPlaylistUrl) {
                                        $seriesUpdates['youtube_playlist_url'] = $newPlaylistUrl;
                                    }
                                    if (! empty($seriesUpdates)) {
                                        $seriesRecord->update($seriesUpdates);
                                    }
                                } else {
                                    if ($newSeasonRecord && $newPlaylistUrl !== null && $newSeasonRecord->youtube_playlist_url !== $newPlaylistUrl) {
                                        $newSeasonRecord->update(['youtube_playlist_url' => $newPlaylistUrl]);
                                    }
                                }

                                // 3. Update Episode records
                                $episodesQuery = Episode::where('program_id', $oldProgramId);
                                if ($oldSeasonNumber !== null) {
                                    $episodesQuery->where('season_number', $oldSeasonNumber);
                                } else {
                                    $episodesQuery->whereNull('season_number');
                                }

                                if ($oldSeasonYear !== null) {
                                    $episodesQuery->where('season_year', $oldSeasonYear);
                                } else {
                                    $episodesQuery->whereNull('season_year');
                                }

                                if ($oldSeriesId !== null) {
                                    $episodesQuery->where('program_series_id', $oldSeriesId);
                                } else {
                                    $episodesQuery->whereNull('program_series_id');
                                }

                                $updatedCount = $episodesQuery->count();

                                $episodesQuery->update([
                                    'season_number' => $newSeasonNumber,
                                    'season_year' => $newSeasonYear,
                                    'program_series_id' => $resolvedSeriesId,
                                ]);

                                // 4. Clean up old orphaned series / season records if any
                                if ($oldSeriesId !== null && $oldSeriesId !== $resolvedSeriesId) {
                                    $isOldSeriesUsed = Episode::where('program_series_id', $oldSeriesId)->exists();
                                    if (! $isOldSeriesUsed) {
                                        ProgramSeries::where('id', $oldSeriesId)->delete();
                                    }
                                }

                                if ($oldSeasonNumber !== null && ($oldSeasonNumber !== $newSeasonNumber || $oldSeasonYear !== $newSeasonYear)) {
                                    $oldSeason = ProgramSeason::findSeason($oldProgramId, $oldSeasonNumber, $oldSeasonYear);
                                    if ($oldSeason) {
                                        $isOldSeasonUsedByEpisodes = Episode::where('program_id', $oldProgramId)
                                            ->where('season_number', $oldSeasonNumber)
                                            ->when($oldSeasonYear !== null, fn ($q) => $q->where('season_year', $oldSeasonYear), fn ($q) => $q->whereNull('season_year'))
                                            ->exists();

                                        $isOldSeasonUsedBySeries = ProgramSeries::where('program_season_id', $oldSeason->id)->exists();

                                        if (! $isOldSeasonUsedByEpisodes && ! $isOldSeasonUsedBySeries) {
                                            $oldSeason->delete();
                                        }
                                    }
                                }
                            });

                            Notification::make()
                                ->title("Grup bilgileri ve {$updatedCount} bölüm başarıyla güncellendi.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title("Grup güncellenirken bir hata oluştu: {$e->getMessage()}")
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('manage')
                    ->label('Yönet')
                    ->icon('heroicon-o-folder-open')
                    ->color('amber')
                    ->url(function (Episode $record) {
                        $url = '/admin/episodes?program_id=' . $record->program_id . '&season_number=' . ($record->season_number ?? 'none');
                        if (filled($record->season_year)) {
                            $url .= '&season_year=' . $record->season_year;
                        }
                        if (filled($record->program_series_id)) {
                            $url .= '&program_series_id=' . $record->program_series_id;
                        }
                        return url($url);
                    }),

                Action::make('delete_group')
                    ->label('Sil')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Bu sezon/seriyi silmek istediğinize emin misiniz?')
                    ->modalDescription(function (Episode $record) {
                        $programName = $record->program?->name ?? 'Program';
                        $seasonLabel = $record->season_number !== null
                            ? "Sezon {$record->season_number}" . (filled($record->season_year) ? " ({$record->season_year})" : "")
                            : "Sezonsuz";
                        $seriesLabel = $record->program_series_id && $record->programSeries
                            ? " — " . $record->programSeries->name
                            : "";
                        $count = $record->episodes_count ?? Episode::where('program_id', $record->program_id)
                            ->when($record->season_number !== null, fn ($q) => $q->where('season_number', $record->season_number), fn ($q) => $q->whereNull('season_number'))
                            ->when(filled($record->season_year), fn ($q) => $q->where('season_year', $record->season_year), fn ($q) => $q->whereNull('season_year'))
                            ->when($record->program_series_id !== null, fn ($q) => $q->where('program_series_id', $record->program_series_id), fn ($q) => $q->whereNull('program_series_id'))
                            ->count();

                        $groupTitle = "{$programName} — {$seasonLabel}{$seriesLabel}";

                        return "{$groupTitle}\nBu gruba bağlı {$count} bölüm bulunmaktadır. Onaylandığında bu gruba ait tüm bölümler, sezon ve seri bağlantıları kalıcı olarak silinecektir.\n(Program kaydının kendisi kesinlikle silinmez.)";
                    })
                    ->modalSubmitActionLabel('Evet, Sil')
                    ->action(function (Episode $record) {
                        $targetProgramId = (int) $record->program_id;
                        $targetSeasonNumber = $record->season_number !== null ? (int) $record->season_number : null;
                        $targetSeasonYear = filled($record->season_year) ? (string) $record->season_year : null;
                        $targetSeriesId = $record->program_series_id ? (int) $record->program_series_id : null;

                        try {
                            DB::transaction(function () use ($targetProgramId, $targetSeasonNumber, $targetSeasonYear, $targetSeriesId) {
                                // 1. Delete target episodes
                                $episodesQuery = Episode::where('program_id', $targetProgramId);

                                if ($targetSeasonNumber !== null) {
                                    $episodesQuery->where('season_number', $targetSeasonNumber);
                                } else {
                                    $episodesQuery->whereNull('season_number');
                                }

                                if ($targetSeasonYear !== null) {
                                    $episodesQuery->where('season_year', $targetSeasonYear);
                                } else {
                                    $episodesQuery->whereNull('season_year');
                                }

                                if ($targetSeriesId !== null) {
                                    $episodesQuery->where('program_series_id', $targetSeriesId);
                                } else {
                                    $episodesQuery->whereNull('program_series_id');
                                }

                                $episodesQuery->delete();

                                // 2. If series was assigned, check if any other episodes in the DB still use this series
                                if ($targetSeriesId !== null) {
                                    $otherEpisodesUsingSeries = Episode::where('program_series_id', $targetSeriesId)->exists();
                                    if (! $otherEpisodesUsingSeries) {
                                        ProgramSeries::where('id', $targetSeriesId)->delete();
                                    }
                                }

                                // 3. If season was assigned, check if any other episodes or series still use this season
                                if ($targetSeasonNumber !== null) {
                                    $existingSeason = ProgramSeason::findSeason($targetProgramId, $targetSeasonNumber, $targetSeasonYear);
                                    if ($existingSeason) {
                                        $otherEpisodesInSeason = Episode::where('program_id', $targetProgramId)
                                            ->where('season_number', $targetSeasonNumber)
                                            ->when($targetSeasonYear !== null, fn ($q) => $q->where('season_year', $targetSeasonYear), fn ($q) => $q->whereNull('season_year'))
                                            ->exists();

                                        $otherSeriesInSeason = ProgramSeries::where('program_season_id', $existingSeason->id)->exists();

                                        if (! $otherEpisodesInSeason && ! $otherSeriesInSeason) {
                                            $existingSeason->delete();
                                        }
                                    }
                                }
                            });

                            Notification::make()
                                ->title('Sezon/seri ve bağlı bölümler silindi.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title("Silme işlemi sırasında hata oluştu: {$e->getMessage()}")
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    protected static function configureSeasonDetailTable(
        Table $table,
        int $programId,
        ?string $seasonParam,
        ?string $seasonYearParam = null,
        ?string $seriesParam = null
    ): Table {
        return $table
            ->recordUrl(fn (Episode $record) => url("/admin/episodes/{$record->id}/edit"))
            ->modifyQueryUsing(function ($query) use ($programId, $seasonParam, $seasonYearParam, $seriesParam) {
                $query->where('program_id', $programId);

                if (filled($seasonParam) && $seasonParam !== 'all') {
                    if ($seasonParam === 'none') {
                        $query->whereNull('season_number');
                    } else {
                        $query->where('season_number', (int) $seasonParam);
                    }
                }

                if (filled($seasonYearParam) && $seasonYearParam !== 'all') {
                    if ($seasonYearParam === 'none') {
                        $query->whereNull('season_year');
                    } else {
                        $query->where('season_year', (string) $seasonYearParam);
                    }
                }

                if (filled($seriesParam) && $seriesParam !== 'all') {
                    if ($seriesParam === 'none') {
                        $query->whereNull('program_series_id');
                    } else {
                        $query->where('program_series_id', (int) $seriesParam);
                    }
                }

                return $query
                    ->with(['programSeries'])
                    ->orderByRaw('CASE WHEN episode_number IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('episode_number', 'asc')
                    ->orderBy('created_at', 'asc');
            })
            ->defaultSort('episode_number', 'asc')
            ->columns([
                TextColumn::make('thumbnail')
                    ->label('Thumbnail')
                    ->formatStateUsing(function ($state, Episode $record) {
                        $imgUrl = null;
                        if (filled($state)) {
                            $imgUrl = Str::startsWith($state, ['http://', 'https://']) ? $state : asset('storage/' . $state);
                        } else {
                            $vId = Youtube::extractVideoId($record->youtube_url);
                            if ($vId) {
                                $imgUrl = "https://i.ytimg.com/vi/{$vId}/hqdefault.jpg";
                            }
                        }

                        if ($imgUrl) {
                            return new HtmlString("
                                <div class='w-[120px] h-[68px] aspect-[16/9] rounded overflow-hidden bg-gray-800 flex-shrink-0 border border-gray-800 select-none'>
                                    <img src='{$imgUrl}' class='w-full h-full object-cover' alt='Thumbnail' loading='lazy' onerror=\"this.onerror=null; this.parentElement.innerHTML='<div class=\\'w-full h-full flex items-center justify-center text-gray-500 text-xs font-semibold\\'>Visual</div>';\" />
                                </div>
                            ");
                        }

                        return new HtmlString("
                            <div class='w-[120px] h-[68px] aspect-[16/9] rounded bg-gray-800 flex items-center justify-center text-gray-400 text-xs font-semibold flex-shrink-0 select-none border border-gray-800'>
                                Görsel Yok
                            </div>
                        ");
                    }),

                TextColumn::make('episode_number')
                    ->label('Bölüm No')
                    ->formatStateUsing(fn ($state) => $state ? "B{$state}" : '-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('programSeries.name')
                    ->label('Seri')
                    ->badge()
                    ->color('warning')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Bölüm Başlığı')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->tooltip(fn (Episode $record) => $record->title)
                    ->weight('bold'),

                TextColumn::make('aired_at')
                    ->label('Yayın Tarihi')
                    ->date('d.m.Y')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Episode::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'published' => 'success',
                        'ready' => 'warning',
                        'draft' => 'gray',
                        'archived' => 'danger',
                        default => 'info',
                    }),

                IconColumn::make('show_on_public')
                    ->label('Public')
                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->tooltip(fn (bool $state): string => $state ? 'Sitede görünüyor' : 'Sitede gizli')
                    ->action(function (Episode $record) {
                        $newPublic = ! (bool) $record->show_on_public;
                        $record->update([
                            'show_on_public' => $newPublic,
                            'is_active' => $newPublic && $record->status === 'published',
                        ]);

                        Notification::make()
                            ->title($newPublic ? 'Bölüm sitede görünür yapıldı.' : 'Bölüm siteden gizlendi.')
                            ->success()
                            ->duration(2500)
                            ->send();
                    }),
            ])
            ->actions([
                EditAction::make(),

                Action::make('archive')
                    ->label('Arşivle')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->visible(fn (Episode $record) => $record->status !== 'archived')
                    ->requiresConfirmation()
                    ->action(function (Episode $record) {
                        $record->update([
                            'status' => 'archived',
                            'show_on_public' => false,
                            'is_active' => false,
                        ]);
                        Notification::make()
                            ->title("{$record->title} arşive kaldırıldı.")
                            ->warning()
                            ->send();
                    }),

                Action::make('unarchive')
                    ->label('Arşivden Çıkar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (Episode $record) => $record->status === 'archived')
                    ->action(function (Episode $record) {
                        $record->update([
                            'status' => 'published',
                            'show_on_public' => true,
                            'is_active' => true,
                        ]);
                        Notification::make()
                            ->title("{$record->title} tekrar yayına alındı.")
                            ->success()
                            ->send();
                    }),

                DeleteAction::make(),
            ])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}
