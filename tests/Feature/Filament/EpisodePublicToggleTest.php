<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Episodes\Pages\ListEpisodes;
use App\Models\Episode;
use App\Models\Program;
use App\Models\ProgramSeason;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class EpisodePublicToggleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Program $program;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'administrator']);
        $this->program = Program::create([
            'name' => 'Hikmet Arayışları',
            'slug' => 'hikmet-arayislari',
            'status' => 'active',
            'is_active' => true,
            'show_on_public' => true,
        ]);
    }

    public function test_episode_public_eye_toggle_switches_visibility_and_updates_public_page_without_touching_youtube(): void
    {
        // Prevent any external HTTP calls to prove YouTube API is never contacted
        Http::preventStrayRequests();

        $season = ProgramSeason::create([
            'program_id' => $this->program->id,
            'season_number' => 1,
            'season_year' => '2026',
        ]);

        $originalYoutubeUrl = 'https://www.youtube.com/watch?v=TEST_TOGGLE_VID_01';

        $episode = Episode::create([
            'program_id' => $this->program->id,
            'season_number' => 1,
            'season_year' => '2026',
            'episode_number' => 1,
            'title' => 'Özel Görünürlük Test Bölümü',
            'youtube_url' => $originalYoutubeUrl,
            'status' => 'published',
            'show_on_public' => true,
            'is_active' => true,
        ]);

        // 1. Initially public = true -> Episode is visible on public page
        $resPublicInitial = $this->get('/programlar/hikmet-arayislari');
        $resPublicInitial->assertOk();
        $resPublicInitial->assertSee('Özel Görünürlük Test Bölümü');

        // 2. Admin navigates to season detail table
        $livewireTest = Livewire::actingAs($this->admin)
            ->withQueryParams([
                'program_id' => $this->program->id,
                'season_number' => 1,
            ])
            ->test(ListEpisodes::class)
            ->assertTableColumnExists('show_on_public');

        // Trigger the single-click toggle on show_on_public column
        $livewireTest->callTableColumnAction('show_on_public', $episode);

        // 3. Verify episode state in database
        $episode->refresh();
        $this->assertFalse($episode->show_on_public);
        $this->assertFalse($episode->is_active);
        $this->assertEquals($originalYoutubeUrl, $episode->youtube_url, 'YouTube URL must remain unchanged');
        $this->assertDatabaseHas('episodes', [
            'id' => $episode->id,
            'show_on_public' => false,
        ]);

        // 4. Public program page: Episode is now hidden from public users
        $resPublicHidden = $this->get('/programlar/hikmet-arayislari');
        $resPublicHidden->assertOk();
        $resPublicHidden->assertDontSee('Özel Görünürlük Test Bölümü');

        // 5. Admin clicks eye icon toggle again
        $livewireTest->callTableColumnAction('show_on_public', $episode);

        // 6. Verify episode state is back to public
        $episode->refresh();
        $this->assertTrue($episode->show_on_public);
        $this->assertTrue($episode->is_active);
        $this->assertEquals($originalYoutubeUrl, $episode->youtube_url);

        // 7. Public program page: Episode is visible again
        $resPublicRestored = $this->get('/programlar/hikmet-arayislari');
        $resPublicRestored->assertOk();
        $resPublicRestored->assertSee('Özel Görünürlük Test Bölümü');
    }
}
