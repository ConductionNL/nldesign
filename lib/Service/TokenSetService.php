<?php

/**
 * NL Design Token Set Service.
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
 * Service for filesystem-based token set discovery.
 *
 * Discovers available token sets by scanning css/tokens/ directory
 * and reading token-sets.json for metadata.
 */
class TokenSetService
{
    private IAppManager $appManager;

    /**
     * Constructor.
     *
     * @param IAppManager $appManager The app manager for resolving paths.
     */
    public function __construct(IAppManager $appManager)
    {
        $this->appManager = $appManager;
    }

    /**
     * Get the absolute path to the app's directory.
     *
     * @return string The app directory path.
     */
    private function getAppPath(): string
    {
        return $this->appManager->getAppPath('nldesign');
    }

    /**
     * Get all available token sets with metadata.
     *
     * Scans css/tokens/ for CSS files and merges metadata from token-sets.json.
     *
     * @return array<array{id: string, name: string, description: string}> The available token sets.
     */
    public function getAvailableTokenSets(): array
    {
        $appPath = $this->getAppPath();
        $tokensDir = $appPath . '/css/tokens';
        $manifestPath = $appPath . '/token-sets.json';

        // Read metadata from token-sets.json
        $metadata = $this->readManifest($manifestPath);

        // Scan filesystem for actual CSS files
        $tokenSets = [];
        if (is_dir($tokensDir)) {
            $files = scandir($tokensDir);
            foreach ($files as $file) {
                if (str_ends_with($file, '.css')) {
                    $id = basename($file, '.css');
                    $meta = $metadata[$id] ?? null;
                    $tokenSet = [
                        'id' => $id,
                        'name' => $meta['name'] ?? $this->formatName($id),
                        'description' => $meta['description'] ?? 'Design tokens for ' . $this->formatName($id),
                    ];
                    if (isset($meta['theming']) && is_array($meta['theming'])) {
                        $tokenSet['theming'] = $meta['theming'];
                    }
                    $tokenSets[] = $tokenSet;
                }
            }
        }

        // Sort alphabetically by name
        usort($tokenSets, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $tokenSets;
    }

    /**
     * Check if a token set exists on the filesystem.
     *
     * @param string $tokenSetId The token set identifier.
     *
     * @return bool True if the CSS file exists.
     */
    public function isValidTokenSet(string $tokenSetId): bool
    {
        // Prevent path traversal
        if (str_contains($tokenSetId, '/') || str_contains($tokenSetId, '..')) {
            return false;
        }

        $appPath = $this->getAppPath();
        $cssFile = $appPath . '/css/tokens/' . $tokenSetId . '.css';

        return file_exists($cssFile);
    }

    /**
     * Read the token-sets.json manifest and index by id.
     *
     * @param string $manifestPath Path to token-sets.json.
     *
     * @return array<string, array{name: string, description: string}> Metadata indexed by id.
     */
    private function readManifest(string $manifestPath): array
    {
        if (!file_exists($manifestPath)) {
            return [];
        }

        $content = file_get_contents($manifestPath);
        if ($content === false) {
            return [];
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return [];
        }

        $indexed = [];
        foreach ($data as $entry) {
            if (isset($entry['id'])) {
                $indexed[$entry['id']] = $entry;
            }
        }

        return $indexed;
    }

    /**
     * Format a kebab-case id into a display name.
     *
     * @param string $id The kebab-case identifier.
     *
     * @return string The formatted display name.
     */
    private function formatName(string $id): string
    {
        return ucwords(str_replace('-', ' ', $id));
    }
}
