<?php

namespace App\Filament\Resources\InstagramVideos\Pages;

use App\Filament\Resources\InstagramVideos\InstagramVideoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInstagramVideo extends EditRecord
{
    protected static string $resource = InstagramVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
