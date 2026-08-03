<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Models\Category;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'super_admin']);
    }

    public function test_category_can_be_created_through_the_admin_form(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateCategory::class)
            ->fillForm([
                'name' => 'Dini Sohbetler',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', ['name' => 'Dini Sohbetler', 'slug' => 'dini-sohbetler']);
    }

    public function test_category_cannot_become_its_own_parent(): void
    {
        $category = Category::create(['name' => 'Genel']);

        $this->expectException(ValidationException::class);

        $category->update(['parent_id' => $category->id]);
    }

    public function test_category_cannot_pick_a_descendant_as_its_parent(): void
    {
        $parent = Category::create(['name' => 'Üst']);
        $child = Category::create(['name' => 'Alt', 'parent_id' => $parent->id]);

        $this->expectException(ValidationException::class);

        $parent->update(['parent_id' => $child->id]);
    }

    public function test_deleting_a_category_with_linked_programs_does_not_delete_the_programs(): void
    {
        $category = Category::create(['name' => 'Haberler']);
        $program = Program::factory()->create(['name' => 'Gündem']);
        $program->categories()->attach($category);

        $category->delete();

        $this->assertDatabaseHas('programs', ['id' => $program->id]);
        $this->assertDatabaseMissing('category_program', ['category_id' => $category->id]);
    }
}
