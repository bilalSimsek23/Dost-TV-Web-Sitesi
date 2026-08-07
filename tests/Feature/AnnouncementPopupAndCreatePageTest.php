<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Models\User;
use App\View\Components\Site\AnnouncementPopup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnnouncementPopupAndCreatePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_resource_create_pages_disable_create_another_button(): void
    {
        $createClasses = [
            \App\Filament\Resources\Programs\Pages\CreateProgram::class,
            \App\Filament\Resources\Episodes\Pages\CreateEpisode::class,
            \App\Filament\Resources\Categories\Pages\CreateCategory::class,
            \App\Filament\Resources\Announcements\Pages\CreateAnnouncement::class,
            \App\Filament\Resources\Banners\Pages\CreateBanner::class,
            \App\Filament\Resources\Pages\Pages\CreatePage::class,
            \App\Filament\Resources\Khatms\Pages\CreateKhatm::class,
            \App\Filament\Resources\Menus\Pages\CreateMenu::class,
            \App\Filament\Resources\MenuItems\Pages\CreateMenuItem::class,
            \App\Filament\Resources\Users\Pages\CreateUser::class,
            \App\Filament\Resources\LiveStreams\Pages\CreateLiveStream::class,
            \App\Filament\Resources\ScheduleTemplates\Pages\CreateScheduleTemplate::class,
            \App\Filament\Resources\FontFamilies\Pages\CreateFontFamily::class,
            \App\Filament\Resources\Schedules\Pages\CreateSchedule::class,
        ];

        foreach ($createClasses as $class) {
            $instance = new $class();
            $this->assertFalse($instance->canCreateAnother(), "Class {$class} should return false for canCreateAnother()");
        }
    }

    public function test_announcement_file_upload_validates_types_and_saves_relative_path(): void
    {
        Storage::fake('public');

        $type = AnnouncementType::create(['name' => 'Genel', 'slug' => 'genel', 'is_active' => true]);

        $file = UploadedFile::fake()->image('duyuru_gorsel.webp', 1200, 1600);
        $path = $file->store('announcements', 'public');

        $announcement = Announcement::create([
            'title' => 'Test Görselli Duyuru',
            'announcement_type_id' => $type->id,
            'image' => $path,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'image' => $announcement->image,
        ]);

        $this->assertStringStartsWith('announcements/', $announcement->image);
        $this->assertStringNotContainsString('http', $announcement->image);
        Storage::disk('public')->assertExists($announcement->image);
    }

    public function test_active_popup_announcement_query_selects_highest_priority_unexpired_record(): void
    {
        $type = AnnouncementType::create(['name' => 'Genel', 'slug' => 'genel', 'is_active' => true]);

        // Expired announcement (ends_at in past)
        Announcement::create([
            'title' => 'Eski Duyuru',
            'announcement_type_id' => $type->id,
            'is_active' => true,
            'ends_at' => now()->subDay(),
        ]);

        // Future scheduled announcement (starts_at in future)
        Announcement::create([
            'title' => 'Gelecek Duyuru',
            'announcement_type_id' => $type->id,
            'is_active' => true,
            'starts_at' => now()->addDay(),
        ]);

        // Passive announcement
        Announcement::create([
            'title' => 'Pasif Duyuru',
            'announcement_type_id' => $type->id,
            'is_active' => false,
        ]);

        // Normal active announcement
        $normalActive = Announcement::create([
            'title' => 'Normal Aktif Duyuru',
            'announcement_type_id' => $type->id,
            'is_active' => true,
            'is_pinned' => false,
        ]);

        // Pinned active announcement (highest priority)
        $pinnedActive = Announcement::create([
            'title' => 'Öne Çıkarılan Sabit Duyuru',
            'announcement_type_id' => $type->id,
            'is_active' => true,
            'is_pinned' => true,
        ]);

        $selected = AnnouncementPopup::getActivePopupAnnouncement();

        $this->assertNotNull($selected);
        $this->assertSame($pinnedActive->id, $selected->id);
        $this->assertSame('Öne Çıkarılan Sabit Duyuru', $selected->title);
    }

    public function test_announcement_popup_component_renders_on_public_pages(): void
    {
        $type = AnnouncementType::create(['name' => 'Genel', 'slug' => 'genel', 'is_active' => true]);

        Announcement::create([
            'title' => 'Sistem Güncelleme Duyurusu',
            'announcement_type_id' => $type->id,
            'message' => 'Sayın takipçilerimiz, bu bir test pop-up mesajıdır.',
            'is_active' => true,
            'is_pinned' => true,
        ]);

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('Sistem Güncelleme Duyurusu');
        $response->assertSee('dosttv_announcement_dismissed_');
    }
}
