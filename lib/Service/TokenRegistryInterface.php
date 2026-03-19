<?php

/**
 * NL Design Token Registry Interface.
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
 * Interface for the token registry.
 *
 * Defines the contract for querying editable CSS custom properties.
 */
interface TokenRegistryInterface
{
    /**
     * Returns the full registry of editable tokens.
     *
     * @return array<string, array{tab: string, type: string, label: string}> The token registry.
     */
    public static function getTokens(): array;

    /**
     * Returns the display labels for each tab.
     *
     * @return array<string, string> Map of tab id to display label.
     */
    public static function getTabLabels(): array;

    /**
     * Checks whether a given token name is editable.
     *
     * @param string $tokenName The CSS custom property name.
     *
     * @return bool True if the token is in the registry.
     */
    public static function isEditable(string $tokenName): bool;
}//end interface
