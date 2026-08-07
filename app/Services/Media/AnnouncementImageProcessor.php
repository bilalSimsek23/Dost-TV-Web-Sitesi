<?php

namespace App\Services\Media;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AnnouncementImageProcessor
{
    public const MAX_RAW_SIZE_BYTES = 25 * 1024 * 1024; // 25 MB
    public const MAX_PIXEL_DIMENSION = 12000;
    public const TARGET_MAX_DIMENSION = 2000;
    public const WEBP_QUALITY = 82;
    public const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Resolves absolute local filesystem path for a given path or storage relative string.
     */
    public static function resolveAbsolutePath(string $input): string
    {
        if (file_exists($input)) {
            return $input;
        }

        $publicPath = Storage::disk('public')->path($input);
        if (file_exists($publicPath)) {
            return $publicPath;
        }

        $localPath = Storage::disk('local')->path($input);
        if (file_exists($localPath)) {
            return $localPath;
        }

        $storagePath = storage_path('app/' . ltrim($input, '/'));
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        throw new \InvalidArgumentException('Görsel dosyası okunamadı veya mevcut değil.');
    }

    /**
     * Process an uploaded announcement image:
     * - Validates MIME type and max pixel dimensions (<=12000px)
     * - Fixes EXIF orientation (JPEG)
     * - Scales long edge to max 2000px preserving aspect ratio (no upscaling)
     * - Preserves PNG alpha transparency
     * - Converts to WebP format at 82 quality
     * - Saves to storage/app/public/announcements/{ULID}.webp
     * - Returns relative path "announcements/{ULID}.webp"
     *
     * @param string $inputPath Path to image (relative storage path or absolute local path)
     * @return string Relative storage path (e.g. "announcements/01j123456789.webp")
     */
    public function process(string $inputPath): string
    {
        try {
            return $this->optimizeToWebp($inputPath);
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('AnnouncementImageProcessor WebP optimization failed, executing safe fallback: ' . $e->getMessage(), [
                'input' => $inputPath,
            ]);

            return $this->fallbackCopyOriginal($inputPath);
        }
    }

    protected function optimizeToWebp(string $inputPath): string
    {
        $absolutePath = self::resolveAbsolutePath($inputPath);

        if (filesize($absolutePath) > self::MAX_RAW_SIZE_BYTES) {
            throw new \InvalidArgumentException('Görsel dosyası maksimum 25 MB yükleme limitini aşıyor.');
        }

        // 1. Validate real image MIME type & dimensions
        $imageInfo = @getimagesize($absolutePath);
        if (! $imageInfo || empty($imageInfo['mime'])) {
            throw new \InvalidArgumentException('Geçersiz görsel dosyası. Lütfen JPG, PNG veya WEBP dosyası yükleyin.');
        }

        $mime = strtolower($imageInfo['mime']);
        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException('Geçersiz görsel dosyası. Lütfen JPG, PNG veya WEBP dosyası yükleyin.');
        }

        $origWidth = $imageInfo[0] ?? 0;
        $origHeight = $imageInfo[1] ?? 0;

        if ($origWidth <= 0 || $origHeight <= 0 || $origWidth > self::MAX_PIXEL_DIMENSION || $origHeight > self::MAX_PIXEL_DIMENSION) {
            throw new \InvalidArgumentException('Görsel piksel boyutları izin verilen maksimum 12000x12000 px sınırını aşıyor.');
        }

        // 2. Load GD Image Resource
        $srcImage = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($absolutePath),
            'image/png' => @imagecreatefrompng($absolutePath),
            'image/webp' => @imagecreatefromwebp($absolutePath),
            default => null,
        };

        if (! $srcImage) {
            throw new \RuntimeException('Görsel okunamadı.');
        }

        // 3. Fix EXIF Orientation for JPEGs
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            try {
                $exif = @exif_read_data($absolutePath);
                if (! empty($exif['Orientation'])) {
                    $rotated = match ((int) $exif['Orientation']) {
                        3 => imagerotate($srcImage, 180, 0),
                        6 => imagerotate($srcImage, -90, 0),
                        8 => imagerotate($srcImage, 90, 0),
                        default => null,
                    };
                    if ($rotated) {
                        imagedestroy($srcImage);
                        $srcImage = $rotated;
                        $origWidth = imagesx($srcImage);
                        $origHeight = imagesy($srcImage);
                    }
                }
            } catch (\Throwable $e) {
                // Ignore EXIF failures safely
            }
        }

        // 4. Calculate Target Dimensions (Preserve Aspect Ratio, Max 2000px, No Upscaling)
        $targetWidth = $origWidth;
        $targetHeight = $origHeight;

        $maxEdge = max($origWidth, $origHeight);
        if ($maxEdge > self::TARGET_MAX_DIMENSION) {
            $ratio = self::TARGET_MAX_DIMENSION / $maxEdge;
            $targetWidth = (int) max(1, round($origWidth * $ratio));
            $targetHeight = (int) max(1, round($origHeight * $ratio));
        }

        // 5. Create Target Truecolor Canvas & Preserve Alpha Channel
        $dstImage = imagecreatetruecolor($targetWidth, $targetHeight);
        if (! $dstImage) {
            imagedestroy($srcImage);
            throw new \RuntimeException('Tuval oluşturulamadı.');
        }

        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
        $transparentColor = imagecolorallocatealpha($dstImage, 0, 0, 0, 127);
        imagefilledrectangle($dstImage, 0, 0, $targetWidth, $targetHeight, $transparentColor);

        // 6. High Quality Resample
        imagecopyresampled(
            $dstImage,
            $srcImage,
            0, 0, 0, 0,
            $targetWidth,
            $targetHeight,
            $origWidth,
            $origHeight
        );

        // 7. Save Output WebP File
        $ulid = strtolower((string) Str::ulid());
        $relativePath = "announcements/{$ulid}.webp";

        $publicDisk = Storage::disk('public');
        $outputAbsolutePath = $publicDisk->path($relativePath);

        $dir = dirname($outputAbsolutePath);
        if (! file_exists($dir)) {
            @mkdir($dir, 0755, true);
        }

        $saved = @imagewebp($dstImage, $outputAbsolutePath, self::WEBP_QUALITY);

        // Free memory
        imagedestroy($srcImage);
        imagedestroy($dstImage);

        if (! $saved || ! file_exists($outputAbsolutePath) || filesize($outputAbsolutePath) === 0) {
            if (file_exists($outputAbsolutePath)) {
                @unlink($outputAbsolutePath);
            }
            throw new \RuntimeException('WebP olarak kaydedilemedi.');
        }

        return $relativePath;
    }

    protected function fallbackCopyOriginal(string $inputPath): string
    {
        $absolutePath = self::resolveAbsolutePath($inputPath);
        $ext = pathinfo($absolutePath, PATHINFO_EXTENSION);
        if (empty($ext)) {
            $ext = 'jpg';
        }

        $ulid = strtolower((string) Str::ulid());
        $relativePath = "announcements/{$ulid}.{$ext}";

        $publicDisk = Storage::disk('public');
        $outputAbsolutePath = $publicDisk->path($relativePath);

        $dir = dirname($outputAbsolutePath);
        if (! file_exists($dir)) {
            @mkdir($dir, 0755, true);
        }

        @copy($absolutePath, $outputAbsolutePath);

        if (file_exists($outputAbsolutePath) && filesize($outputAbsolutePath) > 0) {
            return $relativePath;
        }

        throw new \RuntimeException('Görsel yüklenemedi. Lütfen sayfayı yenileyip JPG, PNG veya WEBP dosyasıyla tekrar deneyin.');
    }
}
