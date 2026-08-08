<?php

/**
 * NL Design Admin Settings.
 *
 * @category Settings
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Settings;

use OCA\NLDesign\AppInfo\Application;
use OCA\NLDesign\Application\Presentation\RuntimeStylesheetPlan;
use OCA\NLDesign\Service\ProfileStateService;
use OCA\NLDesign\Service\TokenSetService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http;
use OCP\IL10N;
use OCP\Settings\IDelegatedSettings;

/**
 * Admin settings form for NL Design.
 *
 * Provides the configuration interface for selecting design token sets.
 */
final class Admin implements IDelegatedSettings
{

    /**
     * Localization helper.
     *
     * @var IL10N
     */
    private IL10N $l;

    /**
     * Token set discovery service.
     *
     * @var TokenSetService
     */
    private TokenSetService $tokenSetService;

    /**
     * Profile state service.
     *
     * @var ProfileStateService
     */
    private ProfileStateService $profileStateService;

    /**
     * Constructor.
     *
     * @param IL10N                 $l                   The localization service.
     * @param TokenSetService       $tokenSetService     Token set discovery service.
     * @param ProfileStateService   $profileStateService Profile state tracking service.
     * @param RuntimeStylesheetPlan $stylesheetPlan      Runtime compatibility plan.
     */
    public function __construct(
        IL10N $l,
        TokenSetService $tokenSetService,
        ProfileStateService $profileStateService,
        private RuntimeStylesheetPlan $stylesheetPlan
    ) {
        $this->l = $l;
        $this->tokenSetService     = $tokenSetService;
        $this->profileStateService = $profileStateService;
    }//end __construct()

    /**
     * Get the settings form.
     *
     * @return         TemplateResponse The settings form template.
     * @phpstan-return TemplateResponse<Http::STATUS_OK, array{}>
     */
    public function getForm(): TemplateResponse
    {
        $tokenSets       = $this->tokenSetService->getAvailableTokenSets();
        $profileState    = $this->profileStateService->getActiveProfileState();
        $currentTokenSet = $profileState['active_profile_id'];
        $currentVersion  = $profileState['active_profile_version'];
        $runtimePlan     = $this->stylesheetPlan->build();

        $currentIsAvailable = $this->isCurrentTokenSetAvailable(
            tokenSets: $tokenSets,
            currentTokenSet: $currentTokenSet,
            currentVersion: $currentVersion
        );

        return new TemplateResponse(
            Application::APP_ID,
            'settings/admin',
            [
                'tokenSets'                => $tokenSets,
                'currentTokenSet'          => $currentTokenSet,
                'currentProfileVersion'    => $currentVersion,
                'currentTokenSetAvailable' => $currentIsAvailable,
                'profileState'             => $profileState,
                'runtimeCompatibility'     => $runtimePlan,
            ]
        );
    }//end getForm()

    /**
     * Check whether the stored token set is available in the discovered token list.
     *
     * @param array<int, array<string, mixed>> $tokenSets       Available token sets from discovery.
     * @param string|null                      $currentTokenSet Stored token set.
     * @param string|null                      $currentVersion  Stored profile version.
     *
     * @return bool True when token set exists.
     */
    private function isCurrentTokenSetAvailable(
        array $tokenSets,
        ?string $currentTokenSet,
        ?string $currentVersion
    ): bool {
        foreach ($tokenSets as $tokenSet) {
            if (($tokenSet['id'] ?? '') === $currentTokenSet
                && ($tokenSet['version'] ?? '') === $currentVersion
            ) {
                return true;
            }
        }

        return false;
    }//end isCurrentTokenSetAvailable()

    /**
     * Get the settings section identifier.
     *
     * @return string The section identifier (theming).
     */
    public function getSection(): string
    {
        return 'theming';
    }//end getSection()

    /**
     * Get the priority for ordering in the settings menu.
     *
     * @return int The priority value (lower = higher priority).
     */
    public function getPriority(): int
    {
        return 50;
    }//end getPriority()

    /**
     * Get the delegated-settings display name.
     *
     * @return string Display name.
     */
    public function getName(): string
    {
        return $this->l->t('NL Design');
    }//end getName()

    /**
     * Restrict delegated administrators to this app's known state keys.
     *
     * @return array<string, array<int, string>> Authorized config patterns.
     */
    public function getAuthorizedAppConfig(): array
    {
        return [
            Application::APP_ID => [
                '/^(active_profile_state|active_profile_revision|active_profile_version|profile_state_history|token_set)$/',
            ],
        ];
    }//end getAuthorizedAppConfig()
}//end class
