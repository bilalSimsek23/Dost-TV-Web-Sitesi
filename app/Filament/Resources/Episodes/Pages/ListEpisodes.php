<?php

namespace App\Filament\Resources\Episodes\Pages;

use App\Filament\Resources\Episodes\EpisodeResource;
use App\Models\Episode;
use App\Models\Program;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\Url;

class ListEpisodes extends ListRecords
{
    protected static string $resource = EpisodeResource::class;

    #[Url]
    public ?string $program_id = null;

    #[Url]
    public ?string $season_number = null;

    public function getSubheading(): ?string
    {
        $programId = $this->program_id ?? request()->query('program_id');
        $season = $this->season_number ?? request()->query('season_number');

        if (filled($programId)) {
            $program = Program::find($programId);
            $programName = $program ? $program->name : 'Program';
            $seasonLabel = ($season === 'none' || blank($season)) ? 'Sezonsuz' : "Sezon {$season}";

            $query = Episode::where('program_id', $programId);
            if ($season === 'none' || blank($season)) {
                $query->whereNull('season_number');
            } else {
                $query->where('season_number', $season);
            }
            $count = $query->count();

            return "{$programName} — {$seasonLabel} ({$count} Bölüm)";
        }

        return 'Program ve Sezon bazında gruplandırılmış bölüm listesi';
    }

    protected function getHeaderActions(): array
    {
        $programId = $this->program_id ?? request()->query('program_id');
        $season = $this->season_number ?? request()->query('season_number');

        if (filled($programId)) {
            $seasonValue = (filled($season) && $season !== 'none') ? $season : '';
            $createUrl = static::getResource()::getUrl('create') . "?program_id={$programId}"
                . (filled($seasonValue) ? "&season_number={$seasonValue}" : '');
            $importUrl = static::getResource()::getUrl('youtube-import') . "?program_id={$programId}"
                . (filled($seasonValue) ? "&season_number={$seasonValue}" : '');

            return [
                Action::make('back_to_main')
                    ->label('← Tüm Program & Sezonlara Dön')
                    ->color('gray')
                    ->url(static::getResource()::getUrl('index')),

                Action::make('youtube_import')
                    ->label("YouTube'dan İçe Aktar")
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('danger')
                    ->url($importUrl),

                Action::make('create_episode')
                    ->label('+ Yeni Bölüm')
                    ->color('success')
                    ->icon('heroicon-o-plus')
                    ->url($createUrl),
            ];
        }

        return [
            CreateAction::make()
                ->label('+ Yeni Bölüm'),
        ];
    }
}


