<?php

/**
 * NL Design Token Registry.
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
 * Canonical registry of editable Nextcloud CSS custom properties.
 *
 * This class is the single source of truth for:
 * - Which tokens the editor exposes for editing
 * - Which tab each token belongs to
 * - Which type of input to render (color picker or text field)
 * - The human-readable label for each token
 *
 * Tokens marked "intentionally not overridden" in overrides.css MUST NOT appear here.
 * The excluded list covers dark-mode vars, auto-calculated values, and layout constants.
 *
 * Tabs: login | content | status | typography
 * Types: color | text
 */
class TokenRegistry
{
    /**
     * Returns the full registry of editable tokens.
     *
     * Keys are CSS custom property names (e.g. '--color-primary').
     * Values are arrays with 'tab', 'type', and 'label' keys.
     *
     * @return array<string, array{tab: string, type: string, label: string}> The token registry.
     */
    public static function getTokens(): array
    {
        return [
            // TAB: login — Primary brand colors (login page, buttons, links, highlights).
            '--color-primary'                     => ['tab' => 'login', 'type' => 'color', 'label' => 'Primary color'],
            '--color-primary-text'                => ['tab' => 'login', 'type' => 'color', 'label' => 'Primary text color'],
            '--color-primary-hover'               => ['tab' => 'login', 'type' => 'color', 'label' => 'Primary hover color'],
            '--color-primary-element'             => [
                'tab'   => 'login',
                'type'  => 'color',
                'label' => 'Primary element color',
            ],
            '--color-primary-element-hover'       => [
                'tab'   => 'login',
                'type'  => 'color',
                'label' => 'Primary element hover',
            ],
            '--color-primary-element-text'        => [
                'tab'   => 'login',
                'type'  => 'color',
                'label' => 'Primary element text',
            ],
            '--color-primary-light'               => ['tab' => 'login', 'type' => 'color', 'label' => 'Primary light'],
            '--color-primary-light-hover'         => ['tab' => 'login', 'type' => 'color', 'label' => 'Primary light hover'],
            '--color-primary-light-text'          => ['tab' => 'login', 'type' => 'color', 'label' => 'Primary light text'],
            '--color-primary-element-light'       => [
                'tab'   => 'login',
                'type'  => 'color',
                'label' => 'Primary element light',
            ],
            '--color-primary-element-light-text'  => [
                'tab'   => 'login',
                'type'  => 'color',
                'label' => 'Primary element light text',
            ],
            '--color-primary-element-light-hover' => [
                'tab'   => 'login',
                'type'  => 'color',
                'label' => 'Primary element light hover',
            ],

            // TAB: content — Backgrounds, borders, scrollbar, border radii, animations.
            '--color-background-hover'            => ['tab' => 'content', 'type' => 'color', 'label' => 'Background hover'],
            '--color-background-dark'             => ['tab' => 'content', 'type' => 'color', 'label' => 'Background dark'],
            '--color-background-darker'           => ['tab' => 'content', 'type' => 'color', 'label' => 'Background darker'],
            '--color-placeholder-light'           => ['tab' => 'content', 'type' => 'color', 'label' => 'Placeholder light'],
            '--color-placeholder-dark'            => ['tab' => 'content', 'type' => 'color', 'label' => 'Placeholder dark'],
            '--color-border'                      => ['tab' => 'content', 'type' => 'color', 'label' => 'Border color'],
            '--color-border-dark'                 => ['tab' => 'content', 'type' => 'color', 'label' => 'Border dark'],
            '--color-border-maxcontrast'          => [
                'tab'   => 'content',
                'type'  => 'color',
                'label' => 'Border max contrast',
            ],
            '--color-scrollbar'                   => ['tab' => 'content', 'type' => 'color', 'label' => 'Scrollbar color'],
            '--border-radius'                     => ['tab' => 'content', 'type' => 'text',  'label' => 'Border radius'],
            '--border-radius-small'               => [
                'tab'   => 'content',
                'type'  => 'text',
                'label' => 'Border radius small',
            ],
            '--border-radius-element'             => [
                'tab'   => 'content',
                'type'  => 'text',
                'label' => 'Border radius element',
            ],
            '--border-radius-large'               => [
                'tab'   => 'content',
                'type'  => 'text',
                'label' => 'Border radius large',
            ],
            '--border-radius-rounded'             => [
                'tab'   => 'content',
                'type'  => 'text',
                'label' => 'Border radius rounded',
            ],
            '--border-radius-pill'                => [
                'tab'   => 'content',
                'type'  => 'text',
                'label' => 'Border radius pill',
            ],
            '--body-container-radius'             => [
                'tab'   => 'content',
                'type'  => 'text',
                'label' => 'Body container radius',
            ],
            '--animation-quick'                   => ['tab' => 'content', 'type' => 'text',  'label' => 'Animation quick'],
            '--animation-slow'                    => ['tab' => 'content', 'type' => 'text',  'label' => 'Animation slow'],

            // TAB: status — Error, warning, success, info, and semantic element/border variants.
            '--color-error'                       => ['tab' => 'status', 'type' => 'color', 'label' => 'Error color'],
            '--color-error-hover'                 => ['tab' => 'status', 'type' => 'color', 'label' => 'Error hover'],
            '--color-error-rgb'                   => ['tab' => 'status', 'type' => 'text',  'label' => 'Error color (RGB)'],
            '--color-element-error'               => ['tab' => 'status', 'type' => 'color', 'label' => 'Element error'],
            '--color-border-error'                => ['tab' => 'status', 'type' => 'color', 'label' => 'Border error'],
            '--color-warning'                     => ['tab' => 'status', 'type' => 'color', 'label' => 'Warning color'],
            '--color-warning-rgb'                 => [
                'tab'   => 'status',
                'type'  => 'text',
                'label' => 'Warning color (RGB)',
            ],
            '--color-element-warning'             => ['tab' => 'status', 'type' => 'color', 'label' => 'Element warning'],
            '--color-success'                     => ['tab' => 'status', 'type' => 'color', 'label' => 'Success color'],
            '--color-success-rgb'                 => [
                'tab'   => 'status',
                'type'  => 'text',
                'label' => 'Success color (RGB)',
            ],
            '--color-element-success'             => ['tab' => 'status', 'type' => 'color', 'label' => 'Element success'],
            '--color-border-success'              => ['tab' => 'status', 'type' => 'color', 'label' => 'Border success'],
            '--color-info'                        => ['tab' => 'status', 'type' => 'color', 'label' => 'Info color'],
            '--color-element-info'                => ['tab' => 'status', 'type' => 'color', 'label' => 'Element info'],
            '--color-favorite'                    => [
                'tab'   => 'status',
                'type'  => 'color',
                'label' => 'Favorite (star) color',
            ],

            // TAB: typography — Text colors and font family.
            '--color-main-text'                   => [
                'tab'   => 'typography',
                'type'  => 'color',
                'label' => 'Main text color',
            ],
            '--color-text-maxcontrast'            => [
                'tab'   => 'typography',
                'type'  => 'color',
                'label' => 'Text max contrast',
            ],
            '--color-text-light'                  => ['tab' => 'typography', 'type' => 'color', 'label' => 'Text light'],
            '--color-text-lighter'                => ['tab' => 'typography', 'type' => 'color', 'label' => 'Text lighter'],
            '--color-text-error'                  => ['tab' => 'typography', 'type' => 'color', 'label' => 'Text error'],
            '--color-text-success'                => ['tab' => 'typography', 'type' => 'color', 'label' => 'Text success'],
            '--color-text-warning'                => ['tab' => 'typography', 'type' => 'color', 'label' => 'Text warning'],
            '--font-face'                         => ['tab' => 'typography', 'type' => 'text',  'label' => 'Font family'],
        ];
    }//end getTokens()

    /**
     * Returns the display labels for each tab.
     *
     * @return array<string, string> Map of tab id → display label.
     */
    public static function getTabLabels(): array
    {
        return [
            'login'      => 'Login page & Branding',
            'content'    => 'Content area',
            'status'     => 'Buttons & Status',
            'typography' => 'Typography',
        ];
    }//end getTabLabels()

    /**
     * Returns the set of all editable token names.
     *
     * @return array<string> List of token names.
     */
    public static function getTokenNames(): array
    {
        return array_keys(self::getTokens());
    }//end getTokenNames()

    /**
     * Checks whether a given token name is editable.
     *
     * @param string $tokenName The CSS custom property name.
     *
     * @return bool True if the token is in the registry.
     */
    public static function isEditable(string $tokenName): bool
    {
        return array_key_exists($tokenName, self::getTokens());
    }//end isEditable()

    /**
     * Returns tokens grouped by tab.
     *
     * @return array<string, array<string, array{tab: string, type: string, label: string}>> Tokens grouped by tab id.
     */
    public static function getTokensByTab(): array
    {
        $grouped = [];
        foreach (self::getTokens() as $name => $meta) {
            $grouped[$meta['tab']][$name] = $meta;
        }

        return $grouped;
    }//end getTokensByTab()
}//end class
