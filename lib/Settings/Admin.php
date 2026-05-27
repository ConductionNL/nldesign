<?php

/**
 * NL Design Admin Settings.
 *
 * @category  Settings
 * @package   OCA\NLDesign
 * @author    Conduction <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0-or-later
 * @link      https://github.com/ConductionNL/nldesign
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * SPDX-FileCopyrightText: Copyright (C) 2024 Conduction B.V.
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-55
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-56
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-57
 */

declare(strict_types=1);

namespace OCA\NLDesign\Settings;

use OCA\NLDesign\AppInfo\Application;
use OCA\NLDesign\Service\TokenSetService;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Settings\IDelegatedSettings;

/**
 * Admin settings form for NL Design.
 *
 * Provides the configuration interface for selecting design token sets.
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
     * The app manager.
     *
     * @var IAppManager
     */
    private IAppManager $appManager;

    /**
     * Constructor.
     *
     * @param IConfig     $config     The config service.
     * @param IL10N       $l          The localization service.
     * @param IAppManager $appManager The app manager.
     */
    public function __construct(IConfig $config, IL10N $l, IAppManager $appManager)
    {
        $this->config     = $config;
        $this->l          = $l;
        $this->appManager = $appManager;
    }//end __construct()

    /**
     * Get the display name for partial admin delegation.
     *
     * Returns null so only the section name appears in the settings list.
     *
     * @return string|null Always null (no extra label needed).
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-55
     */
    public function getName(): ?string
    {
        return null;
    }//end getName()

    /**
     * Get the authorized app config keys for delegation.
     *
     * Returns the nldesign app config keys that can be modified via delegated admin.
     *
     * @return array<string, string[]> Map of app name to allowed config key patterns.
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-55
     */
    public function getAuthorizedAppConfig(): array
    {
        return [
            Application::APP_ID => [
                '/token_set/',
                '/hide_slogan/',
                '/show_menu_labels/',
                '/theming_syncs_total/',
            ],
        ];
    }//end getAuthorizedAppConfig()

    /**
     * Get the settings form.
     *
     * @return TemplateResponse The settings form template.
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-55
     */
    public function getForm(): TemplateResponse
    {
        $tokenSetService = new TokenSetService(appManager: $this->appManager);
        $tokenSets       = $tokenSetService->getAvailableTokenSets();

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

        return new TemplateResponse(
            Application::APP_ID,
                'settings/admin',
                [
                    'tokenSets'       => $tokenSets,
                    'currentTokenSet' => $currentTokenSet,
                    'hideSlogan'      => $hideSlogan,
                    'showMenuLabels'  => $showMenuLabels,
                ]
        );
    }//end getForm()

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
}//end class
