<?php

namespace App\Filament\Resources\ScheduleTemplates\Pages;

use App\Filament\Pages\BaseCreateRecord;
use App\Filament\Resources\ScheduleTemplates\ScheduleTemplateResource;
use App\Models\ScheduleTemplate;

class CreateScheduleTemplate extends BaseCreateRecord
{
    protected static string $resource = ScheduleTemplateResource::class;

    protected function afterCreate(): void
    {
        if ($this->record->is_active) {
            ScheduleTemplate::where('id', '!=', $this->record->id)->update(['is_active' => false]);
        }
    }
}
