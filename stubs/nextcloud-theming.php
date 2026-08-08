<?php

declare(strict_types=1);

namespace OCA\Theming;

/**
 * Static-analysis shape for the isolated, non-load-bearing Theming probe.
 * These private classes are not part of the app's runtime contract.
 */
class ThemingDefaults
{
    public function set(string $setting, string $value): void
    {
    }

    public function undo(string $setting): void
    {
    }
}

class ImageManager
{
    public function updateImage(string $key, string $tmpFile): string
    {
        return '';
    }

    public function hasImage(string $key): bool
    {
        return false;
    }
}
