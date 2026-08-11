<?php

namespace App\Filament\Resources\Episodes\Pages;

use App\Filament\Pages\BaseCreateRecord;
use App\Filament\Resources\Episodes\EpisodeResource;

class CreateEpisode extends BaseCreateRecord
{
    protected static string $resource = EpisodeResource::class;

    public function mount(): void
    {
        parent::mount();

        $fillData = [];
        if (request()->has('program_id')) {
            $fillData['program_id'] = (int) request()->query('program_id');
        }
        if (request()->has('season_number') && request()->query('season_number') !== 'none') {
            $fillData['season_number'] = (int) request()->query('season_number');
        }

        if (! empty($fillData)) {
            $this->form->fill($fillData);
        }
    }
}
