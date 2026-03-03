<?php

/**
 * NL Design Token Set Preview Service.
 *
 * @category Service
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0-or-later
 * @link     https://github.com/ConductionNL/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Service;

use OCP\App\IAppManager;

/**
 * Computes the resolved --color-* values for a given NL Design token set.
 *
 * Resolution pipeline (server-side):
 *   1. Parse defaults.css   → all --nldesign-* default values
 *   2. Parse tokens/{id}.css → org-specific overrides to --nldesign-*
 *   3. Merge                → final --nldesign-* map
 *   4. Parse overrides.css  → extract mapping --color-X: var(--nldesign-Y)
 *   5. Resolve              → for each --color-X, look up --nldesign-Y in merged map
 *
 * This is pure string manipulation — no DOM, no CSS parser.
 * Only editable tokens (in TokenRegistry) are returned.
 */
class TokenSetPreviewService
{

    /**
     * The app manager for resolving the app's CSS directory.
     *
     * @var IAppManager
     */
    private IAppManager $appManager;

    /**
     * Constructor.
     *
     * @param IAppManager $appManager The app manager.
     */
    public function __construct(IAppManager $appManager)
    {
        $this->appManager = $appManager;
    }//end __construct()

    /**
     * Resolve all editable --color-* values for a given token set.
     *
     * @param string $tokenSetId The token set identifier (e.g. 'utrecht').
     *
     * @return array<string, string> Map of --color-* token name => resolved hex/color value.
     */
    public function getResolvedColors(string $tokenSetId): array
    {
        $appPath = $this->appManager->getAppPath('nldesign');

        // Step 1: parse defaults.css → --nldesign-* defaults.
        $nldesignVars = $this->parseCssVars(
            filePath: $appPath.'/css/defaults.css'
        );

        // Step 2: parse tokens/{id}.css → overrides.
        $tokenSetPath = $appPath.'/css/tokens/'.$tokenSetId.'.css';
        if (file_exists($tokenSetPath) === true) {
            $tokenSetVars = $this->parseCssVars(filePath: $tokenSetPath);
            $nldesignVars = array_merge($nldesignVars, $tokenSetVars);
        }

        // Step 3: parse overrides.css → mapping --color-X: var(--nldesign-Y).
        $mappings = $this->parseMappings(
            filePath: $appPath.'/css/overrides.css'
        );

        // Step 4: resolve --color-* values using the nldesign map.
        $resolved        = [];
        $editableTokens  = TokenRegistry::getTokens();

        foreach ($editableTokens as $colorToken => $meta) {
            if (isset($mappings[$colorToken]) === true) {
                // Mapping exists in overrides.css.
                $nldesignRef = $mappings[$colorToken];
                $value       = $this->resolveVarReference(
                    ref: $nldesignRef,
                    vars: $nldesignVars
                );
                $resolved[$colorToken] = $value;
            }
            // Non-mapped tokens (e.g. --border-radius-*) are not in overrides.css mappings.
            // Skip them — they have no nldesign mapping to resolve.
        }

        return $resolved;
    }//end getResolvedColors()

    /**
     * Parse all CSS custom property declarations from a file.
     *
     * Returns only :root-scoped declarations (ignores media queries etc.)
     *
     * @param string $filePath Absolute path to a CSS file.
     *
     * @return array<string, string> Map of --property-name => value.
     */
    private function parseCssVars(string $filePath): array
    {
        if (file_exists($filePath) === false) {
            return [];
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }

        $vars = [];
        preg_match_all('/^\s*(--[\w-]+)\s*:\s*([^;]+);/m', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $vars[trim($match[1])] = trim($match[2]);
        }

        return $vars;
    }//end parseCssVars()

    /**
     * Parse the overrides.css file to extract --color-X: var(--nldesign-Y) mappings.
     *
     * Returns a map of --color-X => --nldesign-Y (without the var() wrapper).
     * Ignores commented-out lines.
     *
     * @param string $filePath Absolute path to overrides.css.
     *
     * @return array<string, string> Map of --color-X => --nldesign-Y.
     */
    private function parseMappings(string $filePath): array
    {
        if (file_exists($filePath) === false) {
            return [];
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }

        $mappings = [];
        // Match lines like: --color-X: var(--nldesign-Y) !important;
        // Ignore commented-out lines (those starting with //).
        preg_match_all(
            '/^\s*(--[\w-]+)\s*:\s*var\((--[\w-]+)\)\s*(?:!important)?\s*;/m',
            $content,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $colorToken    = trim($match[1]);
            $nldesignToken = trim($match[2]);
            $mappings[$colorToken] = $nldesignToken;
        }

        return $mappings;
    }//end parseMappings()

    /**
     * Resolve a var() reference using a variable map.
     *
     * Handles simple var(--name) references (one level deep).
     * Returns the raw reference string if resolution fails.
     *
     * @param string               $ref  The value from overrides.css (e.g. '--nldesign-color-primary').
     * @param array<string, string> $vars The merged --nldesign-* variable map.
     *
     * @return string The resolved value or the original reference.
     */
    private function resolveVarReference(string $ref, array $vars): string
    {
        // Direct lookup (ref is a --nldesign-* token name).
        if (isset($vars[$ref]) === true) {
            $value = $vars[$ref];
            // If the value itself is a var(), resolve one more level.
            if (str_starts_with(haystack: $value, needle: 'var(') === true) {
                preg_match('/var\((--[\w-]+)\)/', $value, $m);
                if (isset($m[1]) === true && isset($vars[$m[1]]) === true) {
                    return $vars[$m[1]];
                }
            }

            return $value;
        }

        return $ref;
    }//end resolveVarReference()

}//end class
