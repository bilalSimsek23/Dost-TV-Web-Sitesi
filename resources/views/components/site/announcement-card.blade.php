@props([
    'announcement' => null,
    'title' => null,
    'message' => null,
    'image' => null,
])

@php
    $title = $title ?? ($announcement ? $announcement->title : 'Duyuru Başlığı');
    $message = $message ?? ($announcement ? $announcement->message : null);
    $image = $image ?? ($announcement ? $announcement->image : null);
    $imgUrl = null;

    if (!empty($image)) {
        $rawImage = is_array($image) ? reset($image) : $image;
        if (is_object($rawImage) && method_exists($rawImage, 'temporaryUrl')) {
            try {
                $imgUrl = $rawImage->temporaryUrl();
            } catch (\Throwable $e) {
                $imgUrl = null;
            }
        } elseif (is_string($rawImage) && !empty(trim($rawImage))) {
            $imgUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($rawImage);
        }
    }
@endphp

<div class="w-full max-w-[1100px] mx-auto overflow-hidden rounded-2xl border border-slate-700/80 shadow-2xl bg-slate-950 relative"
     style="width: 100%; max-width: 1100px; aspect-ratio: 16 / 9; position: relative; overflow: hidden; border-radius: 16px; background: #0f172a;">

    @if($imgUrl)
        <!-- Case 1: Görsel VARSA -> Yalnızca Görsel Önizlemesi -->

        <!-- Blurred Background Layer -->
        <div style="position: absolute; inset: 0; width: 100%; height: 100%; background-image: url('{{ $imgUrl }}'); background-size: cover; background-position: center; filter: blur(28px); transform: scale(1.15); opacity: 0.65; pointer-events: none;"></div>

        <!-- Dark Overlay -->
        <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.35); pointer-events: none;"></div>

        <!-- Centered 3:4 Main Image ONLY -->
        <div style="position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); height: 88%; aspect-ratio: 3 / 4; width: auto; z-index: 5;">
            <img src="{{ $imgUrl }}"
                 alt="{{ $title }}"
                 style="height: 100%; aspect-ratio: 3 / 4; width: auto; object-fit: cover; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.65); border: 1px solid rgba(255,255,255,0.15);">
        </div>
    @else
        <!-- Case 2: Görsel YOKSA -> Başlık + (Varsa) Kısa Mesaj -->

        <!-- Dark Sade Background Scene (No Blur) -->
        <div style="position: absolute; inset: 0; background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #020617 100%); pointer-events: none;"></div>

        <!-- Centered Title + Message Container -->
        <div style="position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); width: 85%; max-width: 750px; text-align: center; z-index: 5; padding: 24px;">
            <h2 style="margin: 0; font-size: 28px; font-weight: 800; color: #ffffff; line-height: 1.35; letter-spacing: -0.02em;">
                {{ $title }}
            </h2>

            @if(!empty(trim((string) $message)))
                <p style="margin: 16px 0 0 0; font-size: 16px; color: #cbd5e1; line-height: 1.6; font-weight: 400; max-width: 650px; margin-left: auto; margin-right: auto;">
                    {{ $message }}
                </p>
            @endif
        </div>
    @endif
</div>
