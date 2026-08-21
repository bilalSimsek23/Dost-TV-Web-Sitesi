<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();
        $currentUser = auth()->user();

        // 1. Optional password update
        if (! empty($data['new_password'])) {
            $data['password'] = $data['new_password'];
        }
        unset($data['new_password'], $data['new_password_confirmation']);

        // 2. Security Check: Last active super admin cannot deactivate self
        if ($record->id === $currentUser?->id && isset($data['is_active']) && ! $data['is_active'] && User::isLastActiveSuperAdmin($record)) {
            Notification::make()
                ->title('Sistemdeki son aktif Süper Admin pasife alınamaz.')
                ->danger()
                ->send();
            $data['is_active'] = true;
        }

        // 3. Security Check: Non-super_admin cannot assign Super Admin role or modify Super Admin users
        if ($currentUser && ! $currentUser->isSuperAdmin()) {
            if ($record->isSuperAdmin()) {
                $data['role_id'] = $record->role_id;
                $data['role'] = 'super_admin';
                $data['is_active'] = $record->is_active;
            } else {
                if (isset($data['role_id'])) {
                    $targetRole = \App\Models\Role::find($data['role_id']);
                    if ($targetRole && $targetRole->base_role === 'super_admin') {
                        $data['role_id'] = $record->role_id;
                        $data['role'] = $record->role;
                    }
                }
                if (($data['role'] ?? null) === 'super_admin') {
                    $data['role'] = $record->role;
                }
            }
        }

        // 4. Security Check: Last active super_admin protection
        if ($record->isSuperAdmin() && User::isLastActiveSuperAdmin($record)) {
            // Prevent role demotion
            if (isset($data['role_id'])) {
                $targetRole = \App\Models\Role::find($data['role_id']);
                if ($targetRole && $targetRole->base_role !== 'super_admin') {
                    Notification::make()
                        ->title('Sistemdeki son aktif Süper Admin rolü değiştirilemez.')
                        ->danger()
                        ->send();
                    $data['role_id'] = $record->role_id;
                    $data['role'] = 'super_admin';
                }
            } elseif (isset($data['role']) && $data['role'] !== 'super_admin') {
                Notification::make()
                    ->title('Sistemdeki son aktif Süper Admin rolü değiştirilemez.')
                    ->danger()
                    ->send();
                $data['role'] = 'super_admin';
            }

            // Prevent deactivation
            if (isset($data['is_active']) && ! $data['is_active']) {
                Notification::make()
                    ->title('Sistemdeki son aktif Süper Admin pasife alınamaz.')
                    ->danger()
                    ->send();
                $data['is_active'] = true;
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $userName = auth()->user()?->name ?? 'Admin';
        $record = $this->record;

        if ($record->wasChanged('role') || $record->wasChanged('role_id')) {
            \App\Services\Audit\AuditLogger::log(
                action: 'role_changed',
                message: "{$userName}, {$record->name} kullanıcısının rolünü değiştirdi.",
                subject: $record,
                subjectLabel: $record->name,
            );

            return;
        }

        \App\Services\Audit\AuditLogger::log(
            action: 'updated',
            message: "{$userName}, {$record->name} kullanıcısını düzenledi.",
            subject: $record,
            subjectLabel: $record->name,
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make('forceDelete')
                ->label('Kalıcı Sil')
                ->modalHeading('Kullanıcıyı Kalıcı Olarak Sil')
                ->modalDescription('Bu kullanıcı kalıcı olarak silinecek. Bu işlem geri alınamaz.')
                ->modalSubmitActionLabel('Evet, Kalıcı Olarak Sil')
                ->visible(function () {
                    $record = $this->getRecord();
                    $currentUser = auth()->user();

                    if (! $currentUser || ! $currentUser->isSuperAdmin()) {
                        return false;
                    }

                    if ($record->isSuperAdmin() && User::isLastActiveSuperAdmin($record)) {
                        return false;
                    }

                    return true;
                })
                ->before(function () {
                    $record = $this->getRecord();
                    $userName = auth()->user()?->name ?? 'Admin';
                    $targetName = $record->name;
                    \App\Services\Audit\AuditLogger::log(
                        action: 'deleted',
                        message: "{$userName}, {$targetName} kullanıcısını kalıcı olarak sildi.",
                        subject: $record,
                        subjectLabel: $targetName,
                        isDestructive: true,
                    );
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
