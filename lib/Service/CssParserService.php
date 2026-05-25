<?php

/**
 * NL Design CSS Parser Service.
 *
 * @category Service
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0-or-later
 * @link     https://github.com/ConductionNL/nldesign
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
        preg_match_all('/^\s*(--[\w-]+)\s*:\s*([^;]+);/m', $content, $matches, PREG_SET_ORDER);

        if (empty($matches) === true) {
            return null;
        }

        $parsed = [];
        foreach ($matches as $match) {
            $parsed[trim($match[1])] = trim($match[2]);
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

        $result = $this->parseDeclarations($rootMatch[1]);

        if ($result !== null) {
            return $result;
        }

        return [];
    }//end parseRootBlock()
}//end class
