<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class _TempSidebarProbe extends TestCase
{
    use RefreshDatabase;

    public function test_probe(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        // Visiting Top Header once triggers its auto-seed of the
        // header_primary menu (Programlar/Yayın Akışı/Hatim.../Canlı),
        // exactly like a real admin's first visit would.
        $this->actingAs($user)->get('/admin/top-header');

        $response = $this->actingAs($user)->get('/admin/categories');
        $html = $response->getContent();

        file_put_contents(sys_get_temp_dir().'/admin_categories_sidebar.html', $html);

        foreach (['TOP HEADER', 'Programlar', 'Yayın Akışı', 'Hatim', 'Canlı', 'Kategoriler'] as $needle) {
            echo $needle.': '.(str_contains($html, $needle) ? 'FOUND' : 'MISSING')."\n";
        }
    }
}
