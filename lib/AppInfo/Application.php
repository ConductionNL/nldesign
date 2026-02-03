<?php

declare(strict_types=1);

namespace OCA\NLDesign\AppInfo;

use OCA\NLDesign\Themes\NLDesignTheme;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
	public const APP_ID = 'nldesign';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		// Register the theme
	}

	public function boot(IBootContext $context): void {
		$serverContainer = $context->getServerContainer();

		// Inject our CSS variables
		$this->injectThemeCSS($serverContainer);
	}

	private function injectThemeCSS($serverContainer): void {
		$config = $serverContainer->getConfig();
		$tokenSet = $config->getAppValue(self::APP_ID, 'token_set', 'rijkshuisstijl');

		// Add fonts (Fira Sans from @fontsource)
		\OCP\Util::addStyle(self::APP_ID, 'fonts');
		
		// Add the CSS file for the selected token set
		\OCP\Util::addStyle(self::APP_ID, 'tokens/' . $tokenSet);
		\OCP\Util::addStyle(self::APP_ID, 'theme');
		
		// Add aggressive overrides last (highest priority)
		\OCP\Util::addStyle(self::APP_ID, 'overrides');
		
		// Add logo for the selected token set
		\OCP\Util::addStyle(self::APP_ID, 'logo-' . $tokenSet);
		
		// Nuclear option for gradients (absolute last)
		\OCP\Util::addStyle(self::APP_ID, 'nuclear');
	}
}
