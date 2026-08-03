<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\FontFamilies\Pages\CreateFontFamily;
use App\Models\FontFamily;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class FontFamilyResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'super_admin']);
    }

    public function test_font_family_can_be_created_through_the_admin_form(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateFontFamily::class)
            ->fillForm([
                'name' => 'Roboto',
                'source_type' => 'system',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('font_families', ['name' => 'Roboto', 'slug' => 'roboto']);
    }

    public function test_only_one_default_font_remains_at_a_time(): void
    {
        $first = FontFamily::create(['name' => 'Font A', 'slug' => 'font-a', 'source_type' => 'system', 'is_default' => true]);
        $second = FontFamily::create(['name' => 'Font B', 'slug' => 'font-b', 'source_type' => 'system', 'is_default' => true]);

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
    }

    public function test_default_font_cannot_be_deleted(): void
    {
        $font = FontFamily::create(['name' => 'Font A', 'slug' => 'font-a', 'source_type' => 'system', 'is_default' => true]);

        $this->expectException(ValidationException::class);

        $font->delete();
    }

    public function test_non_default_font_can_be_deleted(): void
    {
        $font = FontFamily::create(['name' => 'Font A', 'slug' => 'font-a', 'source_type' => 'system', 'is_default' => false]);

        $font->delete();

        $this->assertDatabaseMissing('font_families', ['id' => $font->id]);
    }

    public function test_invalid_font_file_extension_is_rejected_by_the_extensions_rule(): void
    {
        $maliciousFile = File::fake()->create('malicious.php', 10);

        $validator = Validator::make(
            ['font' => $maliciousFile],
            ['font' => 'extensions:woff,woff2']
        );

        $this->assertTrue($validator->fails());
    }

    public function test_valid_woff_file_passes_the_extensions_rule(): void
    {
        $validFile = File::fake()->create('font.woff2', 10);

        $validator = Validator::make(
            ['font' => $validFile],
            ['font' => 'extensions:woff,woff2']
        );

        $this->assertFalse($validator->fails());
    }
}
