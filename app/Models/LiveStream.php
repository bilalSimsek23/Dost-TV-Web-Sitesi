<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LiveStream extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'stream_type',
        'stream_url',
        'embed_code',
        'backup_url',
        'poster_image',
        'is_active',
        'is_currently_live',
        'is_primary',
        'start_time',
        'end_time',
        'button_text',
        'show_watch_button',
        'open_in_new_tab',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_currently_live' => 'boolean',
        'is_primary' => 'boolean',
        'show_watch_button' => 'boolean',
        'open_in_new_tab' => 'boolean',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'sort_order' => 'integer',
    ];

    public const STREAM_TYPES = [
        'hls' => 'HLS Stream (.m3u8)',
        'youtube' => 'YouTube Live / Video',
        'iframe' => 'iFrame / Embed Code',
        'custom' => 'Özel URL / Radyo',
    ];

    protected static function booted(): void
    {
        static::saving(function (LiveStream $stream) {
            // If YouTube URL, auto-convert watch URL to embed URL if needed
            if ($stream->stream_type === 'youtube' && filled($stream->stream_url)) {
                if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $stream->stream_url, $matches)) {
                    $youtubeId = $matches[1];
                    $stream->embed_code = '<iframe src="https://www.youtube.com/embed/' . $youtubeId . '?autoplay=1" frameborder="0" allowfullscreen></iframe>';
                }
            }

            // Enforce single primary stream
            if ($stream->is_primary) {
                static::where('id', '!=', $stream->id)->update(['is_primary' => false]);

                // Sync with SiteSetting so public player uses this stream
                $siteSetting = SiteSetting::first();
                if ($siteSetting) {
                    $siteSetting->update([
                        'live_tv_type' => $stream->stream_type,
                        'live_tv_url' => $stream->stream_url,
                    ]);
                }
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}
