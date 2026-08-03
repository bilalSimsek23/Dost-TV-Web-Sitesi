<?php

namespace App\Filament\Resources\ScheduleTemplates\Pages;

use App\Filament\Resources\ScheduleTemplates\ScheduleTemplateResource;
use App\Models\ScheduleTemplate;
use Filament\Resources\Pages\EditRecord;

class EditScheduleTemplate extends EditRecord
{
    protected static string $resource = ScheduleTemplateResource::class;

    protected function afterSave(): void
    {
        if ($this->record->is_active) {
            ScheduleTemplate::where('id', '!=', $this->record->id)->update(['is_active' => false]);
        }
    }
}
