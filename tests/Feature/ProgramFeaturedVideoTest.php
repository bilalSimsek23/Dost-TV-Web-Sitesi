<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramFeaturedVideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_featured_video_displays_most_recent_published_episode_by_aired_at(): void
    {
        $program = Program::create([
            'name' => 'Test Program',
            'slug' => 'test-program',
            'show_on_public' => true,
            'is_active' => true,
            'status' => 'published',
        ]);

        $oldEpisode = Episode::create([
            'program_id' => $program->id,
            'title' => 'Eski Bolum',
            'slug' => 'eski-bolum',
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=OLD12345678',
            'aired_at' => '2025-01-01',
            'is_active' => true,
            'show_on_public' => true,
            'status' => 'published',
        ]);

        $newEpisode = Episode::create([
            'program_id' => $program->id,
            'title' => 'Yeni Bolum',
            'slug' => 'yeni-bolum',
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=NEW12345678',
            'aired_at' => '2026-02-01',
            'is_active' => true,
            'show_on_public' => true,
            'status' => 'published',
        ]);

        $response = $this->get(route('programs.show', $program));

        $response->assertOk();
        $response->assertSee('https://www.youtube.com/embed/NEW12345678');
    }

    public function test_draft_or_inactive_episode_is_not_selected_as_featured_video(): void
    {
        $program = Program::create([
            'name' => 'Test Program Draft Test',
            'slug' => 'test-program-draft',
            'show_on_public' => true,
            'is_active' => true,
            'status' => 'published',
        ]);

        $publishedEpisode = Episode::create([
            'program_id' => $program->id,
            'title' => 'Yayindaki Bolum',
            'slug' => 'yayindaki-bolum',
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=PUB12345678',
            'aired_at' => '2026-01-01',
            'is_active' => true,
            'show_on_public' => true,
            'status' => 'published',
        ]);

        $draftEpisode = Episode::create([
            'program_id' => $program->id,
            'title' => 'Taslak Bolum',
            'slug' => 'taslak-bolum',
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=DFT12345678',
            'aired_at' => '2026-02-15',
            'is_active' => false,
            'show_on_public' => false,
            'status' => 'draft',
        ]);

        $response = $this->get(route('programs.show', $program));

        $response->assertOk();
        $response->assertSee('https://www.youtube.com/embed/PUB12345678');
        $response->assertDontSee('https://www.youtube.com/embed/DFT12345678');
    }

    public function test_fallback_trailer_is_used_when_no_public_episodes_exist(): void
    {
        $program = Program::create([
            'name' => 'Trailer Test Program',
            'slug' => 'trailer-test-program',
            'show_on_public' => true,
            'is_active' => true,
            'status' => 'published',
            'trailer_url' => 'https://www.youtube.com/watch?v=TRAILER1234',
        ]);

        $response = $this->get(route('programs.show', $program));

        $response->assertOk();
        $response->assertSee('https://www.youtube.com/embed/TRAILER1234');
    }

    public function test_programs_do_not_pull_episodes_from_other_programs(): void
    {
        $programA = Program::create([
            'name' => 'Program A',
            'slug' => 'program-a',
            'show_on_public' => true,
            'is_active' => true,
            'status' => 'published',
        ]);

        $programB = Program::create([
            'name' => 'Program B',
            'slug' => 'program-b',
            'show_on_public' => true,
            'is_active' => true,
            'status' => 'published',
        ]);

        Episode::create([
            'program_id' => $programA->id,
            'title' => 'Program A Bolum',
            'slug' => 'prog-a-bolum',
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=PROGA123456',
            'aired_at' => '2026-01-01',
            'is_active' => true,
            'show_on_public' => true,
            'status' => 'published',
        ]);

        Episode::create([
            'program_id' => $programB->id,
            'title' => 'Program B Bolum',
            'slug' => 'prog-b-bolum',
            'video_source' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=PROGB123456',
            'aired_at' => '2026-02-01',
            'is_active' => true,
            'show_on_public' => true,
            'status' => 'published',
        ]);

        $response = $this->get(route('programs.show', $programA));

        $response->assertOk();
        $response->assertSee('https://www.youtube.com/embed/PROGA123456');
        $response->assertDontSee('https://www.youtube.com/embed/PROGB123456');
    }
}
