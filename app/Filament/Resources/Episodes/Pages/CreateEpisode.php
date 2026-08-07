<?php

namespace App\Filament\Resources\Episodes\Pages;

use App\Filament\Pages\BaseCreateRecord;
use App\Filament\Resources\Episodes\EpisodeResource;

class CreateEpisode extends BaseCreateRecord
{
    protected static string $resource = EpisodeResource::class;

    public function mount(): void
    {
        $programId = request()->query('program_id');
        $season = request()->query('season_number');

        parent::mount();

        if ($programId || $season) {
            $data = [];
            if ($programId) {
                $data['program_id'] = (int) $programId;
            }
            if ($season && $season !== 'none') {
                $data['season_number'] = (int) $season;
            }
            $this->form->fill($data);
        }
    }
}
