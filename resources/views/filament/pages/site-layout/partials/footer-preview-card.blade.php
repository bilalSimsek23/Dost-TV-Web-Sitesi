<div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-200 dark:border-white/10 pb-4">
        <div>
            <h3 class="text-base font-bold text-gray-950 dark:text-white flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-eye" class="h-5 w-5 text-amber-500" />
                Public Footer Önizlemesi
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Ziyaretçilerin kamuya açık sitede göreceği gerçek 3 sütunlu footer görünümü.
            </p>
        </div>
        <div>
            <x-filament::button
                tag="a"
                href="{{ route('home') }}"
                target="_blank"
                color="gray"
                outlined
                icon="heroicon-o-arrow-top-right-on-square"
                size="sm"
            >
                Ana Sayfada İncele
            </x-filament::button>
        </div>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-950 overflow-hidden shadow-2xl p-4 sm:p-6">
        <x-site.footer
            :preview="true"
            :phone="$phone"
            :email="$email"
            :facebook-url="$facebookUrl"
            :instagram-url="$instagramUrl"
            :x-url="$xUrl"
            :youtube-url="$youtubeUrl"
            :whatsapp-url="$whatsappUrl"
            :telegram-url="$telegramUrl"
            :copyright-text="$copyrightText"
        />
    </div>
</div>
