<?php

namespace App\View\Components\Site;

use App\Models\Announcement;
use App\Services\Announcement\AnnouncementService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AnnouncementPopup extends Component
{
    public ?Announcement $announcement = null;
    public ?string $imageUrl = null;
    public ?string $buttonText = null;
    public ?string $buttonUrl = null;

    public function __construct(
        public bool $preview = false,
        ?Announcement $announcement = null,
        public ?string $title = null,
        public ?string $message = null,
        public mixed $image = null,
        ?string $buttonText = null,
        ?string $buttonUrl = null
    ) {
        if ($this->preview) {
            $this->title = $title ?? ($announcement ? $announcement->title : 'Duyuru Başlığı');
            $this->message = $message ?? ($announcement ? $announcement->message : null);
            $rawImg = $image ?? ($announcement ? $announcement->image : null);
            $this->buttonText = $buttonText ?? ($announcement ? $announcement->button_text : null);
            $this->buttonUrl = $buttonUrl ?? ($announcement ? $announcement->button_url : null);

            $this->imageUrl = self::resolveImageUrl($rawImg);
        } else {
            $this->announcement = self::getActivePopupAnnouncement();
            if ($this->announcement) {
                $this->title = $this->announcement->title;
                $this->message = $this->announcement->message;
                $this->buttonText = $this->announcement->button_text;
                $this->buttonUrl = $this->announcement->button_url;
                $this->imageUrl = self::resolveImageUrl($this->announcement->image);
            }
        }
    }

    public static function getActivePopupAnnouncement(?string $placement = null): ?Announcement
    {
        $placement = $placement ?? AnnouncementService::resolveCurrentPlacement();

        return app(AnnouncementService::class)->getTopPopupForPlacement($placement);
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
