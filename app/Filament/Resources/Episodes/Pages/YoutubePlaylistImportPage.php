<?php

namespace App\Filament\Resources\Episodes\Pages;

use App\Filament\Resources\Episodes\EpisodeResource;
use App\Models\Episode;
use App\Models\Program;
use App\Models\ProgramSeason;
use App\Models\ProgramSeries;
use App\Services\YouTube\YouTubePlaylistImportService;
use App\Support\Youtube;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class YoutubePlaylistImportPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = EpisodeResource::class;

    protected static ?string $title = 'YouTube Playlist İçe Aktar';

    protected static ?string $slug = 'youtube-import';

    protected string $view = 'filament.resources.episodes.pages.youtube-playlist-import-page';

    public ?array $data = [];

    public ?int $program_id = null;

    public ?string $playlist_url = '';

    public int $season_number = 1;

    public ?string $season_year = null;

    public ?string $series_name = null;

    public ?int $start_episode_number = 1;

    public bool $strip_program_name = false;

    public string $status = 'published';

    public bool $show_on_public = true;

    public bool $is_active = true;

    // Preview state
    public bool $isPreviewLoaded = false;

    public array $previewItems = [];

    public int $totalItemsCount = 0;

    public int $newItemsCount = 0;

    public int $existingItemsCount = 0;

    public int $otherSeriesItemsCount = 0;

    public int $targetExistingItemsCount = 0;

    public int $willImportCount = 0;

    // Import completion state
    public bool $isImported = false;

    public int $importedCount = 0;

    public int $skippedCount = 0;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('program_id')
                    ->label('Program *')
                    ->placeholder('Program Seçin')
                    ->options(Program::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, $set, $get) {
                        $this->program_id = $state ? (int) $state : null;
                        $this->isPreviewLoaded = false;
                        $this->previewItems = [];
                        if ($this->program_id) {
                            $program = Program::find($this->program_id);

                            // Smart season and year suggestion
                            $maxSeason = Episode::where('program_id', $this->program_id)
                                ->whereNotNull('season_number')
                                ->max('season_number');

                            if ($maxSeason !== null) {
                                $suggestedSeason = (int) $maxSeason + 1;
                                $lastSeasonYear = Episode::where('program_id', $this->program_id)
                                    ->where('season_number', $maxSeason)
                                    ->whereNotNull('season_year')
                                    ->value('season_year');
                                $suggestedYear = static::suggestNextSeasonYear($lastSeasonYear);
                            } else {
                                $suggestedSeason = 1;
                                $suggestedYear = null;
                            }

                            $set('season_number', $suggestedSeason);
                            $set('season_year', $suggestedYear);
                            $set('series_name', null);
                            $this->season_number = $suggestedSeason;
                            $this->season_year = $suggestedYear;
                            $this->series_name = null;

                            // Resolve season-level playlist URL if exists, fallback to program-level
                            $seasonPlaylistUrl = ProgramSeason::resolvePlaylistUrl($program, $suggestedSeason, $suggestedYear);
                            if (filled($seasonPlaylistUrl) && blank($get('playlist_url'))) {
                                $set('playlist_url', $seasonPlaylistUrl);
                                $this->playlist_url = $seasonPlaylistUrl;
                            }

                            $this->calculateStartEpisodeNumber($get, $set, $suggestedSeason, $suggestedYear, null);
                        } else {
                            $set('season_number', 1);
                            $set('season_year', null);
                            $set('series_name', null);
                            $set('start_episode_number', 1);
                        }
                    }),

                TextInput::make('season_number')
                    ->label('Sezon Numarası')
                    ->numeric()
                    ->default(1)
                    ->live()
                    ->afterStateUpdated(function ($state, $set, $get) {
                        $this->season_number = (int) ($state ?? 1);
                        if ($this->program_id) {
                            $program = Program::find($this->program_id);
                            $seasonPlaylistUrl = ProgramSeason::resolvePlaylistUrl($program, $this->season_number, $this->season_year);
                            if (filled($seasonPlaylistUrl) && blank($get('series_name'))) {
                                $set('playlist_url', $seasonPlaylistUrl);
                                $this->playlist_url = $seasonPlaylistUrl;
                            }
                        }
                        $this->calculateStartEpisodeNumber($get, $set);
                    }),

                TextInput::make('season_year')
                    ->label('Sezon Yılı')
                    ->placeholder('Örn: 2017 veya 2022-2023')
                    ->helperText('Opsiyonel (Örn: 2017 veya 2022-2023)')
                    ->regex('/^\d{4}(-\d{4})?$/')
                    ->validationMessages([
                        'regex' => 'Sezon yılı YYYY (örn: 2017) veya YYYY-YYYY (örn: 2022-2023) formatında olmalıdır.',
                    ])
                    ->live()
                    ->afterStateUpdated(function ($state, $set, $get) {
                        $this->season_year = filled($state) ? trim((string) $state) : null;
                        $this->calculateStartEpisodeNumber($get, $set);
                    }),

                TextInput::make('series_name')
                    ->label('Alt Seri')
                    ->placeholder('Örn: Lemalar veya Sözler')
                    ->helperText('Opsiyonel. Program içindeki alt seri başlığı (örn: Lemalar, Sözler).')
                    ->datalist(function ($get) {
                        $pId = $get('program_id') ?? $this->program_id;
                        if (! $pId) {
                            return [];
                        }
                        return ProgramSeries::where('program_id', $pId)->pluck('name', 'name')->toArray();
                    })
                    ->live()
                    ->afterStateUpdated(function ($state, $set, $get) {
                        $this->series_name = filled($state) ? trim((string) $state) : null;
                        if ($this->program_id && filled($this->series_name)) {
                            $series = ProgramSeries::findSeries((int) $this->program_id, null, $this->series_name);
                            if ($series && filled($series->youtube_playlist_url)) {
                                $set('playlist_url', $series->youtube_playlist_url);
                                $this->playlist_url = $series->youtube_playlist_url;
                            }
                        }
                        $this->calculateStartEpisodeNumber($get, $set);
                    }),

                TextInput::make('playlist_url')
                    ->label('YouTube Playlist URL *')
                    ->placeholder('https://www.youtube.com/playlist?list=...')
                    ->required()
                    ->url(),

                TextInput::make('start_episode_number')
                    ->label('Başlangıç Bölüm Numarası')
                    ->numeric()
                    ->helperText('Varsayılan: Seçilen program, sezon veya serinin (Max Bölüm No + 1)'),

                Checkbox::make('strip_program_name')
                    ->label('Program adını başlıktan kaldır')
                    ->helperText('Örn: "Bab-ı Reyyan | 12. Bölüm" -> "12. Bölüm"')
                    ->default(false),
            ])
            ->statePath('data');
    }

    public static function suggestNextSeasonYear(?string $lastSeasonYear): ?string
    {
        if (blank($lastSeasonYear)) {
            return null;
        }

        $lastSeasonYear = trim($lastSeasonYear);

        if (preg_match('/^(\d{4})-(\d{4})$/', $lastSeasonYear, $matches)) {
            $start = (int) $matches[1] + 1;
            $end = (int) $matches[2] + 1;
            return "{$start}-{$end}";
        }

        if (preg_match('/^(\d{4})$/', $lastSeasonYear, $matches)) {
            return (string) ((int) $matches[1] + 1);
        }

        return null;
    }

    public function calculateStartEpisodeNumber(
        $get,
        $set,
        ?int $explicitSeason = null,
        ?string $explicitYear = null,
        ?string $explicitSeries = null
    ): void {
        $pId = $get('program_id') ?? $this->program_id;
        $sNum = $explicitSeason !== null ? $explicitSeason : (int) ($get('season_number') ?? $this->season_number ?? 1);
        $sYear = $explicitYear !== null ? $explicitYear : (filled($get('season_year')) ? trim((string) $get('season_year')) : ($this->season_year ?? null));
        $sSeries = $explicitSeries !== null ? $explicitSeries : (filled($get('series_name')) ? trim((string) $get('series_name')) : ($this->series_name ?? null));

        if ($pId) {
            $query = Episode::where('program_id', (int) $pId);

            if (filled($sSeries)) {
                $seriesModel = ProgramSeries::findSeries((int) $pId, null, (string) $sSeries);
                if ($seriesModel) {
                    $query->where('program_series_id', $seriesModel->id);
                } else {
                    $set('start_episode_number', 1);
                    $this->start_episode_number = 1;
                    return;
                }
            } else {
                $query->where('season_number', $sNum);
                if ($sYear) {
                    $query->where('season_year', $sYear);
                }
            }

            $maxEp = $query->max('episode_number');
            $nextNum = $maxEp !== null ? ($maxEp + 1) : 1;
            $set('start_episode_number', $nextNum);
            $this->start_episode_number = $nextNum;
        } else {
            $set('start_episode_number', 1);
            $this->start_episode_number = 1;
        }
    }

    public function mount(): void
    {
        $requestedProgramId = request()->query('program_id');
        if (filled($requestedProgramId) && Program::where('id', $requestedProgramId)->exists()) {
            $this->program_id = (int) $requestedProgramId;
            $program = Program::find($this->program_id);
            if ($program && filled($program->youtube_playlist_url)) {
                $this->playlist_url = $program->youtube_playlist_url;
            }

            $requestedSeason = request()->query('season_number');
            $requestedYear = request()->query('season_year');
            $requestedSeriesId = request()->query('program_series_id', request()->query('series_id'));
            $requestedSeriesName = request()->query('series_name');

            if (filled($requestedSeriesId)) {
                $seriesRecord = ProgramSeries::where('program_id', $this->program_id)->find($requestedSeriesId);
                if ($seriesRecord) {
                    $this->series_name = $seriesRecord->name;
                    if (filled($seriesRecord->youtube_playlist_url)) {
                        $this->playlist_url = $seriesRecord->youtube_playlist_url;
                    }
                    if ($seriesRecord->programSeason) {
                        $this->season_number = (int) ($seriesRecord->programSeason->season_number ?? 1);
                        $this->season_year = $seriesRecord->programSeason->season_year;
                    }
                }
            } elseif (filled($requestedSeriesName)) {
                $this->series_name = trim((string) $requestedSeriesName);
                $seriesRecord = ProgramSeries::findSeries($this->program_id, null, $this->series_name);
                if ($seriesRecord && filled($seriesRecord->youtube_playlist_url)) {
                    $this->playlist_url = $seriesRecord->youtube_playlist_url;
                }
            }

            if (filled($requestedSeason) && $requestedSeason !== 'none') {
                $this->season_number = (int) $requestedSeason;
                if (filled($requestedYear) && $requestedYear !== 'none') {
                    $this->season_year = trim((string) $requestedYear);
                } else {
                    $this->season_year = null;
                }
            } elseif (blank($this->series_name)) {
                $maxSeason = Episode::where('program_id', $this->program_id)
                    ->whereNotNull('season_number')
                    ->max('season_number');

                if ($maxSeason !== null) {
                    $this->season_number = (int) $maxSeason + 1;
                    $lastSeasonYear = Episode::where('program_id', $this->program_id)
                        ->where('season_number', $maxSeason)
                        ->whereNotNull('season_year')
                        ->value('season_year');
                    $this->season_year = static::suggestNextSeasonYear($lastSeasonYear);
                } else {
                    $this->season_number = 1;
                    $this->season_year = null;
                }
            }

            $query = Episode::where('program_id', $this->program_id);
            if (filled($this->series_name)) {
                $seriesModel = ProgramSeries::findSeries($this->program_id, null, $this->series_name);
                if ($seriesModel) {
                    $query->where('program_series_id', $seriesModel->id);
                }
            } else {
                $query->where('season_number', $this->season_number);
                if ($this->season_year) {
                    $query->where('season_year', $this->season_year);
                }
            }

            $maxEp = $query->max('episode_number');
            $this->start_episode_number = $maxEp !== null ? ($maxEp + 1) : 1;

            if (blank($this->playlist_url)) {
                $seasonPlaylistUrl = ProgramSeason::resolvePlaylistUrl($program, $this->season_number, $this->season_year);
                if (filled($seasonPlaylistUrl)) {
                    $this->playlist_url = $seasonPlaylistUrl;
                }
            }
        }

        $this->form->fill([
            'program_id' => $this->program_id,
            'playlist_url' => $this->playlist_url,
            'season_number' => $this->season_number,
            'season_year' => $this->season_year,
            'series_name' => $this->series_name,
            'start_episode_number' => $this->start_episode_number ?? 1,
            'strip_program_name' => $this->strip_program_name,
        ]);
    }

    public function fetchPreview(): void
    {
        $formData = array_merge($this->data ?? [], $this->form->getRawState() ?? []);

        $this->program_id = ! empty($formData['program_id']) ? (int) $formData['program_id'] : ($this->program_id ?? null);
        $this->playlist_url = ! empty($formData['playlist_url']) ? $formData['playlist_url'] : ($this->playlist_url ?? '');

        $this->season_number = (int) ($formData['season_number'] ?? 1);
        $this->season_year = filled($formData['season_year']) ? trim((string) $formData['season_year']) : null;
        $this->series_name = filled($formData['series_name']) ? trim((string) $formData['series_name']) : null;
        $this->start_episode_number = (int) ($formData['start_episode_number'] ?? 1);
        $this->strip_program_name = (bool) ($formData['strip_program_name'] ?? false);
        $this->status = 'published';
        $this->show_on_public = true;
        $this->is_active = true;

        if (! $this->program_id || ! Program::where('id', $this->program_id)->exists()) {
            Notification::make()
                ->title('Lütfen geçerli bir program seçin.')
                ->danger()
                ->send();

            return;
        }

        if (blank($this->playlist_url)) {
            Notification::make()
                ->title('Lütfen geçerli bir YouTube playlist URL\'si girin.')
                ->danger()
                ->send();

            return;
        }

        try {
            $service = app(YouTubePlaylistImportService::class);
            $result = $service->fetchPlaylistItems($this->playlist_url);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('YouTube Playlist Çekilemedi')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $rawItems = $result['items'] ?? [];
        if (empty($rawItems)) {
            Notification::make()
                ->title('Playlist içinde aktarılabilecek geçerli bir video bulunamadı.')
                ->warning()
                ->send();

            return;
        }

        // 1. Hedef grup bazlı mevcut videoları sorgula
        $targetEpisodesQuery = Episode::where('program_id', $this->program_id);
        if (filled($this->series_name)) {
            $targetSeries = ProgramSeries::findSeries($this->program_id, null, $this->series_name);
            if ($targetSeries) {
                $targetEpisodesQuery->where('program_series_id', $targetSeries->id);
            } else {
                $targetEpisodesQuery->whereRaw('1 = 0');
            }
        } else {
            $targetEpisodesQuery->where('season_number', $this->season_number);
            if (filled($this->season_year)) {
                $targetEpisodesQuery->where('season_year', $this->season_year);
            } else {
                $targetEpisodesQuery->whereNull('season_year');
            }
            $targetEpisodesQuery->whereNull('program_series_id');
        }

        $targetUrls = $targetEpisodesQuery->pluck('youtube_url')->filter()->toArray();
        $targetVideoIds = [];
        foreach ($targetUrls as $url) {
            $vId = Youtube::extractVideoId($url);
            if ($vId) {
                $targetVideoIds[$vId] = true;
            }
        }

        // 2. Diğer tüm seriler/sezonlardaki videoları sorgula
        $allUrls = Episode::pluck('youtube_url')->filter()->toArray();
        $allVideoIds = [];
        foreach ($allUrls as $url) {
            $vId = Youtube::extractVideoId($url);
            if ($vId) {
                $allVideoIds[$vId] = true;
            }
        }

        $program = Program::find($this->program_id);
        $programName = $program ? $program->name : '';

        $previewItems = [];
        $newCount = 0;
        $otherSeriesCount = 0;
        $targetExistingCount = 0;

        foreach ($rawItems as $item) {
            $videoId = $item['video_id'];
            $existsInTarget = isset($targetVideoIds[$videoId]);
            $existsInOther = ! $existsInTarget && isset($allVideoIds[$videoId]);

            $displayTitle = $item['title'];
            if ($this->strip_program_name && filled($programName)) {
                $pattern = '/^' . preg_quote($programName, '/') . '\s*[\|\-–:]?\s*/iu';
                $cleaned = preg_replace($pattern, '', $displayTitle);
                if (filled(trim($cleaned))) {
                    $displayTitle = trim($cleaned);
                }
            }

            if ($existsInTarget) {
                $targetExistingCount++;
                $statusType = 'target_existing';
                $statusLabel = 'Hedefte Mevcut';
            } elseif ($existsInOther) {
                $otherSeriesCount++;
                $statusType = 'other_series';
                $statusLabel = 'Başka Seride Mevcut';
            } else {
                $newCount++;
                $statusType = 'new';
                $statusLabel = 'Yeni';
            }

            $previewItems[] = [
                'video_id' => $videoId,
                'raw_title' => $item['title'],
                'processed_title' => $displayTitle,
                'description' => $item['description'],
                'thumbnail_url' => $item['thumbnail_url'],
                'published_at' => $item['published_at'],
                'published_at_formatted' => $item['published_at'] ? date('d.m.Y', strtotime($item['published_at'])) : '-',
                'position' => $item['position'],
                'canonical_url' => $item['canonical_url'],
                'exists_in_target' => $existsInTarget,
                'exists_in_other' => $existsInOther,
                'is_duplicate' => $existsInTarget,
                'status_type' => $statusType,
                'status_label' => $statusLabel,
            ];
        }

        $this->previewItems = $previewItems;
        $this->totalItemsCount = count($previewItems);
        $this->newItemsCount = $newCount;
        $this->otherSeriesItemsCount = $otherSeriesCount;
        $this->targetExistingItemsCount = $targetExistingCount;
        $this->existingItemsCount = $targetExistingCount + $otherSeriesCount;
        $this->willImportCount = $newCount + $otherSeriesCount;
        $this->isPreviewLoaded = true;
        $this->isImported = false;

        $seriesText = filled($this->series_name) ? " (Alt Seri: {$this->series_name})" : '';
        $msg = "Playlist kontrol edildi{$seriesText}: {$this->willImportCount} aktarılacak video";
        if ($otherSeriesCount > 0) {
            $msg .= " ({$otherSeriesCount} tanesi başka serilerde mevcut)";
        }
        if ($targetExistingCount > 0) {
            $msg .= ", {$targetExistingCount} hedefte zaten mevcut";
        }
        $msg .= '.';

        Notification::make()
            ->title($msg)
            ->info()
            ->send();
    }

    public function importEpisodes(): void
    {
        if (! $this->isPreviewLoaded || empty($this->previewItems)) {
            Notification::make()
                ->title('Lütfen önce playlist kontrolünü gerçekleştirin.')
                ->warning()
                ->send();

            return;
        }

        if ($this->willImportCount === 0) {
            Notification::make()
                ->title('Aktarılacak yeni video bulunmamaktadır. Tüm videolar hedef grupta zaten mevcut.')
                ->warning()
                ->send();

            return;
        }

        $currentEpNumber = max(1, (int) $this->start_episode_number);
        $importedCount = 0;
        $skippedCount = 0;

        try {
            DB::transaction(function () use (&$currentEpNumber, &$importedCount, &$skippedCount) {
                $seasonRecord = null;
                if ($this->program_id) {
                    $seasonRecord = ProgramSeason::firstOrCreate([
                        'program_id' => $this->program_id,
                        'season_number' => $this->season_number,
                        'season_year' => $this->season_year,
                    ]);
                }

                $seriesRecord = null;
                if (filled($this->series_name) && $this->program_id) {
                    $seriesRecord = ProgramSeries::findOrCreateSeries(
                        $this->program_id,
                        $seasonRecord?->id,
                        $this->series_name,
                        $this->playlist_url
                    );
                    $seriesRecord->update([
                        'youtube_playlist_url' => $this->playlist_url,
                        'last_youtube_sync_at' => now(),
                    ]);
                } elseif (filled($this->playlist_url) && $seasonRecord) {
                    $seasonRecord->update([
                        'youtube_playlist_url' => $this->playlist_url,
                        'last_youtube_sync_at' => now(),
                    ]);
                }

                foreach ($this->previewItems as $item) {
                    if (! empty($item['exists_in_target'])) {
                        $skippedCount++;
                        continue;
                    }

                    $canonicalUrl = $item['canonical_url'];
                    $vId = $item['video_id'];

                    // Scoped target duplicate check
                    $targetDuplicateQuery = Episode::where('program_id', $this->program_id);
                    if ($seriesRecord) {
                        $targetDuplicateQuery->where('program_series_id', $seriesRecord->id);
                    } else {
                        $targetDuplicateQuery->where('season_number', $this->season_number);
                        if (filled($this->season_year)) {
                            $targetDuplicateQuery->where('season_year', $this->season_year);
                        } else {
                            $targetDuplicateQuery->whereNull('season_year');
                        }
                        $targetDuplicateQuery->whereNull('program_series_id');
                    }

                    $existsInTarget = $targetDuplicateQuery->where('youtube_url', 'like', "%{$vId}%")->exists();
                    if ($existsInTarget) {
                        $skippedCount++;
                        continue;
                    }

                    Episode::create([
                        'program_id' => $this->program_id,
                        'program_series_id' => $seriesRecord?->id,
                        'episode_number' => $currentEpNumber++,
                        'season_number' => $this->season_number,
                        'season_year' => $this->season_year,
                        'title' => $item['processed_title'],
                        'description' => $item['description'],
                        'thumbnail' => $item['thumbnail_url'],
                        'video_source' => 'youtube',
                        'youtube_url' => $canonicalUrl,
                        'status' => 'published',
                        'show_on_public' => true,
                        'is_active' => true,
                        'aired_at' => $item['published_at'],
                        'sort_order' => $item['position'] ?? 0,
                    ]);

                    $importedCount++;
                }
            });
        } catch (\Throwable $e) {
            Log::error("Bölüm toplu aktarım hatası: {$e->getMessage()}", ['exception' => $e]);
            Notification::make()
                ->title("İçeri aktarım sırasında veritabanı hatası oluştu: {$e->getMessage()}")
                ->danger()
                ->send();

            return;
        }

        $this->importedCount = $importedCount;
        $this->skippedCount = $skippedCount;
        $this->isImported = true;
        $this->isPreviewLoaded = false;

        $seriesMsg = filled($this->series_name) ? " [{$this->series_name}]" : '';
        Notification::make()
            ->title("{$importedCount} bölüm{$seriesMsg} başarıyla oluşturuldu." . ($skippedCount > 0 ? " {$skippedCount} hedefte mevcut video atlandı." : ''))
            ->success()
            ->send();
    }
}
