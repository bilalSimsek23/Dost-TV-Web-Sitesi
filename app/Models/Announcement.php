<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use HasFactory;

    public const TYPES = [
        'general' => 'Genel Bilgilendirme',
        'friday' => 'Cuma Mesajı',
        'kandil' => 'Kandil',
        'ramadan' => 'Ramazan',
        'holiday' => 'Bayram',
        'broadcast' => 'Yayın Bilgilendirmesi',
        'maintenance' => 'Teknik Duyuru',
        'other' => 'Diğer',
    ];

    public const PLACEMENTS = [
        'home' => 'Ana Sayfa',
        'live_tv' => 'Canlı TV',
        'live_radio' => 'Canlı Radyo',
        'schedule' => 'Yayın Akışı',
        'global' => 'Tüm Site',
    ];

    protected $fillable = [
        'title',
        'message',
        'type',
        'announcement_type_id',
        'image',
        'button_text',
        'button_url',
        'placement',
        'starts_at',
        'ends_at',
        'is_pinned',
        'is_active',
        'sort_order',
        'created_by',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Announcement $announcement) {
            if (empty($announcement->created_by) && auth()->check()) {
                $announcement->created_by = auth()->id();
            }
        });

        static::saving(function (Announcement $announcement) {
            if ($announcement->isDirty('image') && ! empty($announcement->image)) {
                $rawInput = $announcement->image;

                $processor = app(\App\Services\Media\AnnouncementImageProcessor::class);
                $processedPath = $processor->process($rawInput);
                $announcement->image = $processedPath;

                // Clean up temporary raw input file after successful processing
                if ($rawInput !== $processedPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($rawInput)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($rawInput);
                }
            }
        });

        static::saved(function (Announcement $announcement) {
            $oldImage = $announcement->getOriginal('image');
            $newImage = $announcement->image;

            if ($oldImage && $oldImage !== $newImage && \Illuminate\Support\Facades\Storage::disk('public')->exists($oldImage)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldImage);
            }
        });

        static::deleted(function (Announcement $announcement) {
            if (! empty($announcement->image) && \Illuminate\Support\Facades\Storage::disk('public')->exists($announcement->image)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($announcement->image);
            }
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function announcementType(): BelongsTo
    {
        return $this->belongsTo(AnnouncementType::class, 'announcement_type_id');
    }

    public function getTypeNameAttribute(): string
    {
        if ($this->announcementType) {
            return $this->announcementType->name;
        }

        return self::TYPES[$this->type] ?? ($this->type ?? 'Genel Bilgilendirme');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrentlyVisible($query)
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }

    public function scopeForPlacement($query, string $placement)
    {
        return $query->whereIn('placement', [$placement, 'global']);
    }

    public function scopeAdminOrdered($query)
    {
        $now = now()->toDateTimeString();

        return $query->orderByRaw("
            CASE
                WHEN is_pinned = 1 THEN 0
                WHEN ends_at IS NOT NULL AND ends_at < '{$now}' THEN 4
                WHEN is_active = 0 THEN 3
                WHEN starts_at IS NOT NULL AND starts_at > '{$now}' THEN 2
                ELSE 1
            END ASC
        ")
        ->orderByRaw("COALESCE(starts_at, created_at) ASC")
        ->orderBy('created_at', 'desc');
    }

    public function getFormattedDateRangeAttribute(): string
    {
        $formatDate = function ($date) {
            if (! $date) {
                return null;
            }
            return $date->locale('tr')->translatedFormat('d F Y');
        };

        $startStr = $formatDate($this->starts_at);
        $endStr = $formatDate($this->ends_at);

        if ($startStr && $endStr) {
            return "{$startStr} - {$endStr}";
        }

        if ($startStr) {
            return "{$startStr}’dan itibaren";
        }

        if ($endStr) {
            return "{$endStr}’ya kadar";
        }

        return 'Süresiz';
    }

    public function getAdminStatusAttribute(): array
    {
        // 1. ends_at geçmişse → Süresi Doldu
        if ($this->ends_at && $this->ends_at->isPast()) {
            return ['label' => 'Süresi Doldu', 'color' => 'gray'];
        }

        // 2. is_active = false ise → Taslak
        if (! $this->is_active) {
            return ['label' => 'Taslak', 'color' => 'info'];
        }

        // 3. starts_at gelecekteyse → Planlandı
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return ['label' => 'Planlandı', 'color' => 'warning'];
        }

        // 4. diğer tüm durumlarda → Aktif
        return ['label' => 'Aktif', 'color' => 'success'];
    }
}
