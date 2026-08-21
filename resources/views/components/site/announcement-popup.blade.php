@props([
    'preview' => false,
])

@if ($preview || ($announcement && ($title || $imageUrl || $message)))
    @php
        $announcementId = $announcement?->id ?? 'preview';
        $storageKey = "dosttv_announcement_dismissed_{$announcementId}";
    @endphp

    <div x-data="{
            show: false,
            preview: {{ $preview ? 'true' : 'false' }},
            storageKey: '{{ $storageKey }}',
            init() {
                if (this.preview) {
                    this.show = true;
                    return;
                }
                const dismissedAt = localStorage.getItem(this.storageKey);
                if (dismissedAt) {
                    const diff = Date.now() - parseInt(dismissedAt, 10);
                    const twentyFourHours = 24 * 60 * 60 * 1000;
                    if (diff < twentyFourHours) {
                        this.show = false;
                        return;
                    }
                }
                this.show = true;
                document.body.style.overflow = 'hidden';
            },
            close() {
                this.show = false;
                if (!this.preview) {
                    localStorage.setItem(this.storageKey, Date.now().toString());
                    document.body.style.overflow = '';
                }
            }
         }"
         x-show="show"
         x-cloak
         @keydown.escape.window="close()"
         class="fixed inset-0 z-[70] flex items-center justify-center p-4 sm:p-6"
         role="dialog"
         aria-modal="true"
         aria-labelledby="announcement-modal-title">

        {{-- Darkened Backdrop Overlay --}}
        <div x-show="show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="close()"
             class="fixed inset-0 bg-black/80 backdrop-blur-sm"></div>

        {{-- Center Modal Content Container --}}
        <div x-show="show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             class="relative z-10 flex max-h-[92vh] max-w-[92vw] flex-col items-center justify-center">

            {{-- Close Button --}}
            <button type="button"
                    @click="close()"
                    aria-label="Duyuruyu Kapat"
                    class="absolute -right-2 -top-10 z-20 flex h-9 w-9 items-center justify-center rounded-full bg-slate-900/90 text-slate-200 shadow-lg ring-1 ring-white/20 transition hover:bg-rose-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-rose-500 sm:-right-4 sm:-top-4">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            {{-- Modal Body --}}
            <div class="relative overflow-hidden rounded-2xl bg-slate-900 border border-white/10 shadow-2xl flex flex-col items-center">
                @if ($imageUrl)
                    {{-- Image Aspect Ratio Preserved --}}
                    <div class="relative max-h-[78vh] max-w-[90vw] overflow-hidden flex items-center justify-center bg-black/40">
                        <img src="{{ $imageUrl }}"
                             alt="{{ $title ?? 'Duyuru Görseli' }}"
                             class="h-auto max-h-[78vh] w-auto max-w-[90vw] object-contain rounded-t-xl {{ empty($buttonUrl) ? 'rounded-b-xl' : '' }}">
                    </div>

                    @if (!empty($buttonUrl))
                        <div class="p-3 bg-slate-900/95 border-t border-white/10 flex items-center justify-center gap-3 w-full">
                            <a href="{{ $buttonUrl }}"
                               @click="close()"
                               class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-rose-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg transition hover:bg-rose-500 hover:scale-[1.02]">
                                <span>{{ $buttonText ?: 'Daha Fazla Bilgi' }}</span>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                            <button type="button"
                                    @click="close()"
                                    class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2.5 text-sm font-medium text-slate-300 hover:bg-white/15 transition">
                                Kapat
                            </button>
                        </div>
                    @endif
                @else
                    {{-- Text Only Modal Content --}}
                    <div class="p-6 sm:p-8 max-w-lg text-center space-y-4 max-h-[85vh] overflow-y-auto">
                        <h3 id="announcement-modal-title" class="text-xl font-bold text-white">
                            {{ $title }}
                        </h3>
                        @if ($message)
                            <p class="text-sm text-slate-300 leading-relaxed whitespace-pre-line text-left sm:text-center">
                                {{ $message }}
                            </p>
                        @endif

                        @if (!empty($buttonUrl))
                            <div class="pt-3 flex flex-wrap items-center justify-center gap-3">
                                <a href="{{ $buttonUrl }}"
                                   @click="close()"
                                   class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-rose-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg transition hover:bg-rose-500 hover:scale-[1.02]">
                                    <span>{{ $buttonText ?: 'İncele' }}</span>
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </a>
                                <button type="button"
                                        @click="close()"
                                        class="inline-flex items-center justify-center rounded-xl bg-white/10 px-5 py-2.5 text-sm font-semibold text-slate-300 hover:bg-white/15 transition">
                                    Kapat
                                </button>
                            </div>
                        @else
                            <div class="pt-2">
                                <button type="button"
                                        @click="close()"
                                        class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:bg-rose-500">
                                    Anladım
                                </button>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
