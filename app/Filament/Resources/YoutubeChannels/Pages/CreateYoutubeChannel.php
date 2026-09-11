<?php

namespace App\Filament\Resources\YoutubeChannels\Pages;

use App\Filament\Resources\YoutubeChannels\YoutubeChannelResource;
use App\Services\YouTube\YouTubeChannelFetchService;
use Filament\Resources\Pages\CreateRecord;

class CreateYoutubeChannel extends CreateRecord
{
    protected static string $resource = YoutubeChannelResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (filled($data['url']) && (blank($data['handle']) || blank($data['channel_id']))) {
            try {
                $service = app(YouTubeChannelFetchService::class);
                $info = $service->fetchChannelInfo($data['url']);
                $data['channel_id'] = $info['channel_id'];
                $data['handle'] = $info['handle'];
                $data['url'] = $info['canonical_url'];
                if (blank($data['logo'])) {
                    $data['logo'] = $info['avatar_url'];
                }
            } catch (\Exception $e) {
                // Keep manual input if API call fails
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
