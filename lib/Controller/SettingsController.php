<?php

/**
 * NL Design Settings Controller.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category  Controller
 * @package   OCA\NLDesign
 * @author    Conduction <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://codeberg.org/Conduction/nldesign
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
use OCA\NLDesign\Service\AppThemingService;
use OCA\NLDesign\Service\EmailThemingService;
use OCA\NLDesign\Service\Exception\ConfigReadOnlyException;
use OCA\NLDesign\Service\Exception\FooterValidationException;
use OCA\NLDesign\Service\Exception\ForeignMailTemplateClassException;
use OCA\NLDesign\Service\ThemingService;
use OCA\NLDesign\Service\TokenSetPreviewService;
use OCA\NLDesign\Service\TokenSetService;
use OCA\NLDesign\Settings\Admin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
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
     * The per-app theming service.
     *
     * @var AppThemingService
     */
    private AppThemingService $appThemingService;

    /**
     * The email theming service.
     *
     * @var EmailThemingService
     */
    private EmailThemingService $emailThemingService;

    /**
     * Constructor.
     *
     * @param string                 $appName             The app name.
     * @param IRequest               $request             The request object.
     * @param IConfig                $config              The config service.
     * @param TokenSetService        $tokenSetService     The token set service.
     * @param ThemingService         $themingService      The theming service.
     * @param TokenSetPreviewService $previewService      The token set preview service.
     * @param AppThemingService      $appThemingService   The per-app theming service.
     * @param EmailThemingService    $emailThemingService The email theming service.
     */
    public function __construct(
        string $appName,
        IRequest $request,
        IConfig $config,
        TokenSetService $tokenSetService,
        ThemingService $themingService,
        TokenSetPreviewService $previewService,
        AppThemingService $appThemingService,
        EmailThemingService $emailThemingService
    ) {
        parent::__construct(appName: $appName, request: $request);
        $this->config            = $config;
        $this->tokenSetService   = $tokenSetService;
        $this->themingService    = $themingService;
        $this->previewService    = $previewService;
        $this->appThemingService = $appThemingService;
        $this->emailThemingService = $emailThemingService;
    }//end __construct()

    /**
     * Set the active design token set.
     *
     * @param string $tokenSet The token set name.
     *
     * @return JSONResponse The response with status and selected token set.
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-14
     */
    #[AuthorizedAdminSetting(Admin::class)]
    public function setTokenSet(string $tokenSet): JSONResponse
    {
        if ($this->tokenSetService->isValidTokenSet(tokenSetId: $tokenSet) === false) {
            return new JSONResponse(['error' => 'Invalid token set'], 400);
        }

        $this->config->setAppValue(Application::APP_ID, 'token_set', $tokenSet);

        return new JSONResponse(['status' => 'ok', 'tokenSet' => $tokenSet]);
    }//end setTokenSet()

    /**
     * Get the currently active design token set.
     *
     * @return JSONResponse The response with the current token set.
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-15
     */
    #[AuthorizedAdminSetting(Admin::class)]
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
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-16
     */
    #[AuthorizedAdminSetting(Admin::class)]
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
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-18
     */
    #[AuthorizedAdminSetting(Admin::class)]
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
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-19
     */
    #[AuthorizedAdminSetting(Admin::class)]
    public function setMenuLabelsSetting(bool $showMenuLabels): JSONResponse
    {
        $this->saveBooleanSetting(key: 'show_menu_labels', value: $showMenuLabels);

        return new JSONResponse(['status' => 'ok', 'showMenuLabels' => $showMenuLabels]);
    }//end setMenuLabelsSetting()

    /**
     * Update Nextcloud theming values from NL Design tokens.
     *
     * @return JSONResponse The response with updated fields.
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-22
     */
    #[AuthorizedAdminSetting(Admin::class)]
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

        // Increment the theming sync counter exposed by MetricsController as
        // nldesign_theming_syncs_total. Only counted on success (after both
        // apply* calls completed without throwing).
        $current = (int) $this->config->getAppValue(Application::APP_ID, 'theming_syncs_total', '0');
        $this->config->setAppValue(Application::APP_ID, 'theming_syncs_total', (string) ($current + 1));

        return new JSONResponse(['status' => 'ok', 'updated' => $updated]);
    }//end updateThemingValues()

    /**
     * Get current Nextcloud theming values for comparison.
     *
     * @return JSONResponse The current theming values.
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-23
     */
    #[AuthorizedAdminSetting(Admin::class)]
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
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-25
     */
    #[AuthorizedAdminSetting(Admin::class)]
    public function getTokenSetPreview(string $tokenSetId): JSONResponse
    {
        if ($this->tokenSetService->isValidTokenSet(tokenSetId: $tokenSetId) === false) {
            return new JSONResponse(['error' => 'Token set not found'], 404);
        }

        $resolved = $this->previewService->getResolvedColors(tokenSetId: $tokenSetId);

        return new JSONResponse(['tokenSetId' => $tokenSetId, 'resolved' => $resolved]);
    }//end getTokenSetPreview()

    /**
     * List enabled apps with their per-app theming state.
     *
     * @return JSONResponse The list of { id, name, themed } entries.
     *
     * @spec openspec/changes/per-app-theming-toggle/tasks.md#task-3.1
     */
    #[AuthorizedAdminSetting(Admin::class)]
    public function getAppTheming(): JSONResponse
    {
        return new JSONResponse(['apps' => $this->appThemingService->getThemableApps()]);
    }//end getAppTheming()

    /**
     * Replace the per-app theming exclusion list.
     *
     * Accepts { disabledApps: string[] }. Unknown and protected ids are dropped
     * by the service before persisting.
     *
     * @param array $disabledApps The app ids to exclude from theming.
     *
     * @return JSONResponse The persisted state after validation.
     *
     * @spec openspec/changes/per-app-theming-toggle/tasks.md#task-3.1
     */
    #[AuthorizedAdminSetting(Admin::class)]
    public function setAppTheming(array $disabledApps=[]): JSONResponse
    {
        $this->appThemingService->setDisabledApps(appIds: $disabledApps);

        return new JSONResponse(
            [
                'status'       => 'ok',
                'disabledApps' => $this->appThemingService->getDisabledApps(),
            ]
        );
    }//end setAppTheming()

    /**
     * Get the email template toggle state and compliance footer config.
     *
     * @return JSONResponse The state, footer config, and manual occ commands.
     *
     * @spec openspec/specs/email-theming/spec.md
     */
    #[AuthorizedAdminSetting(Admin::class)]
    public function getEmailTheming(): JSONResponse
    {
        return new JSONResponse(
            [
                'state'      => $this->emailThemingService->getState(),
                'footer'     => $this->emailThemingService->getFooterConfig(),
                'occEnable'  => EmailThemingService::OCC_ENABLE_COMMAND,
                'occDisable' => EmailThemingService::OCC_DISABLE_COMMAND,
            ]
        );
    }//end getEmailTheming()

    /**
     * Save the compliance footer config and toggle the email template.
     *
     * The footer config is always applied first (app config, always
     * writable) and independently reported, so it saves successfully even
     * when the system-config toggle write fails (read-only config.php or a
     * foreign `mail_template_class`).
     *
     * @param bool   $enabled          Whether the branded template should be enabled.
     * @param string $orgName          The organization name.
     * @param string $accessibilityUrl The toegankelijkheidsverklaring URL.
     * @param string $privacyUrl       The privacy statement URL.
     *
     * @return JSONResponse The result, or a structured 409/422 error.
     *
     * @spec openspec/specs/email-theming/spec.md
     */
    #[AuthorizedAdminSetting(Admin::class)]
    public function setEmailTheming(
        bool $enabled,
        string $orgName='',
        string $accessibilityUrl='',
        string $privacyUrl=''
    ): JSONResponse {
        try {
            $this->emailThemingService->setFooterConfig($orgName, $accessibilityUrl, $privacyUrl);
        } catch (FooterValidationException $e) {
            return new JSONResponse(
                [
                    'error'   => 'invalid_footer',
                    'field'   => $e->getField(),
                    'message' => $e->getMessage(),
                ],
                422
            );
        }

        try {
            if ($enabled === true) {
                $this->emailThemingService->enable();
            }

            if ($enabled === false) {
                $this->emailThemingService->disable();
            }
        } catch (ConfigReadOnlyException $e) {
            return new JSONResponse(
                [
                    'error'      => 'config_read_only',
                    'occEnable'  => $e->getOccEnableCommand(),
                    'occDisable' => $e->getOccDisableCommand(),
                    'footer'     => $this->emailThemingService->getFooterConfig(),
                ],
                409
            );
        } catch (ForeignMailTemplateClassException $e) {
            return new JSONResponse(
                [
                    'error'  => 'foreign_mail_template_class',
                    'class'  => $e->getForeignClass(),
                    'footer' => $this->emailThemingService->getFooterConfig(),
                ],
                409
            );
        }//end try

        return new JSONResponse(
            [
                'status' => 'ok',
                'state'  => $this->emailThemingService->getState(),
                'footer' => $this->emailThemingService->getFooterConfig(),
            ]
        );
    }//end setEmailTheming()
}//end class
