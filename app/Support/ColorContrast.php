<?php

namespace App\Support;

/**
 * WCAG relative-luminance contrast ratio between two colors, used to warn
 * admins when a text/background color pairing they picked would fail
 * accessibility guidelines. Returns null (rather than throwing) for any
 * value it cannot parse, so a malformed/partial color never breaks the form.
 */
class ColorContrast
{
    public static function ratio(?string $colorA, ?string $colorB): ?float
    {
        $rgbA = self::toRgb($colorA);
        $rgbB = self::toRgb($colorB);

        if ($rgbA === null || $rgbB === null) {
            return null;
        }

        $luminanceA = self::relativeLuminance($rgbA);
        $luminanceB = self::relativeLuminance($rgbB);

        $lighter = max($luminanceA, $luminanceB);
        $darker = min($luminanceA, $luminanceB);

        return round(($lighter + 0.05) / ($darker + 0.05), 2);
    }

    public static function meetsWcagAA(?string $colorA, ?string $colorB): bool
    {
        $ratio = self::ratio($colorA, $colorB);

        return $ratio !== null && $ratio >= 4.5;
    }

    /**
     * @return array{0: int, 1: int, 2: int}|null
     */
    private static function toRgb(?string $color): ?array
    {
        if (blank($color)) {
            return null;
        }

        $color = trim($color);

        if (! str_starts_with($color, '#')) {
            return null;
        }

        $hex = ltrim($color, '#');

        if (strlen($hex) === 3) {
            $hex = implode('', array_map(fn ($char) => $char.$char, str_split($hex)));
        }

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return null;
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private static function relativeLuminance(array $rgb): float
    {
        [$r, $g, $b] = array_map(function (int $channel) {
            $normalized = $channel / 255;

            return $normalized <= 0.03928
                ? $normalized / 12.92
                : (($normalized + 0.055) / 1.055) ** 2.4;
        }, $rgb);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
}
