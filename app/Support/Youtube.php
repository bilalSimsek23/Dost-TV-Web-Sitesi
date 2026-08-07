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
}
