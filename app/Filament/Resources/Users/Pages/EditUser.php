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

        // 2. Security Check: Cannot deactivate self
        if ($record->id === $currentUser?->id && isset($data['is_active']) && ! $data['is_active']) {
            Notification::make()
                ->title('Kendi hesabınızı pasife alamazsınız.')
                ->danger()
                ->send();
            $data['is_active'] = true;
        }

        // 3. Security Check: Administrator cannot modify super_admin role
        if ($record->hasRole('super_admin') && $currentUser && ! $currentUser->hasRole('super_admin')) {
            $data['role'] = 'super_admin';
        }

        // 4. Security Check: Last active super_admin protection
        if ($record->hasRole('super_admin')) {
            $activeSuperAdminsCount = User::where('role', 'super_admin')
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->count();

            if ($activeSuperAdminsCount <= 1) {
                // Prevent role demotion
                if (isset($data['role']) && $data['role'] !== 'super_admin') {
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
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make('archive')
                ->label('Arşivle')
                ->modalHeading('Kullanıcıyı Arşivle')
                ->before(function (DeleteAction $action) {
                    $record = $this->getRecord();
                    $currentUser = auth()->user();

                    if ($record->id === $currentUser?->id) {
                        Notification::make()->title('Kendi hesabınızı arşivleyemezsiniz.')->danger()->send();
                        $action->cancel();
                        return;
                    }

                    if ($record->hasRole('super_admin') && $currentUser && ! $currentUser->hasRole('super_admin')) {
                        Notification::make()->title('Süper Admin hesabını arşivleme yetkiniz yoktur.')->danger()->send();
                        $action->cancel();
                        return;
                    }

                    if ($record->hasRole('super_admin')) {
                        $activeSuperAdminsCount = User::where('role', 'super_admin')
                            ->where('is_active', true)
                            ->whereNull('deleted_at')
                            ->count();

                        if ($activeSuperAdminsCount <= 1) {
                            Notification::make()->title('Sistemdeki son aktif Süper Admin arşivlenemez.')->danger()->send();
                            $action->cancel();
                            return;
                        }
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
