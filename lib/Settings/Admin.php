<?php

/**
 * NL Design Admin Settings.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category  Settings
 * @package   OCA\NLDesign
 * @author    Conduction <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://codeberg.org/Conduction/nldesign
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-55
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-56
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-57
 * @spec openspec/specs/admin-settings/spec.md#requirement-session-preview-controls
 */

declare(strict_types=1);

namespace OCA\NLDesign\Settings;

use OCA\NLDesign\AppInfo\Application;
use OCA\NLDesign\Service\EmailThemingService;
use OCA\NLDesign\Service\ThemePreviewService;
use OCA\NLDesign\Service\TokenSetService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUserSession;
use OCP\Settings\IDelegatedSettings;

/**
 * Admin settings form for NL Design.
 *
 * Provides the configuration interface for selecting design token sets.
 * Implements IDelegatedSettings so AuthorizedAdminSetting can reference this class.
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-55
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-56
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-57
 */
class Admin implements IDelegatedSettings
{

    /**
     * The application configuration service.
     *
     * @var IConfig
     */
    private IConfig $config;

    /**
     * The localization service (kept for future i18n use).
     *
     * @var IL10N
     */
    private IL10N $l;

    /**
     * The token set service.
     *
     * @var TokenSetService
     */
    private TokenSetService $tokenSetService;

    /**
     * The email theming service.
     *
     * @var EmailThemingService
     */
    private EmailThemingService $emailThemingService;

    /**
     * The theme preview service (drives the active-preview row).
     *
     * @var ThemePreviewService
     */
    private ThemePreviewService $previewService;

    /**
     * The user session — resolves the requesting admin's uid for the
     * active-preview row.
     *
     * @var IUserSession
     */
    private IUserSession $userSession;

    /**
     * Constructor.
     *
     * @param IConfig             $config              The config service.
     * @param IL10N               $l                   The localization service.
     * @param TokenSetService     $tokenSetService     The token set service.
     * @param EmailThemingService $emailThemingService The email theming service.
     * @param ThemePreviewService $previewService      The theme preview service.
     * @param IUserSession        $userSession         The user session.
     */
    public function __construct(
        IConfig $config,
        IL10N $l,
        TokenSetService $tokenSetService,
        EmailThemingService $emailThemingService,
        ThemePreviewService $previewService,
        IUserSession $userSession
    ) {
        $this->config = $config;
        $this->l      = $l;
        $this->tokenSetService     = $tokenSetService;
        $this->emailThemingService = $emailThemingService;
        $this->previewService      = $previewService;
        $this->userSession         = $userSession;
    }//end __construct()

    /**
     * Get the settings form.
     *
     * @return TemplateResponse The settings form template.
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-55
     */
    public function getForm(): TemplateResponse
    {
        $tokenSets = $this->tokenSetService->getAvailableTokenSets();

        $currentTokenSet = $this->config->getAppValue(
            Application::APP_ID,
            'token_set',
            'nextcloud'
        );

        $hideSlogan = $this->config->getAppValue(
            Application::APP_ID,
            'hide_slogan',
            '0'
        ) === '1';

        $showMenuLabels = $this->config->getAppValue(
            Application::APP_ID,
            'show_menu_labels',
            '0'
        ) === '1';

        $emailThemingState = $this->emailThemingService->getState();
        $emailFooterConfig = $this->emailThemingService->getFooterConfig();

        $activePreview = $this->getActivePreviewForCurrentUser();

        return new TemplateResponse(
            Application::APP_ID,
                'settings/admin',
                [
                    'tokenSets'         => $tokenSets,
                    'currentTokenSet'   => $currentTokenSet,
                    'hideSlogan'        => $hideSlogan,
                    'showMenuLabels'    => $showMenuLabels,
                    'emailThemingState' => $emailThemingState,
                    'emailFooterConfig' => $emailFooterConfig,
                    'occEnableCommand'  => EmailThemingService::OCC_ENABLE_COMMAND,
                    'occDisableCommand' => EmailThemingService::OCC_DISABLE_COMMAND,
                    'activePreview'     => $activePreview,
                ]
        );
    }//end getForm()

    /**
     * Resolve the requesting admin's active preview (name + token set id), or
     * null when they have none — drives the settings panel's active-preview
     * row (admin-settings spec: Session Preview Controls).
     *
     * @return array{tokenSet: string, name: string}|null The active preview, or null.
     *
     * @spec openspec/specs/admin-settings/spec.md#requirement-session-preview-controls
     */
    private function getActivePreviewForCurrentUser(): ?array
    {
        $uid = $this->userSession->getUser()?->getUID();
        if ($uid === null) {
            return null;
        }

        $preview = $this->previewService->getActivePreview(uid: $uid);
        if ($preview === null) {
            return null;
        }

        $meta = $this->tokenSetService->getAvailableTokenSets();
        $name = $preview['tokenSet'];
        foreach ($meta as $tokenSet) {
            if ($tokenSet['id'] === $preview['tokenSet']) {
                $name = $tokenSet['name'];
                break;
            }
        }

        return [
            'tokenSet' => $preview['tokenSet'],
            'name'     => $name,
        ];
    }//end getActivePreviewForCurrentUser()

    /**
     * Get the settings section identifier.
     *
     * @return string The section identifier (theming).
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-56
     */
    public function getSection(): string
    {
        return 'theming';
    }//end getSection()

    /**
     * Get the priority for ordering in the settings menu.
     *
     * @return int The priority value (lower = higher priority).
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-57
     */
    public function getPriority(): int
    {
        return 50;
    }//end getPriority()

    /**
     * Get the display name for this delegated settings section.
     *
     * Returns null so only the section name is displayed (no sub-name).
     *
     * @return string|null The settings display name, or null.
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-55
     */
    public function getName(): ?string
    {
        return null;
    }//end getName()

    /**
     * Get the list of authorized app config keys this setting may modify.
     *
     * Returns the nldesign app config keys that admin-delegated users
     * are allowed to modify through this settings panel.
     *
     * @return array<string, string[]> The authorized app config map.
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-55
     * @spec openspec/specs/upstream-freshness/spec.md
     * @spec openspec/specs/per-group-theming/spec.md
     */
    public function getAuthorizedAppConfig(): array
    {
        return [
            Application::APP_ID => [
                '/token_set/',
                '/hide_slogan/',
                '/show_menu_labels/',
                '/disabled_apps/',
                '/theming_syncs_total/',
                // Per-group theming mapping (openspec/specs/per-group-theming/spec.md):
                // the ordered group->token-set mapping and its cache-invalidation
                // generation counter.
                '/group_token_sets/',
                '/group_token_sets_generation/',
                // Upstream token freshness — only the two admin-initiated
                // config keys (the opt-in toggle and dismissal state); the
                // job-internal ETag/head-SHA/checked-at/notices keys are never
                // written through this delegated-admin surface.
                '/upstream_freshness_enabled/',
                '/upstream_freshness_dismissed/',
                '/email_footer_org_name/',
                '/email_footer_accessibility_url/',
                '/email_footer_privacy_url/',
            ],
        ];
    }//end getAuthorizedAppConfig()
}//end class
