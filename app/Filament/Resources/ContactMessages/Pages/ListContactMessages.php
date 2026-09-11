<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Concerns\PersistsTablePaginationInUrl;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use Filament\Resources\Pages\ListRecords;

class ListContactMessages extends ListRecords
{
    use PersistsTablePaginationInUrl;

    protected static string $resource = ContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
