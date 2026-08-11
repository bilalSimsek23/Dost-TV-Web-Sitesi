<div class="p-5 mb-4 bg-white border border-gray-200 rounded-xl dark:bg-gray-900 dark:border-gray-800 shadow-sm space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <!-- Title and Season -->
        <div class="space-y-1">
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                    {{ $program->name }}
                </h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ ($seasonParam === 'none' || blank($seasonParam)) ? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' : 'bg-primary-50 text-primary-700 dark:bg-primary-950/60 dark:text-primary-300 border border-primary-200 dark:border-primary-800' }}">
                    {{ $seasonLabel }}
                </span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Sezon ve Playlist Yönetim Paneli
            </p>
        </div>

        <!-- Badges and Info -->
        <div class="flex flex-wrap items-center gap-3 text-xs">
            <!-- Total Episodes Count -->
            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-50 dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700">
                <span class="font-semibold text-gray-500 dark:text-gray-400">Toplam Bölüm:</span>
                <span class="font-bold font-mono text-gray-900 dark:text-white text-sm">
                    {{ $episodesCount }}
                </span>
            </div>

            <!-- Playlist Status -->
            @if($playlistStatus === 'connected')
                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Playlist Bağlı
                </span>
            @elseif($playlistStatus === 'imported')
                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    Playlistten Aktarıldı
                </span>
            @else
                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700">
                    Playlist Yok
                </span>
            @endif

            <!-- Playlist URL Button -->
            @if(filled($program->youtube_playlist_url))
                <a href="{{ $program->youtube_playlist_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-500 text-white font-medium text-xs rounded-lg transition select-none shadow-sm">
                    <span>▶</span>
                    <span>YouTube'da Aç ↗</span>
                </a>
            @endif

            <!-- Last Sync Info -->
            @if($lastSync)
                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-50 dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                    <span class="text-gray-400">Son Senkron:</span>
                    <span class="font-mono font-medium text-gray-800 dark:text-gray-200">
                        {{ $lastSync->format('d.m.Y H:i') }}
                    </span>
                </div>
            @endif
        </div>
    </div>
</div>
