<?php

namespace App\Console\Commands;

use App\Models\HomepageLayout;
use App\Models\SiteSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillHomepageLayoutCommand extends Command
{
    protected $signature = 'home:backfill-layout';

    protected $description = 'Mevcut canlı ana sayfa bölümlerini güvenli şekilde ilk HomepageLayout kaydına kopyalar ve builder section configlerinden hero/header/footer kayıtlarını temizler (idempotent).';

    public function handle(): int
    {
        // Strip hero/header/footer from existing layout section configs
        $existingLayouts = HomepageLayout::all();
        foreach ($existingLayouts as $layout) {
            $draft = array_values(array_filter(
                (array) ($layout->draft_sections ?? []),
                fn ($sec) => ! in_array($sec['block_type'] ?? ($sec['key'] ?? ''), ['hero', 'header', 'footer'], true)
            ));

            $published = array_values(array_filter(
                (array) ($layout->published_sections ?? []),
                fn ($sec) => ! in_array($sec['block_type'] ?? ($sec['key'] ?? ''), ['hero', 'header', 'footer'], true)
            ));

            $layout->update([
                'draft_sections' => $draft,
                'published_sections' => $published,
            ]);
        }

        if (HomepageLayout::query()->exists()) {
            $this->info('Ana sayfa düzenleri temizlendi ve güncellendi.');
            return Command::SUCCESS;
        }

        $settings = SiteSetting::current();
        $existingSections = $settings->normalized_homepage_sections;

        $newSections = [];
        foreach ($existingSections as $sec) {
            $key = $sec['key'] ?? '';
            if (in_array($key, ['hero', 'header', 'footer'], true)) {
                continue;
            }

            $newSections[] = [
                'uuid' => (string) Str::uuid(),
                'block_type' => $key,
                'visible' => (bool) ($sec['visible'] ?? true),
                'title' => match ($key) {
                    'live_intro' => 'Canlı Yayın',
                    'today_schedule' => 'Yayın Akışı',
                    'featured_programs' => 'Öne Çıkan Programlar',
                    default => 'Bölüm',
                },
                'show_title' => true,
                'title_alignment' => 'left',
                'desktop_columns' => 4,
                'display_variant' => 'grid',
                'content_limit' => 8,
            ];
        }

        $layout = HomepageLayout::create([
            'name' => 'Mevcut Ana Sayfa',
            'is_active' => true,
            'draft_sections' => $newSections,
            'published_sections' => $newSections,
            'published_at' => now(),
        ]);

        $this->info("İlk Ana Sayfa Düzeni oluşturuldu ve aktif edildi: ID {$layout->id}");

        return Command::SUCCESS;
    }
}
