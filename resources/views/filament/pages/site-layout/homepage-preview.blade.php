<div class="rounded-xl border border-slate-800 bg-slate-950 p-4 text-white overflow-hidden">
    <div class="mb-4 pb-2 border-b border-slate-800 flex items-center justify-between text-xs text-slate-400">
        <span class="font-medium text-slate-300">Public Önizleme (Canlı Form Durumu)</span>
        <span class="rounded bg-slate-800 px-2 py-0.5 text-[10px]">Gerçek Bileşenler</span>
    </div>

    <x-site.homepage-sections
        :sections="$homepageSections"
        :banners="$banners"
        :settings="$settings"
        :today-schedule="$todaySchedule"
        :featured-programs="$featuredPrograms"
        :preview="true"
    />
</div>
