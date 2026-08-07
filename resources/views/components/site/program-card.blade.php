@props([
    'title' => '',
    'coverImage' => null,
    'categories' => [],
    'linkUrl' => null,
    'preview' => false,
])

@php
    $imageUrl = null;
    if ($coverImage) {
        if (is_string($coverImage)) {
            if (str_starts_with($coverImage, 'http') || str_starts_with($coverImage, 'livewire-file:') || str_starts_with($coverImage, 'tmp/')) {
                $imageUrl = $coverImage;
            } else {
                $imageUrl = asset('storage/' . $coverImage);
            }
        } elseif (is_object($coverImage) && method_exists($coverImage, 'temporaryUrl')) {
            try {
                $imageUrl = $coverImage->temporaryUrl();
            } catch (\Throwable $e) {
                $imageUrl = null;
            }
        }
    }
@endphp

<div class="group block max-w-xs">
    <div class="aspect-[3/4] overflow-hidden rounded-xl bg-slate-900 ring-1 ring-white/10">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $title }}" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
        @else
            <div class="flex h-full w-full items-center justify-center text-4xl font-black text-slate-700">
                {{ mb_substr($title ?: 'P', 0, 1) }}
            </div>
        @endif
    </div>
    <p class="mt-3 font-semibold text-white group-hover:text-rose-300">{{ $title ?: 'Program Adı' }}</p>
    @if (!empty($categories))
        <p class="text-xs text-slate-500">{{ is_array($categories) ? implode(', ', $categories) : $categories }}</p>
    @endif
</div>
