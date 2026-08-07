<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Services\Media\AnnouncementImageProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeAnnouncementImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'announcements:optimize-images {--dry-run : Perform a dry run without modifying files or database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize existing announcement images to WebP with aspect ratio preservation (Max 2000px)';

    /**
     * Execute the console command.
     */
    public function handle(AnnouncementImageProcessor $processor): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $this->info($isDryRun ? '🔍 Running Announcement Image Optimization in DRY-RUN mode...' : '🚀 Starting Announcement Image Optimization...');

        $announcements = Announcement::whereNotNull('image')->where('image', '!=', '')->get();

        if ($announcements->isEmpty()) {
            $this->info('No announcements with images found.');
            return self::SUCCESS;
        }

        $processedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        foreach ($announcements as $announcement) {
            $currentImage = $announcement->image;

            // Skip if already in webp format and located in announcements/
            if (str_ends_with(strtolower($currentImage), '.webp') && str_contains($currentImage, 'announcements/')) {
                $this->line("  [SKIPPED] Announcement #{$announcement->id} is already WebP: {$currentImage}");
                $skippedCount++;
                continue;
            }

            try {
                $absolutePath = AnnouncementImageProcessor::resolveAbsolutePath($currentImage);
            } catch (\Throwable $e) {
                $this->warn("  [FAILED] Announcement #{$announcement->id} image file not found: {$currentImage}");
                $failedCount++;
                continue;
            }

            if ($isDryRun) {
                $originalSize = filesize($absolutePath);
                $originalSizeFormatted = number_format($originalSize / 1024, 1) . ' KB';
                $this->info("  [WOULD OPTIMIZE] Announcement #{$announcement->id}: {$currentImage} ({$originalSizeFormatted})");
                $processedCount++;
                continue;
            }

            $originalSize = filesize($absolutePath);
            try {
                $newRelativePath = $processor->process($absolutePath);
                $newAbsolutePath = Storage::disk('public')->path($newRelativePath);
                $newSize = filesize($newAbsolutePath);

                // Update database quietly without triggering model events loop
                $announcement->image = $newRelativePath;
                $announcement->saveQuietly();

                // Delete old file if different
                if ($currentImage !== $newRelativePath && Storage::disk('public')->exists($currentImage)) {
                    Storage::disk('public')->delete($currentImage);
                }

                $savingPercent = round((1 - ($newSize / max(1, $originalSize))) * 100, 1);
                $origKb = number_format($originalSize / 1024, 1);
                $newKb = number_format($newSize / 1024, 1);

                $this->info("  [OPTIMIZED] Announcement #{$announcement->id}: {$origKb} KB -> {$newKb} KB (-{$savingPercent}%) | {$newRelativePath}");
                $processedCount++;

            } catch (\Throwable $e) {
                $this->error("  [ERROR] Announcement #{$announcement->id}: {$e->getMessage()}");
                $failedCount++;
            }
        }

        $this->newLine();
        $this->info("Optimization completed. Processed: {$processedCount}, Skipped: {$skippedCount}, Failed: {$failedCount}");

        return self::SUCCESS;
    }
}
