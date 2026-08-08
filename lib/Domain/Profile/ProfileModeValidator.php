<?php

/**
 * Installable profile colour-mode validation.
 *
 * @category Domain
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Domain\Profile;

/**
 * Normalize one bounded colour mode and enforce accessible text contrast.
 */
final class ProfileModeValidator
{
    private const COLOR_PATTERN = '/^#[0-9a-fA-F]{6}$/';
    private const MODE_FIELDS   = [
        'primary'       => true,
        'primary_text'  => true,
        'primary_hover' => true,
    ];

    /**
     * Normalize one complete colour mode.
     *
     * @param mixed $value Raw mode object.
     *
     * @return array<string, string>|null Canonical colour mode.
     */
    public function normalize(mixed $value): ?array
    {
        if (is_array($value) === false
            || array_is_list($value) === true
            || count($value) !== count(self::MODE_FIELDS)
            || $this->hasOnlyModeFields(value: $value) === false
        ) {
            return null;
        }

        $mode = [];
        foreach (array_keys(self::MODE_FIELDS) as $field) {
            $color = $value[$field] ?? null;
            if (is_string($color) === false || preg_match(self::COLOR_PATTERN, $color) !== 1) {
                return null;
            }

            $mode[$field] = strtolower($color);
        }

        if ($this->contrastRatio(first: $mode['primary'], second: $mode['primary_text']) < 4.5
            || $this->contrastRatio(first: $mode['primary_hover'], second: $mode['primary_text']) < 4.5
        ) {
            return null;
        }

        return $mode;
    }//end normalize()

    /**
     * Keep mode objects closed to unsupported token fields.
     *
     * @param array<string, mixed> $value Candidate mode.
     *
     * @return bool Whether every field is supported.
     */
    private function hasOnlyModeFields(array $value): bool
    {
        foreach (array_keys($value) as $field) {
            if (isset(self::MODE_FIELDS[$field]) === false) {
                return false;
            }
        }

        return true;
    }//end hasOnlyModeFields()

    /**
     * Calculate WCAG relative contrast for two six-digit colours.
     *
     * @param string $first  First colour.
     * @param string $second Second colour.
     *
     * @return float Contrast ratio.
     */
    private function contrastRatio(string $first, string $second): float
    {
        $luminances = [
            $this->relativeLuminance(color: $first),
            $this->relativeLuminance(color: $second),
        ];
        rsort($luminances, SORT_NUMERIC);

        return ($luminances[0] + 0.05) / ($luminances[1] + 0.05);
    }//end contrastRatio()

    /**
     * Calculate relative luminance for one six-digit colour.
     *
     * @param string $color Hex colour.
     *
     * @return float Relative luminance.
     */
    private function relativeLuminance(string $color): float
    {
        $channels = [];
        foreach ([1, 3, 5] as $offset) {
            $channel = (float) hexdec(substr($color, $offset, 2)) / 255.0;
            if ($channel <= 0.04045) {
                $channels[] = $channel / 12.92;
            } else {
                $channels[] = (($channel + 0.055) / 1.055) ** 2.4;
            }
        }

        return (0.2126 * $channels[0]) + (0.7152 * $channels[1]) + (0.0722 * $channels[2]);
    }//end relativeLuminance()
}//end class
