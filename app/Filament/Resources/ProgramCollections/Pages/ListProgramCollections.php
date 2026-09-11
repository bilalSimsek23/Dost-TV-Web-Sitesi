<?php

namespace App\Filament\Resources\ProgramCollections\Pages;

use App\Filament\Resources\ProgramCollections\ProgramCollectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProgramCollections extends ListRecords
{
    protected static string $resource = ProgramCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('+ Yeni Koleksiyon Oluştur'),
        ];
    }
}
