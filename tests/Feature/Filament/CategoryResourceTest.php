<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\RelationManagers\ProgramsRelationManager;
use App\Models\Category;
use App\Models\Episode;
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

    public function test_category_navigation_group_is_under_program_and_video_management(): void
    {
        $this->assertEquals('Program ve Video Yönetimi', \App\Filament\Resources\Categories\CategoryResource::getNavigationGroup());
        $this->assertEquals(1, \App\Filament\Resources\Categories\CategoryResource::getNavigationSort());
    }

    public function test_parent_id_and_sort_order_are_removed_from_form_schema_but_db_columns_exist(): void
    {
        $category = Category::create([
            'name' => 'Dini Programlar',
            'slug' => 'dini-programlar',
            'parent_id' => null,
            'sort_order' => 10,
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'sort_order' => 10,
        ]);
    }

    public function test_attached_programs_relation_manager_lists_category_programs_and_allows_attach_and_detach(): void
    {
        $category = Category::create(['name' => 'Kültür Sanat', 'slug' => 'kultur-sanat']);
        $otherCategory = Category::create(['name' => 'Spor', 'slug' => 'spor']);

        $programAttached = Program::create(['name' => 'Bağlı Program', 'slug' => 'bagli-program', 'status' => 'active']);
        $programOther = Program::create(['name' => 'Diğer Program', 'slug' => 'diger-program', 'status' => 'active']);

        $category->programs()->attach($programAttached);
        $otherCategory->programs()->attach($programOther);

        Episode::create([
            'program_id' => $programAttached->id,
            'title' => 'Test Bölüm',
            'status' => 'published',
        ]);

        // Assert relation manager lists only attached program
        Livewire::actingAs($this->admin)
            ->test(ProgramsRelationManager::class, [
                'ownerRecord' => $category,
                'pageClass' => EditCategory::class,
            ])
            ->assertCanSeeTableRecords([$programAttached])
            ->assertCanNotSeeTableRecords([$programOther]);

        // Attach unattached program
        Livewire::actingAs($this->admin)
            ->test(ProgramsRelationManager::class, [
                'ownerRecord' => $category,
                'pageClass' => EditCategory::class,
            ])
            ->callTableAction('attach', null, ['recordId' => $programOther->id])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('category_program', [
            'category_id' => $category->id,
            'program_id' => $programOther->id,
        ]);

        // Detach program (pivot record removed, program remains intact)
        Livewire::actingAs($this->admin)
            ->test(ProgramsRelationManager::class, [
                'ownerRecord' => $category,
                'pageClass' => EditCategory::class,
            ])
            ->callTableAction('detach', $programAttached)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseMissing('category_program', [
            'category_id' => $category->id,
            'program_id' => $programAttached->id,
        ]);

        // Program record itself was not deleted
        $this->assertDatabaseHas('programs', ['id' => $programAttached->id]);
    }
}
