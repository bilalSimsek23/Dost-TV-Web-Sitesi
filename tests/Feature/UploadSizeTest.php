<?php

namespace Tests\Feature;

use App\Models\AnnouncementType;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UploadSizeTest extends TestCase
{
    use RefreshDatabase;

    public function test_empirical_upload_size_pipeline_across_seven_file_sizes(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $sizes = [
            '10 KB' => 10,
            '100 KB' => 100,
            '1 MB' => 1024,
            '5 MB' => 5 * 1024,
            '10 MB' => 10 * 1024,
            '20 MB' => 20 * 1024,
            '50 MB' => 50 * 1024,
        ];

        echo PHP_EOL . '==========================================================================' . PHP_EOL;
        echo 'FILEUPLOAD COMPONENT EMPIRICAL VALIDATION TEST (maxSize: 10240)' . PHP_EOL;
        echo '==========================================================================' . PHP_EOL;

        $firstFailedSize = null;
        $firstFailedReason = null;

        foreach ($sizes as $label => $sizeInKb) {
            $file = UploadedFile::fake()->create('announcement_' . $sizeInKb . 'kb.jpg', $sizeInKb, 'image/jpeg');

            $validator = Validator::make(
                ['image' => $file],
                ['image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:10240']]
            );

            $isSuccess = ! $validator->fails();
            $errors = $validator->errors()->first('image');
            $reason = $isSuccess ? 'Successfully Passed Application Validation' : 'Validation Error: ' . $errors;

            if (! $isSuccess && $firstFailedSize === null) {
                $firstFailedSize = $label;
                $firstFailedReason = $reason;
            }

            echo sprintf(
                'Size: %-7s (%6d KB) | Success: %-5s | Detail: %s',
                $label,
                $sizeInKb,
                $isSuccess ? 'YES' : 'NO',
                $reason
            ) . PHP_EOL;
        }

        echo '==========================================================================' . PHP_EOL;
        echo 'FIRST FAILED FILE SIZE: ' . ($firstFailedSize ?? 'NONE (ALL PASSED UP TO 50 MB)') . PHP_EOL;
        if ($firstFailedReason) {
            echo 'FAILURE REASON        : ' . $firstFailedReason . PHP_EOL;
        }
        echo '==========================================================================' . PHP_EOL;

        $this->assertSame('20 MB', $firstFailedSize, 'The first size that should fail application validation is 20 MB (max:10240 KB limit)');
    }
}
