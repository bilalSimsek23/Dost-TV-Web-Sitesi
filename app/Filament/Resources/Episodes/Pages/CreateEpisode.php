<?php

namespace App\Filament\Resources\Episodes\Pages;

use App\Filament\Pages\BaseCreateRecord;
use App\Filament\Resources\Episodes\EpisodeResource;
use App\Models\Episode;
use App\Models\Program;

class CreateEpisode extends BaseCreateRecord
{
    protected static string $resource = EpisodeResource::class;

    public ?int $contextProgramId = null;

    public ?int $contextSeasonNumber = null;

    public function mount(): void
    {
        parent::mount();

        $pId = request()->query('program_id');
        $sNum = request()->query('season_number');

        if (filled($pId) && Program::where('id', $pId)->exists()) {
            $this->contextProgramId = (int) $pId;

            if (filled($sNum) && $sNum !== 'none') {
                $this->contextSeasonNumber = (int) $sNum;
            }

            $query = Episode::where('program_id', $this->contextProgramId);
            if ($sNum === 'none' || blank($sNum)) {
                $query->whereNull('season_number');
            } else {
                $query->where('season_number', $this->contextSeasonNumber);
            }
            $maxEp = $query->max('episode_number') ?? 0;

            $data = [
                'program_id' => $this->contextProgramId,
                'season_number' => $this->contextSeasonNumber,
                'episode_number' => $maxEp + 1,
            ];

            $this->form->fill($data);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($this->contextProgramId) {
            $data['program_id'] = $this->contextProgramId;
        } elseif (request()->has('program_id')) {
            $data['program_id'] = (int) request()->query('program_id');
        }

        if ($this->contextSeasonNumber !== null) {
            $data['season_number'] = $this->contextSeasonNumber;
        } elseif (request()->has('season_number') && request()->query('season_number') !== 'none') {
            $data['season_number'] = (int) request()->query('season_number');
        }

        return $data;
    }
}
