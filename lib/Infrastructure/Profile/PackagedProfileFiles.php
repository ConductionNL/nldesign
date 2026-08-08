<?php

/**
 * NL Design packaged profile-file boundary.
 *
 * @category Infrastructure
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/ConductionNL/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Infrastructure\Profile;

use OCP\App\IAppManager;

/**
 * Resolve only bounded regular files from the immutable app package.
 */
final class PackagedProfileFiles
{
    private const PROFILE_ID_PATTERN   = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
    private const ASSET_PATTERN        = '#^img/(?:logos|backgrounds)/[a-zA-Z0-9._-]+\.(?:svg|png|jpe?g|webp)$#i';
    private const MAX_MANIFEST_BYTES   = 262144;
    private const MAX_STYLESHEET_BYTES = 32768;
    private const MAX_ASSET_BYTES      = 2097152;

    /**
     * Resolved app package path.
     *
     * @var string|null
     */
    private ?string $appPath = null;

    /**
     * Constructor.
     *
     * @param IAppManager $appManager App path resolver.
     */
    public function __construct(private IAppManager $appManager)
    {
    }//end __construct()

    /**
     * Read the bounded, non-symlinked profile manifest.
     *
     * @return string|null Manifest JSON, or null when the package is invalid.
     */
    public function readManifest(): ?string
    {
        $appPath      = $this->getAppPath();
        $manifestPath = $appPath.'/token-sets.json';
        if (is_link($manifestPath) === true
            || is_file($manifestPath) === false
            || is_readable($manifestPath) === false
            || realpath($manifestPath) !== $manifestPath
        ) {
            return null;
        }

        $manifestSize = filesize($manifestPath);
        if ($manifestSize === false || $manifestSize > self::MAX_MANIFEST_BYTES) {
            return null;
        }

        $content = file_get_contents(filename: $manifestPath);
        if ($content === false) {
            return null;
        }

        return $content;
    }//end readManifest()

    /**
     * Confirm that a profile stylesheet is a bounded regular package file.
     *
     * @param string $profileId Valid profile identifier.
     *
     * @return bool Whether the stylesheet is safe and readable.
     */
    public function hasSafeStylesheet(string $profileId): bool
    {
        if (strlen($profileId) > 64 || preg_match(self::PROFILE_ID_PATTERN, $profileId) !== 1) {
            return false;
        }

        $appPath            = $this->getAppPath();
        $tokenDirectoryPath = $appPath.'/css/tokens';
        $tokenDirectory     = realpath($tokenDirectoryPath);
        if ($tokenDirectory === false
            || is_dir($tokenDirectory) === false
            || is_link($tokenDirectoryPath) === true
            || str_starts_with($tokenDirectory, $appPath.DIRECTORY_SEPARATOR) === false
        ) {
            return false;
        }

        $stylesheetPath = $tokenDirectory.'/'.$profileId.'.css';
        $stylesheet     = realpath($stylesheetPath);
        if ($stylesheet === false
            || is_link($stylesheetPath) === true
            || is_file($stylesheet) === false
            || is_readable($stylesheet) === false
        ) {
            return false;
        }

        $stylesheetSize = filesize($stylesheetPath);
        if ($stylesheetSize === false || $stylesheetSize > self::MAX_STYLESHEET_BYTES) {
            return false;
        }

        return str_starts_with(
            haystack: $stylesheet,
            needle: $tokenDirectory.DIRECTORY_SEPARATOR
        );
    }//end hasSafeStylesheet()

    /**
     * Confirm that a manual theming asset is a bounded regular package file.
     *
     * @param string $asset App-relative asset path.
     *
     * @return bool Whether the asset can safely be recommended.
     */
    public function hasSafeAsset(string $asset): bool
    {
        if (preg_match(self::ASSET_PATTERN, $asset) !== 1) {
            return false;
        }

        $appPath   = $this->getAppPath();
        $assetPath = $appPath.'/'.$asset;
        if (is_link($assetPath) === true
            || is_file($assetPath) === false
            || is_readable($assetPath) === false
        ) {
            return false;
        }

        $assetSize = filesize($assetPath);
        if ($assetSize === false || $assetSize > self::MAX_ASSET_BYTES) {
            return false;
        }

        $directoryPath = dirname($assetPath);
        $realAsset     = realpath($assetPath);
        $directory     = realpath($directoryPath);
        if ($realAsset === false
            || $directory === false
            || is_link($directoryPath) === true
            || str_starts_with($directory, $appPath.DIRECTORY_SEPARATOR) === false
        ) {
            return false;
        }

        return str_starts_with(
            haystack: $realAsset,
            needle: $directory.DIRECTORY_SEPARATOR
        );
    }//end hasSafeAsset()

    /**
     * Resolve the real app package path once per request.
     *
     * @return string App package path.
     */
    private function getAppPath(): string
    {
        if ($this->appPath === null) {
            $configuredPath = $this->appManager->getAppPath(appId: 'nldesign');
            $resolvedPath   = realpath($configuredPath);
            $this->appPath  = $configuredPath;
            if ($resolvedPath !== false) {
                $this->appPath = $resolvedPath;
            }
        }

        return $this->appPath;
    }//end getAppPath()
}//end class
