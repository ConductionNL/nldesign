<?php

/**
 * NL Design Theming Service.
 *
 * @category Service
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0-or-later
 * @link     https://github.com/ConductionNL/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Service;

use OCA\Theming\ImageManager;
use OCA\Theming\ThemingDefaults;
use OCP\App\IAppManager;

/**
 * Service for managing Nextcloud theming values.
 *
 * Handles validation and application of color and image changes
 * to the Nextcloud theming system.
 */
class ThemingService
{

    /**
     * The theming image manager.
     *
     * @var ImageManager
     */
    private ImageManager $imageManager;

    /**
     * The theming defaults service.
     *
     * @var ThemingDefaults
     */
    private ThemingDefaults $themingDefaults;

    /**
     * The app manager for resolving paths.
     *
     * @var IAppManager
     */
    private IAppManager $appManager;

    /**
     * Constructor.
     *
     * @param ImageManager    $imageManager    The theming image manager.
     * @param ThemingDefaults $themingDefaults The theming defaults service.
     * @param IAppManager     $appManager      The app manager for resolving paths.
     */
    public function __construct(
        ImageManager $imageManager,
        ThemingDefaults $themingDefaults,
        IAppManager $appManager
    ) {
        $this->imageManager    = $imageManager;
        $this->themingDefaults = $themingDefaults;
        $this->appManager      = $appManager;
    }//end __construct()

    /**
     * Validate a hex color string.
     *
     * @param string $color The color to validate.
     *
     * @return bool True if valid hex color.
     */
    public function isValidHexColor(string $color): bool
    {
        return (bool) preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color);
    }//end isValidHexColor()

    /**
     * Validate color parameters from the request.
     *
     * @param array $params The request parameters.
     *
     * @return string|null An error message if validation fails, or null on success.
     */
    public function validateColors(array $params): ?string
    {
        foreach (['primary_color', 'background_color'] as $colorKey) {
            if (isset($params[$colorKey]) === true && $params[$colorKey] !== '') {
                if ($this->isValidHexColor($params[$colorKey]) === false) {
                    return "Invalid hex color for $colorKey: {$params[$colorKey]}";
                }
            }
        }

        return null;
    }//end validateColors()

    /**
     * Validate image path parameters from the request.
     *
     * @param array $params The request parameters.
     *
     * @return string|null An error message if validation fails, or null on success.
     */
    public function validateImagePaths(array $params): ?string
    {
        foreach (['logo', 'background'] as $imageKey) {
            if (isset($params[$imageKey]) === true && $params[$imageKey] !== '') {
                $error = $this->validateSinglePath(
                    imageKey: $imageKey,
                    imagePath: $params[$imageKey]
                );
                if ($error !== null) {
                    return $error;
                }
            }
        }

        return null;
    }//end validateImagePaths()

    /**
     * Validate a single image path for security and existence.
     *
     * @param string $imageKey  The image key name.
     * @param string $imagePath The image path to validate.
     *
     * @return string|null An error message if validation fails, or null on success.
     */
    private function validateSinglePath(string $imageKey, string $imagePath): ?string
    {
        $hasDotDot   = str_contains(haystack: $imagePath, needle: '..');
        $startsSlash = str_starts_with(haystack: $imagePath, prefix: '/');
        if ($hasDotDot === true || $startsSlash === true) {
            return "Invalid image path for $imageKey: path traversal not allowed";
        }

        $inLogos = str_starts_with(haystack: $imagePath, prefix: 'img/logos/');
        $inBgs   = str_starts_with(haystack: $imagePath, prefix: 'img/backgrounds/');
        if ($inLogos === false && $inBgs === false) {
            return "Invalid image path for $imageKey: must be in img/logos/ or img/backgrounds/";
        }

        $appPath  = $this->appManager->getAppPath(appId: 'nldesign');
        $fullPath = $appPath.'/'.$imagePath;
        if (file_exists(filename: $fullPath) === false) {
            return "Image file not found: $imagePath";
        }

        return null;
    }//end validateSinglePath()

    /**
     * Apply color changes to the theming defaults.
     *
     * @param array $params The request parameters.
     *
     * @return array The list of updated color keys.
     */
    public function applyColors(array $params): array
    {
        $updated = [];

        foreach (['primary_color', 'background_color'] as $colorKey) {
            if (isset($params[$colorKey]) === true && $params[$colorKey] !== '') {
                $this->themingDefaults->set(setting: $colorKey, value: $params[$colorKey]);
                $updated[] = $colorKey;
            }
        }

        return $updated;
    }//end applyColors()

    /**
     * Apply image changes to the theming image manager.
     *
     * @param array $params The request parameters.
     *
     * @return array The list of updated image keys.
     */
    public function applyImages(array $params): array
    {
        $updated = [];

        foreach (['logo', 'background'] as $imageKey) {
            if (isset($params[$imageKey]) === true && $params[$imageKey] !== '') {
                $appPath  = $this->appManager->getAppPath(appId: 'nldesign');
                $fullPath = $appPath.'/'.$params[$imageKey];
                $this->imageManager->updateImage(key: $imageKey, tmpFile: $fullPath);
                $updated[] = $imageKey;
            }
        }

        return $updated;
    }//end applyImages()

    /**
     * Get the current Nextcloud theming image manager.
     *
     * @return ImageManager The image manager instance.
     */
    public function getImageManager(): ImageManager
    {
        return $this->imageManager;
    }//end getImageManager()
}//end class
