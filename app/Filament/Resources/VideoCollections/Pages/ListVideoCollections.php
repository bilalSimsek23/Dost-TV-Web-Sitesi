<?php

namespace App\Filament\Resources\VideoCollections\Pages;

use App\Filament\Resources\VideoCollections\VideoCollectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVideoCollections extends ListRecords
{
    protected static string $resource = VideoCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('+ Yeni Koleksiyon Oluştur'),
        ];
    }
}
