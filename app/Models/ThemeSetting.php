<?php

namespace App\Models;

use App\Support\SiteCache;
use Illuminate\Database\Eloquent\Model;

class ThemeSetting extends Model
{
    public const GROUPS = [
        'brand' => 'Marka',
        'colors' => 'Renkler',
        'typography' => 'Tipografi',
        'spacing' => 'Boşluk/Yerleşim',
        'borders' => 'Kenar ve Köşe',
        'shadows' => 'Gölgeler',
        'buttons' => 'Butonlar',
        'cards' => 'Kartlar',
        'header' => 'Header',
        'mobile_menu' => 'Mobil Menü',
        'footer' => 'Footer',
        'pages' => 'Sayfalar',
        'player' => 'Oynatıcı',
        'forms' => 'Formlar',
        'accessibility' => 'Erişilebilirlik',
        'animations' => 'Animasyonlar',
        'seo' => 'SEO',
        'social' => 'Sosyal Medya',
    ];

    public const VALUE_TYPES = [
        'color' => 'Renk',
        'font' => 'Font',
        'number' => 'Sayı',
        'text' => 'Metin',
        'boolean' => 'Evet/Hayır',
        'select' => 'Seçenek Listesi',
    ];

    protected $fillable = [
        'key',
        'group',
        'value',
        'value_type',
        'label',
        'description',
        'options',
        'is_public',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_public' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => SiteCache::forgetTheme());
        static::deleted(fn () => SiteCache::forgetTheme());
    }

    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
}
