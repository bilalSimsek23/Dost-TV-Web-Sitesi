<?php

namespace App\Filament\Resources\Episodes\Pages;

use App\Filament\Resources\Episodes\EpisodeResource;
use App\Models\Episode;
use App\Models\Program;
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
                            if ($program && filled($program->youtube_playlist_url) && blank($get('playlist_url'))) {
                                $set('playlist_url', $program->youtube_playlist_url);
                                $this->playlist_url = $program->youtube_playlist_url;
                            }
                            $seasonNum = (int) ($get('season_number') ?? 1);
                            $maxEp = Episode::where('program_id', $this->program_id)
                                ->where('season_number', $seasonNum)
                                ->max('episode_number')
                                ?? Episode::where('program_id', $this->program_id)->max('episode_number')
                                ?? 0;
                            $set('start_episode_number', $maxEp + 1);
                        } else {
                            $set('start_episode_number', 1);
                        }
                    }),

                TextInput::make('playlist_url')
                    ->label('YouTube Playlist URL *')
                    ->placeholder('https://www.youtube.com/playlist?list=...')
                    ->required()
                    ->url(),

                TextInput::make('season_number')
                    ->label('Sezon Numarası')
                    ->numeric()
                    ->default(1)
                    ->live()
                    ->afterStateUpdated(function ($state, $set, $get) {
                        $this->season_number = (int) ($state ?? 1);
                        $pId = $get('program_id');
                        if ($pId) {
                            $maxEp = Episode::where('program_id', (int) $pId)
                                ->where('season_number', $this->season_number)
                                ->max('episode_number')
                                ?? Episode::where('program_id', (int) $pId)->max('episode_number')
                                ?? 0;
                            $set('start_episode_number', $maxEp + 1);
                        }
                    }),

                TextInput::make('start_episode_number')
                    ->label('Başlangıç Bölüm Numarası')
                    ->numeric()
                    ->helperText('Varsayılan: Seçilen program ve sezonun (Max Bölüm No + 1)'),

                Checkbox::make('strip_program_name')
                    ->label('Program adını başlıktan kaldır')
                    ->helperText('Örn: "Bab-ı Reyyan | 12. Bölüm" -> "12. Bölüm"')
                    ->default(false),
            ])
            ->statePath('data');
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
            if (filled($requestedSeason) && $requestedSeason !== 'none') {
                $this->season_number = (int) $requestedSeason;
                $maxEp = Episode::where('program_id', $this->program_id)
                    ->where('season_number', $this->season_number)
                    ->max('episode_number')
                    ?? Episode::where('program_id', $this->program_id)->max('episode_number')
                    ?? 0;
                $this->start_episode_number = $maxEp + 1;
            } else {
                $maxEp = Episode::where('program_id', $this->program_id)->max('episode_number') ?? 0;
                $this->start_episode_number = $maxEp + 1;
            }
        }

        $this->form->fill([
            'program_id' => $this->program_id,
            'playlist_url' => $this->playlist_url,
            'season_number' => $this->season_number,
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

        $service = app(YouTubePlaylistImportService::class);

        try {
            $result = $service->fetchPlaylistItems($this->playlist_url);
        } catch (\InvalidArgumentException $e) {
            Notification::make()
                ->title($e->getMessage())
                ->warning()
                ->send();

            return;
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();

            return;
        } catch (\Throwable $e) {
            Log::error("YouTube Playlist Önizleme Hatası: {$e->getMessage()}", ['exception' => $e]);
            Notification::make()
                ->title('YouTube playlist verisi çekilirken beklenmeyen bir hata oluştu.')
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

        // Mevcut YouTube videolarını DB'den sorgula (Video ID bazlı)
        $existingUrls = Episode::pluck('youtube_url')->filter()->toArray();
        $existingVideoIds = [];
        foreach ($existingUrls as $url) {
            $vId = Youtube::extractVideoId($url);
            if ($vId) {
                $existingVideoIds[$vId] = true;
            }
        }

        $program = Program::find($this->program_id);
        $programName = $program ? $program->name : '';

        $previewItems = [];
        $newCount = 0;
        $existingCount = 0;

        foreach ($rawItems as $item) {
            $videoId = $item['video_id'];
            $isDuplicate = isset($existingVideoIds[$videoId]);

            $displayTitle = $item['title'];
            if ($this->strip_program_name && filled($programName)) {
                $pattern = '/^' . preg_quote($programName, '/') . '\s*[\|\-–:]?\s*/iu';
                $cleaned = preg_replace($pattern, '', $displayTitle);
                if (filled(trim($cleaned))) {
                    $displayTitle = trim($cleaned);
                }
            }

            if ($isDuplicate) {
                $existingCount++;
            } else {
                $newCount++;
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
                'is_duplicate' => $isDuplicate,
                'status_label' => $isDuplicate ? 'Mevcut' : 'Yeni',
            ];
        }

        $this->previewItems = $previewItems;
        $this->totalItemsCount = count($previewItems);
        $this->newItemsCount = $newCount;
        $this->existingItemsCount = $existingCount;
        $this->isPreviewLoaded = true;
        $this->isImported = false;

        Notification::make()
            ->title("Playlist kontrol edildi: {$newCount} yeni video, {$existingCount} mevcut video.")
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

        if ($this->newItemsCount === 0) {
            Notification::make()
                ->title('Aktarılacak yeni video bulunmamaktadır. Tüm videolar zaten eklenmiş.')
                ->warning()
                ->send();

            return;
        }

        $currentEpNumber = max(1, (int) $this->start_episode_number);
        $importedCount = 0;
        $skippedCount = 0;

        try {
            DB::transaction(function () use (&$currentEpNumber, &$importedCount, &$skippedCount) {
                foreach ($this->previewItems as $item) {
                    if ($item['is_duplicate']) {
                        $skippedCount++;
                        continue;
                    }

                    // Dublikasyon çift kontrolü
                    $canonicalUrl = $item['canonical_url'];
                    $vId = $item['video_id'];

                    $existsInDb = Episode::where('youtube_url', 'like', "%{$vId}%")->exists();
                    if ($existsInDb) {
                        $skippedCount++;
                        continue;
                    }

                    Episode::create([
                        'program_id' => $this->program_id,
                        'episode_number' => $currentEpNumber++,
                        'season_number' => $this->season_number,
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

        Notification::make()
            ->title("{$importedCount} bölüm başarıyla oluşturuldu. {$skippedCount} mevcut video atlandı.")
            ->success()
            ->send();
    }
}
