<?php

/**
 * NL Design Application Bootstrap.
 *
 * @category Application
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0-or-later
 * @link     https://github.com/ConductionNL/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\AppInfo;

use OCA\NLDesign\Service\CustomOverridesService;
use OCA\NLDesign\Themes\NLDesignTheme;
use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

/**
 * Main application class for NL Design.
 *
 * Bootstraps the NL Design theme system and injects design tokens.
 */
class Application extends App implements IBootstrap
{
    public const APP_ID = 'nldesign';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(self::APP_ID);
    }//end __construct()

    /**
     * Register services and providers.
     *
     * @param IRegistrationContext $context The registration context.
     *
     * @return void
     */
    public function register(IRegistrationContext $context): void
    {
        // Register the theme.
    }//end register()

    /**
     * Boot the application.
     *
     * @param IBootContext $context The boot context.
     *
     * @return void
     */
    public function boot(IBootContext $context): void
    {
        $serverContainer = $context->getServerContainer();

        // Inject our CSS variables.
        $this->injectThemeCSS($serverContainer);
    }//end boot()

    /**
     * Inject theme CSS files based on configuration.
     *
     * @param mixed $serverContainer The server container.
     *
     * @return void
     */
    private function injectThemeCSS($serverContainer): void
    {
        $config         = $serverContainer->getConfig();
        $tokenSet       = $config->getAppValue(self::APP_ID, 'token_set', 'rijkshuisstijl');
        $hideSlogan     = $config->getAppValue(self::APP_ID, 'hide_slogan', '0') === '1';
        $showMenuLabels = $config->getAppValue(self::APP_ID, 'show_menu_labels', '0') === '1';

        // CSS Load Order: fonts, defaults, tokens/{org}, utrecht-bridge, theme, overrides, element-overrides.
        // 1. Fonts (Fira Sans from @fontsource).
        \OCP\Util::addStyle(appName: self::APP_ID, styleName: 'fonts');

        // 2. Defaults — sensible Rijkshuisstijl-based defaults for ALL --nldesign-* tokens.
        \OCP\Util::addStyle(appName: self::APP_ID, styleName: 'defaults');

        // 3. Token set — organization-specific tokens override defaults.
        \OCP\Util::addStyle(appName: self::APP_ID, styleName: 'tokens/'.$tokenSet);

        // 4. Utrecht bridge — maps --utrecht-* component tokens to --nldesign-component-*.
        \OCP\Util::addStyle(appName: self::APP_ID, styleName: 'utrecht-bridge');

        // 5. Theme — maps --nldesign-* tokens to Nextcloud element styling.
        \OCP\Util::addStyle(appName: self::APP_ID, styleName: 'theme');

        // 6. Variable overrides — maps Nextcloud CSS variables to --nldesign-* tokens.
        \OCP\Util::addStyle(appName: self::APP_ID, styleName: 'overrides');

        // 7. Element overrides — applies NL Design styling to specific Nextcloud elements.
        \OCP\Util::addStyle(appName: self::APP_ID, styleName: 'element-overrides');

        // 8. Custom overrides — admin-defined token overrides, always wins (loaded last).
        $appManager           = $serverContainer->get(IAppManager::class);
        $customOverridesSvc   = new CustomOverridesService(appManager: $appManager);
        $customOverridesSvc->ensureExists();
        \OCP\Util::addStyle(appName: self::APP_ID, styleName: 'custom-overrides');

        // Hide slogan if enabled.
        if ($hideSlogan === true) {
            \OCP\Util::addStyle(appName: self::APP_ID, styleName: 'hide-slogan');
        }

        // Show menu labels (instead of icons) if enabled.
        if ($showMenuLabels === true) {
            \OCP\Util::addStyle(appName: self::APP_ID, styleName: 'show-menu-labels');
        }
    }//end injectThemeCSS()
}//end class
