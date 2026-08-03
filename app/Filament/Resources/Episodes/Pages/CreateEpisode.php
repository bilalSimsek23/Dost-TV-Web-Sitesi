<?php

namespace App\Filament\Resources\Episodes\Pages;

use App\Filament\Resources\Episodes\EpisodeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEpisode extends CreateRecord
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
