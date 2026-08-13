<?php

namespace App\Filament\Resources\Episodes\Pages;

use App\Filament\Resources\Episodes\EpisodeResource;
use App\Models\Episode;
use App\Models\Program;
use App\Models\ProgramSeason;
use App\Services\YouTube\YouTubePlaylistSyncService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\Url;

class ListEpisodes extends ListRecords
{
    protected static string $resource = EpisodeResource::class;

    #[Url]
    public ?string $program_id = null;

    #[Url]
    public ?string $season_number = null;

    #[Url]
    public ?string $season_year = null;

    public function getTitle(): string
    {
        $programId = $this->program_id ?? request()->query('program_id');
        $season = $this->season_number ?? request()->query('season_number');
        $year = $this->season_year ?? request()->query('season_year');

        if (filled($programId)) {
            $program = Program::find($programId);
            $programName = $program ? $program->name : 'Program';
            $seasonLabel = ($season === 'none' || blank($season)) ? 'Sezonsuz' : "Sezon {$season}";
            if (filled($year) && $year !== 'none') {
                $seasonLabel .= " ({$year})";
            }

            return "{$programName} — {$seasonLabel}";
        }

        return 'Bölümler';
    }

    public function getSubheading(): ?string
    {
        $programId = $this->program_id ?? request()->query('program_id');
        $season = $this->season_number ?? request()->query('season_number');
        $year = $this->season_year ?? request()->query('season_year');

        if (filled($programId)) {
            $query = Episode::where('program_id', $programId);
            if ($season === 'none' || blank($season)) {
                $query->whereNull('season_number');
            } else {
                $query->where('season_number', $season);
            }

            if (filled($year) && $year !== 'none') {
                $query->where('season_year', (string) $year);
            } elseif ($year === 'none') {
                $query->whereNull('season_year');
            }

            $count = $query->count();

            return "{$count} Bölüm";
        }

        return null;
    }

    protected function getHeaderActions(): array
    {
        $programId = $this->program_id ?? request()->query('program_id');
        $season = $this->season_number ?? request()->query('season_number');
        $year = $this->season_year ?? request()->query('season_year');

        if (filled($programId)) {
            $program = Program::find($programId);
            $seasonValue = (filled($season) && $season !== 'none') ? $season : '';
            $yearValue = (filled($year) && $year !== 'none') ? $year : '';

            $seasonNum = ($season === 'none' || blank($season)) ? null : (int) $season;
            $seasonYr = ($year === 'none' || blank($year)) ? null : (string) $year;

            $params = "?program_id={$programId}";
            if (filled($seasonValue)) {
                $params .= "&season_number={$seasonValue}";
            }
            if (filled($yearValue)) {
                $params .= "&season_year={$yearValue}";
            }

            $createUrl = static::getResource()::getUrl('create') . $params;
            $importUrl = static::getResource()::getUrl('youtube-import') . $params;

            $seasonRecord = $program ? ProgramSeason::findSeason($program->id, $seasonNum, $seasonYr) : null;
            $playlistUrl = $seasonRecord?->youtube_playlist_url ?? ProgramSeason::resolvePlaylistUrl($program, $seasonNum, $seasonYr);
            $hasPlaylistUrl = filled($playlistUrl);

            // Check if this season has any youtube episodes
            $hasYoutubeEpisodes = false;
            if ($program) {
                $episodesQuery = Episode::where('program_id', $program->id);
                if ($seasonNum === null) {
                    $episodesQuery->whereNull('season_number');
                } else {
                    $episodesQuery->where('season_number', $seasonNum);
                }

                if ($seasonYr !== null) {
                    $episodesQuery->where('season_year', $seasonYr);
                } else {
                    $episodesQuery->whereNull('season_year');
                }

                $hasYoutubeEpisodes = $episodesQuery->where(function ($q) {
                    $q->where('video_source', 'youtube')->orWhereNotNull('youtube_url');
                })->exists();
            }

            $actions = [
                Action::make('back_to_main')
                    ->label('← Tüm Program & Sezonlara Dön')
                    ->color('gray')
                    ->url(static::getResource()::getUrl('index')),
            ];

            if ($hasPlaylistUrl) {
                $actions[] = Action::make('open_playlist_url')
                    ->label("YouTube Playlist'i Aç ↗")
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url($playlistUrl)
                    ->openUrlInNewTab();

                $actions[] = Action::make('sync_youtube_playlist')
                    ->label('YouTube ile Senkronize Et')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function () use ($program, $seasonRecord, $seasonNum, $seasonYr) {
                        $service = app(YouTubePlaylistSyncService::class);
                        if ($seasonRecord) {
                            $result = $service->syncSeason($seasonRecord, false, true);
                        } else {
                            $result = $service->syncProgramPlaylist($program, false, true, $seasonNum, $seasonYr);
                        }

                        if (! ($result['success'] ?? true)) {
                            Notification::make()
                                ->title('YouTube senkronizasyonu başarısız oldu.')
                                ->body($result['message'] ?? 'Bilinmeyen hata oluştu.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $total = $result['total_items'] ?? 0;
                        $new = $result['created_episodes'] ?? 0;
                        $updated = $result['updated_episodes'] ?? 0;
                        $unchanged = $result['unchanged_episodes'] ?? 0;
                        $errors = $result['errors'] ?? 0;

                        $summary = "Toplam video: {$total}\n"
                            . "Yeni eklenen: {$new}\n"
                            . "Güncellenen: {$updated}\n"
                            . "Değişmeyen: {$unchanged}\n"
                            . "Hatalı: {$errors}";

                        Notification::make()
                            ->title('YouTube Senkronizasyonu Tamamlandı')
                            ->body($summary)
                            ->success()
                            ->send();
                    });
            } else {
                $seasonLabel = ($seasonNum ? "Sezon {$seasonNum}" : "Sezonsuz") . ($seasonYr ? " ({$seasonYr})" : '');
                $actions[] = Action::make('attach_playlist_url')
                    ->label('Playlist URL Bağla')
                    ->icon('heroicon-o-link')
                    ->color('gray')
                    ->modalHeading(($program ? $program->name : 'Program') . " — {$seasonLabel} İçin YouTube Playlist Bağla")
                    ->modalDescription('Bu sezon için YouTube Playlist URL\'si tanımlanmamış. URL bağlayarak tek tıkla bu sezonun playlistini açabilir ve senkronize edebilirsiniz.')
                    ->modalSubmitActionLabel('Kaydet ve Bağla')
                    ->form([
                        TextInput::make('youtube_playlist_url')
                            ->label('YouTube Playlist URL')
                            ->placeholder('https://www.youtube.com/playlist?list=...')
                            ->helperText('Geçerli bir YouTube playlist URL\'si girin.')
                            ->url()
                            ->required(),
                    ])
                    ->action(function (array $data) use ($program, $seasonNum, $seasonYr) {
                        if (! $program) {
                            return;
                        }
                        $url = trim($data['youtube_playlist_url']);
                        ProgramSeason::updateOrCreate(
                            [
                                'program_id' => $program->id,
                                'season_number' => $seasonNum,
                                'season_year' => $seasonYr,
                            ],
                            [
                                'youtube_playlist_url' => $url,
                            ]
                        );

                        Notification::make()
                            ->title('YouTube Playlist URL başarıyla kaydedildi.')
                            ->success()
                            ->send();
                    });

                $actions[] = Action::make('sync_youtube_playlist')
                    ->label('YouTube ile Senkronize Et')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->disabled()
                    ->tooltip($hasYoutubeEpisodes
                        ? 'Bu sezon playlistten aktarılmış ancak playlist URL\'si kayıtlı değil. Önce Playlist URL Bağlayın.'
                        : 'Bu sezona ait bir YouTube Playlist URL tanımlanmamış. Önce Playlist URL Bağlayın.');
            }


            $actions[] = Action::make('youtube_import')
                ->label("YouTube Playlist İçe Aktar")
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->url($importUrl);

            $actions[] = Action::make('create_episode')
                ->label('Yeni Bölüm')
                ->color('success')
                ->icon('heroicon-o-plus')
                ->url($createUrl);

            return $actions;
        }

        return [
            Action::make('youtube_import')
                ->label('YouTube Playlist İçe Aktar')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->url(fn (): string => static::getResource()::getUrl('youtube-import')),

            CreateAction::make()
                ->label('Yeni Bölüm'),
        ];
    }
}

