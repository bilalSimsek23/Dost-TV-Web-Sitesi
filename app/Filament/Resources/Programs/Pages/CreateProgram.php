<?php

namespace App\Filament\Resources\Programs\Pages;

use App\Filament\Pages\BaseCreateRecord;
use App\Filament\Resources\Programs\ProgramResource;
use App\Services\Audit\AuditLogger;

class CreateProgram extends BaseCreateRecord
{
    protected static string $resource = ProgramResource::class;

    protected function afterCreate(): void
    {
        $userName = auth()->user()?->name ?? 'Kullanıcı';
        AuditLogger::log(
            action: 'created',
            message: "{$userName}, {$this->record->name} programını ekledi.",
            subject: $this->record,
            subjectLabel: $this->record->name,
        );
    }
}
