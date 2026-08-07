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

        if (request()->has('program_id')) {
            $this->form->fill([
                'program_id' => request()->query('program_id'),
            ]);
        }
    }
}
