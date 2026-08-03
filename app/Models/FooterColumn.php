<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FooterColumn extends Model
{
    public const COLUMN_TYPES = [
        'menu' => 'Menü',
        'text' => 'Metin',
        'contact' => 'İletişim',
        'social' => 'Sosyal Medya',
        'logo' => 'Logo',
        'newsletter' => 'Bülten',
        'custom' => 'Özel',
    ];

    protected $fillable = [
        'title',
        'column_type',
        'menu_id',
        'content',
        'width',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'content' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
