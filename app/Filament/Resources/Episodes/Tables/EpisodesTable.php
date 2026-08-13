<?php

namespace App\Filament\Resources\Episodes\Tables;

use App\Models\Episode;
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

        if (filled($programId)) {
            return static::configureSeasonDetailTable($table, (int) $programId, $seasonParam, $seasonYearParam);
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
                return url($url);
            })
            ->modifyQueryUsing(function ($query) {
                return $query
                    ->select(
                        'program_id',
                        'season_number',
                        'season_year',
                        DB::raw('COUNT(id) as episodes_count'),
                        DB::raw('COUNT(CASE WHEN video_source = "youtube" OR (youtube_url IS NOT NULL AND youtube_url != "") THEN 1 END) as youtube_episodes_count'),
                        DB::raw('MAX(aired_at) as last_aired_at'),
                        DB::raw('MIN(id) as id')
                    )
                    ->groupBy('program_id', 'season_number', 'season_year')
                    ->with('program');
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

                TextColumn::make('episodes_count')
                    ->label('Bölüm Sayısı')
                    ->formatStateUsing(fn ($state) => "{$state} Bölüm")
                    ->sortable(),

                TextColumn::make('playlist')
                    ->label('Playlist')
                    ->badge()
                    ->state(function (Episode $record) {
                        $playlistUrl = \App\Models\ProgramSeason::resolvePlaylistUrl($record->program, $record->season_number, $record->season_year);
                        if (filled($playlistUrl)) {
                            return 'Playlist Bağlı';
                        }
                        if (($record->youtube_episodes_count ?? 0) > 0) {
                            return 'Playlistten Aktarıldı';
                        }
                        return 'Playlist Yok';
                    })
                    ->color(function (Episode $record) {
                        $playlistUrl = \App\Models\ProgramSeason::resolvePlaylistUrl($record->program, $record->season_number, $record->season_year);
                        if (filled($playlistUrl)) {
                            return 'success';
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
                    ->modalHeading('Sezon Bilgilerini Düzenle')
                    ->modalDescription(function (Episode $record) {
                        $count = $record->episodes_count ?? 1;
                        return "Bu işlem bu gruptaki {$count} bölümün Sezon Numarası ve Sezon Yılı bilgisini topluca güncelleyecektir.";
                    })
                    ->modalSubmitActionLabel('Kaydet ve Güncelle')
                    ->form([
                        TextInput::make('program_name')
                            ->label('Program')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(fn (Episode $record) => $record->program?->name),

                        TextInput::make('season_number')
                            ->label('Sezon Numarası')
                            ->numeric()
                            ->nullable()
                            ->default(fn (Episode $record) => $record->season_number)
                            ->placeholder('Örn: 1'),

                        TextInput::make('season_year')
                            ->label('Sezon Yılı')
                            ->nullable()
                            ->default(fn (Episode $record) => $record->season_year)
                            ->placeholder('Örn: 2017 veya 2022-2023')
                            ->helperText('Opsiyonel (Örn: 2017 veya 2022-2023)')
                            ->regex('/^\d{4}(-\d{4})?$/')
                            ->validationMessages([
                                'regex' => 'Sezon yılı YYYY (örn: 2017) veya YYYY-YYYY (örn: 2022-2023) formatında olmalıdır.',
                            ]),
                    ])
                    ->fillForm(fn (Episode $record): array => [
                        'program_name' => $record->program?->name,
                        'season_number' => $record->season_number,
                        'season_year' => $record->season_year,
                    ])
                    ->action(function (Episode $record, array $data) {
                        $oldProgramId = (int) $record->program_id;
                        $oldSeasonNumber = $record->season_number !== null ? (int) $record->season_number : null;
                        $oldSeasonYear = filled($record->season_year) ? (string) $record->season_year : null;

                        $newSeasonNumber = filled($data['season_number']) ? (int) $data['season_number'] : null;
                        $newSeasonYear = filled($data['season_year']) ? trim((string) $data['season_year']) : null;

                        // If no changes, return early
                        if ($oldSeasonNumber === $newSeasonNumber && $oldSeasonYear === $newSeasonYear) {
                            Notification::make()
                                ->title('Herhangi bir değişiklik yapılmadı.')
                                ->info()
                                ->send();

                            return;
                        }

                        // Conflict check: Does another season exist for this program with the new values?
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

                        $hasConflict = $conflictQuery->whereNotIn('id', $excludeQuery->select('id'))->exists();

                        if ($hasConflict) {
                            $targetLabel = ($newSeasonNumber !== null ? "Sezon {$newSeasonNumber}" : "Sezonsuz")
                                . ($newSeasonYear !== null ? " ({$newSeasonYear})" : "");

                            Notification::make()
                                ->title("Bu programda {$targetLabel} zaten mevcut.")
                                ->danger()
                                ->send();

                            return;
                        }

                        // Execute bulk update in transaction
                        $updatedCount = 0;
                        try {
                            DB::transaction(function () use ($oldProgramId, $oldSeasonNumber, $oldSeasonYear, $newSeasonNumber, $newSeasonYear, &$updatedCount) {
                                $query = Episode::where('program_id', $oldProgramId);
                                if ($oldSeasonNumber !== null) {
                                    $query->where('season_number', $oldSeasonNumber);
                                } else {
                                    $query->whereNull('season_number');
                                }

                                if ($oldSeasonYear !== null) {
                                    $query->where('season_year', $oldSeasonYear);
                                } else {
                                    $query->whereNull('season_year');
                                }

                                $updatedCount = $query->count();

                                $query->update([
                                    'season_number' => $newSeasonNumber,
                                    'season_year' => $newSeasonYear,
                                ]);

                                $existingSeason = \App\Models\ProgramSeason::findSeason($oldProgramId, $oldSeasonNumber, $oldSeasonYear);
                                if ($existingSeason) {
                                    $existingSeason->update([
                                        'season_number' => $newSeasonNumber,
                                        'season_year' => $newSeasonYear,
                                    ]);
                                }
                            });

                            Notification::make()
                                ->title("{$updatedCount} bölümün sezon bilgisi başarıyla güncellendi.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title("Sezon güncellenirken bir hata oluştu: {$e->getMessage()}")
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
                        return url($url);
                    }),
            ])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    protected static function configureSeasonDetailTable(Table $table, int $programId, ?string $seasonParam, ?string $seasonYearParam = null): Table
    {
        return $table
            ->recordUrl(fn (Episode $record) => url("/admin/episodes/{$record->id}/edit"))
            ->modifyQueryUsing(function ($query) use ($programId, $seasonParam, $seasonYearParam) {
                $query->where('program_id', $programId);

                if ($seasonParam === 'none' || blank($seasonParam)) {
                    $query->whereNull('season_number');
                } else {
                    $query->where('season_number', (int) $seasonParam);
                }

                if (filled($seasonYearParam) && $seasonYearParam !== 'none') {
                    $query->where('season_year', (string) $seasonYearParam);
                } elseif ($seasonYearParam === 'none') {
                    $query->whereNull('season_year');
                }


                return $query
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
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('gray'),
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
