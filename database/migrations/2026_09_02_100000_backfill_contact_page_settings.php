<?php

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $contactPage = Page::where('slug', 'iletisim')->first();

        if (! $contactPage) {
            return;
        }

        $settings = $contactPage->settings ?? [];
        $siteSettings = SiteSetting::current();

        $updated = false;

        if (blank($settings['address'] ?? null)) {
            $settings['address'] = $siteSettings->address ?: 'Zübeyde Hanım, İstanbul Cad., Devrez Sok. No:1, 06070 Altındağ/Ankara';
            $updated = true;
        }

        if (blank($settings['phone'] ?? null)) {
            $settings['phone'] = $siteSettings->phone ?: '(0312) 341 21 21';
            $updated = true;
        }

        if (blank($settings['email'] ?? null)) {
            $settings['email'] = $siteSettings->email ?: 'iletisim@dosttv.com';
            $updated = true;
        }

        if ($updated) {
            $contactPage->settings = $settings;
            $contactPage->save();
        }
    }

    public function down(): void
    {
        // No-op for data backfill
    }
};
