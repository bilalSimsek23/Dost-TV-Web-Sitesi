<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Concerns\PersistsTablePaginationInUrl;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    use PersistsTablePaginationInUrl;

    protected static string $resource = CategoryResource::class;

    protected string $view = 'filament.resources.categories.pages.list-categories';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
