<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Menu extends Model
{
    /** @use HasFactory<\Database\Factories\MenuFactory> */
    use HasFactory;

    public const LOCATIONS = [
        'header_primary' => 'Header - Birincil',
        'header_secondary' => 'Header - İkincil',
        'mobile' => 'Mobil Menü',
        'footer_primary' => 'Footer - Birincil',
        'footer_secondary' => 'Footer - İkincil',
        'footer_legal' => 'Footer - Yasal',
        'sidebar' => 'Kenar Çubuğu',
    ];

    protected $fillable = [
        'name',
        'location',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Menu $menu) {
            if (! $menu->is_active) {
                return;
            }

            $alreadyActiveExists = static::query()
                ->where('location', $menu->location)
                ->where('is_active', true)
                ->when($menu->exists, fn ($query) => $query->whereKeyNot($menu->getKey()))
                ->exists();

            if ($alreadyActiveExists) {
                throw ValidationException::withMessages([
                    'is_active' => "'{$menu->location}' konumu için zaten aktif bir menü var. Önce onu pasife alın.",
                ]);
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function rootItems(): HasMany
    {
        return $this->items()->whereNull('parent_id')->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
