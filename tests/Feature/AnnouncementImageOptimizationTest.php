<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Services\Media\AnnouncementImageProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnnouncementImageOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_500kb_jpg_upload_is_converted_to_webp_and_optimized(): void
    {
        $type = AnnouncementType::create(['name' => 'Genel', 'slug' => 'genel', 'is_active' => true]);

        // Create a fake JPG file (~500 KB)
        $file = UploadedFile::fake()->image('duyuru_poster.jpg', 1600, 1200)->size(500);

        $processor = new AnnouncementImageProcessor();
        $relativePath = $processor->process($file->getRealPath());

        $this->assertStringEndsWith('.webp', $relativePath);
        $this->assertStringStartsWith('announcements/', $relativePath);

        $absolutePath = Storage::disk('public')->path($relativePath);
        $this->assertFileExists($absolutePath);

        $imageInfo = getimagesize($absolutePath);
        $this->assertSame('image/webp', $imageInfo['mime']);
        $this->assertSame(1600, $imageInfo[0]);
        $this->assertSame(1200, $imageInfo[1]);
    }

    public function test_large_image_above_2000px_is_resized_maintaining_aspect_ratio(): void
    {
        // 3000x2000 image (3:2 aspect ratio)
        $file = UploadedFile::fake()->image('huge_poster.jpg', 3000, 2000);

        $processor = new AnnouncementImageProcessor();
        $relativePath = $processor->process($file->getRealPath());

        $absolutePath = Storage::disk('public')->path($relativePath);
        $imageInfo = getimagesize($absolutePath);

        // Long edge (3000) scaled down to 2000, short edge (2000) scaled down to 1333
        $this->assertSame(2000, $imageInfo[0]);
        $this->assertSame(1333, $imageInfo[1]);
    }

    public function test_small_image_under_2000px_is_not_upscaled(): void
    {
        // 800x600 image
        $file = UploadedFile::fake()->image('small_poster.png', 800, 600);

        $processor = new AnnouncementImageProcessor();
        $relativePath = $processor->process($file->getRealPath());

        $absolutePath = Storage::disk('public')->path($relativePath);
        $imageInfo = getimagesize($absolutePath);

        $this->assertSame(800, $imageInfo[0]);
        $this->assertSame(600, $imageInfo[1]);
    }

    public function test_transparent_png_preserves_alpha_channel_when_converted_to_webp(): void
    {
        // Create transparent PNG with GD
        $im = imagecreatetruecolor(400, 300);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        $trans = imagecolorallocatealpha($im, 255, 0, 0, 127); // fully transparent
        imagefilledrectangle($im, 0, 0, 400, 300, $trans);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_png_') . '.png';
        imagepng($im, $tempPath);
        imagedestroy($im);

        $processor = new AnnouncementImageProcessor();
        $relativePath = $processor->process($tempPath);
        @unlink($tempPath);

        $absolutePath = Storage::disk('public')->path($relativePath);
        $imageInfo = getimagesize($absolutePath);
        $this->assertSame('image/webp', $imageInfo['mime']);

        // Inspect WebP alpha channel presence
        $loadedWebp = imagecreatefromwebp($absolutePath);
        $this->assertNotFalse($loadedWebp);
        $color = imagecolorat($loadedWebp, 50, 50);
        $alpha = ($color >> 24) & 0x7F;
        $this->assertGreaterThan(0, $alpha, 'Alpha transparency should be preserved');
        imagedestroy($loadedWebp);
    }

    public function test_invalid_file_with_fake_jpg_extension_is_rejected(): void
    {
        $fakeFile = tempnam(sys_get_temp_dir(), 'fake_') . '.jpg';
        file_put_contents($fakeFile, '<html><body><?php echo "evil script"; ?></body></html>');

        $processor = new AnnouncementImageProcessor();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Geçersiz görsel dosyası. Lütfen JPG, PNG veya WEBP dosyası yükleyin.');

        try {
            $processor->process($fakeFile);
        } finally {
            @unlink($fakeFile);
        }
    }

    public function test_announcement_model_automatically_optimizes_image_on_save(): void
    {
        $type = AnnouncementType::create(['name' => 'Genel', 'slug' => 'genel', 'is_active' => true]);

        // 1. Create initial unoptimized PNG image file in public disk
        $file = UploadedFile::fake()->image('banner_raw.png', 2400, 1800);
        $initialPath = $file->store('announcements', 'public');

        $announcement = Announcement::create([
            'title' => 'Test Model Event Duyuru',
            'announcement_type_id' => $type->id,
            'image' => $initialPath,
            'is_active' => true,
        ]);

        // Model saving hook should process and convert to webp
        $this->assertStringEndsWith('.webp', $announcement->image);
        $this->assertNotEquals($initialPath, $announcement->image);

        $newAbsolutePath = Storage::disk('public')->path($announcement->image);
        $this->assertFileExists($newAbsolutePath);

        $imageInfo = getimagesize($newAbsolutePath);
        // Long edge 2400 scaled down to 2000, 1800 scaled down to 1500
        $this->assertSame(2000, $imageInfo[0]);
        $this->assertSame(1500, $imageInfo[1]);

        // Initial raw file should be cleaned up
        Storage::disk('public')->assertMissing($initialPath);
    }

    public function test_artisan_optimize_command_dry_run_and_execution(): void
    {
        $type = AnnouncementType::create(['name' => 'Genel', 'slug' => 'genel', 'is_active' => true]);

        $file = UploadedFile::fake()->image('legacy_raw.jpg', 3000, 2000);
        $path = $file->store('announcements', 'public');

        $announcement = Announcement::create([
            'title' => 'Eski Duyuru',
            'announcement_type_id' => $type->id,
            'is_active' => true,
        ]);

        // Bypass model hook for legacy simulation
        $announcement->updateQuietly(['image' => $path]);

        // Test Dry-run
        $this->artisan('announcements:optimize-images --dry-run')
            ->expectsOutputToContain('DRY-RUN mode')
            ->assertExitCode(0);

        $this->assertSame($path, $announcement->fresh()->image);

        // Test Real Run
        $this->artisan('announcements:optimize-images')
            ->expectsOutputToContain('Optimization completed')
            ->assertExitCode(0);

        $updated = $announcement->fresh();
        $this->assertStringEndsWith('.webp', $updated->image);
        Storage::disk('public')->assertExists($updated->image);
        Storage::disk('public')->assertMissing($path);
    }
}
