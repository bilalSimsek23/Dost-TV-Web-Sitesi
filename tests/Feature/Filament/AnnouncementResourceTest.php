<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Models\User;
use App\Services\Announcement\AnnouncementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AnnouncementResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected AnnouncementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'administrator']);
        $this->service = app(AnnouncementService::class);
        Storage::fake('public');

        // Seed default announcement types if not already seeded
        if (AnnouncementType::count() === 0) {
            AnnouncementType::create(['name' => 'Genel Bilgilendirme', 'slug' => 'general', 'sort_order' => 1]);
            AnnouncementType::create(['name' => 'Cuma Mesajı', 'slug' => 'friday', 'sort_order' => 2]);
            AnnouncementType::create(['name' => 'Diğer', 'slug' => 'other', 'sort_order' => 8]);
        }
    }

    public function test_authorized_user_can_access_announcements_page(): void
    {
        $this->actingAs($this->admin)
            ->get(AnnouncementResource::getUrl())
            ->assertSuccessful()
            ->assertSee('Duyuru Ekle');
    }

    public function test_announcement_can_be_created_without_message(): void
    {
        $type = AnnouncementType::where('slug', 'general')->first();

        Livewire::actingAs($this->admin)
            ->test(CreateAnnouncement::class)
            ->fillForm([
                'title' => 'Mesajsız Duyuru',
                'announcement_type_id' => $type->id,
                'message' => null,
                'placement' => 'global',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('announcements', [
            'title' => 'Mesajsız Duyuru',
            'message' => null,
            'announcement_type_id' => $type->id,
        ]);
    }

    public function test_other_and_custom_announcement_type_can_be_created_and_selected(): void
    {
        $customType = AnnouncementType::create([
            'name' => 'Özel Etkinlik',
            'slug' => 'ozel-etkinlik',
            'sort_order' => 9,
        ]);

        Livewire::actingAs($this->admin)
            ->test(CreateAnnouncement::class)
            ->fillForm([
                'title' => 'Özel Etkinlik Duyurusu',
                'announcement_type_id' => $customType->id,
                'message' => 'Özel etkinlik mesajı',
                'placement' => 'home',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $announcement = Announcement::where('title', 'Özel Etkinlik Duyurusu')->first();
        $this->assertNotNull($announcement);
        $this->assertEquals($customType->id, $announcement->announcement_type_id);
        $this->assertEquals('Özel Etkinlik', $announcement->type_name);
    }

    public function test_announcement_search_by_title_and_message(): void
    {
        $target = Announcement::create([
            'title' => 'Arıza Mesajı Özel',
            'message' => 'Yayın kesintisi yaşanmaktadır.',
            'type' => 'maintenance',
            'placement' => 'global',
        ]);

        $other = Announcement::create([
            'title' => 'Cuma Tebriği',
            'message' => 'Hayırlı cumalar dileriz.',
            'type' => 'friday',
            'placement' => 'home',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListAnnouncements::class)
            ->searchTable('Yayın kesintisi')
            ->assertCanSeeTableRecords([$target])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_strict_status_priority_calculation(): void
    {
        $expired = Announcement::create([
            'title' => 'Süresi Doldu Duyurusu',
            'message' => 'Mesaj',
            'is_active' => false,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
        ]);

        $draft = Announcement::create([
            'title' => 'Taslak Duyuru',
            'message' => 'Mesaj',
            'is_active' => false,
        ]);

        $planned = Announcement::create([
            'title' => 'Planlandı Duyurusu',
            'message' => 'Mesaj',
            'is_active' => true,
            'starts_at' => now()->addDays(5),
        ]);

        $active = Announcement::create([
            'title' => 'Aktif Duyuru',
            'message' => 'Mesaj',
            'is_active' => true,
        ]);

        $this->assertEquals('Süresi Doldu', $expired->admin_status['label']);
        $this->assertEquals('Taslak', $draft->admin_status['label']);
        $this->assertEquals('Planlandı', $planned->admin_status['label']);
        $this->assertEquals('Aktif', $active->admin_status['label']);
    }

    public function test_turkish_date_range_translated_format(): void
    {
        $range = Announcement::create([
            'title' => 'Cuma Mesajı',
            'message' => 'Mesaj',
            'starts_at' => Carbon::create(2026, 8, 7, 10, 0, 0),
            'ends_at' => Carbon::create(2026, 8, 8, 20, 0, 0),
        ]);

        $fromOnly = Announcement::create([
            'title' => 'Sadece Başlangıç',
            'message' => 'Mesaj',
            'starts_at' => Carbon::create(2026, 8, 7, 10, 0, 0),
        ]);

        $toOnly = Announcement::create([
            'title' => 'Sadece Bitiş',
            'message' => 'Mesaj',
            'ends_at' => Carbon::create(2026, 8, 8, 20, 0, 0),
        ]);

        $dateless = Announcement::create([
            'title' => 'Tarihsiz Duyuru',
            'message' => 'Mesaj',
        ]);

        $this->assertEquals('07 Ağustos 2026 - 08 Ağustos 2026', $range->formatted_date_range);
        $this->assertEquals('07 Ağustos 2026’dan itibaren', $fromOnly->formatted_date_range);
        $this->assertEquals('08 Ağustos 2026’ya kadar', $toOnly->formatted_date_range);
        $this->assertEquals('Süresiz', $dateless->formatted_date_range);
    }

    public function test_admin_ordered_scope_sorts_by_status_group_priority(): void
    {
        $expired = Announcement::create([
            'title' => 'Süresi Doldu',
            'message' => 'M',
            'is_active' => true,
            'ends_at' => now()->subDay(),
        ]);

        $draft = Announcement::create([
            'title' => 'Taslak',
            'message' => 'M',
            'is_active' => false,
        ]);

        $planned = Announcement::create([
            'title' => 'Planlandı',
            'message' => 'M',
            'is_active' => true,
            'starts_at' => now()->addDay(),
        ]);

        $active = Announcement::create([
            'title' => 'Aktif',
            'message' => 'M',
            'is_active' => true,
        ]);

        $pinned = Announcement::create([
            'title' => 'Sabitle (Öne Çıkarılan)',
            'message' => 'M',
            'is_active' => true,
            'is_pinned' => true,
        ]);

        $ordered = Announcement::adminOrdered()->get();
        $this->assertEquals($pinned->id, $ordered[0]->id);
        $this->assertEquals($active->id, $ordered[1]->id);
        $this->assertEquals($planned->id, $ordered[2]->id);
        $this->assertEquals($draft->id, $ordered[3]->id);
        $this->assertEquals($expired->id, $ordered[4]->id);
    }

    public function test_delete_action_removes_only_target_announcement(): void
    {
        $announcement1 = Announcement::create([
            'title' => 'Duyuru 1',
            'message' => 'Mesaj 1',
        ]);

        $announcement2 = Announcement::create([
            'title' => 'Duyuru 2',
            'message' => 'Mesaj 2',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListAnnouncements::class)
            ->callTableAction('delete', $announcement1);

        $this->assertDatabaseMissing('announcements', ['id' => $announcement1->id]);
        $this->assertDatabaseHas('announcements', ['id' => $announcement2->id]);
    }

    public function test_announcement_image_upload_under_10mb_succeeds(): void
    {
        $type = AnnouncementType::where('slug', 'general')->first();
        $file500k = UploadedFile::fake()->image('announcement_500k.jpg')->size(500);

        Livewire::actingAs($this->admin)
            ->test(CreateAnnouncement::class)
            ->fillForm([
                'title' => '500KB Görsel Duyuru',
                'announcement_type_id' => $type->id,
                'message' => 'Mesaj metni',
                'placement' => 'global',
                'image' => $file500k,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $announcement = Announcement::where('title', '500KB Görsel Duyuru')->first();
        $this->assertNotNull($announcement);
        $this->assertNotNull($announcement->image);
        Storage::disk('public')->assertExists($announcement->image);
    }

    public function test_draft_status_selector_sets_is_active_false(): void
    {
        $type = AnnouncementType::where('slug', 'general')->first();

        Livewire::actingAs($this->admin)
            ->test(CreateAnnouncement::class)
            ->fillForm([
                'title' => 'Taslak Duyuru Testi',
                'announcement_type_id' => $type->id,
                'status_selector' => 'draft',
                'is_active' => false,
                'is_pinned' => true,
                'placement' => 'global',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('announcements', [
            'title' => 'Taslak Duyuru Testi',
            'is_active' => false,
            'is_pinned' => true,
        ]);
    }
}
