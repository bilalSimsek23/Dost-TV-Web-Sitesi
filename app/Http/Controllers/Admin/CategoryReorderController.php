<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Menu\ProgramMegaMenuService;
use App\Support\SiteCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryReorderController extends Controller
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
            'items.*.id' => ['required', 'integer', 'exists:categories,id'],
            'items.*.position' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['items'] as $item) {
                Category::whereKey($item['id'])->update([
                    'sort_order' => $item['position'],
                ]);
            }
        });

        ProgramMegaMenuService::forgetCache();
        SiteCache::forgetCategoryTree();

        return response()->json([
            'success' => true,
            'message' => 'Kategori sıralaması başarıyla kaydedildi.',
        ]);
    }
}
