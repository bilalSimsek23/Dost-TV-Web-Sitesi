<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => ! $this->getRecord()->isSystem())
                ->before(function (DeleteAction $action) {
                    $record = $this->getRecord();
                    if ($record->isSystem()) {
                        Notification::make()
                            ->title('Sistem rolleri silinemez.')
                            ->danger()
                            ->send();
                        $action->cancel();
                        return;
                    }

                    if ($record->users()->exists()) {
                        Notification::make()
                            ->title('Bu role atanmış kullanıcılar olduğu için silinemez.')
                            ->danger()
                            ->send();
                        $action->cancel();
                        return;
                    }

                    $userName = auth()->user()?->name ?? 'Admin';
                    \App\Services\Audit\AuditLogger::log(
                        action: 'deleted',
                        message: "{$userName}, {$record->name} rolünü sildi.",
                        subject: $record,
                        subjectLabel: $record->name,
                        isDestructive: true,
                    );
                }),
        ];
    }

    protected function afterSave(): void
    {
        $userName = auth()->user()?->name ?? 'Admin';
        \App\Services\Audit\AuditLogger::log(
            action: 'updated',
            message: "{$userName}, {$this->record->name} rolünü düzenledi.",
            subject: $this->record,
            subjectLabel: $this->record->name,
        );
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();
        $currentUser = auth()->user();

        // System role immutable protections
        if ($record->isSystem()) {
            $data['name'] = $record->name;
            $data['base_role'] = $record->base_role;
            $data['is_system'] = true;
            $data['is_active'] = true;
        } elseif (($data['base_role'] ?? null) === 'super_admin' && (! $currentUser || ! $currentUser->isSuperAdmin())) {
            $data['base_role'] = $record->base_role;
        }

        return $data;
    }
}
