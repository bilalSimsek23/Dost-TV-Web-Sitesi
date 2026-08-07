<?php

namespace Tests\Feature;

use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LiveFormUploadVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected AnnouncementType $type;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->type = AnnouncementType::create(['name' => 'Genel', 'slug' => 'genel', 'is_active' => true]);
    }

    public function test_create_announcement_with_100kb_jpg_upload_succeeds_end_to_end(): void
    {
        $file = UploadedFile::fake()->image('test_100kb.jpg', 800, 600)->size(100);

        Livewire::actingAs($this->user)
            ->test(CreateAnnouncement::class)
            ->fillForm([
                'title' => '100 KB JPG Duyuru Test',
                'announcement_type_id' => $this->type->id,
                'placement' => 'global',
                'status_selector' => 'active',
                'is_active' => true,
                'image' => $file,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $announcement = Announcement::where('title', '100 KB JPG Duyuru Test')->first();
        $this->assertNotNull($announcement);
        $this->assertStringEndsWith('.webp', $announcement->image);
        $this->assertStringStartsWith('announcements/', $announcement->image);
        Storage::disk('public')->assertExists($announcement->image);
    }

    public function test_create_announcement_with_1mb_png_upload_succeeds_end_to_end(): void
    {
        $file = UploadedFile::fake()->image('test_1mb.png', 1200, 900)->size(1024);

        Livewire::actingAs($this->user)
            ->test(CreateAnnouncement::class)
            ->fillForm([
                'title' => '1 MB PNG Duyuru Test',
                'announcement_type_id' => $this->type->id,
                'placement' => 'global',
                'status_selector' => 'active',
                'is_active' => true,
                'image' => $file,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $announcement = Announcement::where('title', '1 MB PNG Duyuru Test')->first();
        $this->assertNotNull($announcement);
        $this->assertStringEndsWith('.webp', $announcement->image);
        Storage::disk('public')->assertExists($announcement->image);
    }

    public function test_create_announcement_with_5mb_webp_upload_succeeds_end_to_end(): void
    {
        $file = UploadedFile::fake()->image('test_5mb.webp', 2400, 1800)->size(5120);

        Livewire::actingAs($this->user)
            ->test(CreateAnnouncement::class)
            ->fillForm([
                'title' => '5 MB WEBP Duyuru Test',
                'announcement_type_id' => $this->type->id,
                'placement' => 'global',
                'status_selector' => 'active',
                'is_active' => true,
                'image' => $file,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $announcement = Announcement::where('title', '5 MB WEBP Duyuru Test')->first();
        $this->assertNotNull($announcement);
        $this->assertStringEndsWith('.webp', $announcement->image);
        Storage::disk('public')->assertExists($announcement->image);

        // Verify downscaled long edge to max 2000px
        $absPath = Storage::disk('public')->path($announcement->image);
        $info = getimagesize($absPath);
        $this->assertSame(2000, $info[0]);
        $this->assertSame(1500, $info[1]);
    }

    public function test_editing_announcement_replaces_image_and_deletes_old_file(): void
    {
        // Initial creation
        $file1 = UploadedFile::fake()->image('old_image.jpg', 600, 400);
        $initialPath = $file1->store('announcements', 'public');

        $announcement = Announcement::create([
            'title' => 'Görsel Güncelleme Duyurusu',
            'announcement_type_id' => $this->type->id,
            'image' => $initialPath,
            'is_active' => true,
        ]);

        $oldImage = $announcement->image;
        Storage::disk('public')->assertExists($oldImage);

        // Edit with new image
        $newTempFile = UploadedFile::fake()->image('new_image.png', 1000, 800);

        Livewire::actingAs($this->user)
            ->test(EditAnnouncement::class, ['record' => $announcement->getKey()])
            ->fillForm([
                'title' => 'Görsel Güncelleme Duyurusu Güncellendi',
                'image' => [$newTempFile],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $announcement->refresh();
        $this->assertNotEquals($oldImage, $announcement->image);
        $this->assertStringEndsWith('.webp', $announcement->image);
        Storage::disk('public')->assertExists($announcement->image);
        Storage::disk('public')->assertMissing($oldImage);
    }
}
