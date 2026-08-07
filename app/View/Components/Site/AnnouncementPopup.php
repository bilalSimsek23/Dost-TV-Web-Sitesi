<?php

namespace App\View\Components\Site;

use App\Models\Announcement;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AnnouncementPopup extends Component
{
    public ?Announcement $announcement = null;
    public ?string $imageUrl = null;

    public function __construct(
        public bool $preview = false,
        ?Announcement $announcement = null,
        public ?string $title = null,
        public ?string $message = null,
        public mixed $image = null
    ) {
        if ($this->preview) {
            $this->title = $title ?? ($announcement ? $announcement->title : 'Duyuru Başlığı');
            $this->message = $message ?? ($announcement ? $announcement->message : null);
            $rawImg = $image ?? ($announcement ? $announcement->image : null);

            $this->imageUrl = self::resolveImageUrl($rawImg);
        } else {
            $this->announcement = self::getActivePopupAnnouncement();
            if ($this->announcement) {
                $this->title = $this->announcement->title;
                $this->message = $this->announcement->message;
                $this->imageUrl = self::resolveImageUrl($this->announcement->image);
            }
        }
    }

    public static function getActivePopupAnnouncement(): ?Announcement
    {
        return Announcement::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderByDesc('is_pinned')
            ->latest()
            ->first();
    }

    public static function resolveImageUrl(mixed $image): ?string
    {
        if (empty($image)) {
            return null;
        }

        $rawImg = is_array($image) ? reset($image) : $image;

        if (is_object($rawImg) && method_exists($rawImg, 'temporaryUrl')) {
            try {
                return $rawImg->temporaryUrl();
            } catch (\Throwable $e) {
                return null;
            }
        }

        if (is_string($rawImg) && !empty(trim($rawImg))) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($rawImg);
        }

        return null;
    }

    public function render(): View
    {
        return view('components.site.announcement-popup');
    }
}
