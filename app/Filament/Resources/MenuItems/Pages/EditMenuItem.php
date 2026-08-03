<?php

namespace App\Filament\Resources\MenuItems\Pages;

use App\Filament\Resources\MenuItems\MenuItemResource;
use App\Services\Menu\ProgramMegaMenuService;
use App\Support\SiteCache;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMenuItem extends EditRecord
{
    protected static string $resource = MenuItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->after(function () {
                    SiteCache::forgetMenu('header_primary');
                    ProgramMegaMenuService::forgetCache();
                }),
        ];
    }

    protected function afterSave(): void
    {
        SiteCache::forgetMenu('header_primary');
        ProgramMegaMenuService::forgetCache();
    }
}
