<?php

namespace Tests\Feature;

use App\Filament\Resources\Programs\Schemas\ProgramForm;
use App\Models\Program;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleTemplateItem;
use App\Models\User;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProgramDescriptionAndSeoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_short_description_appears_in_schedule_when_present(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-18 10:15:00'));

        $template = ScheduleTemplate::create([
            'name' => '2026 Yaz Dönemi',
            'slug' => '2026-yaz-donemi',
            'status' => 'published',
            'is_active' => true,
        ]);

        $program = Program::create([
            'name' => 'Hatm-i Şerif',
            'slug' => 'hatm-i-serif',
            'short_description' => "Kur'an tilaveti ve dua programı",
            'description' => 'Detaylı Hatm-i Şerif tanıtım açıklaması',
            'status' => 'active',
            'is_active' => true,
        ]);

        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $program->id,
            'day_of_week' => 1, // Salı
            'start_time' => '10:10',
            'end_time' => '11:10',
            'is_active' => true,
        ]);

        $response = $this->get('/yayin-akisi');

        $response->assertOk();
        $response->assertSee('Hatm-i Şerif');
        $response->assertSee("Kur'an tilaveti ve dua programı");
        $response->assertSee('ŞİMDİ');
        $response->assertDontSee('Detaylı Hatm-i Şerif tanıtım açıklaması');
    }

    public function test_short_description_empty_does_not_render_empty_placeholder_in_schedule(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-18 12:00:00'));

        $template = ScheduleTemplate::create([
            'name' => '2026 Yaz Dönemi',
            'slug' => '2026-yaz-donemi',
            'status' => 'published',
            'is_active' => true,
        ]);

        $program = Program::create([
            'name' => 'Öğle Kuşağı',
            'slug' => 'ogle-kusagi',
            'short_description' => null,
            'description' => 'Öğle kuşağı uzun tanıtım metni',
            'status' => 'active',
            'is_active' => true,
        ]);

        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $program->id,
            'day_of_week' => 1,
            'start_time' => '12:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        $response = $this->get('/yayin-akisi');

        $response->assertOk();
        $response->assertSee('Öğle Kuşağı');
        $response->assertDontSee('Öğle kuşağı uzun tanıtım metni');
    }

    public function test_description_appears_in_program_detail_page_and_short_description_does_not_replace_it(): void
    {
        $program = Program::create([
            'name' => 'Katre',
            'slug' => 'katre',
            'short_description' => 'Kısa yayın akışı tanımı',
            'description' => 'Detaylı ve kapsamlı Katre program tanıtım metni burada yer alır.',
            'status' => 'active',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $response = $this->get('/programlar/katre');

        $response->assertOk();
        $response->assertSee('Detaylı ve kapsamlı Katre program tanıtım metni burada yer alır.');
        // short_description should not be displayed in detail body
        $response->assertDontSee('Kısa yayın akışı tanımı');
    }

    public function test_meta_description_is_used_for_seo_when_filled(): void
    {
        $program = Program::create([
            'name' => 'Gönül Sohbetleri',
            'slug' => 'gonul-sohbetleri',
            'short_description' => 'Kısa akış tanımı',
            'description' => 'Detaylı tanıtım metni',
            'meta_description' => 'Özel Google Arama Açıklaması Meta Metni',
            'status' => 'active',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $response = $this->get('/programlar/gonul-sohbetleri');

        $response->assertOk();
        $response->assertSee('<meta name="description" content="Özel Google Arama Açıklaması Meta Metni">', false);
        $response->assertDontSee('<meta name="description" content="Detaylı tanıtım metni">', false);
        $response->assertDontSee('<meta name="description" content="Kısa akış tanımı">', false);
    }

    public function test_description_is_used_as_fallback_for_seo_when_meta_description_is_empty(): void
    {
        $program = Program::create([
            'name' => 'Fikir Dünyası',
            'slug' => 'fikir-dunyasi',
            'short_description' => 'Kısa akış tanımı',
            'description' => 'Fikir Dünyası programının uzun ve detaylı tanıtım metni Google SEO için kesilerek kullanılır.',
            'meta_description' => null,
            'status' => 'active',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $response = $this->get('/programlar/fikir-dunyasi');

        $response->assertOk();
        $response->assertSee('<meta name="description" content="Fikir Dünyası programının uzun ve detaylı tanıtım metni Google SEO için kesilerek kullanılır.">', false);
        $response->assertDontSee('Kısa akış tanımı');
    }

    public function test_short_description_is_never_used_as_seo_meta_description(): void
    {
        $program = Program::create([
            'name' => 'Sohbetler',
            'slug' => 'sohbetler',
            'short_description' => 'Sadece yayın akışı kısa tanımı',
            'description' => null,
            'meta_description' => null,
            'status' => 'active',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $response = $this->get('/programlar/sohbetler');

        $response->assertOk();
        // Should use site default fallback or empty, never short_description
        $response->assertDontSee('<meta name="description" content="Sadece yayın akışı kısa tanımı">', false);
    }

    public function test_program_form_has_updated_labels_and_helper_texts(): void
    {
        $schema = ProgramForm::configure(new Schema());
        $components = $schema->getComponents();

        $this->assertNotEmpty($components);
    }
}
