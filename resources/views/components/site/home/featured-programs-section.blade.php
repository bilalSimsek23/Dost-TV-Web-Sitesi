@props([
    'featuredPrograms' => collect(),
    'preview' => false,
])

<section class="mx-auto max-w-7xl px-4 pb-24 sm:px-6 lg:px-8">
    <div class="mb-8 flex items-end justify-between">
        <div>
            <h2 class="text-2xl font-bold text-white">Öne Çıkan Programlar</h2>
            <p class="mt-1 text-slate-400">Kanalımızın en sevilen programlarını keşfedin.</p>
        </div>
        <a href="{{ $preview ? 'javascript:void(0)' : route('programs.index') }}" class="text-sm font-semibold text-rose-400 hover:text-rose-300">
            Tümünü Gör &rarr;
        </a>
    </div>

    <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
        @forelse ($featuredPrograms as $program)
            @if($preview)
                <x-site.program-card
                    :title="$program->name"
                    :cover-image="$program->cover_image"
                    :categories="$program->categories->pluck('name')->all()"
                    :preview="true"
                />
            @else
                <a href="{{ route('programs.show', $program) }}" class="group block">
                    <x-site.program-card
                        :title="$program->name"
                        :cover-image="$program->cover_image"
                        :categories="$program->categories->pluck('name')->all()"
                    />
                </a>
            @endif
        @empty
            <p class="col-span-full text-slate-500">Henüz program eklenmedi. Admin panelden program ekleyebilirsiniz.</p>
        @endforelse
    </div>
</section>
