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
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-50
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-51
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-52
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-53
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
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-50
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-51
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-52
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-53
 * @spec openspec/changes/custom-token-set-upload/tasks.md#task-2.3
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
     * Constructor.
     *
     * @param IAppManager     $appManager The app manager for resolving paths.
     * @param IConfig         $config     The config service.
     * @param LoggerInterface $logger     The logger.
     */
    public function __construct(IAppManager $appManager, IConfig $config, LoggerInterface $logger)
    {
        $this->appManager = $appManager;
        $this->config     = $config;
        $this->logger     = $logger;
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
     * @return array<array{id: string, name: string, description: string}> The available token sets.
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-50
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
        if (is_dir($tokensDir) === true) {
            $files = scandir($tokensDir);
            foreach ($files as $file) {
                if (str_ends_with($file, '.css') === true) {
                    $id        = basename($file, '.css');
                    $isCustom  = str_starts_with($id, 'custom-');

                    // Shipped manifest takes precedence on an (impossible) id
                    // collision; log it so the operator can investigate.
                    $shippedMeta = $metadata[$id] ?? null;
                    $customMeta  = $customMetadata[$id] ?? null;
                    if ($shippedMeta !== null && $customMeta !== null) {
                        $this->logger->warning(
                            'NL Design token set id "'.$id.'" exists in both the shipped manifest and the custom manifest; using the shipped metadata.'
                        );
                        $customMeta = null;
                    }

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

                    if ($isCustom === true && $shippedMeta === null) {
                        $tokenSet['custom'] = true;
                        if (isset($meta['warnings']) === true && is_array($meta['warnings']) === true) {
                            $tokenSet['warnings'] = $meta['warnings'];
                        }
                    }

                    $tokenSets[] = $tokenSet;
                }//end if
            }//end foreach
        }//end if

        // Sort alphabetically by name.
        usort($tokenSets, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $tokenSets;
    }//end getAvailableTokenSets()

    /**
     * Check if a token set exists on the filesystem.
     *
     * @param string $tokenSetId The token set identifier.
     *
     * @return bool True if the CSS file exists.
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-51
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
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-52
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
     * @spec openspec/changes/custom-token-set-upload/tasks.md#task-2.3
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
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-53
     */
    private function formatName(string $id): string
    {
        return ucwords(str_replace('-', ' ', $id));
    }//end formatName()
}//end class
