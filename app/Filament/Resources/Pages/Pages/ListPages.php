<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Concerns\PersistsTablePaginationInUrl;
use App\Filament\Resources\Pages\PageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPages extends ListRecords
{
    use PersistsTablePaginationInUrl;

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Yeni Kurumsal Bilgi')
                ->icon('heroicon-o-plus')
                ->color('warning'),
        ];
    }
}
