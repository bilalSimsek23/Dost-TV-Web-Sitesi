@props([
    'banners' => collect(),
])

@if ($banners->isNotEmpty())
    <section class="relative overflow-hidden" x-data="{ active: 0, count: {{ $banners->count() }} }"
              x-init="setInterval(() => active = (active + 1) % count, 6000)">
        <div class="relative aspect-[21/9] w-full sm:aspect-[3/1]">
            @foreach ($banners as $index => $banner)
                <div x-show="active === {{ $index }}" x-transition:enter="transition ease-out duration-700"
                     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     class="absolute inset-0">
                    <x-site.hero-banner
                        :title="$banner->title"
                        :subtitle="$banner->subtitle"
                        :image="$banner->image"
                        :link-url="$banner->link_url"
                    />
                </div>
            @endforeach
        </div>

        @if ($banners->count() > 1)
            <div class="absolute bottom-4 right-6 flex gap-2">
                @foreach ($banners as $index => $banner)
                    <button type="button" @click="active = {{ $index }}"
                            :class="active === {{ $index }} ? 'bg-rose-500' : 'bg-white/30'"
                            class="h-2 w-2 rounded-full transition"
                            aria-label="Slide {{ $index + 1 }}"></button>
                @endforeach
            </div>
        @endif
    </section>
@endif
