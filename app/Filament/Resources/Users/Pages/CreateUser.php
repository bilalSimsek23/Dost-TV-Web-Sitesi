<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Pages\BaseCreateRecord;
use App\Filament\Resources\Users\UserResource;

class CreateUser extends BaseCreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Enforce safe default role if not provided or if non-super_admin tries creating super_admin
        $currentUser = auth()->user();
        if ($data['role'] === 'super_admin' && $currentUser && ! $currentUser->hasRole('super_admin')) {
            $data['role'] = 'editor';
        }

        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
