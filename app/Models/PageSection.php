<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSection extends Model
{
    public const SECTION_TYPES = [
        'hero' => 'Manşet',
        'rich_text' => 'Zengin Metin',
        'image_text' => 'Görsel + Metin',
        'cards' => 'Kartlar',
        'program_grid' => 'Program Izgarası',
        'episode_grid' => 'Bölüm Izgarası',
        'category_grid' => 'Kategori Izgarası',
        'live_player' => 'Canlı TV Oynatıcı',
        'radio_player' => 'Radyo Oynatıcı',
        'schedule' => 'Yayın Akışı',
        'banner' => 'Banner',
        'call_to_action' => 'Aksiyon Çağrısı',
        'donation' => 'Bağış',
        'contact' => 'İletişim',
        'faq' => 'Sık Sorulan Sorular',
        'gallery' => 'Galeri',
        'video' => 'Video',
        'statistics' => 'İstatistikler',
        'social_links' => 'Sosyal Medya Bağlantıları',
        'spacer' => 'Boşluk',
        'divider' => 'Ayırıcı',
        'custom_html' => 'Özel HTML',
    ];

    protected $fillable = [
        'page_id',
        'section_type',
        'title',
        'subtitle',
        'content',
        'settings',
        'background_color',
        'text_color',
        'background_image',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'content' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
