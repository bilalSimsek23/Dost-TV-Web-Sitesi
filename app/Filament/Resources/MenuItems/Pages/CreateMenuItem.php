<?php

namespace App\Filament\Resources\MenuItems\Pages;

use App\Filament\Resources\MenuItems\MenuItemResource;
use App\Models\Menu;
use App\Services\Menu\ProgramMegaMenuService;
use App\Support\SiteCache;
use Filament\Resources\Pages\CreateRecord;

class CreateMenuItem extends CreateRecord
{
    protected static string $resource = MenuItemResource::class;

    public function mount(): void
    {
        parent::mount();

        $primaryMenu = Menu::where('location', 'header_primary')->first();
        if ($primaryMenu && empty($this->data['menu_id'])) {
            $this->form->fill([
                'menu_id' => $primaryMenu->id,
                'is_active' => true,
                'sort_order' => 1,
            ]);
        }
    }

    protected function afterSave(): void
    {
        SiteCache::forgetMenu('header_primary');
        ProgramMegaMenuService::forgetCache();
    }
}
