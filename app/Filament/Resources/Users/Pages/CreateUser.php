<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Pages\BaseCreateRecord;
use App\Filament\Resources\Users\UserResource;

class CreateUser extends BaseCreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $currentUser = auth()->user();

        // Enforce safe default role if non-super_admin tries creating super_admin
        if (isset($data['role_id'])) {
            $targetRole = \App\Models\Role::find($data['role_id']);
            if ($targetRole && $targetRole->base_role === 'super_admin' && (! $currentUser || ! $currentUser->isSuperAdmin())) {
                $data['role_id'] = \App\Models\Role::where('slug', 'editor')->value('id');
            }
        } elseif (($data['role'] ?? null) === 'super_admin' && (! $currentUser || ! $currentUser->isSuperAdmin())) {
            $data['role'] = 'editor';
            $data['role_id'] = \App\Models\Role::where('slug', 'editor')->value('id');
        }

        $data['is_active'] = $data['is_active'] ?? true;
        // Assign random unguessable password until invitation is accepted
        $data['password'] = \Illuminate\Support\Str::random(64);

        return $data;
    }

    protected function afterCreate(): void
    {
        $userName = auth()->user()?->name ?? 'Admin';
        \App\Services\Audit\AuditLogger::log(
            action: 'created',
            message: "{$userName}, {$this->record->name} kullanıcısını oluşturdu.",
            subject: $this->record,
            subjectLabel: $this->record->name,
        );

        // Send 72-hour invitation
        $invitationService = app(\App\Services\Auth\UserInvitationService::class);
        $result = $invitationService->createInvitation($this->record, auth()->user());

        if (! $result['mail_sent']) {
            \Filament\Notifications\Notification::make()
                ->title('Kullanıcı oluşturuldu ancak davet e-postası gönderilemedi.')
                ->warning()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
