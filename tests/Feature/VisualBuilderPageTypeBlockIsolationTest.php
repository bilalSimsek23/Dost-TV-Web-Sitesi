<?php

namespace Tests\Feature;

use App\Models\HomepageLayout;
use App\Models\ProgramCollection;
use App\Models\VideoCollection;
use App\Services\Home\HomepageBlockRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisualBuilderPageTypeBlockIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_fixed_blocks_are_only_present_for_homepage_layouts()
    {
        $homeFixed = HomepageBlockRegistry::getFixedBlocksForPageType('home');
        $this->assertEquals(['header', 'hero', 'footer'], $homeFixed);

        $this->assertEmpty(HomepageBlockRegistry::getFixedBlocksForPageType('program_collection'));
        $this->assertEmpty(HomepageBlockRegistry::getFixedBlocksForPageType('video_collection'));
        $this->assertEmpty(HomepageBlockRegistry::getFixedBlocksForPageType('program_detail'));
        $this->assertEmpty(HomepageBlockRegistry::getFixedBlocksForPageType('schedule'));
        $this->assertEmpty(HomepageBlockRegistry::getFixedBlocksForPageType('program_index'));
        $this->assertEmpty(HomepageBlockRegistry::getFixedBlocksForPageType('live_tv'));
    }

    public function test_add_block_dropdown_is_isolated_per_page_type()
    {
        // 1. Program Collection
        $pColBlocks = HomepageBlockRegistry::getBlockTypesForPageType('program_collection');
        $this->assertArrayHasKey('collection_header', $pColBlocks);
        $this->assertArrayHasKey('program_collection_grid', $pColBlocks);
        $this->assertArrayHasKey('today_schedule', $pColBlocks);
        $this->assertArrayNotHasKey('instagram_videos', $pColBlocks);

        // 2. Video Collection
        $vColBlocks = HomepageBlockRegistry::getBlockTypesForPageType('video_collection');
        $this->assertArrayHasKey('collection_header', $vColBlocks);
        $this->assertArrayHasKey('video_collection_grid', $vColBlocks);
        $this->assertArrayHasKey('today_schedule', $vColBlocks);
        $this->assertArrayNotHasKey('instagram_videos', $vColBlocks);


        // 3. Program Detail
        $pDetailBlocks = HomepageBlockRegistry::getBlockTypesForPageType('program_detail');
        $this->assertArrayHasKey('program_hero', $pDetailBlocks);
        $this->assertArrayHasKey('episodes_shelf', $pDetailBlocks);
        $this->assertArrayNotHasKey('today_schedule', $pDetailBlocks);

        // 4. Schedule
        $schedBlocks = HomepageBlockRegistry::getBlockTypesForPageType('schedule');
        $this->assertArrayHasKey('today_schedule', $schedBlocks);
        $this->assertArrayNotHasKey('program_hero', $schedBlocks);

        // 5. Live TV
        $liveTvBlocks = HomepageBlockRegistry::getBlockTypesForPageType('live_tv');
        $this->assertArrayHasKey('live_stream', $liveTvBlocks);
        $this->assertArrayNotHasKey('program_hero', $liveTvBlocks);
    }
}
