<?php

/**
 * NL Design — Design System Service.
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
 * Service for resolving design system stylesheet bundles.
 *
 * Reads design-systems.json and token-sets.json to determine which CSS
 * stylesheets should be loaded for a given token set.
 */
class DesignSystemService
{

    /**
     * The app manager for resolving paths.
     *
     * @var IAppManager
     */
    private IAppManager $appManager;

    /**
     * Cached design systems (indexed by id).
     *
     * @var array<string, array>|null
     */
    private ?array $designSystems = null;

    /**
     * Cached token set metadata (indexed by id).
     *
     * @var array<string, array>|null
     */
    private ?array $tokenSetMeta = null;

    /**
     * Constructor.
     *
     * @param IAppManager $appManager The app manager for resolving paths.
     */
    public function __construct(IAppManager $appManager)
    {
        $this->appManager = $appManager;
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
     * Get all available design systems.
     *
     * @return array<string, array{id: string, name: string, description: string, stylesheets: string[]}> Indexed by id.
     */
    public function getDesignSystems(): array
    {
        if ($this->designSystems !== null) {
            return $this->designSystems;
        }

        $path = $this->getAppPath().'/design-systems.json';
        $this->designSystems = $this->readJsonManifest($path);

        return $this->designSystems;
    }//end getDesignSystems()

    /**
     * Get a single design system by id.
     *
     * Returns a fallback with empty stylesheets if the id is not found.
     *
     * @param string $id The design system identifier.
     *
     * @return array{id: string, name: string, description: string, stylesheets: string[]} The design system.
     */
    public function getDesignSystem(string $id): array
    {
        $systems = $this->getDesignSystems();

        if (isset($systems[$id]) === true) {
            return $systems[$id];
        }

        // Unknown design system — fall back to no stylesheets for safety.
        return [
            'id'          => $id,
            'name'        => $id,
            'description' => 'Unknown design system',
            'stylesheets' => [],
        ];
    }//end getDesignSystem()

    /**
     * Get metadata for a token set (including its design_system field).
     *
     * @param string $tokenSetId The token set identifier.
     *
     * @return array The token set metadata from token-sets.json (empty array if not found).
     */
    public function getTokenSetMeta(string $tokenSetId): array
    {
        if ($this->tokenSetMeta === null) {
            $path               = $this->getAppPath().'/token-sets.json';
            $this->tokenSetMeta = $this->readJsonManifest($path);
        }

        return $this->tokenSetMeta[$tokenSetId] ?? [];
    }//end getTokenSetMeta()

    /**
     * Get all design systems as a flat list (for API responses).
     *
     * @return array<array{id: string, name: string, description: string, stylesheets: string[]}> List of design systems.
     */
    public function getDesignSystemsList(): array
    {
        return array_values($this->getDesignSystems());
    }//end getDesignSystemsList()

    /**
     * Read a JSON manifest file and index entries by their 'id' field.
     *
     * @param string $path Absolute path to the JSON file.
     *
     * @return array<string, array> Entries indexed by id.
     */
    private function readJsonManifest(string $path): array
    {
        if (file_exists($path) === false) {
            return [];
        }

        $content = file_get_contents($path);
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
    }//end readJsonManifest()
}//end class
