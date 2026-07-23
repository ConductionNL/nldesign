<?php

/**
 * NL Design Token Set Service.
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
 * @spec openspec/specs/token-sets/spec.md
 */

declare(strict_types=1);

namespace OCA\NLDesign\Service;

use OCA\NLDesign\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * Service for filesystem-based token set discovery.
 *
 * Discovers available token sets by scanning css/tokens/ directory and merging
 * metadata from token-sets.json (shipped sets) and the custom_token_sets
 * appconfig manifest (admin-uploaded custom-* sets).
 *
 * @spec openspec/specs/token-sets/spec.md
 * @spec openspec/specs/custom-token-sets/spec.md
 */
class TokenSetService
{

    /**
     * The app manager for resolving paths.
     *
     * @var IAppManager
     */
    private IAppManager $appManager;

    /**
     * The config service for reading the custom-set appconfig manifest.
     *
     * @var IConfig
     */
    private IConfig $config;

    /**
     * The logger for the defensive id-collision warning.
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * The shipped-set contrast audit service (runtime warning surface).
     *
     * @var ShippedTokenSetAuditService
     */
    private ShippedTokenSetAuditService $audit;

    /**
     * Constructor.
     *
     * @param IAppManager                 $appManager The app manager for resolving paths.
     * @param IConfig                     $config     The config service.
     * @param LoggerInterface             $logger     The logger.
     * @param ShippedTokenSetAuditService $audit      The shipped-set contrast audit service.
     */
    public function __construct(
        IAppManager $appManager,
        IConfig $config,
        LoggerInterface $logger,
        ShippedTokenSetAuditService $audit
    ) {
        $this->appManager = $appManager;
        $this->config     = $config;
        $this->logger     = $logger;
        $this->audit      = $audit;
    }//end __construct()

    /**
     * Get the absolute path to the app's directory.
     *
     * @return string The app directory path.
     */
    private function getAppPath(): string
    {
        return $this->appManager->getAppPath('nldesign');
    }//end getAppPath()

    /**
     * Get all available token sets with metadata.
     *
     * Scans css/tokens/ for CSS files and merges metadata from token-sets.json.
     *
     * @return array<int, array<string, mixed>> The available token sets. Every entry carries at
     *         least `id`, `name` and `description`; manifest entries may add open-shape keys
     *         (`theming`, `design_system`, `note`, and later provenance/version fields).
     *
     * @spec openspec/specs/token-sets/spec.md
     */
    public function getAvailableTokenSets(): array
    {
        $appPath      = $this->getAppPath();
        $tokensDir    = $appPath.'/css/tokens';
        $manifestPath = $appPath.'/token-sets.json';

        // Read metadata from token-sets.json (shipped sets).
        $metadata = $this->readManifest(manifestPath: $manifestPath);

        // Read metadata for admin-uploaded custom sets from appconfig.
        $customMetadata = $this->readCustomManifest();

        // Scan filesystem for actual CSS files.
        $tokenSets = [];
        $files     = [];
        if (is_dir($tokensDir) === true) {
            $files = scandir($tokensDir);
        }

        foreach ($files as $file) {
            if (str_ends_with($file, '.css') === false) {
                continue;
            }

            $id = basename($file, '.css');

            // Shipped manifest takes precedence on an (impossible) id
            // collision; log it so the operator can investigate.
            $shippedMeta = $metadata[$id] ?? null;
            $customMeta  = $customMetadata[$id] ?? null;
            if ($shippedMeta !== null && $customMeta !== null) {
                $this->logger->warning(
                    'NL Design token set id "'.$id.'" exists in both the shipped and custom manifests; using the shipped metadata.'
                );
                $customMeta = null;
            }

            $tokenSets[] = $this->buildTokenSetEntry(
                appPath: $appPath,
                id: $id,
                shippedMeta: $shippedMeta,
                customMeta: $customMeta
            );
        }//end foreach

        // Sort alphabetically by name.
        usort($tokenSets, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $tokenSets;
    }//end getAvailableTokenSets()

    /**
     * Build one token-set list entry from its manifest metadata.
     *
     * Custom uploads carry their stored upload-time warnings; shipped sets
     * surface the same non-blocking WCAG contrast warning the apply dialog
     * raises for a custom upload, so a sub-AA or unevaluated shipped set is
     * not silently applied.
     *
     * @param string                    $appPath     The app root path.
     * @param string                    $id          The token set id (CSS basename).
     * @param array<string, mixed>|null $shippedMeta The token-sets.json entry, if any.
     * @param array<string, mixed>|null $customMeta  The custom-manifest entry, if any.
     *
     * @return array<string, mixed> The list entry.
     *
     * @spec openspec/specs/token-sets/spec.md
     */
    private function buildTokenSetEntry(string $appPath, string $id, ?array $shippedMeta, ?array $customMeta): array
    {
        $meta     = ($shippedMeta ?? $customMeta);
        $tokenSet = [
            'id'            => $id,
            'name'          => $meta['name'] ?? $this->formatName(id: $id),
            'description'   => $meta['description'] ?? 'Design tokens for '.$this->formatName(id: $id),
            'design_system' => $meta['design_system'] ?? 'nldesign',
        ];
        if (isset($meta['theming']) === true && is_array($meta['theming']) === true) {
            $tokenSet['theming'] = $meta['theming'];
        }

        $isCustom = str_starts_with($id, 'custom-');
        if ($isCustom === true && $shippedMeta === null) {
            $tokenSet['custom'] = true;
            if (isset($meta['warnings']) === true && is_array($meta['warnings']) === true) {
                $tokenSet['warnings'] = $meta['warnings'];
            }

            return $tokenSet;
        }

        $warnings = $this->audit->warningsFor(
            appPath: $appPath,
            id: $id,
            designSystem: $tokenSet['design_system'],
            theming: ($tokenSet['theming'] ?? [])
        );
        if (empty($warnings) === false) {
            $tokenSet['warnings'] = $warnings;
        }

        return $tokenSet;
    }//end buildTokenSetEntry()

    /**
     * Check if a token set exists on the filesystem.
     *
     * @param string $tokenSetId The token set identifier.
     *
     * @return bool True if the CSS file exists.
     *
     * @spec openspec/specs/token-sets/spec.md
     */
    public function isValidTokenSet(string $tokenSetId): bool
    {
        // Prevent path traversal.
        if (str_contains($tokenSetId, '/') === true || str_contains($tokenSetId, '..') === true) {
            return false;
        }

        $appPath = $this->getAppPath();
        $cssFile = $appPath.'/css/tokens/'.$tokenSetId.'.css';

        return file_exists($cssFile);
    }//end isValidTokenSet()

    /**
     * Read the token-sets.json manifest and index by id.
     *
     * @param string $manifestPath Path to token-sets.json.
     *
     * @return array<string, array<string, mixed>> Metadata indexed by id.
     *
     * @spec openspec/specs/token-sets/spec.md
     */
    private function readManifest(string $manifestPath): array
    {
        if (file_exists($manifestPath) === false) {
            return [];
        }

        $content = file_get_contents($manifestPath);
        if ($content === false) {
            return [];
        }

        $data = json_decode($content, true);
        if (is_array($data) === false) {
            return [];
        }

        $indexed = [];
        foreach ($data as $entry) {
            if (isset($entry['id']) === true) {
                $indexed[$entry['id']] = $entry;
            }
        }

        return $indexed;
    }//end readManifest()

    /**
     * Read the custom-set appconfig manifest, indexed by id.
     *
     * The manifest is a JSON object keyed by the custom set id, so it is
     * already in the indexed shape readManifest() produces for the shipped
     * list. Malformed JSON degrades to an empty map.
     *
     * @return array<string, array<string, mixed>> Custom metadata indexed by id.
     *
     * @spec openspec/specs/custom-token-sets/spec.md
     */
    private function readCustomManifest(): array
    {
        $raw     = $this->config->getAppValue(Application::APP_ID, 'custom_token_sets', '{}');
        $decoded = json_decode($raw, true);

        if (is_array($decoded) === false) {
            return [];
        }

        return $decoded;
    }//end readCustomManifest()

    /**
     * Format a kebab-case id into a display name.
     *
     * @param string $id The kebab-case identifier.
     *
     * @return string The formatted display name.
     *
     * @spec openspec/specs/token-sets/spec.md
     */
    private function formatName(string $id): string
    {
        return ucwords(str_replace('-', ' ', $id));
    }//end formatName()
}//end class
