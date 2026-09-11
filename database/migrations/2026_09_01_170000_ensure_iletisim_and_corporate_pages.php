<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Page::updateOrCreate(
            ['slug' => 'iletisim'],
            [
                'title' => 'İletişim',
                'show_in_menu' => true,
                'show_in_footer' => true,
                'page_type' => 'corporate',
                'status' => 'published',
                'sort_order' => 6,
                'content' => <<<'HTML'
<h2>Adres</h2>
<p>Zübeyde Hanım, İstanbul Cad., Devrez Sok. No:1, 06070 Altındağ/Ankara</p>
<h2>Telefon</h2>
<p>(0312) 341 21 21</p>
<h2>Sosyal Medya</h2>
<p><a href="https://www.youtube.com/@DostRadyoTV">YouTube: @DostRadyoTV</a></p>
HTML,
                'settings' => [
                    'map_enabled' => true,
                    'map_title' => 'DOST TV Genel Merkezi',
                    'map_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3059.277800762953!2d32.8507817!3d39.9463428!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14d34e6012bf8e1d%3A0xb30e38600ec030e2!2zWsO8YmV5ZGUgSGFuxLFtLCDEsHN0YW5idWwgQ2FkLiBEZXZyZXogU29rLiBObzoxLCAwNjA3MCBBbHTEsW5kYcSfL0Fua2FyYQ!5e0!3m2!1str!2str!4v1700000000000!5m2!1str!2str',
                    'map_height' => 320,
                ],
            ]
        );

        // Ensure existing corporate pages have page_type = 'corporate' and show_in_footer = true
        Page::whereIn('slug', [
            'dost-tv-yayin-ilkeleri',
            'yayinci-kunye-bilgisi',
            'neden-dost-tv',
            'dost-vakfi-hesap-numaralari',
            'kisisel-verilerin-korunmasi-ve-gizlilik-politikasi',
        ])->update([
            'page_type' => 'corporate',
            'show_in_footer' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
