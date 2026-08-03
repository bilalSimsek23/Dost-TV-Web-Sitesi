<?php

namespace Database\Seeders;

use App\Models\FontFamily;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the baseline font catalog: the 5 requested system fonts, plus
 * "Instrument Sans" (google, marked default) since that is the font the
 * public site's Tailwind theme already uses — without it, the Tema
 * Ayarları typography Select would default to a value with no matching
 * FontFamily option.
 */
class FontFamilySeeder extends Seeder
{
    public function run(): void
    {
        $systemFonts = [
            'System UI',
            'Arial',
            'Georgia',
            'Times New Roman',
            'Verdana',
        ];

        foreach ($systemFonts as $name) {
            FontFamily::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'source_type' => 'system',
                    'is_active' => true,
                    'is_default' => false,
                ]
            );
        }

        FontFamily::query()->updateOrCreate(
            ['slug' => 'instrument-sans'],
            [
                'name' => 'Instrument Sans',
                'source_type' => 'google',
                'font_url' => 'https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap',
                'weights' => ['400', '500', '600', '700'],
                'is_active' => true,
                'is_default' => true,
            ]
        );
    }
}
