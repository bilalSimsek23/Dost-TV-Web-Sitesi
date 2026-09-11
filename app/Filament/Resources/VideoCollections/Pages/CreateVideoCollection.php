<?php

namespace App\Filament\Resources\VideoCollections\Pages;

use App\Filament\Resources\VideoCollections\VideoCollectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVideoCollection extends CreateRecord
{
    protected static string $resource = VideoCollectionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
