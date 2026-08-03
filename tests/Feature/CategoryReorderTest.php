<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryReorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_reorder_categories(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);

        $cat1 = Category::create(['name' => 'Kategori 1', 'slug' => 'cat-1', 'sort_order' => 0]);
        $cat2 = Category::create(['name' => 'Kategori 2', 'slug' => 'cat-2', 'sort_order' => 1]);
        $cat3 = Category::create(['name' => 'Kategori 3', 'slug' => 'cat-3', 'sort_order' => 2]);

        // Reorder cat3 to position 0, cat1 to position 1, cat2 to position 2
        $payload = [
            'items' => [
                ['id' => $cat3->id, 'position' => 0],
                ['id' => $cat1->id, 'position' => 1],
                ['id' => $cat2->id, 'position' => 2],
            ],
        ];

        $response = $this->actingAs($user)
            ->postJson(route('admin.categories.reorder'), $payload);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(0, $cat3->fresh()->sort_order);
        $this->assertSame(1, $cat1->fresh()->sort_order);
        $this->assertSame(2, $cat2->fresh()->sort_order);
    }

    public function test_unauthorized_user_cannot_reorder_categories(): void
    {
        $user = User::factory()->create(['role' => 'viewer']);

        $cat1 = Category::create(['name' => 'Kategori 1', 'slug' => 'cat-1', 'sort_order' => 0]);
        $cat2 = Category::create(['name' => 'Kategori 2', 'slug' => 'cat-2', 'sort_order' => 1]);

        $payload = [
            'items' => [
                ['id' => $cat2->id, 'position' => 0],
                ['id' => $cat1->id, 'position' => 1],
            ],
        ];

        $response = $this->actingAs($user)
            ->postJson(route('admin.categories.reorder'), $payload);

        $response->assertStatus(403);
    }

    public function test_invalid_payload_returns_validation_error(): void
    {
        $user = User::factory()->create(['role' => 'administrator']);

        $payload = [
            'items' => [
                ['id' => 999999, 'position' => -1],
            ],
        ];

        $response = $this->actingAs($user)
            ->postJson(route('admin.categories.reorder'), $payload);

        $response->assertStatus(422);
    }
}
