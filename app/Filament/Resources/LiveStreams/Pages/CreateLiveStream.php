<?php

namespace App\Filament\Resources\LiveStreams\Pages;

use App\Filament\Resources\LiveStreams\LiveStreamResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLiveStream extends CreateRecord
{
    protected static string $resource = LiveStreamResource::class;
}
