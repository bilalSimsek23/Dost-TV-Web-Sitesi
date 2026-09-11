@props([
    'videoCollection' => null,
    'programCollection' => null,
    'page' => null,
    'program' => null,
    'isHomepage' => false,
])

@php
    $user = auth()->user();
    $isAuthorizedAdmin = auth()->check() && $user?->hasAnyRole(['super_admin', 'administrator', 'designer', 'editor']);

    $previewToken = request('collection_preview_token') ?? session('collection_preview_token');
    $previewData = null;
    $isCollectionPreview = false;
    $activeCollectionName = '';
    $closeRoute = '#';
    $editRoute = '#';

    if ($isAuthorizedAdmin && $previewToken) {
        $previewData = \Illuminate\Support\Facades\Cache::get('collection_preview_' . $previewToken) ?? session('collection_preview_data');
        if ($previewData && is_array($previewData)) {
            $previewId = $previewData['id'] ?? null;
            $previewSlug = $previewData['slug'] ?? null;
            $previewType = $previewData['type'] ?? null;

            if ($videoCollection && $previewType === 'video_collection' && ($previewId === $videoCollection->id || $previewSlug === $videoCollection->slug)) {
                $isCollectionPreview = true;
                $activeCollectionName = $videoCollection->name;
                $editRoute = \App\Filament\Resources\VideoCollections\VideoCollectionResource::getUrl('edit', ['record' => $videoCollection]);
                $closeRoute = route('video.collection.preview.close', ['slug' => $videoCollection->slug]);
            } elseif ($programCollection && $previewType === 'program_collection' && ($previewId === $programCollection->id || $previewSlug === $programCollection->slug)) {
                $isCollectionPreview = true;
                $activeCollectionName = $programCollection->name;
                $editRoute = \App\Filament\Resources\ProgramCollections\ProgramCollectionResource::getUrl('edit', ['record' => $programCollection]);
                $closeRoute = route('program.collection.preview.close', ['slug' => $programCollection->slug]);
            }
        }
    }
@endphp

@if($isAuthorizedAdmin && $isCollectionPreview)
    <div class="fixed top-0 left-0 right-0 z-[9999] bg-slate-950/95 border-b border-amber-500/50 px-4 py-2 text-xs text-white shadow-2xl backdrop-blur-md flex items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <span class="relative flex h-2.5 w-2.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
            </span>
            <strong class="font-bold uppercase tracking-wider text-amber-400">KOLEKSİYON TEST MODU</strong>
            <span class="text-slate-300 font-semibold truncate max-w-xs sm:max-w-md">— {{ $activeCollectionName }}</span>
            <span class="text-slate-400 hidden lg:inline">(Geçici Görünüm — Veritabanına Kaydedilmedi)</span>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ $editRoute }}"
               class="px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition font-medium flex items-center gap-1">
                ‹ Düzenlemeye Dön
            </a>
            <a href="{{ $closeRoute }}"
               class="px-3 py-1 rounded-lg bg-rose-600/90 hover:bg-rose-600 text-white font-medium transition shadow">
                Testi Kapat ✕
            </a>
        </div>
    </div>
    <div class="h-10 w-full"></div>
@endif
