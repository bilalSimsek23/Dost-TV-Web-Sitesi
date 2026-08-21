<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Role extends Model
{
    use HasFactory;

    public const BASE_ROLES = [
        'super_admin' => 'Süper Admin',
        'administrator' => 'Yönetici',
        'editor' => 'Editör',
    ];

    protected $attributes = [
        'is_system' => false,
        'is_active' => true,
    ];

    protected $fillable = [
        'name',
        'slug',
        'base_role',
        'description',
        'is_active',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Role $role) {
            if (blank($role->slug) && filled($role->name)) {
                $role->slug = Str::slug($role->name);
            }

            // Guard against changing base_role or name or deactivating system roles
            if ($role->exists && $role->is_system) {
                if ($role->isDirty('name')) {
                    $role->name = $role->getOriginal('name');
                }
                if ($role->isDirty('base_role')) {
                    $role->base_role = $role->getOriginal('base_role');
                }
                if ($role->isDirty('slug')) {
                    $role->slug = $role->getOriginal('slug');
                }
                if ($role->isDirty('is_system')) {
                    $role->is_system = true;
                }
                if ($role->isDirty('is_active') && ! $role->is_active) {
                    $role->is_active = true;
                }
            }
        });

        static::deleting(function (Role $role) {
            if ($role->is_system || $role->users()->exists()) {
                return false; // Prevent deletion of system roles or roles with assigned users
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function isSystem(): bool
    {
        return (bool) $this->is_system;
    }
}
