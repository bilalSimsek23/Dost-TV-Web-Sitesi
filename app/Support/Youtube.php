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
}
