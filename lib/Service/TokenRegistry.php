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
 * Token definitions are organized by tab in separate provider classes
 * under the Tokens namespace. This class aggregates them.
 *
 * Tokens marked "intentionally not overridden" in overrides.css MUST NOT appear here.
 *
 * @SuppressWarnings(PHPMD.StaticAccess) - Token provider classes use static methods by design
 */
class TokenRegistry implements TokenRegistryInterface
{
    /**
     * Returns the full registry of editable tokens.
     *
     * Aggregates tokens from all tab-specific provider classes.
     *
     * @return array<string, array{tab: string, type: string, label: string}> The token registry.
     */
    public static function getTokens(): array
    {
        return array_merge(
            LoginTokens::getTokens(),
            ContentTokens::getTokens(),
            StatusTokens::getTokens(),
            TypographyTokens::getTokens()
        );
    }//end getTokens()

    /**
     * Returns the display labels for each tab.
     *
     * @return array<string, string> Map of tab id to display label.
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
