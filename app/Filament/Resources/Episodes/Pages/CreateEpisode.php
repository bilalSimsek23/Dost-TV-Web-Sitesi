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
        if (request()->has('season_year') && request()->query('season_year') !== 'none' && filled(request()->query('season_year'))) {
            $fillData['season_year'] = trim((string) request()->query('season_year'));
        }
        if (request()->has('program_series_id') && request()->query('program_series_id') !== 'none') {
            $fillData['program_series_id'] = (int) request()->query('program_series_id');
        } elseif (request()->has('series_id') && request()->query('series_id') !== 'none') {
            $fillData['program_series_id'] = (int) request()->query('series_id');
        }

        if (! empty($fillData)) {
            $this->form->fill($fillData);
        }
    }
}
