<?php

/**
 * NL Design Settings Controller.
 *
 * @category Controller
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0-or-later
 * @link     https://github.com/ConductionNL/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Controller;

use OCA\NLDesign\AppInfo\Application;
use OCA\NLDesign\Service\TokenSetService;
use OCA\Theming\ImageManager;
use OCA\Theming\ThemingDefaults;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;

/**
 * Settings controller for NL Design app.
 *
 * Handles API requests for managing NL Design theme settings.
 */
class SettingsController extends Controller
{
    private IConfig $config;
    private TokenSetService $tokenSetService;
    private ImageManager $imageManager;
    private ThemingDefaults $themingDefaults;
    private IAppManager $appManager;

    /**
     * Constructor.
     *
     * @param string           $appName         The app name.
     * @param IRequest         $request         The request object.
     * @param IConfig          $config          The config service.
     * @param TokenSetService  $tokenSetService The token set service.
     * @param ImageManager     $imageManager    The theming image manager.
     * @param ThemingDefaults  $themingDefaults The theming defaults service.
     * @param IAppManager      $appManager      The app manager for resolving paths.
     */
    public function __construct(
        string $appName,
        IRequest $request,
        IConfig $config,
        TokenSetService $tokenSetService,
        ImageManager $imageManager,
        ThemingDefaults $themingDefaults,
        IAppManager $appManager
    ) {
        parent::__construct($appName, $request);
        $this->config = $config;
        $this->tokenSetService = $tokenSetService;
        $this->imageManager = $imageManager;
        $this->themingDefaults = $themingDefaults;
        $this->appManager = $appManager;
    }

    /**
     * Set the active design token set.
     *
     * @param string $tokenSet The token set name.
     *
     * @return JSONResponse The response with status and selected token set.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     */
    public function setTokenSet(string $tokenSet): JSONResponse
    {
        if (!$this->tokenSetService->isValidTokenSet($tokenSet)) {
            return new JSONResponse(['error' => 'Invalid token set'], 400);
        }

        $this->config->setAppValue(Application::APP_ID, 'token_set', $tokenSet);

        return new JSONResponse(['status' => 'ok', 'tokenSet' => $tokenSet]);
    }

    /**
     * Get the currently active design token set.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     *
     * @return JSONResponse The response with the current token set.
     */
    public function getTokenSet(): JSONResponse
    {
        $tokenSet = $this->config->getAppValue(
            Application::APP_ID,
            'token_set',
            'rijkshuisstijl'
        );

        return new JSONResponse(['tokenSet' => $tokenSet]);
    }

    /**
     * Get all available token sets.
     *
     * @return JSONResponse The list of available token sets with id, name, description.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     */
    public function getAvailableTokenSets(): JSONResponse
    {
        $tokenSets = $this->tokenSetService->getAvailableTokenSets();

        return new JSONResponse(['tokenSets' => $tokenSets]);
    }

    /**
     * Set the hide slogan setting.
     *
     * @param bool $hideSlogan Whether to hide the slogan on login page.
     *
     * @return JSONResponse The response with the status.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     */
    public function setSloganSetting(bool $hideSlogan): JSONResponse
    {
        $this->config->setAppValue(
            Application::APP_ID,
            'hide_slogan',
            $hideSlogan ? '1' : '0'
        );

        return new JSONResponse(['status' => 'ok', 'hideSlogan' => $hideSlogan]);
    }

    /**
     * Set the show menu labels setting.
     *
     * @param bool $showMenuLabels Whether to show text labels in app menu.
     *
     * @return JSONResponse The response with the status.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     */
    public function setMenuLabelsSetting(bool $showMenuLabels): JSONResponse
    {
        $this->config->setAppValue(
            Application::APP_ID,
            'show_menu_labels',
            $showMenuLabels ? '1' : '0'
        );

        return new JSONResponse(['status' => 'ok', 'showMenuLabels' => $showMenuLabels]);
    }

    /**
     * Validate a hex color string.
     *
     * @param string $color The color to validate.
     *
     * @return bool True if valid hex color.
     */
    private function isValidHexColor(string $color): bool
    {
        return (bool)preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color);
    }

    /**
     * Update Nextcloud theming values (colors and/or images).
     *
     * @return JSONResponse The response with updated fields.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     */
    public function updateThemingValues(): JSONResponse
    {
        $params = $this->request->getParams();
        $updated = [];

        // Validate colors before applying any changes
        foreach (['primary_color', 'background_color'] as $colorKey) {
            if (isset($params[$colorKey]) && $params[$colorKey] !== '') {
                if (!$this->isValidHexColor($params[$colorKey])) {
                    return new JSONResponse(
                        ['error' => "Invalid hex color for $colorKey: {$params[$colorKey]}"],
                        400
                    );
                }
            }
        }

        // Validate image paths before applying any changes
        foreach (['logo', 'background'] as $imageKey) {
            if (isset($params[$imageKey]) && $params[$imageKey] !== '') {
                $imagePath = $params[$imageKey];

                // Check for path traversal
                if (str_contains($imagePath, '..') || str_starts_with($imagePath, '/')) {
                    return new JSONResponse(
                        ['error' => "Invalid image path for $imageKey: path traversal not allowed"],
                        400
                    );
                }

                // Validate path is within allowed directories
                if (!str_starts_with($imagePath, 'img/logos/') && !str_starts_with($imagePath, 'img/backgrounds/')) {
                    return new JSONResponse(
                        ['error' => "Invalid image path for $imageKey: must be in img/logos/ or img/backgrounds/"],
                        400
                    );
                }

                // Check file exists
                $appPath = $this->appManager->getAppPath('nldesign');
                $fullPath = $appPath . '/' . $imagePath;
                if (!file_exists($fullPath)) {
                    return new JSONResponse(
                        ['error' => "Image file not found: $imagePath"],
                        400
                    );
                }
            }
        }

        // Apply color changes
        foreach (['primary_color', 'background_color'] as $colorKey) {
            if (isset($params[$colorKey]) && $params[$colorKey] !== '') {
                $this->themingDefaults->set($colorKey, $params[$colorKey]);
                $updated[] = $colorKey;
            }
        }

        // Apply image changes
        foreach (['logo', 'background'] as $imageKey) {
            if (isset($params[$imageKey]) && $params[$imageKey] !== '') {
                $appPath = $this->appManager->getAppPath('nldesign');
                $fullPath = $appPath . '/' . $params[$imageKey];
                $this->imageManager->updateImage($imageKey, $fullPath);
                $updated[] = $imageKey;
            }
        }

        return new JSONResponse(['status' => 'ok', 'updated' => $updated]);
    }

    /**
     * Get current Nextcloud theming values for comparison in the sync dialog.
     *
     * @return JSONResponse The current theming values.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     */
    public function getThemingValues(): JSONResponse
    {
        $primaryColor = $this->config->getAppValue('theming', 'primary_color', '');
        $backgroundColor = $this->config->getAppValue('theming', 'background_color', '');

        $logoUrl = $this->imageManager->getImageUrl('logo');
        $backgroundUrl = $this->imageManager->getImageUrl('background');

        $hasCustomLogo = $this->imageManager->hasImage('logo');
        $hasCustomBackground = $this->imageManager->hasImage('background');

        return new JSONResponse([
            'primary_color' => $primaryColor,
            'background_color' => $backgroundColor,
            'logo_url' => $logoUrl,
            'background_url' => $backgroundUrl,
            'has_custom_logo' => $hasCustomLogo,
            'has_custom_background' => $hasCustomBackground,
        ]);
    }
}
