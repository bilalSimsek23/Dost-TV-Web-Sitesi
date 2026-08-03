<?php

namespace App\Filament\Resources\LiveStreams\Pages;

use App\Filament\Resources\LiveStreams\LiveStreamResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLiveStream extends EditRecord
{
    protected static string $resource = LiveStreamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
