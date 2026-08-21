<?php

namespace App\Filament\Resources\LiveStreams\Pages;

use App\Filament\Concerns\PersistsTablePaginationInUrl;
use App\Filament\Resources\LiveStreams\LiveStreamResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLiveStreams extends ListRecords
{
    use PersistsTablePaginationInUrl;

    protected static string $resource = LiveStreamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Yeni Canlı Yayın Ekle'),
        ];
    }
}
