<div class="p-4 bg-slate-900/60 rounded-xl border border-rose-500/20 shadow-inner flex flex-col items-center justify-center min-h-[220px]"
     x-data="{
         initEmbed() {
             if (window.instgrm) {
                 window.instgrm.Embeds.process();
             } else {
                 const script = document.createElement('script');
                 script.src = '//platform.instagram.com/en_US/embeds.js';
                 script.async = true;
                 script.onload = () => {
                     if (window.instgrm) window.instgrm.Embeds.process();
                 };
                 document.body.appendChild(script);
             }
         }
     }"
     x-init="$nextTick(() => initEmbed())">

    @if(!empty($embedHtml))
        <div class="w-full max-w-[360px] mx-auto overflow-hidden rounded-xl shadow-lg bg-black flex justify-center">
            {!! $embedHtml !!}
        </div>
    @elseif(!empty($permalink))
        <blockquote class="instagram-media" data-instgrm-permalink="{{ $permalink }}" data-instgrm-version="14" style="background:#FFF; border:0; border-radius:12px; margin: 1px; max-width:360px; min-width:280px; padding:0; width:99.375%;">
            <div style="padding:16px;">
                <a href="{{ $permalink }}" target="_blank" class="text-rose-400 font-bold hover:underline">Instagram'da Görüntüle</a>
            </div>
        </blockquote>
    @else
        <div class="text-center py-6 text-slate-400 text-xs">
            Henüz Instagram içeriği yüklenmedi.
        </div>
    @endif

    <div class="mt-3 flex items-center justify-center space-x-2">
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-sm">
            ✓ Instagram İçeriği Doğrulandı
        </span>
    </div>
</div>
