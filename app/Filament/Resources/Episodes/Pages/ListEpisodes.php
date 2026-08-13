<?php

namespace App\Filament\Resources\Episodes\Pages;

use App\Filament\Resources\Episodes\EpisodeResource;
use App\Models\Episode;
use App\Models\Program;
use App\Models\ProgramSeason;
use App\Models\ProgramSeries;
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

    #[Url]
    public ?string $program_series_id = null;

    #[Url]
    public ?string $series_id = null;

    public function getTitle(): string
    {
        $programId = $this->program_id ?? request()->query('program_id');
        $season = $this->season_number ?? request()->query('season_number');
        $year = $this->season_year ?? request()->query('season_year');
        $seriesId = $this->program_series_id ?? $this->series_id ?? request()->query('program_series_id', request()->query('series_id'));

        if (filled($programId)) {
            $program = Program::find($programId);
            $programName = $program ? $program->name : 'Program';
            $seasonLabel = ($season === 'none' || blank($season)) ? 'Sezonsuz' : "Sezon {$season}";
            if (filled($year) && $year !== 'none') {
                $seasonLabel .= " ({$year})";
            }

            if (filled($seriesId) && $seriesId !== 'none') {
                $seriesRecord = ProgramSeries::find($seriesId);
                if ($seriesRecord) {
                    return "{$programName} — {$seasonLabel} · {$seriesRecord->name}";
                }
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
        $seriesId = $this->program_series_id ?? $this->series_id ?? request()->query('program_series_id', request()->query('series_id'));

        if (filled($programId)) {
            $query = Episode::where('program_id', $programId);
            if ($season === 'none' || blank($season)) {
                $query->whereNull('season_number');
            } elseif (filled($season) && $season !== 'all') {
                $query->where('season_number', $season);
            }

            if (filled($year) && $year !== 'none' && $year !== 'all') {
                $query->where('season_year', (string) $year);
            } elseif ($year === 'none') {
                $query->whereNull('season_year');
            }

            if (filled($seriesId) && $seriesId !== 'none' && $seriesId !== 'all') {
                $query->where('program_series_id', (int) $seriesId);
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
        $seriesId = $this->program_series_id ?? $this->series_id ?? request()->query('program_series_id', request()->query('series_id'));

        if (filled($programId)) {
            $program = Program::find($programId);
            $seasonValue = (filled($season) && $season !== 'none') ? $season : '';
            $yearValue = (filled($year) && $year !== 'none') ? $year : '';
            $seriesValue = (filled($seriesId) && $seriesId !== 'none') ? $seriesId : '';

            $seasonNum = ($season === 'none' || blank($season)) ? null : (int) $season;
            $seasonYr = ($year === 'none' || blank($year)) ? null : (string) $year;

            $seriesRecord = (filled($seriesValue) && $program)
                ? ProgramSeries::where('program_id', $program->id)->find($seriesValue)
                : null;

            $params = "?program_id={$programId}";
            if (filled($seasonValue)) {
                $params .= "&season_number={$seasonValue}";
            }
            if (filled($yearValue)) {
                $params .= "&season_year={$yearValue}";
            }
            if ($seriesRecord) {
                $params .= "&program_series_id={$seriesRecord->id}&series_name=" . urlencode($seriesRecord->name);
            }

            $createUrl = static::getResource()::getUrl('create') . $params;
            $importUrl = static::getResource()::getUrl('youtube-import') . $params;

            $playlistUrl = null;
            if ($seriesRecord) {
                $playlistUrl = $seriesRecord->youtube_playlist_url;
            } else {
                $seasonRecord = $program ? ProgramSeason::findSeason($program->id, $seasonNum, $seasonYr) : null;
                $playlistUrl = $seasonRecord?->youtube_playlist_url ?? ProgramSeason::resolvePlaylistUrl($program, $seasonNum, $seasonYr);
            }

            $hasPlaylistUrl = filled($playlistUrl);

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
                    ->action(function () use ($program, $seriesRecord, $seasonNum, $seasonYr) {
                        $service = app(YouTubePlaylistSyncService::class);

                        if ($seriesRecord) {
                            $result = $service->syncSeries($seriesRecord, false, true);
                        } else {
                            $seasonRecord = $program ? ProgramSeason::findSeason($program->id, $seasonNum, $seasonYr) : null;
                            if ($seasonRecord) {
                                $result = $service->syncSeason($seasonRecord, false, true);
                            } else {
                                $result = $service->syncProgramPlaylist($program, false, true, $seasonNum, $seasonYr);
                            }
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

                        $label = $seriesRecord ? " ({$seriesRecord->name})" : '';
                        Notification::make()
                            ->title("YouTube Senkronizasyonu Tamamlandı{$label}")
                            ->body($summary)
                            ->success()
                            ->send();
                    });
            } else {
                $targetLabel = $seriesRecord
                    ? ($seriesRecord->name . ' Serisi')
                    : (($seasonNum ? "Sezon {$seasonNum}" : "Sezonsuz") . ($seasonYr ? " ({$seasonYr})" : ''));

                $actions[] = Action::make('attach_playlist_url')
                    ->label('Playlist URL Bağla')
                    ->icon('heroicon-o-link')
                    ->color('gray')
                    ->modalHeading(($program ? $program->name : 'Program') . " — {$targetLabel} İçin YouTube Playlist Bağla")
                    ->modalDescription('Bu kayıt için YouTube Playlist URL\'si tanımlanmamış. URL bağlayarak tek tıkla playlisti açabilir ve senkronize edebilirsiniz.')
                    ->modalSubmitActionLabel('Kaydet ve Bağla')
                    ->form([
                        TextInput::make('youtube_playlist_url')
                            ->label('YouTube Playlist URL')
                            ->placeholder('https://www.youtube.com/playlist?list=...')
                            ->helperText('Geçerli bir YouTube playlist URL\'si girin.')
                            ->url()
                            ->required(),
                    ])
                    ->action(function (array $data) use ($program, $seriesRecord, $seasonNum, $seasonYr) {
                        if (! $program) {
                            return;
                        }
                        $url = trim($data['youtube_playlist_url']);

                        if ($seriesRecord) {
                            $seriesRecord->update([
                                'youtube_playlist_url' => $url,
                            ]);
                        } else {
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
                        }

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
                    ->tooltip('Bir YouTube Playlist URL tanımlanmamış. Önce Playlist URL Bağlayın.');
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
