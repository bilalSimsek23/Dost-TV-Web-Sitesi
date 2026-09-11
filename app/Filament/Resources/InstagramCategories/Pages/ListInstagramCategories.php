<?php

namespace App\Filament\Resources\InstagramCategories\Pages;

use App\Filament\Resources\InstagramCategories\InstagramCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInstagramCategories extends ListRecords
{
    protected static string $resource = InstagramCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
