<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currentUser = auth()->user();

        // Non-super_admin cannot create super_admin base_role
        if (($data['base_role'] ?? null) === 'super_admin' && (! $currentUser || ! $currentUser->isSuperAdmin())) {
            $data['base_role'] = 'editor';
        }

        $data['is_system'] = false;

        return $data;
    }

    protected function afterCreate(): void
    {
        $userName = auth()->user()?->name ?? 'Admin';
        \App\Services\Audit\AuditLogger::log(
            action: 'created',
            message: "{$userName}, {$this->record->name} rolünü oluşturdu.",
            subject: $this->record,
            subjectLabel: $this->record->name,
        );
    }
}
