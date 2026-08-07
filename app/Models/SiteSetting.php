<?php

namespace App\Models;

use App\Support\SiteCache;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'site_name',
        'logo',
        'favicon',
        'logo_alt_text',
        'live_button_text',
        'live_button_is_visible',
        'search_is_visible',
        'header_is_sticky',
        'footer_logo',
        'footer_description',
        'phone',
        'email',
        'address',
        'kep_address',
        'facebook_url',
        'instagram_url',
        'x_url',
        'youtube_url',
        'whatsapp_url',
        'telegram_url',
        'copyright_text',
        'footer_show_socials',
        'footer_show_contact',
        'footer_show_bank_link',
        'custom_css',
        'homepage_sections',
        'live_tv_type',
        'live_tv_url',
        'live_tv_title',
        'live_tv_description',
        'live_tv_backup_url',
        'live_tv_poster',
        'live_tv_maintenance_message',
        'live_tv_error_message',
        'live_tv_is_active',
        'live_tv_is_public',
        'radio_stream_url',
        'radio_name',
        'radio_description',
        'radio_backup_url',
        'radio_image',
        'radio_maintenance_message',
        'radio_error_message',
        'radio_is_active',
        'radio_is_public',
    ];

    protected $casts = [
        'live_button_is_visible' => 'boolean',
        'search_is_visible' => 'boolean',
        'header_is_sticky' => 'boolean',
        'footer_show_socials' => 'boolean',
        'footer_show_contact' => 'boolean',
        'footer_show_bank_link' => 'boolean',
        'live_tv_is_active' => 'boolean',
        'live_tv_is_public' => 'boolean',
        'radio_is_active' => 'boolean',
        'radio_is_public' => 'boolean',
        'homepage_sections' => 'array',
    ];

    public const CANONICAL_HOMEPAGE_SECTIONS = [
        'hero' => 'Hero Slider',
        'live_intro' => 'Canlı Yayın ve Tanıtım',
        'today_schedule' => 'Bugünün Yayın Akışı',
        'featured_programs' => 'Öne Çıkan Programlar',
    ];

    public static function getDefaultHomepageSections(): array
    {
        return [
            ['key' => 'hero', 'visible' => true],
            ['key' => 'live_intro', 'visible' => true],
            ['key' => 'today_schedule', 'visible' => true],
            ['key' => 'featured_programs', 'visible' => true],
        ];
    }

    public function getNormalizedHomepageSectionsAttribute(): array
    {
        $raw = $this->homepage_sections;
        if (! is_array($raw)) {
            return static::getDefaultHomepageSections();
        }

        $canonicalKeys = array_keys(self::CANONICAL_HOMEPAGE_SECTIONS);
        $normalized = [];
        $seenKeys = [];

        foreach ($raw as $item) {
            if (! is_array($item) || ! isset($item['key'])) {
                continue;
            }

            $key = (string) $item['key'];
            if (! in_array($key, $canonicalKeys, true) || isset($seenKeys[$key])) {
                continue;
            }

            $seenKeys[$key] = true;
            $normalized[] = [
                'key' => $key,
                'visible' => (bool) ($item['visible'] ?? true),
            ];
        }

        foreach ($canonicalKeys as $canonicalKey) {
            if (! isset($seenKeys[$canonicalKey])) {
                $normalized[] = [
                    'key' => $canonicalKey,
                    'visible' => true,
                ];
            }
        }

        return $normalized;
    }

    protected static function booted(): void
    {
        static::saved(function () {
            SiteCache::forgetSiteSetting();
        });
    }

    public static function current(): self
    {
        return static::query()->first() ?? static::query()->forceCreate([
            'id' => 1,
            'site_name' => 'Dost TV',
            'live_tv_type' => 'iframe',
            'live_button_text' => 'Canlı İzle',
            'live_button_is_visible' => true,
            'search_is_visible' => true,
            'header_is_sticky' => true,
            'footer_show_socials' => true,
            'footer_show_contact' => true,
            'footer_show_bank_link' => true,
        ]);
    }
}
