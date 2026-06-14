<?php

/**
 * NL Design W3C Design Tokens Mapper.
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
 * @spec openspec/changes/custom-token-set-upload/tasks.md#task-1.2
 */

declare(strict_types=1);

namespace OCA\NLDesign\Service;

/**
 * Maps a W3C Design Tokens Community Group (DTCG) JSON document onto the
 * `--nldesign-*` CSS custom-property vocabulary.
 *
 * The DTCG draft uses `$value` / `$type` leaves nested in groups. We flatten
 * the tree into dotted paths and match each path against a published mapping
 * table by suffix. Recognised tokens are emitted as declarations; everything
 * else is skipped and counted. The mapper is deliberately tolerant: format
 * drift degrades to a higher `skipped` count, never an error (only malformed
 * JSON is an error, handled by the caller).
 *
 * @spec openspec/changes/custom-token-set-upload/tasks.md#task-1.2
 */
class DesignTokensMapper
{

    /**
     * Suffix-match mapping table: DTCG dotted-path suffix => --nldesign target.
     *
     * The longest matching suffix wins, so `color.primary-text` is preferred
     * over `color.primary` for a `color.primary-text` path.
     *
     * @var array<string, string>
     */
    private const MAPPING = [
        'color.primary-text'      => '--nldesign-color-primary-text',
        'color.on-primary'        => '--nldesign-color-primary-text',
        'color.primary-hover'     => '--nldesign-color-primary-hover',
        'color.primary-light'     => '--nldesign-color-primary-light',
        'color.primary'           => '--nldesign-color-primary',
        'brand.primary'           => '--nldesign-color-primary',
        'color.background'        => '--nldesign-color-background',
        'color.text'              => '--nldesign-color-text',
        'fontfamily.base'         => '--nldesign-font-family',
        'typography.font-family'  => '--nldesign-font-family',
        'border-radius.base'      => '--nldesign-border-radius',
        'dimension.border-radius' => '--nldesign-border-radius',
    ];

    /**
     * Map a parsed DTCG document onto nldesign declarations.
     *
     * @param array<string, mixed> $document The decoded DTCG JSON document.
     *
     * @return array{declarations: array<string, string>, imported: int, skipped: string[]}
     *     The mapped declarations plus import/skip accounting.
     *
     * @spec openspec/changes/custom-token-set-upload/tasks.md#task-1.2
     */
    public function map(array $document): array
    {
        $leaves = [];
        $this->flatten(node: $document, prefix: '', leaves: $leaves);

        $declarations = [];
        $skipped      = [];

        foreach ($leaves as $path => $value) {
            $target = $this->resolveTarget(path: $path);
            if ($target === null) {
                $skipped[] = $path;
                continue;
            }

            // First match wins for a given target; later collisions are skipped
            // so a more specific path earlier in the document is preserved.
            if (isset($declarations[$target]) === true) {
                $skipped[] = $path;
                continue;
            }

            $declarations[$target] = $value;
        }//end foreach

        return [
            'declarations' => $declarations,
            'imported'     => count($declarations),
            'skipped'      => $skipped,
        ];
    }//end map()

    /**
     * Recursively flatten a DTCG node into dotted-path => $value leaves.
     *
     * A node is a leaf when it carries a `$value` key. Group keys build the
     * dotted path. Keys beginning with `$` (metadata such as `$type`,
     * `$description`) are not part of the path.
     *
     * @param mixed                 $node   The current node (array or scalar).
     * @param string                $prefix The accumulated dotted path.
     * @param array<string, string> $leaves Collected path => value leaves (by reference).
     *
     * @return void
     *
     * @spec openspec/changes/custom-token-set-upload/tasks.md#task-1.2
     */
    private function flatten($node, string $prefix, array &$leaves): void
    {
        if (is_array($node) === false) {
            return;
        }

        if (array_key_exists('$value', $node) === true) {
            $value = $node['$value'];
            if (is_scalar($value) === true) {
                $leaves[$prefix] = (string) $value;
            }

            return;
        }

        foreach ($node as $key => $child) {
            if (is_string($key) === true && str_starts_with($key, '$') === true) {
                continue;
            }

            $path = $prefix.'.'.$key;
            if ($prefix === '') {
                $path = (string) $key;
            }

            $this->flatten(node: $child, prefix: $path, leaves: $leaves);
        }
    }//end flatten()

    /**
     * Resolve a dotted DTCG path to a --nldesign target by longest suffix match.
     *
     * @param string $path The dotted token path (e.g. "theme.color.primary").
     *
     * @return string|null The --nldesign target, or null when unmapped.
     *
     * @spec openspec/changes/custom-token-set-upload/tasks.md#task-1.2
     */
    private function resolveTarget(string $path): ?string
    {
        $lower = strtolower($path);

        $best       = null;
        $bestLength = 0;
        foreach (self::MAPPING as $suffix => $target) {
            if (str_ends_with($lower, $suffix) === true && strlen($suffix) > $bestLength) {
                $best       = $target;
                $bestLength = strlen($suffix);
            }
        }

        return $best;
    }//end resolveTarget()

    /**
     * Get the published mapping table for transparency (API/docs).
     *
     * @return array<string, string> The DTCG-suffix => --nldesign-target table.
     *
     * @spec openspec/changes/custom-token-set-upload/tasks.md#task-1.2
     */
    public function getMappingTable(): array
    {
        return self::MAPPING;
    }//end getMappingTable()
}//end class
