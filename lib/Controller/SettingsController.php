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
use OCA\NLDesign\Service\CustomOverridesService;
use OCA\NLDesign\Service\ThemingService;
use OCA\NLDesign\Service\TokenRegistry;
use OCA\NLDesign\Service\TokenSetPreviewService;
use OCA\NLDesign\Service\TokenSetService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataDownloadResponse;
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
     * The custom overrides service.
     *
     * @var CustomOverridesService
     */
    private CustomOverridesService $overridesSvc;

    /**
     * The token set preview service.
     *
     * @var TokenSetPreviewService
     */
    private TokenSetPreviewService $previewSvc;

    /**
     * Constructor.
     *
     * @param string                 $appName         The app name.
     * @param IRequest               $request         The request object.
     * @param IConfig                $config          The config service.
     * @param TokenSetService        $tokenSetService The token set service.
     * @param ThemingService         $themingService  The theming service.
     * @param CustomOverridesService $overridesSvc    The custom overrides service.
     * @param TokenSetPreviewService $previewSvc      The token set preview service.
     */
    public function __construct(
        string $appName,
        IRequest $request,
        IConfig $config,
        TokenSetService $tokenSetService,
        ThemingService $themingService,
        CustomOverridesService $overridesSvc,
        TokenSetPreviewService $previewSvc
    ) {
        parent::__construct(appName: $appName, request: $request);
        $this->config          = $config;
        $this->tokenSetService = $tokenSetService;
        $this->themingService  = $themingService;
        $this->overridesSvc    = $overridesSvc;
        $this->previewSvc      = $previewSvc;
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

    /**
     * Get the current custom token overrides.
     *
     * Returns only tokens explicitly set in custom-overrides.css,
     * plus the full editable token registry for the UI.
     *
     * @return JSONResponse The overrides and token registry.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     */
    public function getOverrides(): JSONResponse
    {
        $overrides = $this->customOverridesService->read();
        $registry  = TokenRegistry::getTokens();
        $tabs      = TokenRegistry::getTabLabels();

        return new JSONResponse(
            [
                'overrides' => $overrides,
                'registry'  => $registry,
                'tabs'      => $tabs,
            ]
        );
    }//end getOverrides()

    /**
     * Write new custom token overrides to custom-overrides.css.
     *
     * Accepts a JSON body with an 'overrides' key containing token name => value pairs.
     * Returns HTTP 400 if any token is not in the editable registry.
     *
     * @return JSONResponse Status and count of written tokens.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     */
    public function setOverrides(): JSONResponse
    {
        $params    = $this->request->getParams();
        $overrides = $params['overrides'] ?? [];

        if (is_array($overrides) === false) {
            return new JSONResponse(['error' => 'overrides must be an object'], 400);
        }

        // Reject any token that is not in the editable registry.
        foreach (array_keys($overrides) as $name) {
            if (TokenRegistry::isEditable(tokenName: $name) === false) {
                return new JSONResponse(
                    ['error' => 'Token not editable: '.$name],
                    400
                );
            }
        }

        try {
            $this->customOverridesService->write(tokens: $overrides);
        } catch (\RuntimeException $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }

        return new JSONResponse(['status' => 'ok', 'written' => count($overrides)]);
    }//end setOverrides()

    /**
     * Download custom-overrides.css as a file.
     *
     * @return DataDownloadResponse The CSS file as a download.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     */
    public function exportOverrides(): DataDownloadResponse
    {
        $content = $this->customOverridesService->getRawContent();

        return new DataDownloadResponse(
            data: $content,
            filename: 'custom-overrides.css',
            contentType: 'text/css'
        );
    }//end exportOverrides()

    /**
     * Import custom token overrides from an uploaded CSS file.
     *
     * Accepts a multipart/form-data upload with a 'file' field.
     * Only recognized editable tokens are imported; unknown tokens are silently skipped.
     * The import fully replaces the existing custom-overrides.css.
     *
     * @return JSONResponse Import result with 'imported' and 'skipped' counts.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     */
    public function importOverrides(): JSONResponse
    {
        $file = $this->request->getUploadedFile(key: 'file');

        if (empty($file) === true || isset($file['tmp_name']) === false) {
            return new JSONResponse(['error' => 'No file uploaded'], 400);
        }

        // Enforce size limit (256 KB).
        if ($file['size'] > (256 * 1024)) {
            return new JSONResponse(['error' => 'File exceeds the 256 KB size limit'], 413);
        }

        $content = file_get_contents($file['tmp_name']);
        if ($content === false) {
            return new JSONResponse(['error' => 'Could not read uploaded file'], 400);
        }

        // Parse --color-* declarations.
        $parsed = [];
        preg_match_all('/^\s*(--[\w-]+)\s*:\s*([^;]+);/m', $content, $matches, PREG_SET_ORDER);
        if (empty($matches) === true) {
            return new JSONResponse(['error' => 'No CSS custom property declarations found in the uploaded file'], 400);
        }

        foreach ($matches as $match) {
            $parsed[trim($match[1])] = trim($match[2]);
        }

        // Split into recognized and unknown.
        $toImport = [];
        $skipped  = 0;
        foreach ($parsed as $name => $value) {
            if (TokenRegistry::isEditable(tokenName: $name) === true) {
                $toImport[$name] = $value;
            } else {
                $skipped++;
            }
        }

        try {
            $this->customOverridesService->write(tokens: $toImport);
        } catch (\RuntimeException $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }

        return new JSONResponse(
            [
                'status'   => 'ok',
                'imported' => count($toImport),
                'skipped'  => $skipped,
            ]
        );
    }//end importOverrides()

    /**
     * Get resolved --color-* values for a given token set.
     *
     * Used by the apply dialog to compare current resolved values against what
     * a token set would produce, without applying anything to the CSS stack.
     *
     * @param string $tokenSetId The token set identifier.
     *
     * @return JSONResponse The resolved color map.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     */
    public function getTokenSetPreview(string $tokenSetId): JSONResponse
    {
        if ($this->tokenSetService->isValidTokenSet(tokenSetId: $tokenSetId) === false) {
            return new JSONResponse(['error' => 'Token set not found'], 404);
        }

        $resolved = $this->tokenSetPreviewService->getResolvedColors(tokenSetId: $tokenSetId);

        return new JSONResponse(['tokenSetId' => $tokenSetId, 'resolved' => $resolved]);
    }//end getTokenSetPreview()
}//end class
