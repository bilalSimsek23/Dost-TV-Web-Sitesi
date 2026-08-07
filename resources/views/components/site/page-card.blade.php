@props([
    'title' => '',
    'content' => '',
    'preview' => false,
])

<div class="mx-auto max-w-3xl px-4 py-8 bg-slate-950 rounded-xl border border-slate-800 text-slate-100 shadow-xl">
    <h1 class="text-3xl font-black text-white tracking-tight">{{ $title ?: 'Sayfa Başlığı' }}</h1>
    <div class="prose prose-invert mt-6 max-w-none prose-headings:text-white prose-a:text-rose-400 text-slate-300">
        {!! $content ?: '<p class="text-slate-500 italic">İçerik henüz girilmedi.</p>' !!}
    </div>
</div>
