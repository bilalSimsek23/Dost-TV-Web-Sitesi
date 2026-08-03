<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Services\Menu\ProgramMegaMenuService;
use App\Support\SiteCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuItemReorderController extends Controller
{
    public function reorder(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole(['super_admin', 'administrator', 'designer', 'editor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Bu işlemi gerçekleştirme yetkiniz bulunmuyor.',
            ], 403);
        }

        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.position' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['items'] as $item) {
                MenuItem::whereKey($item['id'])->update([
                    'sort_order' => $item['position'],
                ]);
            }
        });

        SiteCache::forgetMenu('header_primary');
        ProgramMegaMenuService::forgetCache();

        return response()->json([
            'success' => true,
            'message' => 'Top Header sıralaması başarıyla kaydedildi.',
        ]);
    }
}
