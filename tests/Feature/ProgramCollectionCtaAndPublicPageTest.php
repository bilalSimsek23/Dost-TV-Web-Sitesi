<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Episode;
use App\Models\Program;
use App\Models\ProgramCollection;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleTemplateItem;
use App\Models\User;
use App\Services\Home\CtaRouteResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramCollectionCtaAndPublicPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@dosttv.com',
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    public function test_cta_route_resolver_points_to_program_collection_show_when_collection_id_is_set(): void
    {
        $collection = ProgramCollection::create([
            'name' => 'Güncel Programlar',
            'slug' => 'guncel-programlar',
            'source_type' => 'active_period_schedule',
            'is_active' => true,
        ]);

        $block = [
            'block_type' => 'program_showcase',
            'title' => 'Bu Dönem Yayında', // Title is presentation only
            'source_mode' => 'program_collections',
            'program_collection_id' => $collection->id,
        ];

        $ctaUrl = CtaRouteResolver::resolveForProgramBlock($block);

        $this->assertEquals(route('program.collections.show', 'guncel-programlar'), $ctaUrl);
    }

    public function test_cta_route_resolver_falls_back_to_programs_index_for_legacy_block_without_collection_id(): void
    {
        $legacyBlock = [
            'block_type' => 'program_showcase',
            'source_mode' => 'featured_programs',
            'program_collection_id' => null,
        ];

        $ctaUrl = CtaRouteResolver::resolveForProgramBlock($legacyBlock);

        $this->assertEquals(route('programs.index'), $ctaUrl);
    }

    public function test_public_program_collection_page_resolves_active_period_schedule_programs(): void
    {
        $template = ScheduleTemplate::create([
            'name' => 'Bahar Şablonu',
            'status' => 'published',
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
        ]);

        $program = Program::create([
            'name' => 'Akış Programı',
            'slug' => 'akis-programi',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $program->id,
            'day_of_week' => 1,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'is_active' => true,
        ]);

        $collection = ProgramCollection::create([
            'name' => 'Güncel Programlar',
            'slug' => 'guncel-programlar',
            'source_type' => 'active_period_schedule',
            'is_active' => true,
        ]);

        $response = $this->get(route('program.collections.show', $collection->slug));

        $response->assertStatus(200);
        $response->assertSee('Güncel Programlar');
        $response->assertSee('Akış Programı');
        $response->assertSee(route('programs.show', $program));
    }

    public function test_public_program_collection_page_resolves_category_programs(): void
    {
        $category = Category::create(['name' => 'Aile', 'slug' => 'aile', 'is_active' => true]);

        $program = Program::create([
            'name' => 'Aile Sohbetleri',
            'slug' => 'aile-sohbetleri',
            'is_active' => true,
            'show_on_public' => true,
        ]);
        $program->categories()->attach($category->id);

        $collection = ProgramCollection::create([
            'name' => 'Aile Programları',
            'slug' => 'aile-programlari',
            'source_type' => 'category',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $response = $this->get(route('program.collections.show', $collection->slug));

        $response->assertStatus(200);
        $response->assertSee('Aile Programları');
        $response->assertSee('Aile Sohbetleri');
    }

    public function test_public_program_collection_page_resolves_manual_programs(): void
    {
        $program = Program::create([
            'name' => 'Manuel Seçim Programı',
            'slug' => 'manuel-secim-programi',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $collection = ProgramCollection::create([
            'name' => 'Editörün Seçtikleri',
            'slug' => 'editorun-sectikleri',
            'source_type' => 'manual',
            'is_active' => true,
        ]);
        $collection->programs()->attach($program->id);

        $response = $this->get(route('program.collections.show', $collection->slug));

        $response->assertStatus(200);
        $response->assertSee('Editörün Seçtikleri');
        $response->assertSee('Manuel Seçim Programı');
    }

    public function test_inactive_program_collection_returns_404(): void
    {
        $collection = ProgramCollection::create([
            'name' => 'Pasif Koleksiyon',
            'slug' => 'pasif-koleksiyon',
            'source_type' => 'manual',
            'is_active' => false,
        ]);

        $response = $this->get(route('program.collections.show', $collection->slug));

        $response->assertStatus(404);
    }

    public function test_empty_active_collection_renders_gracefully_without_500(): void
    {
        $collection = ProgramCollection::create([
            'name' => 'Boş Koleksiyon',
            'slug' => 'bos-koleksiyon',
            'source_type' => 'manual',
            'is_active' => true,
        ]);

        $response = $this->get(route('program.collections.show', $collection->slug));

        $response->assertStatus(200);
        $response->assertSee('Boş Koleksiyon');
        $response->assertSee('Henüz bu koleksiyonda gösterilecek program bulunmuyor.');
    }

    public function test_general_programs_index_page_remains_independent_and_lists_all_programs(): void
    {
        Program::create([
            'name' => 'Genel Arşiv Programı',
            'slug' => 'genel-arsiv-programi',
            'is_active' => true,
            'show_on_public' => true,
        ]);

        $response = $this->get(route('programs.index'));

        $response->assertStatus(200);
        $response->assertSee('Programlar');
        $response->assertSee('Genel Arşiv Programı');
    }

    public function test_homepage_rendered_html_contains_program_collection_cta_url_for_guncel_programlar(): void
    {
        $collection = ProgramCollection::create([
            'name' => 'Güncel Programlar',
            'slug' => 'guncel-programlar',
            'source_type' => 'active_period_schedule',
            'is_active' => true,
        ]);

        $layout = \App\Models\HomepageLayout::create([
            'name' => 'Canlı Test Düzeni',
            'is_active' => true,
            'draft_sections' => [
                [
                    'uuid' => 'block-guncel-prog',
                    'block_type' => 'program_showcase',
                    'visible' => true,
                    'title' => 'Bu Dönem Yayında', // Title is presentation only
                    'show_title' => true,
                    'source_mode' => 'program_collections',
                    'program_collection_id' => $collection->id,
                ],
            ],
            'published_sections' => [
                [
                    'uuid' => 'block-guncel-prog',
                    'block_type' => 'program_showcase',
                    'visible' => true,
                    'title' => 'Bu Dönem Yayında', // Title is presentation only
                    'show_title' => true,
                    'source_mode' => 'program_collections',
                    'program_collection_id' => $collection->id,
                ],
            ],
            'published_at' => now(),
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee(route('program.collections.show', 'guncel-programlar'));
    }

    public function test_cta_route_resolver_matches_collection_by_source_mode_when_collection_id_is_null(): void
    {
        $collection = ProgramCollection::create([
            'name' => 'Güncel Programlar',
            'slug' => 'guncel-programlar',
            'source_type' => 'active_period_schedule',
            'is_active' => true,
        ]);

        $blockWithoutCollectionId = [
            'block_type' => 'program_showcase',
            'title' => 'Özel Başlık',
            'source_mode' => 'active_period_schedule',
            'program_collection_id' => null,
        ];

        $ctaUrl = CtaRouteResolver::resolveForProgramBlock($blockWithoutCollectionId);

        $this->assertEquals(route('program.collections.show', 'guncel-programlar'), $ctaUrl);
    }

    public function test_public_program_collection_page_resolves_active_period_live_programs_only(): void
    {
        $template = ScheduleTemplate::create([
            'name' => 'Canlı Yayın Dönemi',
            'status' => 'published',
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
        ]);

        $liveProg = Program::create([
            'name' => 'Canlı Hakikat İklimi',
            'slug' => 'canli-hakikat-iklimi',
            'is_active' => true,
            'show_on_public' => true,
            'is_featured' => false,
        ]);

        $tapeProg = Program::create([
            'name' => 'Bant Yayın Programı',
            'slug' => 'bant-yayin-programi',
            'is_active' => true,
            'show_on_public' => true,
            'is_featured' => false,
        ]);

        $legacyFeaturedProg = Program::create([
            'name' => 'Eski Yıldızlı Ama Akışta Olmayan Program',
            'slug' => 'eski-yildizli-program',
            'is_active' => true,
            'show_on_public' => true,
            'is_featured' => true,
        ]);

        // Scheduled live
        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $liveProg->id,
            'day_of_week' => 0,
            'start_time' => '20:00:00',
            'end_time' => '21:00:00',
            'is_live' => true,
            'is_active' => true,
        ]);

        // Scheduled non-live tape
        ScheduleTemplateItem::create([
            'schedule_template_id' => $template->id,
            'program_id' => $tapeProg->id,
            'day_of_week' => 1,
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'is_live' => false,
            'is_active' => true,
        ]);

        $collection = ProgramCollection::create([
            'name' => 'Öne Çıkan Programlar',
            'slug' => 'one-cikan-programlar',
            'source_type' => 'active_period_live_programs',
            'is_active' => true,
        ]);

        $response = $this->get(route('program.collections.show', $collection->slug));

        $response->assertStatus(200);
        $response->assertSee('Öne Çıkan Programlar');
        $response->assertSee('Canlı Hakikat İklimi');
        $response->assertDontSee('Bant Yayın Programı');
        $response->assertDontSee('Eski Yıldızlı Ama Akışta Olmayan Program');
    }

    public function test_one_cikanlar_homepage_block_cta_resolves_to_live_programs_collection_route(): void
    {
        $collection = ProgramCollection::create([
            'name' => 'Öne Çıkan Programlar',
            'slug' => 'one-cikan-programlar',
            'source_type' => 'active_period_live_programs',
            'is_active' => true,
        ]);

        $block = [
            'block_type' => 'program_showcase',
            'title' => 'Öne Çıkan Programlar',
            'source_mode' => 'program_collections',
            'program_collection_id' => $collection->id,
        ];

        $ctaUrl = CtaRouteResolver::resolveForProgramBlock($block);

        $this->assertEquals(route('program.collections.show', 'one-cikan-programlar'), $ctaUrl);
    }
}
