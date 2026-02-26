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
use OCA\NLDesign\Service\ThemingService;
use OCA\NLDesign\Service\TokenSetService;
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

    /**
     * The config service.
     *
     * @var IConfig
     */
    private IConfig $config;

    /**
     * The token set service.
     *
     * @var TokenSetService
     */
    private TokenSetService $tokenSetService;

    /**
     * The theming service.
     *
     * @var ThemingService
     */
    private ThemingService $themingService;

    /**
     * Constructor.
     *
     * @param string          $appName         The app name.
     * @param IRequest        $request         The request object.
     * @param IConfig         $config          The config service.
     * @param TokenSetService $tokenSetService The token set service.
     * @param ThemingService  $themingService  The theming service.
     */
    public function __construct(
        string $appName,
        IRequest $request,
        IConfig $config,
        TokenSetService $tokenSetService,
        ThemingService $themingService
    ) {
        parent::__construct($appName, $request);
        $this->config          = $config;
        $this->tokenSetService = $tokenSetService;
        $this->themingService  = $themingService;
    }//end __construct()

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
        if ($this->tokenSetService->isValidTokenSet($tokenSet) === false) {
            return new JSONResponse(['error' => 'Invalid token set'], 400);
        }

        $this->config->setAppValue(Application::APP_ID, 'token_set', $tokenSet);

        return new JSONResponse(['status' => 'ok', 'tokenSet' => $tokenSet]);
    }//end setTokenSet()

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
    }//end getTokenSet()

    /**
     * Get all available token sets.
     *
     * @return JSONResponse The list of available token sets.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     */
    public function getAvailableTokenSets(): JSONResponse
    {
        $tokenSets = $this->tokenSetService->getAvailableTokenSets();

        return new JSONResponse(['tokenSets' => $tokenSets]);
    }//end getAvailableTokenSets()

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
        $sloganValue = '0';
        if ($hideSlogan === true) {
            $sloganValue = '1';
        }

        $this->config->setAppValue(
            Application::APP_ID,
            'hide_slogan',
            $sloganValue
        );

        return new JSONResponse(['status' => 'ok', 'hideSlogan' => $hideSlogan]);
    }//end setSloganSetting()

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
        $menuLabelValue = '0';
        if ($showMenuLabels === true) {
            $menuLabelValue = '1';
        }

        $this->config->setAppValue(
            Application::APP_ID,
            'show_menu_labels',
            $menuLabelValue
        );

        return new JSONResponse(['status' => 'ok', 'showMenuLabels' => $showMenuLabels]);
    }//end setMenuLabelsSetting()

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

        $colorError = $this->themingService->validateColors(params: $params);
        if ($colorError !== null) {
            return new JSONResponse(['error' => $colorError], 400);
        }

        $imageError = $this->themingService->validateImagePaths(params: $params);
        if ($imageError !== null) {
            return new JSONResponse(['error' => $imageError], 400);
        }

        $updatedColors = $this->themingService->applyColors(params: $params);
        $updatedImages = $this->themingService->applyImages(params: $params);
        $updated       = array_merge($updatedColors, $updatedImages);

        return new JSONResponse(['status' => 'ok', 'updated' => $updated]);
    }//end updateThemingValues()

    /**
     * Get current Nextcloud theming values for comparison.
     *
     * @return JSONResponse The current theming values.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     */
    public function getThemingValues(): JSONResponse
    {
        $imgManager = $this->themingService->getImageManager();

        $primaryColor = $this->config->getAppValue('theming', 'primary_color', '');
        $bgColor      = $this->config->getAppValue('theming', 'background_color', '');

        $logoUrl = $imgManager->getImageUrl('logo');
        $bgUrl   = $imgManager->getImageUrl('background');

        $hasLogo = $imgManager->hasImage('logo');
        $hasBg   = $imgManager->hasImage('background');

        return new JSONResponse(
            [
                'primary_color'         => $primaryColor,
                'background_color'      => $bgColor,
                'logo_url'              => $logoUrl,
                'background_url'        => $bgUrl,
                'has_custom_logo'       => $hasLogo,
                'has_custom_background' => $hasBg,
            ]
        );
    }//end getThemingValues()

}//end class
