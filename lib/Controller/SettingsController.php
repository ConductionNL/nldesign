<?php

/**
 * NL Design Settings Controller.
 *
 * @category Controller
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0-or-later
 * @link     https://github.com/ConductionNL/nldesign
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-14
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-15
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-16
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-17
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-18
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-19
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-20
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-21
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-22
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-23
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-24
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-25
 */

declare(strict_types=1);

namespace OCA\NLDesign\Controller;

use OCA\NLDesign\AppInfo\Application;
use OCA\NLDesign\Service\CustomOverridesService;
use OCA\NLDesign\Service\ThemingService;
use OCA\NLDesign\Service\TokenRegistry;
use OCA\NLDesign\Service\TokenSetPreviewService;
use OCA\NLDesign\Service\TokenSetService;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;

/**
 * Settings controller for NL Design app.
 *
 * Handles API requests for managing token sets, theming, and display settings.
 * Override-related endpoints are handled by OverridesController.
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-14
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-15
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-16
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-17
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-18
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-19
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-20
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-21
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-22
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-23
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-24
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-25
 */
class SettingsController extends Controller
{

    /**
     * The application configuration service.
     *
     * @var IConfig
     */
    private IConfig $config;

    /**
     * The app manager.
     *
     * @var IAppManager
     */
    private IAppManager $appManager;

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
     * The token set preview service.
     *
     * @var TokenSetPreviewService
     */
    private TokenSetPreviewService $previewService;

    /**
     * Constructor.
     *
     * @param string                 $appName         The app name.
     * @param IRequest               $request         The request object.
     * @param IConfig                $config          The config service.
     * @param IAppManager            $appManager      The app manager.
     * @param TokenSetService        $tokenSetService The token set service.
     * @param ThemingService         $themingService  The theming service.
     * @param TokenSetPreviewService $previewService  The token set preview service.
     */
    public function __construct(
        string $appName,
        IRequest $request,
        IConfig $config,
        IAppManager $appManager,
        TokenSetService $tokenSetService,
        ThemingService $themingService,
        TokenSetPreviewService $previewService
    ) {
        parent::__construct(appName: $appName, request: $request);
        $this->config          = $config;
        $this->appManager      = $appManager;
        $this->tokenSetService = $tokenSetService;
        $this->themingService  = $themingService;
        $this->previewService  = $previewService;
    }//end __construct()

    /**
     * Set the active design token set.
     *
     * @param string $tokenSet The token set name.
     *
     * @return JSONResponse The response with status and selected token set.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-14
     */
    public function setTokenSet(string $tokenSet): JSONResponse
    {
        $tokenSetService = new TokenSetService(appManager: $this->appManager);
        if ($tokenSetService->isValidTokenSet(tokenSetId: $tokenSet) === false) {
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
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-15
     */
    public function getTokenSet(): JSONResponse
    {
        $tokenSet = $this->config->getAppValue(
            Application::APP_ID,
            'token_set',
            'nextcloud'
        );

        return new JSONResponse(['tokenSet' => $tokenSet]);
    }//end getTokenSet()

    /**
     * Get all available token sets.
     *
     * @return JSONResponse The list of available token sets.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-16
     */
    public function getAvailableTokenSets(): JSONResponse
    {
        $tokenSets = $this->tokenSetService->getAvailableTokenSets();

        return new JSONResponse(['tokenSets' => $tokenSets]);
    }//end getAvailableTokenSets()

    /**
     * Store a boolean app setting as '0' or '1'.
     *
     * @param string $key   The app config key.
     * @param bool   $value The boolean value.
     *
     * @return void
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-17
     */
    private function saveBooleanSetting(string $key, bool $value): void
    {
        $stored = '0';
        if ($value === true) {
            $stored = '1';
        }

        $this->config->setAppValue(Application::APP_ID, $key, $stored);
    }//end saveBooleanSetting()

    /**
     * Set the hide slogan setting.
     *
     * @param bool $hideSlogan Whether to hide the slogan on login page.
     *
     * @return JSONResponse The response with the status.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-18
     */
    public function setSloganSetting(bool $hideSlogan): JSONResponse
    {
        $this->saveBooleanSetting(key: 'hide_slogan', value: $hideSlogan);

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
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-19
     */
    public function setMenuLabelsSetting(bool $showMenuLabels): JSONResponse
    {
        $this->saveBooleanSetting(key: 'show_menu_labels', value: $showMenuLabels);

        return new JSONResponse(['status' => 'ok', 'showMenuLabels' => $showMenuLabels]);
    }//end setMenuLabelsSetting()

    /**
     * Get the token overrides (registry, tabs, and saved overrides).
     *
     * @return JSONResponse The token editor data.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-20
     */
    public function getOverrides(): JSONResponse
    {
        $customOverridesService = new CustomOverridesService(appManager: $this->appManager);
        $customOverridesService->ensureExists();

        return new JSONResponse([
            'registry'  => TokenRegistry::getTokens(),
            'tabs'      => TokenRegistry::getTabLabels(),
            'overrides' => $customOverridesService->read(),
        ]);
    }//end getOverrides()

    /**
     * Save token overrides.
     *
     * @param array $overrides The token overrides to save.
     *
     * @return JSONResponse The response with the status.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-21
     */
    public function setOverrides(array $overrides): JSONResponse
    {
        $customOverridesService = new CustomOverridesService(appManager: $this->appManager);
        $customOverridesService->write(tokens: $overrides);

        return new JSONResponse(['status' => 'ok']);
    }//end setOverrides()

    /**
     * Update Nextcloud theming values from NL Design tokens.
     *
     * @return JSONResponse The response with updated fields.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-22
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
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-23
     */
    public function getThemingValues(): JSONResponse
    {
        $values = $this->buildThemingSnapshot();

        return new JSONResponse($values);
    }//end getThemingValues()

    /**
     * Build a snapshot of the current theming state.
     *
     * @return array<string, mixed> The theming snapshot.
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-24
     */
    private function buildThemingSnapshot(): array
    {
        $imgManager = $this->themingService->getImageManager();

        return [
            'primary_color'         => $this->config->getAppValue('theming', 'primary_color', ''),
            'background_color'      => $this->config->getAppValue('theming', 'background_color', ''),
            'logo_url'              => $imgManager->getImageUrl('logo'),
            'background_url'        => $imgManager->getImageUrl('background'),
            'has_custom_logo'       => $imgManager->hasImage('logo'),
            'has_custom_background' => $imgManager->hasImage('background'),
        ];
    }//end buildThemingSnapshot()

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
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-25
     */
    public function getTokenSetPreview(string $tokenSetId): JSONResponse
    {
        if ($this->tokenSetService->isValidTokenSet(tokenSetId: $tokenSetId) === false) {
            return new JSONResponse(['error' => 'Token set not found'], 404);
        }

        $resolved = $this->previewService->getResolvedColors(tokenSetId: $tokenSetId);

        return new JSONResponse(['tokenSetId' => $tokenSetId, 'resolved' => $resolved]);
    }//end getTokenSetPreview()
}//end class
