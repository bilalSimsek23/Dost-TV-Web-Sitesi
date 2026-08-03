@extends('layouts.app')

@section('title', 'Programlar - Dost TV')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-black text-white">Programlar</h1>
        <p class="mt-2 text-slate-400">Dost TV'nin tüm programlarını keşfedin.</p>

        @if ($categories->isNotEmpty())
            <div class="mt-6 flex flex-wrap gap-2">
                <a href="{{ route('programs.index') }}"
                   class="rounded-full px-4 py-2 text-sm font-medium transition {{ $activeCategory === '' ? 'bg-rose-600 text-white' : 'bg-white/5 text-slate-300 hover:bg-white/10' }}">
                    Tümü
                </a>
                @foreach ($categories as $category)
                    <a href="{{ route('programs.index', ['kategori' => $category->slug]) }}"
                       class="rounded-full px-4 py-2 text-sm font-medium transition {{ $activeCategory === $category->slug ? 'bg-rose-600 text-white' : 'bg-white/5 text-slate-300 hover:bg-white/10' }}">
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        @endif

        <div class="mt-10 grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4">
            @forelse ($programs as $program)
                <a href="{{ route('programs.show', $program) }}" class="group">
                    <div class="aspect-[3/4] overflow-hidden rounded-xl bg-slate-900 ring-1 ring-white/10">
                        @if ($program->cover_image)
                            <img src="{{ asset('storage/' . $program->cover_image) }}" alt="{{ $program->name }}"
                                 class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-4xl font-black text-slate-700">
                                {{ mb_substr($program->name, 0, 1) }}
                            </div>
                        @endif
                    </div>
                    <p class="mt-3 font-semibold text-white group-hover:text-rose-300">{{ $program->name }}</p>
                    @if ($program->categories->isNotEmpty())
                        <p class="text-xs text-slate-500">{{ $program->categories->pluck('name')->join(', ') }}</p>
                    @endif
                </a>
            @empty
                <p class="col-span-full text-slate-500">Bu kategoride henüz program eklenmedi.</p>
            @endforelse
        </div>

        <div class="mt-12">
            {{ $programs->links() }}
        </div>
    </section>
@endsection
