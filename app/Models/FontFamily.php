<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FontFamily extends Model
{
    public const SOURCE_TYPES = [
        'system' => 'Sistem Fontu',
        'google' => 'Google Fonts',
        'custom' => 'Özel Yükleme',
    ];

    public const FALLBACK_STACK = 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';

    protected $fillable = [
        'name',
        'slug',
        'source_type',
        'font_url',
        'local_path',
        'weights',
        'styles',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'weights' => 'array',
        'styles' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (FontFamily $font) {
            if (blank($font->slug)) {
                $font->slug = Str::slug($font->name);
            }
        });

        static::saved(function (FontFamily $font) {
            if ($font->is_default) {
                static::query()->whereKeyNot($font->getKey())->update(['is_default' => false]);
            }
        });

        static::deleting(function (FontFamily $font) {
            if ($font->is_default) {
                throw ValidationException::withMessages([
                    'is_default' => 'Varsayılan font silinemez. Önce başka bir fontu varsayılan yapın.',
                ]);
            }
        });
    }

    /**
     * CSS-safe font-family stack: the font itself plus the shared fallback,
     * so a missing/broken font never leaves text unstyled.
     */
    public function getCssStackAttribute(): string
    {
        return '"'.$this->name.'", '.self::FALLBACK_STACK;
    }
}
