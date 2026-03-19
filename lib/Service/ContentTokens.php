<?php

/**
 * NL Design Content Token Definitions.
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
 * Content area tab token definitions.
 *
 * Content area colors including backgrounds, borders, border radii,
 * placeholders, and animation timings.
 */
class ContentTokens
{
    /**
     * Returns the content area tab tokens.
     *
     * @return array<string, array{tab: string, type: string, label: string}> Content tokens.
     */
    public static function getTokens(): array
    {
        return [
            '--color-background-hover'   => ['tab' => 'content', 'type' => 'color', 'label' => 'Background hover'],
            '--color-background-dark'    => ['tab' => 'content', 'type' => 'color', 'label' => 'Background dark'],
            '--color-background-darker'  => ['tab' => 'content', 'type' => 'color', 'label' => 'Background darker'],
            '--color-placeholder-light'  => ['tab' => 'content', 'type' => 'color', 'label' => 'Placeholder light'],
            '--color-placeholder-dark'   => ['tab' => 'content', 'type' => 'color', 'label' => 'Placeholder dark'],
            '--color-border'             => ['tab' => 'content', 'type' => 'color', 'label' => 'Border color'],
            '--color-border-dark'        => ['tab' => 'content', 'type' => 'color', 'label' => 'Border dark'],
            '--color-border-maxcontrast' => ['tab' => 'content', 'type' => 'color', 'label' => 'Border max contrast'],
            '--border-radius'            => ['tab' => 'content', 'type' => 'text',  'label' => 'Border radius'],
            '--border-radius-small'      => ['tab' => 'content', 'type' => 'text',  'label' => 'Border radius small'],
            '--border-radius-element'    => ['tab' => 'content', 'type' => 'text',  'label' => 'Border radius element'],
            '--border-radius-large'      => ['tab' => 'content', 'type' => 'text',  'label' => 'Border radius large'],
            '--border-radius-rounded'    => ['tab' => 'content', 'type' => 'text',  'label' => 'Border radius rounded'],
            '--border-radius-pill'       => ['tab' => 'content', 'type' => 'text',  'label' => 'Border radius pill'],
            '--body-container-radius'    => ['tab' => 'content', 'type' => 'text',  'label' => 'Body container radius'],
            '--animation-quick'          => ['tab' => 'content', 'type' => 'text',  'label' => 'Animation quick'],
            '--animation-slow'           => ['tab' => 'content', 'type' => 'text',  'label' => 'Animation slow'],
        ];
    }//end getTokens()
}//end class
