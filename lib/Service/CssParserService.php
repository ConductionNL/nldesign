<?php

/**
 * NL Design CSS Parser Service.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category  Service
 * @package   OCA\NLDesign
 * @author    Conduction <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://codeberg.org/Conduction/nldesign
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-27
 */

declare(strict_types=1);

namespace OCA\NLDesign\Service;

/**
 * Service for parsing CSS custom property declarations.
 *
 * Extracts --token-name: value pairs from raw CSS strings.
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-27
 */
class CssParserService
{
    /**
     * Parse CSS custom property declarations from a raw CSS string.
     *
     * Matches all lines like: --some-token: some-value;
     *
     * @param string $content The raw CSS content.
     *
     * @return array<string, string>|null Parsed token map, or null if none found.
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-27
     */
    public function parseDeclarations(string $content): ?array
    {
        // Match each `--name: value;` custom-property declaration anywhere in the
        // block. The previous `^…/m` anchor required every declaration to start a
        // line, which silently dropped all but the first when several share one
        // line (e.g. a minified `:root { --a: x; --b: y; }`). A `var(--ref)`
        // inside a value is never captured because it is not followed by `:value;`.
        preg_match_all('/(--[\w-]+)\s*:\s*([^;]+);/', $content, $matches, PREG_SET_ORDER);

        if (empty($matches) === true) {
            return null;
        }

        $parsed = [];
        foreach ($matches as $match) {
            $value = trim($match[2]);

            // Strip a trailing !important so persisted overrides (which are written
            // with !important to win the cascade) round-trip back to the editor as
            // the clean value the admin entered.
            $value = trim(preg_replace('/\s*!\s*important\s*$/i', '', $value));

            $parsed[trim($match[1])] = $value;
        }

        return $parsed;
    }//end parseDeclarations()

    /**
     * Parse CSS custom property declarations from within a :root {} block.
     *
     * @param string $css The raw CSS string containing a :root {} block.
     *
     * @return array<string, string> Map of token name => value.
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-27
     */
    public function parseRootBlock(string $css): array
    {
        if (preg_match('/:root\s*\{([^}]*)\}/s', $css, $rootMatch) !== 1) {
            return [];
        }

        $result = $this->parseDeclarations(content: $rootMatch[1]);

        if ($result !== null) {
            return $result;
        }

        return [];
    }//end parseRootBlock()
}//end class
