<?php

namespace App\Support;

class Youtube
{
    public static function embedUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        // Match YouTube Playlists
        if (preg_match('/[?&]list=([A-Za-z0-9_-]+)/', $url, $matches)) {
            return "https://www.youtube.com/embed/videoseries?list={$matches[1]}";
        }

        // Match YouTube Single Videos
        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([A-Za-z0-9_-]{11})/', $url, $matches)) {
            return "https://www.youtube.com/embed/{$matches[1]}";
        }

        return null;
    }

    public static function extractPlaylistId(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $url = trim($url);

        if (preg_match('/[?&]list=([A-Za-z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Direct playlist ID if entered directly
        if (preg_match('/^(PL|FL|UU|LL|RD|OLAK5uy_)[A-Za-z0-9_-]+$/', $url)) {
            return $url;
        }

        return null;
    }

    public static function extractVideoId(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $url = trim($url);

        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/|v\/))([A-Za-z0-9_-]{11})/', $url, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $url)) {
            return $url;
        }

        return null;
    }

    public static function canonicalUrl(?string $videoId): ?string
    {
        if (blank($videoId)) {
            return null;
        }

        $id = static::extractVideoId($videoId) ?? $videoId;

        return "https://www.youtube.com/watch?v={$id}";
    }

    public static function thumbnailUrl(?string $videoId): ?string
    {
        if (blank($videoId)) {
            return null;
        }

        $id = static::extractVideoId($videoId) ?? $videoId;

        return "https://i.ytimg.com/vi/{$id}/hqdefault.jpg";
    }

    /**
     * Extracts YouTube channel identifier (handle, channel_id, username, or custom).
     *
     * @param string|null $url
     * @return array{type: 'handle'|'id'|'username'|'custom', value: string}|null
     */
    public static function extractChannelIdentifier(?string $url): ?array
    {
        if (blank($url)) {
            return null;
        }

        $url = trim($url);

        // 1. Handle (@Handle or youtube.com/@Handle)
        if (preg_match('/(?:youtube\.com\/)?@([A-Za-z0-9_.-]+)/i', $url, $matches)) {
            return ['type' => 'handle', 'value' => '@' . $matches[1]];
        }

        // 2. Channel ID (UC... or youtube.com/channel/UC...)
        if (preg_match('/(?:youtube\.com\/channel\/)?(UC[A-Za-z0-9_-]{22})/i', $url, $matches)) {
            return ['type' => 'id', 'value' => $matches[1]];
        }

        // 3. Custom URL: youtube.com/c/CustomName
        if (preg_match('/youtube\.com\/c\/([A-Za-z0-9_.-]+)/i', $url, $matches)) {
            return ['type' => 'custom', 'value' => $matches[1]];
        }

        // 4. Legacy Username: youtube.com/user/UserName
        if (preg_match('/youtube\.com\/user\/([A-Za-z0-9_.-]+)/i', $url, $matches)) {
            return ['type' => 'username', 'value' => $matches[1]];
        }

        // 5. Bare string starting with UC (UC...)
        if (preg_match('/^UC[A-Za-z0-9_-]{22}$/', $url)) {
            return ['type' => 'id', 'value' => $url];
        }

        return null;
    }
}
