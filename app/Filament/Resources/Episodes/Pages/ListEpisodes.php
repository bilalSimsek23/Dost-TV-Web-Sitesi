<?php

namespace App\Filament\Resources\Episodes\Pages;

use App\Filament\Resources\Episodes\EpisodeResource;
use App\Models\Episode;
use App\Models\Program;
use App\Services\YouTube\YouTubePlaylistSyncService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
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

    public function getTitle(): string
    {
        $programId = $this->program_id ?? request()->query('program_id');
        $season = $this->season_number ?? request()->query('season_number');

        if (filled($programId)) {
            $program = Program::find($programId);
            $programName = $program ? $program->name : 'Program';
            $seasonLabel = ($season === 'none' || blank($season)) ? 'Sezonsuz' : "Sezon {$season}";

            return "{$programName} — {$seasonLabel}";
        }

        return 'Bölümler';
    }

    public function getSubheading(): ?string
    {
        $programId = $this->program_id ?? request()->query('program_id');
        $season = $this->season_number ?? request()->query('season_number');

        if (filled($programId)) {
            $query = Episode::where('program_id', $programId);
            if ($season === 'none' || blank($season)) {
                $query->whereNull('season_number');
            } else {
                $query->where('season_number', $season);
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

        if (filled($programId)) {
            $program = Program::find($programId);
            $seasonValue = (filled($season) && $season !== 'none') ? $season : '';
            $createUrl = static::getResource()::getUrl('create') . "?program_id={$programId}"
                . (filled($seasonValue) ? "&season_number={$seasonValue}" : '');
            $importUrl = static::getResource()::getUrl('youtube-import') . "?program_id={$programId}"
                . (filled($seasonValue) ? "&season_number={$seasonValue}" : '');

            $actions = [
                Action::make('back_to_main')
                    ->label('← Tüm Program & Sezonlara Dön')
                    ->color('gray')
                    ->url(static::getResource()::getUrl('index')),
            ];

            if ($program && filled($program->youtube_playlist_url)) {
                $actions[] = Action::make('open_playlist_url')
                    ->label("YouTube Playlist'i Aç ↗")
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url($program->youtube_playlist_url)
                    ->openUrlInNewTab();

                $actions[] = Action::make('sync_youtube_playlist')
                    ->label('YouTube ile Senkronize Et')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function () use ($program) {
                        $service = app(YouTubePlaylistSyncService::class);
                        $result = $service->syncProgramPlaylist($program, false, true);

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
            CreateAction::make()
                ->label('Yeni Bölüm'),
        ];
    }
}
