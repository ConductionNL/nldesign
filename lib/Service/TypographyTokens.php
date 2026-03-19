<?php

/**
 * NL Design Typography Token Definitions.
 *
 * @category Service
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0-or-later
 * @link     https://github.com/ConductionNL/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Service;

/**
 * Typography tab token definitions.
 *
 * Text colors and font family settings.
 */
class TypographyTokens
{
    /**
     * Returns the typography tab tokens.
     *
     * @return array<string, array{tab: string, type: string, label: string}> Typography tokens.
     */
    public static function getTokens(): array
    {
        return [
            '--color-main-text'        => ['tab' => 'typography', 'type' => 'color', 'label' => 'Main text color'],
            '--color-text-maxcontrast' => ['tab' => 'typography', 'type' => 'color', 'label' => 'Text max contrast'],
            '--color-text-light'       => ['tab' => 'typography', 'type' => 'color', 'label' => 'Text light'],
            '--color-text-lighter'     => ['tab' => 'typography', 'type' => 'color', 'label' => 'Text lighter'],
            '--color-text-error'       => ['tab' => 'typography', 'type' => 'color', 'label' => 'Text error'],
            '--color-text-success'     => ['tab' => 'typography', 'type' => 'color', 'label' => 'Text success'],
            '--color-text-warning'     => ['tab' => 'typography', 'type' => 'color', 'label' => 'Text warning'],
            '--font-face'              => ['tab' => 'typography', 'type' => 'text',  'label' => 'Font family'],
        ];
    }//end getTokens()
}//end class
