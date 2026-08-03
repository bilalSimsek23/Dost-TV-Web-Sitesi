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
        static::saving(function (Page $page) {
            if (blank($page->slug)) {
                $page->slug = Str::slug($page->title);
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
}
