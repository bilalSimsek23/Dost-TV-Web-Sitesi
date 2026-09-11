<?php

namespace App\Filament\Resources\InstagramVideos\Pages;

use App\Filament\Resources\InstagramVideos\InstagramVideoResource;
use App\Services\Instagram\InstagramFetchService;
use Filament\Resources\Pages\CreateRecord;

class CreateInstagramVideo extends CreateRecord
{
    protected static string $resource = InstagramVideoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-enrich data if user didn't hit fetch button manually
        if (! empty($data['permalink']) && (empty($data['shortcode']) || empty($data['thumbnail_url']))) {
            try {
                $service = app(InstagramFetchService::class);
                $info = $service->fetchMediaData($data['permalink']);
                $data['permalink'] = $info['permalink'];
                $data['shortcode'] = $info['shortcode'];
                $data['username'] = $info['username'];
                $data['caption'] = ! empty($data['caption']) ? $data['caption'] : $info['caption'];
                $data['thumbnail_url'] = $info['thumbnail_url'];
                $data['instagram_media_id'] = $info['instagram_media_id'];
                $data['media_type'] = $info['media_type'];
            } catch (\Exception $e) {
                // Pass through if validation fails
            }
        }

        return $data;
    }
}
