<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Page extends Model
{
    public const STATUSES = [
        'draft' => 'Taslak',
        'review' => 'İncelemede',
        'published' => 'Yayında',
        'archived' => 'Arşivlendi',
    ];

    public const TEMPLATES = [
        'default' => 'Varsayılan',
        'full_width' => 'Tam Genişlik',
        'corporate' => 'Kurumsal',
        'landing' => 'Kampanya/İniş Sayfası',
        'contact' => 'İletişim',
        'donation' => 'Bağış',
        'video_archive' => 'Video Arşivi',
        'program_listing' => 'Program Listesi',
        'schedule' => 'Yayın Akışı',
        'live' => 'Canlı Yayın',
        'blank' => 'Boş',
    ];

    protected $fillable = [
        'title',
        'slug',
        'content',
        'show_in_menu',
        'sort_order',
        'parent_id',
        'page_type',
        'template',
        'status',
        'published_at',
        'show_in_header',
        'show_in_footer',
        'menu_group',
        'menu_location',
        'seo_title',
        'seo_description',
        'og_image',
        'custom_css',
        'settings',
    ];

    protected $casts = [
        'show_in_menu' => 'boolean',
        'show_in_header' => 'boolean',
        'show_in_footer' => 'boolean',
        'sort_order' => 'integer',
        'published_at' => 'datetime',
        'settings' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Page $page) {
            if (! array_key_exists('page_type', $page->getAttributes())) {
                $page->page_type = 'corporate';
            }
            if (! array_key_exists('status', $page->getAttributes())) {
                $page->status = 'published';
            }
        });

        static::saving(function (Page $page) {
            if (blank($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });

        static::deleting(function (Page $page) {
            $protectedSystemSlugs = [
                'dost-tv-yayin-ilkeleri',
                'yayinci-kunye-bilgisi',
                'neden-dost-tv',
                'dost-vakfi-hesap-numaralari',
                'kisisel-verilerin-korunmasi-ve-gizlilik-politikasi',
                'iletisim',
            ];

            if (in_array($page->slug, $protectedSystemSlugs, true) || $page->template === 'contact') {
                throw new \RuntimeException("Sistem kurumsal sayfası ('{$page->title}') silinemez.");
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Page::class, 'parent_id')->orderBy('sort_order');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function getGoogleMapsEmbedUrlAttribute(): ?string
    {
        $settings = $this->settings ?? [];
        $embed = $settings['map_embed_url'] ?? null;
        if (blank($embed)) {
            return null;
        }

        if (preg_match('/src=["\']([^"\']+)["\']/', $embed, $matches)) {
            $url = $matches[1];
        } else {
            $url = trim($embed);
        }

        $allowedPrefixes = [
            'https://www.google.com/maps/embed',
            'https://maps.google.com/maps',
            'https://www.google.com/maps?',
            'https://maps.google.com/?',
        ];

        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($url, $prefix)) {
                return $url;
            }
        }

        return null;
    }

    public function isMapEnabled(): bool
    {
        $settings = $this->settings ?? [];
        return (bool) ($settings['map_enabled'] ?? true);
    }

    public function getMapTitle(): string
    {
        $settings = $this->settings ?? [];
        return $settings['map_title'] ?? 'DOST TV';
    }

    public function getMapHeight(): int
    {
        $settings = $this->settings ?? [];
        $height = (int) ($settings['map_height'] ?? 320);
        return ($height >= 150 && $height <= 800) ? $height : 320;
    }

    public function getResolvedDesignAttribute(): array
    {
        return \App\Support\PageDesignResolver::resolve($this);
    }
}
