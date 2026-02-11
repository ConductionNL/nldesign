<?php

/**
 * NL Design Admin Settings.
 *
 * @category Settings
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0-or-later
 * @link     https://github.com/ConductionNL/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Settings;

use OCA\NLDesign\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Settings\ISettings;

/**
 * Admin settings form for NL Design.
 *
 * Provides the configuration interface for selecting design token sets.
 */
class Admin implements ISettings
{
    private IConfig $config;
    private IL10N $l;

    /**
     * Constructor.
     *
     * @param IConfig $config The config service.
     * @param IL10N   $l      The localization service.
     */
    public function __construct(IConfig $config, IL10N $l)
    {
        $this->config = $config;
        $this->l = $l;
    }

    /**
     * Get the settings form.
     *
     * @return TemplateResponse The settings form template.
     */
    public function getForm(): TemplateResponse
    {
        $tokenSets = [
            'rijkshuisstijl' => [
                'name' => 'Rijkshuisstijl',
                'description' => $this->l->t('Dutch national government (Rijksoverheid)'),
            ],
            'utrecht' => [
                'name' => 'Gemeente Utrecht',
                'description' => $this->l->t('Municipality of Utrecht'),
            ],
            'amsterdam' => [
                'name' => 'Gemeente Amsterdam',
                'description' => $this->l->t('Municipality of Amsterdam'),
            ],
            'denhaag' => [
                'name' => 'Gemeente Den Haag',
                'description' => $this->l->t('Municipality of The Hague'),
            ],
            'rotterdam' => [
                'name' => 'Gemeente Rotterdam',
                'description' => $this->l->t('Municipality of Rotterdam'),
            ],
        ];

        $currentTokenSet = $this->config->getAppValue(
            Application::APP_ID,
            'token_set',
            'rijkshuisstijl'
        );

        $hideSlogan = $this->config->getAppValue(
            Application::APP_ID,
            'hide_slogan',
            '0'
        ) === '1';

        return new TemplateResponse(
            Application::APP_ID, 'settings/admin', [
            'tokenSets' => $tokenSets,
            'currentTokenSet' => $currentTokenSet,
            'hideSlogan' => $hideSlogan,
            ]
        );
    }

    /**
     * Get the settings section identifier.
     *
     * @return string The section identifier (theming).
     */
    public function getSection(): string
    {
        return 'theming';
    }

    /**
     * Get the priority for ordering in the settings menu.
     *
     * @return int The priority value (lower = higher priority).
     */
    public function getPriority(): int
    {
        return 50;
    }
}
