<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class AnalyticsIntegration extends Model
{
    protected $fillable = [
        'provider',
        'is_enabled',
        'property_id',
        'credentials',
        'last_synced_at',
        'last_error',
        'metrics_snapshot',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'last_synced_at' => 'datetime',
        'metrics_snapshot' => 'array',
    ];

    /**
     * Encrypted like the built-in 'encrypted' cast, but tolerates values that
     * can no longer be decrypted (e.g. after an APP_KEY rotation/migration)
     * instead of throwing and taking down the whole page with them.
     */
    protected function credentials(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (blank($value)) {
                    return null;
                }

                try {
                    return Crypt::decryptString($value);
                } catch (DecryptException $e) {
                    Log::warning('AnalyticsIntegration.credentials could not be decrypted (APP_KEY mismatch or corrupted data); treating as empty.', [
                        'id' => $this->id,
                        'provider' => $this->provider,
                    ]);

                    return null;
                }
            },
            set: fn (?string $value) => $value === null ? null : Crypt::encryptString($value),
        );
    }
}
