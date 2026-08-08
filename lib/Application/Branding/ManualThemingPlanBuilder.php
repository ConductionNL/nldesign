<?php

/**
 * NL Design Manual Theming Plan Builder.
 *
 * @category Application
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Application\Branding;

/**
 * Convert validated profile hints into a non-executing theming plan.
 */
final class ManualThemingPlanBuilder
{
    private const COLOR_PATTERN = '/^#[0-9a-fA-F]{6}$/';
    private const ASSET_PATTERN = '#^img/(?:logos|backgrounds)/[a-zA-Z0-9._-]+\.(?:svg|png|jpe?g|webp)$#i';

    /**
     * Build an allowlisted manual plan.
     *
     * @param mixed $theming Raw profile theming hints.
     *
     * @return array{
     *     mode: string,
     *     steps: array<int, array{field: string, value: string, manual_instruction: string}>,
     *     appliesAutomatically: bool,
     *     note: string
     * }
     */
    public function build(mixed $theming): array
    {
        $steps = [];
        if (is_array($theming) === true) {
            $instructions = [
                'primary_color'    => 'Nextcloud theming primary color',
                'background_color' => 'Nextcloud theming background color',
                'logo'             => 'Upload/use this file as the Nextcloud theming logo',
                'background'       => 'Upload/use this file as the Nextcloud theming background',
            ];

            foreach ($instructions as $field => $instruction) {
                $value = $theming[$field] ?? null;
                if (is_string($value) === false || $this->isValidValue(field: $field, value: $value) === false) {
                    continue;
                }

                $steps[] = [
                    'field'              => $field,
                    'value'              => $value,
                    'manual_instruction' => $instruction,
                ];
            }
        }//end if

        return [
            'mode'                 => 'manual',
            'steps'                => $steps,
            'appliesAutomatically' => false,
            'note'                 => 'Profile activation does not change Nextcloud Theming settings.',
        ];
    }//end build()

    /**
     * Validate an allowlisted hint value.
     *
     * @param string $field Hint field.
     * @param string $value Hint value.
     *
     * @return bool Whether the value is safe.
     */
    private function isValidValue(string $field, string $value): bool
    {
        if ($field === 'primary_color' || $field === 'background_color') {
            return preg_match(self::COLOR_PATTERN, $value) === 1;
        }

        return preg_match(self::ASSET_PATTERN, $value) === 1;
    }//end isValidValue()
}//end class
