<?php

namespace Tests\Feature;

use App\Models\HomepageLayout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageLayoutFaz1ATest extends TestCase
{
    use RefreshDatabase;

    public function test_page_type_defaults_to_home()
    {
        $layout = HomepageLayout::create([
            'name' => 'Test Ana Sayfa',
            'is_active' => true,
        ]);

        $this->assertEquals('home', $layout->page_type);
    }

    public function test_duplicate_creates_inactive_copy_with_fresh_uuids()
    {
        $original = HomepageLayout::create([
            'name' => 'Ana Sayfa Düzeni',
            'page_type' => 'home',
            'is_active' => true,
            'published_at' => now(),
            'draft_sections' => [
                ['uuid' => 'uuid-1111', 'type' => 'hero', 'is_visible' => true],
            ],
            'published_sections' => [
                ['uuid' => 'uuid-1111', 'type' => 'hero', 'is_visible' => true],
            ],
        ]);

        $copy = $original->duplicate();

        $this->assertEquals('Ana Sayfa Düzeni - Kopya', $copy->name);
        $this->assertFalse($copy->is_active);
        $this->assertNull($copy->published_at);
        $this->assertEquals('home', $copy->page_type);

        // Section UUIDs should be freshly generated and distinct
        $this->assertNotEquals('uuid-1111', $copy->draft_sections[0]['uuid']);
        $this->assertNotEquals('uuid-1111', $copy->published_sections[0]['uuid']);

        // Original layout should remain unaffected
        $this->assertTrue($original->fresh()->is_active);
        $this->assertEquals('uuid-1111', $original->fresh()->draft_sections[0]['uuid']);
    }

    public function test_active_isolation_by_page_type()
    {
        $home1 = HomepageLayout::create(['name' => 'Home 1', 'page_type' => 'home', 'is_active' => true]);
        $home2 = HomepageLayout::create(['name' => 'Home 2', 'page_type' => 'home', 'is_active' => false]);
        $programLayout = HomepageLayout::create(['name' => 'Program Layout', 'page_type' => 'program_detail', 'is_active' => true]);

        // Activate Home 2
        $home2->activate();

        $this->assertFalse($home1->fresh()->is_active);
        $this->assertTrue($home2->fresh()->is_active);

        // Program detail layout should REMAIN active!
        $this->assertTrue($programLayout->fresh()->is_active);
    }

    public function test_active_layout_deletion_is_prevented()
    {
        $activeLayout = HomepageLayout::create(['name' => 'Active Layout', 'page_type' => 'home', 'is_active' => true]);

        $this->expectException(\Exception::class);
        $activeLayout->delete();
    }
}
