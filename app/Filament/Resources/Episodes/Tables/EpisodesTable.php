<?php

namespace App\Filament\Resources\Episodes\Tables;

use App\Models\Episode;
use App\Support\Youtube;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class EpisodesTable
{
    public static function configure(Table $table): Table
    {
        $programId = request()->query('program_id');
        $seasonParam = request()->query('season_number');

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

                TextColumn::make('last_aired_at')
                    ->label('Son Yayın Tarihi')
                    ->date('d.m.Y')
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
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}
