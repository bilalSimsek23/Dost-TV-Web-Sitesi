<?php

namespace Tests\Feature;

use App\Filament\Pages\ScheduleCalendarPage;
use App\Models\Program;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleTemplateItem;
use App\Models\User;
use App\Services\Schedule\ScheduleExcelImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ScheduleExcelImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Program $program1;
    protected Program $program2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->program1 = Program::create(['name' => 'Bab-ı Reyyan', 'slug' => 'bab-i-reyyan', 'is_active' => true]);
        $this->program2 = Program::create(['name' => 'Mukabele', 'slug' => 'mukabele', 'is_active' => true]);
    }

    public function test_sample_excel_template_can_be_generated(): void
    {
        $service = new ScheduleExcelImportService();
        $filePath = $service->generateSampleTemplate();

        $this->assertFileExists($filePath);
        $this->assertGreaterThan(0, filesize($filePath));
        @unlink($filePath);
    }

    public function test_valid_xlsx_file_is_parsed_and_imported_successfully(): void
    {
        $file = $this->createExcelFile([
            ['Gün', 'Başlangıç', 'Bitiş', 'Program', 'Yayın Türü', 'Aktif'],
            ['Pazartesi', '08:30', '10:00', 'Bab-ı Reyyan', 'Normal', 'Evet'],
            ['Salı', '10:00', '11:30', 'Mukabele', 'Tekrar Yayın', 'Evet'],
        ]);

        $service = new ScheduleExcelImportService();
        $result = $service->parseAndValidate($file);

        $this->assertFalse($result['has_errors']);
        $this->assertSame(2, $result['total_count']);
        $this->assertSame(2, $result['valid_count']);

        $template = ScheduleTemplate::create([
            'name' => 'Excel Test Akışı',
            'slug' => 'excel-test-akisi',
            'status' => 'draft',
        ]);

        $importedCount = $service->importToTemplate($template, $result['rows']);
        $this->assertSame(2, $importedCount);

        $this->assertDatabaseHas('schedule_template_items', [
            'schedule_template_id' => $template->id,
            'program_id' => $this->program1->id,
            'day_of_week' => 0,
            'start_time' => '08:30',
            'end_time' => '10:00',
            'is_live' => false,
            'is_repeat' => false,
        ]);

        $this->assertDatabaseHas('schedule_template_items', [
            'schedule_template_id' => $template->id,
            'program_id' => $this->program2->id,
            'day_of_week' => 1,
            'start_time' => '10:00',
            'end_time' => '11:30',
            'is_live' => false,
            'is_repeat' => true,
        ]);

        @unlink($file);
    }

    public function test_turkish_and_english_headers_and_any_column_order_are_supported(): void
    {
        // Reordered columns: Program, Type, Day, End, Start, Active
        $file = $this->createExcelFile([
            ['Program Adı', 'Yayin Turu', 'Gun', 'End Time', 'Baslangic', 'Active'],
            ['Bab-ı Reyyan', 'Canlı', 'Cuma', '13:30', '12:00', 'true'],
        ]);

        $service = new ScheduleExcelImportService();
        $result = $service->parseAndValidate($file);

        $this->assertFalse($result['has_errors']);
        $row = $result['rows'][0];
        $this->assertSame(4, $row['day_of_week']); // Cuma = 4
        $this->assertSame('12:00', $row['start_time']);
        $this->assertSame('13:30', $row['end_time']);
        $this->assertTrue($row['is_live']);

        @unlink($file);
    }

    public function test_case_insensitive_and_normalized_program_matching(): void
    {
        // "bab-ı reyyan" in lowercase
        $file = $this->createExcelFile([
            ['Gün', 'Başlangıç', 'Bitiş', 'Program', 'Yayın Türü', 'Aktif'],
            ['Pazartesi', '08:30', '10:00', 'bab-ı reyyan', 'Normal', 'Evet'],
        ]);

        $service = new ScheduleExcelImportService();
        $result = $service->parseAndValidate($file);

        $this->assertFalse($result['has_errors']);
        $this->assertSame($this->program1->id, $result['rows'][0]['program_id']);

        @unlink($file);
    }

    public function test_unmatched_program_name_returns_error(): void
    {
        $file = $this->createExcelFile([
            ['Gün', 'Başlangıç', 'Bitiş', 'Program', 'Yayın Türü', 'Aktif'],
            ['Pazartesi', '08:30', '10:00', 'Bilinmeyen Program', 'Normal', 'Evet'],
        ]);

        $service = new ScheduleExcelImportService();
        $result = $service->parseAndValidate($file);

        $this->assertTrue($result['has_errors']);
        $this->assertSame(1, $result['error_count']);
        $this->assertStringContainsString('Bu program sistemde bulunamadı', $result['errors'][0]['message']);

        @unlink($file);
    }

    public function test_missing_required_column_returns_general_error(): void
    {
        $file = $this->createExcelFile([
            ['Gün', 'Başlangıç', 'Program'], // Missing Bitiş
            ['Pazartesi', '08:30', 'Bab-ı Reyyan'],
        ]);

        $service = new ScheduleExcelImportService();
        $result = $service->parseAndValidate($file);

        $this->assertTrue($result['has_errors']);
        $this->assertStringContainsString('Eksik zorunlu sütun', $result['general_error']);

        @unlink($file);
    }

    public function test_invalid_time_and_end_before_start_are_rejected(): void
    {
        $file = $this->createExcelFile([
            ['Gün', 'Başlangıç', 'Bitiş', 'Program', 'Yayın Türü', 'Aktif'],
            ['Pazartesi', '10:00', '08:30', 'Bab-ı Reyyan', 'Normal', 'Evet'],
        ]);

        $service = new ScheduleExcelImportService();
        $result = $service->parseAndValidate($file);

        $this->assertTrue($result['has_errors']);
        $this->assertStringContainsString('Bitiş saati', $result['errors'][0]['message']);

        @unlink($file);
    }

    public function test_overlapping_broadcasts_on_same_day_are_rejected(): void
    {
        $file = $this->createExcelFile([
            ['Gün', 'Başlangıç', 'Bitiş', 'Program', 'Yayın Türü', 'Aktif'],
            ['Pazartesi', '08:30', '10:00', 'Bab-ı Reyyan', 'Normal', 'Evet'],
            ['Pazartesi', '09:30', '11:00', 'Mukabele', 'Normal', 'Evet'], // Overlaps with row 1
        ]);

        $service = new ScheduleExcelImportService();
        $result = $service->parseAndValidate($file);

        $this->assertTrue($result['has_errors']);
        $this->assertStringContainsString('Yayın çakışması', $result['errors'][0]['message']);

        @unlink($file);
    }

    public function test_duplicate_rows_are_flagged(): void
    {
        $file = $this->createExcelFile([
            ['Gün', 'Başlangıç', 'Bitiş', 'Program', 'Yayın Türü', 'Aktif'],
            ['Pazartesi', '08:30', '10:00', 'Bab-ı Reyyan', 'Normal', 'Evet'],
            ['Pazartesi', '08:30', '10:00', 'Bab-ı Reyyan', 'Normal', 'Evet'], // Duplicate
        ]);

        $service = new ScheduleExcelImportService();
        $result = $service->parseAndValidate($file);

        $this->assertTrue($result['has_errors']);
        $this->assertStringContainsString('daha önce eklenmiş', $result['errors'][0]['message']);

        @unlink($file);
    }

    public function test_error_report_excel_can_be_generated(): void
    {
        $service = new ScheduleExcelImportService();
        $filePath = $service->generateErrorExport([
            ['row_num' => 2, 'program_name' => 'Bilinmeyen Program', 'message' => 'Bu program sistemde bulunamadı'],
        ]);

        $this->assertFileExists($filePath);
        $this->assertGreaterThan(0, filesize($filePath));
        @unlink($filePath);
    }

    public function test_500_rows_excel_file_is_processed_efficiently(): void
    {
        $rows = [['Gün', 'Başlangıç', 'Bitiş', 'Program', 'Yayın Türü', 'Aktif']];

        $days = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
        for ($i = 0; $i < 500; $i++) {
            $day = $days[$i % 7];
            $hStart = sprintf('%02d:00', ($i % 20));
            $hEnd = sprintf('%02d:30', ($i % 20));
            $rows[] = [$day, $hStart, $hEnd, 'Bab-ı Reyyan', 'Normal', 'Evet'];
        }

        $file = $this->createExcelFile($rows);

        $startMemory = memory_get_usage();
        $startTime = microtime(true);

        $service = new ScheduleExcelImportService();
        $result = $service->parseAndValidate($file);

        $duration = microtime(true) - $startTime;
        $memoryUsed = (memory_get_usage() - $startMemory) / (1024 * 1024);

        $this->assertLessThan(3.0, $duration, '500 rows should process in under 3 seconds');
        $this->assertLessThan(64, $memoryUsed, 'Memory consumption should be well below 64 MB');
        $this->assertSame(500, $result['total_count']);

        @unlink($file);
    }

    public function test_existing_creation_modes_empty_and_copy_continue_to_work(): void
    {
        // 1. Empty mode
        Livewire::actingAs($this->user)
            ->test(ScheduleCalendarPage::class)
            ->callAction('create_template', [
                'name' => 'Boş Akış 2026',
                'creation_mode' => 'empty',
            ])
            ->assertHasNoActionErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('schedule_templates', ['name' => 'Boş Akış 2026']);
        $source = ScheduleTemplate::where('name', 'Boş Akış 2026')->first();

        // Create item in source
        ScheduleTemplateItem::create([
            'schedule_template_id' => $source->id,
            'program_id' => $this->program1->id,
            'day_of_week' => 0,
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        // 2. Copy mode
        Livewire::actingAs($this->user)
            ->test(ScheduleCalendarPage::class)
            ->callAction('create_template', [
                'name' => 'Kopyalanan Akış 2026',
                'creation_mode' => 'copy',
                'source_template_id' => $source->id,
            ])
            ->assertHasNoActionErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('schedule_templates', ['name' => 'Kopyalanan Akış 2026']);
        $target = ScheduleTemplate::where('name', 'Kopyalanan Akış 2026')->first();

        $this->assertDatabaseHas('schedule_template_items', [
            'schedule_template_id' => $target->id,
            'program_id' => $this->program1->id,
            'day_of_week' => 0,
        ]);
    }

    public function test_schedule_calendar_page_renders_compact_template_and_day_selects(): void
    {
        $template = ScheduleTemplate::create([
            'name' => 'Aktif Yayın Dönemi',
            'slug' => 'aktif-yayin-donemi',
            'status' => 'published',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('filament.admin.pages.schedule-calendar'));
        $response->assertStatus(200);
        $response->assertSee('schedule-calendar-ui', false);
        $response->assertSee('id="template-select"', false);
        $response->assertSee('schedule-day-card', false);
        $response->assertSee('wire:click="selectDay(0)"', false);
        $response->assertSee('wire:click="selectDay(6)"', false);
        $response->assertSee('Pazartesi');
        $response->assertSee('Pazar');
        $response->assertSee('Yayın Akışı');
    }

    public function test_dynamic_monday_to_sunday_dates_and_today_badge_advancement(): void
    {
        ScheduleTemplate::create([
            'name' => 'Test Dönemi',
            'slug' => 'test-donemi-dates',
            'status' => 'published',
            'is_active' => true,
        ]);

        // Test on Thursday 2026-08-06
        \Illuminate\Support\Carbon::setTestNow('2026-08-06 14:00:00');
        $response = $this->actingAs($this->user)->get(route('filament.admin.pages.schedule-calendar'));
        $response->assertStatus(200);
        $response->assertSee('3 Ağustos'); // Monday
        $response->assertSee('6 Ağustos'); // Thursday
        $response->assertSee('9 Ağustos'); // Sunday
        $response->assertSee('Bugün');

        // Test on Sunday 2026-08-09 -> remains 3 Aug .. 9 Aug week
        \Illuminate\Support\Carbon::setTestNow('2026-08-09 20:00:00');
        $responseSunday = $this->actingAs($this->user)->get(route('filament.admin.pages.schedule-calendar'));
        $responseSunday->assertSee('3 Ağustos');
        $responseSunday->assertSee('9 Ağustos');
        $responseSunday->assertSee('Bugün');

        // Test on next Monday 2026-08-10 -> advances to 10 Aug .. 16 Aug week
        \Illuminate\Support\Carbon::setTestNow('2026-08-10 09:00:00');
        $responseNextMonday = $this->actingAs($this->user)->get(route('filament.admin.pages.schedule-calendar'));
        $responseNextMonday->assertSee('10 Ağustos');
        $responseNextMonday->assertSee('16 Ağustos');
        $responseNextMonday->assertSee('Bugün');

        \Illuminate\Support\Carbon::setTestNow();
    }

    public function test_changing_day_preserves_selected_template(): void
    {
        $template = ScheduleTemplate::create([
            'name' => 'Yaz Akışı 2026',
            'slug' => 'yaz-akisi-2026',
            'status' => 'published',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(ScheduleCalendarPage::class, ['template' => $template->id, 'day' => 0])
            ->set('selectedDay', 3)
            ->assertSet('selectedTemplateId', $template->id)
            ->assertSet('selectedDay', 3);
    }

    public function test_admin_template_selection_does_not_affect_public_active_period(): void
    {
        $activeTemplate = ScheduleTemplate::create([
            'name' => 'Aktif Dönem',
            'slug' => 'aktif-donem',
            'status' => 'published',
            'is_active' => true,
        ]);

        $draftTemplate = ScheduleTemplate::create([
            'name' => 'Taslak Akış',
            'slug' => 'taslak-akis',
            'status' => 'draft',
            'is_active' => false,
        ]);

        // Admin selects draft template for editing
        Livewire::actingAs($this->user)
            ->test(ScheduleCalendarPage::class)
            ->set('selectedTemplateId', $draftTemplate->id);

        // Public resolver still returns active template
        $resolver = new \App\Services\Schedule\BroadcastScheduleResolver();
        $publicTemplate = $resolver->getActivePublishedTemplateForDate(now());

        $this->assertNotNull($publicTemplate);
        $this->assertEquals($activeTemplate->id, $publicTemplate->id);
    }

    public function test_single_durum_column_and_display_status_mapping(): void
    {
        $draft = ScheduleTemplate::create([
            'name' => 'Taslak Akış',
            'slug' => 'taslak-akis-test',
            'status' => 'draft',
            'is_active' => false,
        ]);

        $ready = ScheduleTemplate::create([
            'name' => 'Hazır Akış',
            'slug' => 'hazir-akis-test',
            'status' => 'published',
            'is_active' => false,
        ]);

        $active = ScheduleTemplate::create([
            'name' => 'Gösterimdeki Akış',
            'slug' => 'gosterimdeki-akis-test',
            'status' => 'published',
            'is_active' => true,
        ]);

        $this->assertEquals('Taslak', $draft->display_status);
        $this->assertEquals('Hazır', $ready->display_status);
        $this->assertEquals('Gösterimde', $active->display_status);

        $response = $this->actingAs($this->user)->get('/admin/schedule-templates');
        $response->assertStatus(200);
        $response->assertSee('Durum');
        $response->assertSee('Taslak');
        $response->assertSee('Hazır');
        $response->assertSee('Gösterimde');
        $response->assertDontSee('is_active');
    }

    public function test_status_transitions_and_active_period_exclusivity(): void
    {
        $t1 = ScheduleTemplate::create([
            'name' => 'Dönem 1',
            'slug' => 'donem-1',
            'status' => 'draft',
            'is_active' => false,
        ]);

        $t2 = ScheduleTemplate::create([
            'name' => 'Dönem 2',
            'slug' => 'donem-2',
            'status' => 'draft',
            'is_active' => false,
        ]);

        // 1. Taslak -> Hazır
        Livewire::actingAs($this->user)
            ->test(\App\Filament\Resources\ScheduleTemplates\Pages\ListScheduleTemplates::class)
            ->callTableAction('make_ready', $t1);

        $t1->refresh();
        $this->assertEquals('Hazır', $t1->display_status);
        $this->assertEquals('published', $t1->status);
        $this->assertFalse($t1->is_active);

        // Hazır record is NOT shown on public site
        $resolver = new \App\Services\Schedule\BroadcastScheduleResolver();
        $this->assertNull($resolver->getActivePublishedTemplateForDate(now()));

        // 2. Hazır -> Gösterimde
        Livewire::actingAs($this->user)
            ->test(\App\Filament\Resources\ScheduleTemplates\Pages\ListScheduleTemplates::class)
            ->callTableAction('set_active', $t1);

        $t1->refresh();
        $this->assertEquals('Gösterimde', $t1->display_status);
        $this->assertTrue($t1->is_active);
        $this->assertEquals($t1->id, $resolver->getActivePublishedTemplateForDate(now())->id);

        // 3. Make t2 Hazır then Gösterimde -> deactivates t1 automatically
        Livewire::actingAs($this->user)
            ->test(\App\Filament\Resources\ScheduleTemplates\Pages\ListScheduleTemplates::class)
            ->callTableAction('make_ready', $t2)
            ->callTableAction('set_active', $t2);

        $t1->refresh();
        $t2->refresh();

        $this->assertEquals('Hazır', $t1->display_status);
        $this->assertFalse($t1->is_active);

        $this->assertEquals('Gösterimde', $t2->display_status);
        $this->assertTrue($t2->is_active);

        // Strictly only ONE record is active
        $this->assertEquals(1, ScheduleTemplate::where('is_active', true)->count());
    }

    public function test_schedule_calendar_shows_empty_state_when_no_active_period(): void
    {
        ScheduleTemplate::query()->delete();

        $response = $this->actingAs($this->user)->get(route('filament.admin.pages.schedule-calendar'));
        $response->assertStatus(200);
        $response->assertSee('Gösterimde olan bir yayın dönemi bulunmuyor.');
        $response->assertSee('Yayın Dönemlerine Git');
    }

    public function test_set_active_action_changes_active_period(): void
    {
        $template1 = ScheduleTemplate::create([
            'name' => 'Eski Akış 2025',
            'slug' => 'eski-akis-2025',
            'status' => 'published',
            'is_active' => true,
        ]);

        $template2 = ScheduleTemplate::create([
            'name' => 'Yaz Akışı 2026',
            'slug' => 'yaz-akisi-2026',
            'status' => 'published',
            'is_active' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test(\App\Filament\Resources\ScheduleTemplates\Pages\ListScheduleTemplates::class)
            ->callTableAction('set_active', $template2);

        $this->assertTrue($template2->fresh()->is_active);
        $this->assertFalse($template1->fresh()->is_active);
    }

    public function test_active_published_template_cannot_be_deleted(): void
    {
        $template = ScheduleTemplate::create([
            'name' => 'Aktif Yayın Dönemi',
            'slug' => 'aktif-yayin-donemi',
            'status' => 'published',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(\App\Filament\Resources\ScheduleTemplates\Pages\ListScheduleTemplates::class)
            ->assertTableActionHidden('delete', record: $template);

        $this->assertDatabaseHas('schedule_templates', ['id' => $template->id]);
    }

    public function test_dosttv_standard_template_generation_contains_all_programs_in_turkish_order_and_types(): void
    {
        $inactiveProg = Program::create(['name' => 'Arşiv Programı', 'slug' => 'arsiv-programi', 'is_active' => false, 'status' => 'archived']);

        $service = new ScheduleExcelImportService();
        $filePath = $service->generateDostTvStandardTemplate();

        $this->assertFileExists($filePath);

        $spreadsheet = IOFactory::load($filePath);
        $progSheet = $spreadsheet->getSheetByName('Programlar');
        $this->assertNotNull($progSheet);

        $progValues = [];
        for ($r = 1; $r <= 20; $r++) {
            $val = $progSheet->getCell("A{$r}")->getValue();
            if (filled($val)) {
                $progValues[] = $val;
            }
        }

        $this->assertContains('Bab-ı Reyyan', $progValues);
        $this->assertContains('Mukabele', $progValues);
        $this->assertContains('Arşiv Programı', $progValues);

        $typeValues = [];
        for ($r = 1; $r <= 5; $r++) {
            $val = $progSheet->getCell("B{$r}")->getValue();
            if (filled($val)) {
                $typeValues[] = $val;
            }
        }
        $this->assertContains('CANLI', $typeValues);
        $this->assertContains('TEKRAR', $typeValues);
        $this->assertContains('PAKET', $typeValues);

        @unlink($filePath);
    }

    public function test_dosttv_multiday_excel_parsing_and_import_with_24h_validation(): void
    {
        $file = $this->createDostTvMultiDayExcelFile('2026 Güz Dönemi', '01.09.2026', '31.12.2026', [
            // Pazartesi
            0 => [
                ['00:00', '08:00', 'Bab-ı Reyyan', 'Normal', 'Gece Yayını'],
                ['08:00', '14:00', 'Mukabele', 'CANLI', 'Sabah Mukabelesi'],
                ['14:00', '20:00', 'Bab-ı Reyyan', 'TEKRAR', 'Öğle Tekrarı'],
                ['20:00', '00:00', 'Mukabele', 'PAKET', 'Akşam Yayını'],
            ],
            // Salı
            1 => [
                ['00:00', '12:00', 'Bab-ı Reyyan', 'Normal', ''],
                ['12:00', '00:00', 'Mukabele', 'Normal', ''],
            ],
            // Çarşamba
            2 => [
                ['00:00', '12:00', 'Bab-ı Reyyan', 'Normal', ''],
                ['12:00', '00:00', 'Mukabele', 'Normal', ''],
            ],
            // Perşembe
            3 => [
                ['00:00', '12:00', 'Bab-ı Reyyan', 'Normal', ''],
                ['12:00', '00:00', 'Mukabele', 'Normal', ''],
            ],
            // Cuma
            4 => [
                ['00:00', '12:00', 'Bab-ı Reyyan', 'CANLI', ''],
                ['12:00', '00:00', 'Mukabele', 'Normal', ''],
            ],
            // Cumartesi
            5 => [
                ['00:00', '12:00', 'Bab-ı Reyyan', 'Normal', ''],
                ['12:00', '00:00', 'Mukabele', 'Normal', ''],
            ],
            // Pazar
            6 => [
                ['00:00', '12:00', 'Bab-ı Reyyan', 'Normal', ''],
                ['12:00', '00:00', 'Mukabele', 'Normal', ''],
            ],
        ]);

        $service = new ScheduleExcelImportService();
        $result = $service->parseAndValidate($file);

        $this->assertFalse($result['has_errors']);
        $this->assertSame('2026 Güz Dönemi', $result['period_name']);
        $this->assertSame('01.09.2026', $result['valid_from_formatted']);
        $this->assertSame('31.12.2026', $result['valid_until_formatted']);
        $this->assertSame(16, $result['total_count']);

        $template = ScheduleTemplate::create([
            'name' => $result['period_name'],
            'valid_from' => $result['valid_from'],
            'valid_until' => $result['valid_until'],
            'status' => 'draft',
            'is_active' => false,
        ]);

        $importedCount = $service->importToTemplate($template, $result['rows']);
        $this->assertSame(16, $importedCount);

        // Check imported period is draft & not active
        $this->assertEquals('Taslak', $template->display_status);
        $this->assertFalse($template->is_active);

        // Check broadcast types and notes
        $this->assertDatabaseHas('schedule_template_items', [
            'schedule_template_id' => $template->id,
            'day_of_week' => 0,
            'start_time' => '08:00',
            'end_time' => '14:00',
            'is_live' => true,
            'is_repeat' => false,
            'note' => 'Sabah Mukabelesi',
        ]);

        $this->assertDatabaseHas('schedule_template_items', [
            'schedule_template_id' => $template->id,
            'day_of_week' => 0,
            'start_time' => '14:00',
            'end_time' => '20:00',
            'is_live' => false,
            'is_repeat' => true,
            'note' => 'Öğle Tekrarı',
        ]);

        $this->assertDatabaseHas('schedule_template_items', [
            'schedule_template_id' => $template->id,
            'day_of_week' => 0,
            'start_time' => '20:00',
            'end_time' => '00:00',
            'note' => 'Akşam Yayını',
        ]);

        @unlink($file);
    }

    public function test_dosttv_duplicate_period_name_is_rejected(): void
    {
        ScheduleTemplate::create([
            'name' => 'Mevcut Dönem',
            'status' => 'published',
            'is_active' => true,
        ]);

        $validDay = [
            ['00:00', '12:00', 'Bab-ı Reyyan', 'Normal', ''],
            ['12:00', '00:00', 'Mukabele', 'Normal', ''],
        ];

        $file = $this->createDostTvMultiDayExcelFile('Mevcut Dönem', '01.09.2026', '31.12.2026', [
            0 => $validDay, 1 => $validDay, 2 => $validDay, 3 => $validDay, 4 => $validDay, 5 => $validDay, 6 => $validDay,
        ]);

        $service = new ScheduleExcelImportService();
        $result = $service->parseAndValidate($file);

        $this->assertTrue($result['has_errors']);
        $this->assertStringContainsString('Bu isimde bir yayın dönemi zaten mevcut', json_encode($result['errors'], JSON_UNESCAPED_UNICODE));

        @unlink($file);
    }

    public function test_dosttv_overnight_broadcast_is_supported(): void
    {
        $validDay = [
            ['00:00', '12:00', 'Bab-ı Reyyan', 'Normal', ''],
            ['12:00', '00:00', 'Mukabele', 'Normal', ''],
        ];

        $file = $this->createDostTvMultiDayExcelFile('Gece Yayını Akışı', '01.09.2026', '31.12.2026', [
            0 => [
                ['00:00', '23:30', 'Bab-ı Reyyan', 'Normal', ''],
                ['23:30', '01:00', 'Mukabele', 'Normal', 'Overnight'], // Overnight past midnight
            ],
            1 => $validDay, 2 => $validDay, 3 => $validDay, 4 => $validDay, 5 => $validDay, 6 => $validDay,
        ]);

        $service = new ScheduleExcelImportService();
        $result = $service->parseAndValidate($file);

        $this->assertFalse($result['has_errors']);
        $this->assertTrue($result['rows'][1]['is_overnight']);

        @unlink($file);
    }

    public function test_dosttv_day_not_starting_at_0000_is_rejected(): void
    {
        $validDay = [
            ['00:00', '12:00', 'Bab-ı Reyyan', 'Normal', ''],
            ['12:00', '00:00', 'Mukabele', 'Normal', ''],
        ];

        $file = $this->createDostTvMultiDayExcelFile('Hatalı Başlangıç Akışı', '01.09.2026', '31.12.2026', [
            0 => [['08:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']], // Starts at 08:00 instead of 00:00
            1 => $validDay, 2 => $validDay, 3 => $validDay, 4 => $validDay, 5 => $validDay, 6 => $validDay,
        ]);

        $service = new ScheduleExcelImportService();
        $result = $service->parseAndValidate($file);

        $this->assertTrue($result['has_errors']);
        $this->assertStringContainsString('00:00\'da başlamalıdır', json_encode($result['errors'], JSON_UNESCAPED_UNICODE));

        @unlink($file);
    }

    public function test_dosttv_day_not_ending_at_0000_is_rejected(): void
    {
        $validDay = [
            ['00:00', '12:00', 'Bab-ı Reyyan', 'Normal', ''],
            ['12:00', '00:00', 'Mukabele', 'Normal', ''],
        ];

        $file = $this->createDostTvMultiDayExcelFile('Hatalı Bitiş Akışı', '01.09.2026', '31.12.2026', [
            0 => [['00:00', '22:00', 'Bab-ı Reyyan', 'Normal', '']], // Ends at 22:00 instead of 00:00
            1 => $validDay, 2 => $validDay, 3 => $validDay, 4 => $validDay, 5 => $validDay, 6 => $validDay,
        ]);

        $service = new ScheduleExcelImportService();
        $result = $service->parseAndValidate($file);

        $this->assertTrue($result['has_errors']);
        $this->assertStringContainsString('00:00\'da tamamlanmamıştır', json_encode($result['errors'], JSON_UNESCAPED_UNICODE));

        @unlink($file);
    }

    public function test_dosttv_broadcast_gap_is_rejected(): void
    {
        $validDay = [
            ['00:00', '12:00', 'Bab-ı Reyyan', 'Normal', ''],
            ['12:00', '00:00', 'Mukabele', 'Normal', ''],
        ];

        $file = $this->createDostTvMultiDayExcelFile('Boşluklu Akış', '01.09.2026', '31.12.2026', [
            0 => [
                ['00:00', '10:00', 'Bab-ı Reyyan', 'Normal', ''],
                ['11:00', '00:00', 'Mukabele', 'Normal', ''], // Gap from 10:00 to 11:00
            ],
            1 => $validDay, 2 => $validDay, 3 => $validDay, 4 => $validDay, 5 => $validDay, 6 => $validDay,
        ]);

        $service = new ScheduleExcelImportService();
        $result = $service->parseAndValidate($file);

        $this->assertTrue($result['has_errors']);
        $this->assertStringContainsString('Yayın boşluğu', json_encode($result['errors'], JSON_UNESCAPED_UNICODE));

        @unlink($file);
    }

    public function test_schedule_templates_index_has_excel_download_and_import_buttons(): void
    {
        Livewire::actingAs($this->user)
            ->test(\App\Filament\Resources\ScheduleTemplates\Pages\ListScheduleTemplates::class)
            ->assertActionExists('download_template')
            ->assertActionExists('excel_import')
            ->assertActionExists('create');
    }

    public function test_schedule_excel_import_livewire_page_end_to_end(): void
    {
        $file = $this->createDostTvMultiDayExcelFile('2026 Canlı Test Akışı', '01.09.2026', '31.12.2026', [
            0 => [
                ['00:00', '12:00', 'Bab-ı Reyyan', 'CANLI', 'Canlı Gece'],
                ['12:00', '00:00', 'Mukabele', 'Normal', ''],
            ],
            1 => [
                ['00:00', '12:00', 'Mukabele', 'TEKRAR', ''],
                ['12:00', '00:00', 'Bab-ı Reyyan', 'Normal', ''],
            ],
            2 => [
                ['00:00', '12:00', 'Bab-ı Reyyan', 'Normal', ''],
                ['12:00', '00:00', 'Mukabele', 'Normal', ''],
            ],
            3 => [
                ['00:00', '12:00', 'Mukabele', 'Normal', ''],
                ['12:00', '00:00', 'Bab-ı Reyyan', 'Normal', ''],
            ],
            4 => [
                ['00:00', '12:00', 'Bab-ı Reyyan', 'Normal', ''],
                ['12:00', '00:00', 'Mukabele', 'Normal', ''],
            ],
            5 => [
                ['00:00', '12:00', 'Mukabele', 'Normal', ''],
                ['12:00', '00:00', 'Bab-ı Reyyan', 'Normal', ''],
            ],
            6 => [
                ['00:00', '12:00', 'Bab-ı Reyyan', 'Normal', ''],
                ['12:00', '00:00', 'Mukabele', 'Normal', ''],
            ],
        ]);

        $uploadedFile = UploadedFile::fake()->createWithContent('test_dosttv.xlsx', file_get_contents($file));

        Livewire::actingAs($this->user)
            ->test(\App\Filament\Resources\ScheduleTemplates\Pages\ScheduleExcelImportPage::class)
            ->set('data.excel_file', [$uploadedFile])
            ->call('fetchPreview')
            ->assertSet('isPreviewLoaded', true)
            ->assertSet('has_errors', false)
            ->assertSet('period_name', '2026 Canlı Test Akışı')
            ->assertSet('total_count', 14)
            ->call('createSchedulePeriod')
            ->assertSet('isImported', true);

        $this->assertDatabaseHas('schedule_templates', [
            'name' => '2026 Canlı Test Akışı',
            'status' => 'draft',
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('schedule_template_items', [
            'day_of_week' => 0,
            'is_live' => true,
            'note' => 'Canlı Gece',
        ]);

        @unlink($file);
    }

    protected function createDostTvMultiDayExcelFile(string $periodName, string $from, string $until, array $daysSchedule): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Yayın Akışı');

        $sheet->setCellValue('A1', 'DOST TV YAYIN AKIŞI DÖNEM ŞABLONU');
        $sheet->setCellValue('A3', 'Akış Adı *');
        $sheet->setCellValue('B3', $periodName);
        $sheet->setCellValue('A4', 'Başlangıç Tarihi *');
        $sheet->setCellValue('B4', $from);
        $sheet->setCellValue('A5', 'Bitiş Tarihi *');
        $sheet->setCellValue('B5', $until);

        $curRow = 7;
        $dayNames = ['PAZARTESİ', 'SALI', 'ÇARŞAMBA', 'PERŞEMBE', 'CUMA', 'CUMARTESİ', 'PAZAR'];

        foreach ($dayNames as $idx => $dName) {
            $sheet->setCellValue("A{$curRow}", $dName);
            $sheet->setCellValue("A" . ($curRow + 1), 'Başlangıç');
            $sheet->setCellValue("B" . ($curRow + 1), 'Bitiş');
            $sheet->setCellValue("C" . ($curRow + 1), 'Program');
            $sheet->setCellValue("D" . ($curRow + 1), 'Yayın Türü');
            $sheet->setCellValue("E" . ($curRow + 1), 'Not');

            $dataRows = $daysSchedule[$idx] ?? [];
            $rowPtr = $curRow + 2;

            foreach ($dataRows as $rData) {
                $sheet->setCellValue("A{$rowPtr}", $rData[0] ?? '');
                $sheet->setCellValue("B{$rowPtr}", $rData[1] ?? '');
                $sheet->setCellValue("C{$rowPtr}", $rData[2] ?? '');
                $sheet->setCellValue("D{$rowPtr}", $rData[3] ?? '');
                $sheet->setCellValue("E{$rowPtr}", $rData[4] ?? '');
                $rowPtr++;
            }

            // fill extra empty rows up to 25 rows
            while ($rowPtr < $curRow + 27) {
                $sheet->setCellValue("A{$rowPtr}", '');
                $sheet->setCellValue("B{$rowPtr}", '');
                $sheet->setCellValue("C{$rowPtr}", '');
                $sheet->setCellValue("D{$rowPtr}", '');
                $sheet->setCellValue("E{$rowPtr}", '');
                $rowPtr++;
            }

            $curRow = $rowPtr + 2;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'test_dosttv_excel_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    protected function createExcelFile(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($rows, null, 'A1');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_excel_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }
}

