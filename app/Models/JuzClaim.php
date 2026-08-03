<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JuzClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'khatm_id',
        'juz_number',
        'status',
        'claimed_by_name',
        'claimed_by_phone',
        'claimed_by_email',
        'claimed_at',
        'notes',
    ];

    protected $casts = [
        'juz_number' => 'integer',
        'claimed_at' => 'datetime',
    ];

    public const STATUSES = [
        'empty' => 'Boş (Atanmadı)',
        'assigned' => 'Atandı (Okunuyor)',
        'completed' => 'Tamamlandı (Okundu)',
    ];

    public function khatm(): BelongsTo
    {
        return $this->belongsTo(Khatm::class);
    }
}
