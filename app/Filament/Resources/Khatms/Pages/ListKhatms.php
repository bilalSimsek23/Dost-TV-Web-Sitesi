<?php

namespace App\Filament\Resources\Khatms\Pages;

use App\Filament\Concerns\PersistsTablePaginationInUrl;
use App\Filament\Resources\Khatms\KhatmResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKhatms extends ListRecords
{
    use PersistsTablePaginationInUrl;

    protected static string $resource = KhatmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Yeni Hatim Oluştur'),
        ];
    }
}
