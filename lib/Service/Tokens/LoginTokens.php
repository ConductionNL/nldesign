<?php

/**
 * NL Design Login Token Definitions.
 *
 * @category Service
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0-or-later
 * @link     https://github.com/ConductionNL/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Service\Tokens;

/**
 * Login and branding tab token definitions.
 *
 * Primary brand colors that drive login page buttons, links,
 * navigation accents, and interactive highlights.
 */
class LoginTokens
{
    /**
     * Returns the login and branding tab tokens.
     *
     * @return array<string, array{tab: string, type: string, label: string}> Login tokens.
     */
    public static function getTokens(): array
    {
        return [
            '--color-primary'                     => ['tab' => 'login', 'type' => 'color', 'label' => 'Primary color'],
            '--color-primary-text'                => ['tab' => 'login', 'type' => 'color', 'label' => 'Primary text color'],
            '--color-primary-hover'               => ['tab' => 'login', 'type' => 'color', 'label' => 'Primary hover color'],
            '--color-primary-element'             => ['tab' => 'login', 'type' => 'color', 'label' => 'Primary element color'],
            '--color-primary-element-hover'       => ['tab' => 'login', 'type' => 'color', 'label' => 'Primary element hover'],
            '--color-primary-element-text'        => ['tab' => 'login', 'type' => 'color', 'label' => 'Primary element text'],
            '--color-primary-light'               => ['tab' => 'login', 'type' => 'color', 'label' => 'Primary light'],
            '--color-primary-light-hover'         => ['tab' => 'login', 'type' => 'color', 'label' => 'Primary light hover'],
            '--color-primary-light-text'          => ['tab' => 'login', 'type' => 'color', 'label' => 'Primary light text'],
            '--color-primary-element-light'       => ['tab' => 'login', 'type' => 'color', 'label' => 'Primary element light'],
            '--color-primary-element-light-text'  => ['tab' => 'login', 'type' => 'color', 'label' => 'Primary element light text'],
            '--color-primary-element-light-hover' => ['tab' => 'login', 'type' => 'color', 'label' => 'Primary element light hover'],
        ];
    }//end getTokens()
}//end class
