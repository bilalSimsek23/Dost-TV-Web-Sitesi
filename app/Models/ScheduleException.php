<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleException extends Model
{
    use HasFactory;

    protected $fillable = [
        'exception_date',
        'name',
        'description',
        'override_type',
        'status',
    ];

    protected $casts = [
        'exception_date' => 'date',
    ];

    public const OVERRIDE_TYPES = [
        'replace_all' => 'O Günkü Tüm Akışı Değiştir (Ez)',
        'additional' => 'Mevcut Akışa Ekle',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ScheduleExceptionItem::class)->orderBy('start_time');
    }
}
