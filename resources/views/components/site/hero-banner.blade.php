@props([
    'title' => '',
    'subtitle' => null,
    'image' => null,
    'linkUrl' => null,
    'preview' => false,
])

@php
    $imageUrl = null;
    if ($image) {
        if (is_string($image)) {
            if (str_starts_with($image, 'http') || str_starts_with($image, 'livewire-file:') || str_starts_with($image, 'tmp/')) {
                $imageUrl = $image;
            } else {
                $imageUrl = asset('storage/' . $image);
            }
        } elseif (is_object($image) && method_exists($image, 'temporaryUrl')) {
            try {
                $imageUrl = $image->temporaryUrl();
            } catch (\Throwable $e) {
                $imageUrl = null;
            }
        }
    }
@endphp

<div class="relative aspect-[21/9] w-full sm:aspect-[3/1] overflow-hidden rounded-xl ring-1 ring-white/10" style="background-color: var(--color-surface, #0f172a);">
    @if ($imageUrl)
        <img src="{{ $imageUrl }}" alt="{{ $title }}" class="h-full w-full object-cover">
    @else
        <div class="flex h-full w-full items-center justify-center text-slate-700" style="background-color: var(--color-surface, #0f172a);">
            <span class="text-4xl font-black">{{ mb_substr($title ?: 'D', 0, 1) }}</span>
        </div>
    @endif
    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"></div>
    <div class="absolute inset-x-0 bottom-0 p-6 sm:p-10">
        <h2 class="text-2xl font-black text-white sm:text-4xl">{{ $title ?: 'Banner Başlığı' }}</h2>
        @if ($subtitle)
            <p class="mt-2 max-w-2xl text-slate-300">{{ $subtitle }}</p>
        @endif
    </div>
</div>
