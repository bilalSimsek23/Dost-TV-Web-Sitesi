<?php

namespace App\Filament\Resources\InstagramCategories\Pages;

use App\Filament\Resources\InstagramCategories\InstagramCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInstagramCategory extends EditRecord
{
    protected static string $resource = InstagramCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
