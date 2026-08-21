<?php

namespace App\Filament\Resources\FontFamilies\Pages;

use App\Filament\Concerns\PersistsTablePaginationInUrl;
use App\Filament\Resources\FontFamilies\FontFamilyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFontFamilies extends ListRecords
{
    use PersistsTablePaginationInUrl;

    protected static string $resource = FontFamilyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
