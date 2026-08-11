<?php

namespace App\Filament\Resources\Episodes\Tables;

use App\Models\Episode;
use App\Support\Youtube;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
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

        if (filled($programId)) {
            return static::configureSeasonDetailTable($table, (int) $programId, $seasonParam);
        }

        return static::configureGroupedMainTable($table);
    }

    protected static function configureGroupedMainTable(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                return $query
                    ->select(
                        'program_id',
                        'season_number',
                        DB::raw('COUNT(id) as episodes_count'),
                        DB::raw('COUNT(CASE WHEN video_source = "youtube" OR (youtube_url IS NOT NULL AND youtube_url != "") THEN 1 END) as youtube_episodes_count'),
                        DB::raw('MAX(aired_at) as last_aired_at'),
                        DB::raw('MIN(id) as id')
                    )
                    ->groupBy('program_id', 'season_number')
                    ->with('program');
            })
            ->columns([
                TextColumn::make('program.name')
                    ->label('Program Adı')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('season_number')
                    ->label('Sezon')
                    ->formatStateUsing(fn ($state) => filled($state) ? "Sezon {$state}" : 'Sezonsuz')
                    ->badge()
                    ->color(fn ($state) => filled($state) ? 'primary' : 'gray')
                    ->sortable(),

                TextColumn::make('episodes_count')
                    ->label('Bölüm Sayısı')
                    ->formatStateUsing(fn ($state) => "{$state} Bölüm")
                    ->sortable(),

                TextColumn::make('playlist')
                    ->label('Playlist')
                    ->badge()
                    ->state(function (Episode $record) {
                        if (filled($record->program?->youtube_playlist_url)) {
                            return 'Playlist Bağlı';
                        }
                        if (($record->youtube_episodes_count ?? 0) > 0) {
                            return 'Playlistten Aktarıldı';
                        }
                        return 'Playlist Yok';
                    })
                    ->color(function (Episode $record) {
                        if (filled($record->program?->youtube_playlist_url)) {
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
                Action::make('manage')
                    ->label('Yönet')
                    ->icon('heroicon-o-folder-open')
                    ->color('amber')
                    ->url(fn (Episode $record) => url('/admin/episodes?program_id=' . $record->program_id . '&season_number=' . ($record->season_number ?? 'none'))),
            ])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    protected static function configureSeasonDetailTable(Table $table, int $programId, ?string $seasonParam): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) use ($programId, $seasonParam) {
                $query->where('program_id', $programId);

                if ($seasonParam === 'none' || blank($seasonParam)) {
                    $query->whereNull('season_number');
                } else {
                    $query->where('season_number', (int) $seasonParam);
                }

                return $query
                    ->orderByRaw('CASE WHEN episode_number IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('episode_number', 'desc')
                    ->orderBy('created_at', 'desc');
            })
            ->defaultSort('episode_number', 'desc')
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

                TextColumn::make('youtube_url')
                    ->label('YouTube')
                    ->formatStateUsing(function ($state, Episode $record) {
                        $url = $record->canonical_url ?: $state;
                        if (blank($url)) {
                            return new HtmlString("<span class='text-gray-500 text-xs'>-</span>");
                        }

                        return new HtmlString("
                            <a href='{$url}' target='_blank' class='inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-500 text-white font-medium text-xs rounded-lg transition select-none shadow-sm'>
                                <span>▶</span>
                                <span>YouTube'da Aç ↗</span>
                            </a>
                        ");
                    }),

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

