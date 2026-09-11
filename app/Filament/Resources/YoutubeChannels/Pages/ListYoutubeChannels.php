<?php

namespace App\Filament\Resources\YoutubeChannels\Pages;

use App\Filament\Resources\YoutubeChannels\YoutubeChannelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListYoutubeChannels extends ListRecords
{
    protected static string $resource = YoutubeChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Yeni YouTube Kanalı Ekle'),
        ];
    }
}
