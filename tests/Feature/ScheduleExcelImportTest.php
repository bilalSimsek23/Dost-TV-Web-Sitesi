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
        $this->assertStringContainsString('Program sistemde bulunamadı', $result['errors'][0]['message']);

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

    public function test_standard_template_has_date_format_and_time_format_and_guidance(): void
    {
        $service = new ScheduleExcelImportService();
        $filePath = $service->generateDostTvStandardTemplate();

        $this->assertFileExists($filePath);

        $spreadsheet = IOFactory::load($filePath);
        $mainSheet = $spreadsheet->getSheetByName('Yayın Akışı');
        $progSheet = $spreadsheet->getSheetByName('Programlar');

        $this->assertNotNull($mainSheet);
        $this->assertNotNull($progSheet);

        // 1. Date format dd.mm.yyyy
        $formatB4 = $mainSheet->getStyle('B4')->getNumberFormat()->getFormatCode();
        $formatB5 = $mainSheet->getStyle('B5')->getNumberFormat()->getFormatCode();
        $this->assertSame('dd.mm.yyyy', $formatB4);
        $this->assertSame('dd.mm.yyyy', $formatB5);

        // 2. Guidance & copy notes in D3:E5
        $guideVal = $mainSheet->getCell('D3')->getValue();
        $this->assertStringContainsString('HIZLI KULLANIM', $guideVal);
        $this->assertStringContainsString('Saat Kopyalama', $guideVal);
        $this->assertStringNotContainsString('+15 dk', $guideVal);

        // 3. Programlar sheet only has Column A (programs) and Column B (types), NO durations
        $this->assertNull($progSheet->getCell('C1')->getValue());

        // 4. Column headers are clean 5 columns: Başlangıç, Bitiş, Program, Yayın Türü, Not
        $this->assertSame('Başlangıç', $mainSheet->getCell('A8')->getValue());
        $this->assertSame('Bitiş', $mainSheet->getCell('B8')->getValue());
        $this->assertSame('Program', $mainSheet->getCell('C8')->getValue());
        $this->assertSame('Yayın Türü', $mainSheet->getCell('D8')->getValue());
        $this->assertSame('Not', $mainSheet->getCell('E8')->getValue());

        // 5. Time format hh:mm on Start and End data rows (Row 9 is first data row)
        $formatA9 = $mainSheet->getStyle('A9')->getNumberFormat()->getFormatCode();
        $formatB9 = $mainSheet->getStyle('B9')->getNumberFormat()->getFormatCode();
        $this->assertSame('hh:mm', $formatA9);
        $this->assertSame('hh:mm', $formatB9);

        // 6. First row starts at 0 (00:00) and automatic continuation in A10
        $this->assertSame(0, $mainSheet->getCell('A9')->getValue());
        $formulaA10 = $mainSheet->getCell('A10')->getValue();
        $this->assertStringContainsString('B9', $formulaA10);

        // 7. Program validation on C9 and Broadcast Type validation on D9
        $progValidation = $mainSheet->getCell('C9')->getDataValidation();
        $this->assertStringContainsString('Programlar!$A$1:$A$', $progValidation->getFormula1());

        $typeValidation = $mainSheet->getCell('D9')->getDataValidation();
        $this->assertStringContainsString('Programlar!$B$1:$B$3', $typeValidation->getFormula1());

        @unlink($filePath);
    }

    public function test_date_cells_with_leading_zeros_and_serials_are_parsed_accurately(): void
    {
        $service = new ScheduleExcelImportService();

        // 01.04.2026 to 15.09.2026
        $file = $this->createDostTvMultiDayExcelFile('Bahar Dönemi 2026', '01.04.2026', '15.09.2026', [
            0 => [
                ['00:00', '01:00', 'Bab-ı Reyyan', 'CANLI', ''],
                ['01:00', '00:00', 'Mukabele', 'TEKRAR', ''],
            ],
            1 => [
                ['00:00', '00:00', 'Bab-ı Reyyan', 'PAKET', ''],
            ],
            2 => [
                ['00:00', '00:00', 'Bab-ı Reyyan', 'CANLI', ''],
            ],
            3 => [
                ['00:00', '00:00', 'Bab-ı Reyyan', 'CANLI', ''],
            ],
            4 => [
                ['00:00', '00:00', 'Bab-ı Reyyan', 'CANLI', ''],
            ],
            5 => [
                ['00:00', '00:00', 'Bab-ı Reyyan', 'CANLI', ''],
            ],
            6 => [
                ['00:00', '00:00', 'Bab-ı Reyyan', 'CANLI', ''],
            ],
        ]);

        $result = $service->parseAndValidate($file);

        $this->assertFalse($result['has_errors']);
        $this->assertNotNull($result['valid_from']);
        $this->assertNotNull($result['valid_until']);
        $this->assertSame('2026-04-01', $result['valid_from']->format('Y-m-d'));
        $this->assertSame('2026-09-15', $result['valid_until']->format('Y-m-d'));

        @unlink($file);
    }

    public function test_manual_custom_end_times_are_fully_supported(): void
    {
        $service = new ScheduleExcelImportService();

        // Manuel specific times like 08:37, 09:10, 10:25
        $file = $this->createDostTvMultiDayExcelFile('Manuel Saat Testi', '01.09.2026', '31.12.2026', [
            0 => [
                ['00:00', '08:37', 'Bab-ı Reyyan', 'Normal', 'Özel Saat 1'],
                ['08:37', '09:10', 'Mukabele', 'CANLI', 'Özel Saat 2'],
                ['09:10', '10:25', 'Bab-ı Reyyan', 'TEKRAR', 'Özel Saat 3'],
                ['10:25', '00:00', 'Mukabele', 'PAKET', 'Geceye Kadar'],
            ],
            1 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            2 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            3 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            4 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            5 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            6 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
        ]);

        $result = $service->parseAndValidate($file);

        $this->assertFalse($result['has_errors']);
        $pazartesiRows = array_filter($result['rows'], fn ($r) => $r['day_of_week'] === 0);
        $times = array_map(fn ($r) => [$r['start_time'], $r['end_time']], array_values($pazartesiRows));

        $this->assertSame([
            ['00:00', '08:37'],
            ['08:37', '09:10'],
            ['09:10', '10:25'],
            ['10:25', '00:00'],
        ], $times);

        @unlink($file);
    }

    public function test_copy_hours_across_days_works_smoothly(): void
    {
        $service = new ScheduleExcelImportService();

        // Pazartesi hours copied across all 7 days
        $baseHours = [
            ['00:00', '07:30', 'Bab-ı Reyyan', 'Normal', ''],
            ['07:30', '12:00', 'Mukabele', 'CANLI', ''],
            ['12:00', '18:00', 'Bab-ı Reyyan', 'TEKRAR', ''],
            ['18:00', '00:00', 'Mukabele', 'PAKET', ''],
        ];

        $schedule = [];
        for ($d = 0; $d < 7; $d++) {
            $schedule[$d] = $baseHours;
        }

        $file = $this->createDostTvMultiDayExcelFile('Haftalık Kopyalanan Akış', '01.09.2026', '31.12.2026', $schedule);
        $result = $service->parseAndValidate($file);

        $this->assertFalse($result['has_errors']);
        $this->assertSame(28, $result['total_count']);
        $this->assertSame(28, $result['valid_count']);

        $template = ScheduleTemplate::create([
            'name' => 'Haftalık Kopyalanan Akış',
            'slug' => 'haftalik-kopyalanan-akis',
            'status' => 'draft',
        ]);

        $imported = $service->importToTemplate($template, $result['rows']);
        $this->assertSame(28, $imported);
        $this->assertSame(28, ScheduleTemplateItem::where('schedule_template_id', $template->id)->count());

        @unlink($file);
    }

    public function test_program_name_with_parenthesis_matches_base_program_with_warning_and_no_error(): void
    {
        $aklaKapi = Program::create(['name' => 'Akla Kapı', 'slug' => 'akla-kapi', 'is_active' => true]);

        $service = new ScheduleExcelImportService();

        // "Bab-ı Reyyan (Lemalar)", "Mukabele (Tekrar)", "Akla Kapı (Nevzat Tarhan)"
        $file = $this->createDostTvMultiDayExcelFile('Parantezli Program Akışı', '01.09.2026', '31.12.2026', [
            0 => [
                ['00:00', '08:00', 'Bab-ı Reyyan (Lemalar)', 'Normal', ''],
                ['08:00', '14:00', 'Mukabele (Tekrar)', 'CANLI', ''],
                ['14:00', '20:00', 'Akla Kapı (Nevzat Tarhan)', 'PAKET', ''],
                ['20:00', '00:00', 'Bab-ı Reyyan', 'Normal', ''], // Exact match (no warning)
            ],
            1 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            2 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            3 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            4 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            5 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            6 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
        ]);

        $result = $service->parseAndValidate($file);

        $this->assertFalse($result['has_errors']);
        $this->assertTrue($result['has_warnings']);
        $this->assertSame(3, $result['warning_count']);
        $this->assertSame(0, $result['error_count']);
        $this->assertSame(10, $result['total_count']);
        $this->assertSame(10, $result['valid_count']);

        // Check first row matched Bab-ı Reyyan
        $row1 = $result['rows'][0];
        $this->assertSame('warning', $row1['status']);
        $this->assertSame($this->program1->id, $row1['program_id']);
        $this->assertSame('Bab-ı Reyyan', $row1['program_name']);
        $this->assertSame('Bab-ı Reyyan (Lemalar)', $row1['raw_program']);
        $this->assertStringContainsString('Lemalar', $row1['warnings'][0]);

        // Check template import
        $template = ScheduleTemplate::create([
            'name' => 'Parantezli Program Akışı',
            'slug' => 'parantezli-program-akisi',
            'status' => 'draft',
        ]);

        $importedCount = $service->importToTemplate($template, $result['rows']);
        $this->assertSame(10, $importedCount);

        $this->assertDatabaseHas('schedule_template_items', [
            'schedule_template_id' => $template->id,
            'program_id' => $this->program1->id,
            'day_of_week' => 0,
            'start_time' => '00:00',
            'end_time' => '08:00',
        ]);

        $this->assertDatabaseHas('schedule_template_items', [
            'schedule_template_id' => $template->id,
            'program_id' => $aklaKapi->id,
            'day_of_week' => 0,
            'start_time' => '14:00',
            'end_time' => '20:00',
        ]);

        @unlink($file);
    }

    public function test_program_name_with_extra_whitespace_and_parenthesis_is_cleaned_and_matched(): void
    {
        $service = new ScheduleExcelImportService();

        $file = $this->createDostTvMultiDayExcelFile('Boşluklu Program Akışı', '01.09.2026', '31.12.2026', [
            0 => [
                ['00:00', '12:00', '   Bab-ı Reyyan    (Özel Bölüm)  ', 'Normal', ''],
                ['12:00', '00:00', '  Mukabele  ', 'CANLI', ''],
            ],
            1 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            2 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            3 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            4 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            5 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            6 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
        ]);

        $result = $service->parseAndValidate($file);

        $this->assertFalse($result['has_errors']);
        $this->assertSame($this->program1->id, $result['rows'][0]['program_id']);
        $this->assertSame('Bab-ı Reyyan', $result['rows'][0]['program_name']);

        @unlink($file);
    }

    public function test_unknown_program_with_parenthesis_fails_with_clear_normalized_error_message(): void
    {
        $service = new ScheduleExcelImportService();

        $file = $this->createDostTvMultiDayExcelFile('Bilinmeyen Program Akışı', '01.09.2026', '31.12.2026', [
            0 => [
                ['00:00', '12:00', 'Bilinmeyen Program (Deneme)', 'Normal', ''],
                ['12:00', '00:00', 'Mukabele', 'Normal', ''],
            ],
            1 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            2 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            3 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            4 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            5 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            6 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
        ]);

        $result = $service->parseAndValidate($file);

        $this->assertTrue($result['has_errors']);
        $this->assertGreaterThanOrEqual(1, $result['error_count']);

        $errorMessages = array_column($result['errors'], 'message');
        $foundError = false;
        foreach ($errorMessages as $msg) {
            if (str_contains($msg, 'Bilinmeyen Program') && str_contains($msg, 'Normalize edilen ad')) {
                $foundError = true;
                break;
            }
        }

        $this->assertTrue($foundError, 'Expected normalized error message not found in errors: ' . json_encode($errorMessages));

        @unlink($file);
    }

    public function test_import_with_only_warnings_allows_schedule_creation_via_livewire(): void
    {
        $file = $this->createDostTvMultiDayExcelFile('2026 Uyarı Toleranslı Akış', '01.09.2026', '31.12.2026', [
            0 => [
                ['00:00', '12:00', 'Bab-ı Reyyan (Günün Özeti)', 'CANLI', 'Özet'],
                ['12:00', '00:00', 'Mukabele (Cüz 1)', 'TEKRAR', 'Cüz'],
            ],
            1 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            2 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            3 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            4 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            5 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
            6 => [['00:00', '00:00', 'Bab-ı Reyyan', 'Normal', '']],
        ]);

        $uploadedFile = UploadedFile::fake()->createWithContent('test_warnings.xlsx', file_get_contents($file));

        Livewire::actingAs($this->user)
            ->test(\App\Filament\Resources\ScheduleTemplates\Pages\ScheduleExcelImportPage::class)
            ->set('data.excel_file', [$uploadedFile])
            ->call('fetchPreview')
            ->assertSet('isPreviewLoaded', true)
            ->assertSet('has_errors', false)
            ->assertSet('has_warnings', true)
            ->assertSet('warning_count', 2)
            ->assertSet('period_name', '2026 Uyarı Toleranslı Akış')
            ->assertSet('total_count', 8)
            ->call('createSchedulePeriod')
            ->assertSet('isImported', true);

        $this->assertDatabaseHas('schedule_templates', [
            'name' => '2026 Uyarı Toleranslı Akış',
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('schedule_template_items', [
            'program_id' => $this->program1->id,
            'is_live' => true,
            'day_of_week' => 0,
        ]);

        $this->assertDatabaseHas('schedule_template_items', [
            'program_id' => $this->program2->id,
            'is_repeat' => true,
            'day_of_week' => 0,
        ]);

        @unlink($file);
    }

    protected function createDostTvMultiDayExcelFile(string $periodName, mixed $from, mixed $until, array $daysSchedule): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Yayın Akışı');

        $sheet->setCellValue('A1', 'DOST TV YAYIN AKIŞI DÖNEM ŞABLONU');
        $sheet->setCellValue('A3', 'Akış Adı *');
        $sheet->setCellValue('B3', $periodName);
        $sheet->setCellValue('A4', 'Başlangıç Tarihi *');
        $sheet->setCellValue('B4', $from);
        $sheet->getStyle('B4')->getNumberFormat()->setFormatCode('dd.mm.yyyy');
        $sheet->setCellValue('A5', 'Bitiş Tarihi *');
        $sheet->setCellValue('B5', $until);
        $sheet->getStyle('B5')->getNumberFormat()->setFormatCode('dd.mm.yyyy');

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

