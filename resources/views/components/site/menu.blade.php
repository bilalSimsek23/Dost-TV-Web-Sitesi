<nav class="hidden items-center gap-1 lg:flex">
    @foreach ($items as $item)
        @if ($item->item_type === 'program_mega_menu')
            {{-- Programlar Dinamik Mega Menüsü --}}
            @php
                $firstCatId = $megaMenuCategories->first()?->id ?? 0;
            @endphp
            <div class="group relative" x-data="{ open: false, activeCat: 'cat-{{ $firstCatId }}' }" @mouseleave="open = false" @click.outside="open = false" @keydown.escape.window="open = false">
                <button type="button" 
                        @mouseenter="open = true" 
                        @click="open = !open" 
                        class="flex items-center gap-1 rounded-full px-4 py-2 text-sm font-medium transition text-slate-300 hover:bg-white/5 hover:text-white"
                        :class="{ 'bg-white/10 text-white': open }">
                    @if ($item->icon)<i class="{{ $item->icon }} mr-1"></i>@endif
                    {{ $item->title }}
                    @if ($item->badge_text)
                        <span class="ml-1 rounded bg-rose-500/20 px-1.5 py-0.5 text-xs text-rose-300">{{ $item->badge_text }}</span>
                    @endif
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 transition duration-200" :class="{ 'rotate-180': open }">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div x-show="open" 
                     x-cloak 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 translate-y-1"
                     class="absolute left-1/2 top-full z-50 mt-2 w-screen max-w-6xl -translate-x-1/2 rounded-2xl border border-white/10 bg-slate-900/95 p-6 shadow-2xl backdrop-blur-xl">
                    
                    <div class="flex gap-6 min-h-[380px] max-h-[75vh]">
                        {{-- Sol Sütun: KATEGORİLER --}}
                        <div class="w-1/4 min-w-[220px] border-r border-white/10 pr-4 overflow-y-auto space-y-1">
                            <div class="text-xs font-bold uppercase tracking-wider text-slate-400 px-3 py-2 border-b border-white/5 mb-2">KATEGORİLER</div>
                            
                            @foreach ($megaMenuCategories as $cat)
                                <div class="space-y-1">
                                    <button type="button"
                                            @mouseenter="activeCat = 'cat-{{ $cat->id }}'"
                                            @click="activeCat = 'cat-{{ $cat->id }}'"
                                            class="w-full flex items-center justify-between rounded-xl px-3 py-2 text-left text-sm font-medium transition"
                                            :class="activeCat === 'cat-{{ $cat->id }}' ? 'bg-amber-500/20 text-amber-300 font-semibold border-l-2 border-amber-500 pl-4' : 'text-slate-300 hover:bg-white/5 hover:text-white'">
                                        <span class="flex items-center gap-2">
                                            @if ($cat->icon)
                                                <i class="{{ $cat->icon }} text-xs"></i>
                                            @endif
                                            {{ $cat->name }}
                                        </span>
                                        @if ($cat->activeChildren && $cat->activeChildren->count() > 0)
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 opacity-60">
                                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                            </svg>
                                        @endif
                                    </button>

                                    {{-- Seviye 2 Alt Kategoriler --}}
                                    @if ($cat->activeChildren && $cat->activeChildren->count() > 0)
                                        <div class="pl-4 space-y-1">
                                            @foreach ($cat->activeChildren as $child)
                                                <button type="button"
                                                        @mouseenter="activeCat = 'cat-{{ $child->id }}'"
                                                        @click="activeCat = 'cat-{{ $child->id }}'"
                                                        class="w-full flex items-center justify-between rounded-lg px-3 py-1.5 text-left text-xs font-medium transition"
                                                        :class="activeCat === 'cat-{{ $child->id }}' ? 'bg-amber-500/15 text-amber-300 font-semibold border-l-2 border-amber-400 pl-3' : 'text-slate-400 hover:bg-white/5 hover:text-white'">
                                                    <span>{{ $child->name }}</span>
                                                </button>

                                                {{-- Seviye 3 Alt Kategoriler --}}
                                                @if ($child->activeChildren && $child->activeChildren->count() > 0)
                                                    <div class="pl-3 space-y-1">
                                                        @foreach ($child->activeChildren as $subChild)
                                                            <button type="button"
                                                                    @mouseenter="activeCat = 'cat-{{ $subChild->id }}'"
                                                                    @click="activeCat = 'cat-{{ $subChild->id }}'"
                                                                    class="w-full text-left rounded px-2 py-1 text-xs transition"
                                                                    :class="activeCat === 'cat-{{ $subChild->id }}' ? 'text-amber-300 font-semibold' : 'text-slate-500 hover:text-slate-300'">
                                                                • {{ $subChild->name }}
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Sağ Bölüm: Dinamik Başlık ve Dengeli Sütunlu Program Listesi --}}
                        <div class="flex-1 overflow-y-auto pr-2">
                            @foreach ($megaMenuCategoryDetails as $catKey => $detail)
                                <div x-show="activeCat === '{{ $catKey }}'" x-cloak class="space-y-4">
                                    <div class="flex items-center justify-between border-b border-white/10 pb-3">
                                        <h4 class="text-base font-bold text-white flex items-center gap-2">
                                            {{ $detail['right_title'] }}
                                            <span class="text-xs font-normal text-slate-400">
                                                ({{ $detail['total_programs'] }} Program)
                                            </span>
                                        </h4>
                                        <a href="{{ $detail['all_url'] }}" class="text-xs text-rose-400 hover:text-rose-300 hover:underline">
                                            Tümünü Gör →
                                        </a>
                                    </div>

                                    @if ($detail['total_programs'] > 0)
                                        <div class="grid gap-4 @if($detail['column_count'] === 1) grid-cols-1 @elseif($detail['column_count'] === 2) grid-cols-2 @elseif($detail['column_count'] === 3) grid-cols-3 @else grid-cols-4 @endif">
                                            @foreach ($detail['columns'] as $columnItems)
                                                <div class="space-y-2">
                                                    @foreach ($columnItems as $prog)
                                                        <a href="{{ $prog['url'] }}"
                                                           class="group flex items-center gap-3 rounded-xl border border-white/5 bg-slate-950/50 p-2 transition hover:border-rose-500/40 hover:bg-slate-800/60">
                                                            @if ($prog['cover_image'])
                                                                <img src="{{ $prog['cover_image'] }}" alt="{{ $prog['title'] }}" class="h-9 w-9 shrink-0 rounded-lg object-cover">
                                                            @else
                                                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-rose-400 font-bold text-xs">
                                                                    D
                                                                </div>
                                                            @endif
                                                            <span class="text-xs font-medium text-slate-200 group-hover:text-white line-clamp-2">{{ $prog['title'] }}</span>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="py-12 text-center text-sm text-slate-400">
                                            Bu kategoride henüz program bulunmuyor.
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @elseif ($item->children->count() > 0 || $item->item_type === 'dropdown')
            {{-- Standart Dropdown --}}
            <div class="group relative" x-data="{ open: false }" @mouseleave="open = false" @click.outside="open = false">
                <button type="button" @mouseenter="open = true" @click="open = !open" class="flex items-center gap-1 rounded-full px-4 py-2 text-sm font-medium text-slate-300 transition hover:bg-white/5 hover:text-white">
                    @if ($item->icon)<i class="{{ $item->icon }} mr-1"></i>@endif
                    {{ $item->title }}
                    @if ($item->badge_text)
                        <span class="ml-1 rounded bg-rose-500/20 px-1.5 py-0.5 text-xs text-rose-300">{{ $item->badge_text }}</span>
                    @endif
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div class="invisible absolute left-0 top-full z-50 w-56 rounded-xl border border-white/10 bg-slate-900 p-2 opacity-0 shadow-xl transition group-hover:visible group-hover:opacity-100">
                    @foreach ($item->children as $child)
                        @if ($child->resolved_url)
                            <a href="{{ $child->resolved_url }}"
                               @if ($child->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                               class="block rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-white/5 hover:text-white">
                                @if ($child->icon)<i class="{{ $child->icon }} mr-1"></i>@endif
                                {{ $child->title }}
                                @if ($child->badge_text)
                                    <span class="ml-1 rounded bg-rose-500/20 px-1 py-0.5 text-xs text-rose-300">{{ $child->badge_text }}</span>
                                @endif
                            </a>
                        @else
                            <span title="Bağlantı henüz tanımlanmadı" class="block cursor-not-allowed rounded-lg px-3 py-2 text-sm text-slate-500 opacity-60">
                                {{ $child->title }}
                            </span>
                        @endif
                    @endforeach
                </div>
            </div>
        @else
            {{-- Tekil Menü Öğesi --}}
            @if ($item->resolved_url)
                <a href="{{ $item->resolved_url }}"
                   @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                   class="rounded-full px-4 py-2 text-sm font-medium transition {{ request()->url() === $item->resolved_url ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    @if ($item->icon)<i class="{{ $item->icon }} mr-1"></i>@endif
                    {{ $item->title }}
                    @if ($item->badge_text)
                        <span class="ml-1 rounded bg-rose-500/20 px-1.5 py-0.5 text-xs text-rose-300">{{ $item->badge_text }}</span>
                    @endif
                </a>
            @else
                <span title="Bağlantı henüz tanımlanmadı" class="cursor-not-allowed rounded-full px-4 py-2 text-sm font-medium text-slate-500 opacity-60">
                    {{ $item->title }}
                </span>
            @endif
        @endif
    @endforeach
</nav>
