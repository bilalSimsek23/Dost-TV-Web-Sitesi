<?php

namespace App\Filament\Resources\Programs\Pages;

use App\Filament\Concerns\PersistsTablePaginationInUrl;
use App\Filament\Resources\Programs\ProgramResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrograms extends ListRecords
{
    use PersistsTablePaginationInUrl;

    protected static string $resource = ProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
