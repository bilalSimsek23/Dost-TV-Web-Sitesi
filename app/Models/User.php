<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public const ROLES = [
        'super_admin' => 'Süper Admin',
        'administrator' => 'Yönetici',
        'designer' => 'Tasarımcı',
        'editor' => 'Editör',
        'content_manager' => 'İçerik Yöneticisi',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'role_id',
        'is_active',
        'avatar_url',
        'phone',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            $isLastAdmin = $user->exists && self::isLastActiveSuperAdmin($user);

            // Guard: Prevent demoting last active super admin
            if ($isLastAdmin) {
                $targetRole = $user->role_id ? Role::find($user->role_id)?->base_role : $user->role;
                if ($targetRole !== 'super_admin') {
                    $user->role = 'super_admin';
                    $user->role_id = Role::where('slug', 'super-admin')->value('id');
                }

                // Guard: Prevent deactivating last active super admin
                if ($user->isDirty('is_active') && ! $user->is_active) {
                    $user->is_active = true;
                }
            }

            // Synchronize role and role_id
            if ($user->role_id) {
                $roleObj = Role::find($user->role_id);
                if ($roleObj) {
                    $user->role = $roleObj->base_role;
                }
            } elseif (filled($user->role) && ! $user->role_id) {
                $roleId = match ($user->role) {
                    'super_admin' => Role::where('slug', 'super-admin')->value('id'),
                    'administrator' => Role::where('slug', 'yonetici')->value('id'),
                    'editor', 'content_manager', 'designer' => Role::where('slug', 'editor')->value('id'),
                    default => null,
                };
                if ($roleId) {
                    $user->role_id = $roleId;
                }
            }

            // Normalize phone number to +905XXXXXXXXX standard
            if (array_key_exists('phone', $user->attributes)) {
                $user->attributes['phone'] = self::normalizePhone($user->attributes['phone']);
            }

            // Terminate active sessions immediately when deactivated
            if ($user->isDirty('is_active') && ! $user->is_active && $user->exists) {
                \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            // When an inactive user is reactivated, randomize password to require fresh invitation/setup
            if ($user->isDirty('is_active') && $user->is_active && $user->exists && ! (bool) $user->getOriginal('is_active')) {
                $user->password = \Illuminate\Support\Str::random(64);
            }
        });

        static::saved(function (User $user) {
            // If reactivated, send fresh 72h password setup invitation
            if ($user->wasChanged('is_active') && $user->is_active && ! (bool) $user->getOriginal('is_active')) {
                try {
                    app(\App\Services\Auth\UserInvitationService::class)->createInvitation($user, auth()->user());
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Yeniden aktifleştirme daveti oluşturulamadı: {$e->getMessage()}");
                }
            }
        });

        static::deleting(function (User $user) {
            if (self::isLastActiveSuperAdmin($user)) {
                return false; // Prevent deletion of last active super admin
            }

            \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->delete();
            \App\Models\AuditLog::where('user_id', $user->id)->update(['user_id' => null]);
        });
    }

    public function invitations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserInvitation::class, 'user_id');
    }

    public function latestInvitation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserInvitation::class, 'user_id')->latestOfMany();
    }

    public function getInvitationStatusAttribute(): string
    {
        $latest = $this->latestInvitation;
        return $latest ? $latest->status : 'none';
    }

    public function getInvitationStatusLabelAttribute(): string
    {
        $latest = $this->latestInvitation;
        return $latest ? $latest->status_label : '—';
    }

    public function getInvitationStatusColorAttribute(): string
    {
        $latest = $this->latestInvitation;
        return $latest ? $latest->status_color : 'gray';
    }

    public function sendPasswordResetNotification($token): void
    {
        if (! $this->is_active) {
            return;
        }

        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }

    public static function isLastActiveSuperAdmin(User $user): bool
    {
        $wasSuperAdmin = ($user->getOriginal('role') === 'super_admin')
            || ($user->baseRole() === 'super_admin')
            || ($user->role_id && Role::find($user->role_id)?->base_role === 'super_admin');

        $wasActive = (bool) ($user->getOriginal('is_active') ?? $user->is_active);

        if (! $wasSuperAdmin || ! $wasActive) {
            return false;
        }

        $activeSuperAdminsCount = self::where('role', 'super_admin')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->count();

        return $activeSuperAdminsCount <= 1;
    }

    public static function normalizePhone(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $value);

        // If provided with country code (e.g. 905321234567)
        if (str_starts_with($digits, '90') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '5')) {
            return '+90' . $digits;
        }

        if (str_starts_with($value, '+90') && strlen($digits) === 12) {
            return '+90' . substr($digits, 2);
        }

        return $value;
    }

    public static function formatPhoneForDisplay(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $value);
        if (str_starts_with($digits, '90') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 10) {
            return sprintf(
                '+90 %s %s %s %s',
                substr($digits, 0, 3),
                substr($digits, 3, 3),
                substr($digits, 6, 2),
                substr($digits, 8, 2)
            );
        }

        return $value;
    }

    public function getFormattedPhoneAttribute(): ?string
    {
        return self::formatPhoneForDisplay($this->phone);
    }

    public function roleModel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function baseRole(): string
    {
        if ($this->relationLoaded('roleModel') && $this->roleModel) {
            return (string) $this->roleModel->base_role;
        }

        if ($this->role_id) {
            $roleObj = Role::find($this->role_id);
            if ($roleObj) {
                return (string) $roleObj->base_role;
            }
        }

        $rawRole = $this->attributes['role'] ?? null;

        if (is_string($rawRole) && filled($rawRole)) {
            return $rawRole;
        }

        return 'editor';
    }

    public function isSuperAdmin(): bool
    {
        return $this->baseRole() === 'super_admin';
    }

    public function isAdministrator(): bool
    {
        return $this->baseRole() === 'administrator';
    }

    public function isEditor(): bool
    {
        return $this->baseRole() === 'editor';
    }

    public function hasRole(string $role): bool
    {
        $base = $this->baseRole();
        $rawRole = is_string($this->attributes['role'] ?? null) ? $this->attributes['role'] : null;
        $slug = $this->roleModel?->slug;

        return $base === $role || $rawRole === $role || ($slug && $slug === $role);
    }

    /**
     * @param  array<int, string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        $base = $this->baseRole();
        $rawRole = is_string($this->attributes['role'] ?? null) ? $this->attributes['role'] : null;
        $slug = $this->roleModel?->slug;

        return in_array($base, $roles, true)
            || in_array($rawRole, $roles, true)
            || ($slug && in_array($slug, $roles, true));
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $base = $this->baseRole();
        $validBaseRoles = array_keys(Role::BASE_ROLES);

        return (in_array($base, $validBaseRoles, true) || array_key_exists($this->attributes['role'] ?? '', self::ROLES))
            && (bool) $this->is_active
            && ! $this->trashed();
    }
}
