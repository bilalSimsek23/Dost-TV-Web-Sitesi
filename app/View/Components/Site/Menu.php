<?php

namespace App\View\Components\Site;

use App\Services\Menu\MenuService;
use App\Services\Menu\ProgramMegaMenuService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Menu extends Component
{
    public Collection $items;
    public array $megaMenuData;

    public function __construct(
        public string $location = 'header_primary',
        ?MenuService $menuService = null,
        ?ProgramMegaMenuService $megaMenuService = null
    ) {
        $service = $menuService ?? app(MenuService::class);
        $megaService = $megaMenuService ?? app(ProgramMegaMenuService::class);

        $this->items = $service->forLocation($location);
        $this->megaMenuData = $megaService->getMenuData();
    }

    public function render(): View
    {
        return view('components.site.menu', [
            'megaMenuCategories' => $this->megaMenuData['categories'] ?? collect(),
            'megaMenuCategoryDetails' => $this->megaMenuData['category_details'] ?? [],
        ]);
    }
}
