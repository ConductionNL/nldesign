<?php

declare(strict_types=1);

namespace OCA\NLDesign\Controller;

use OCA\NLDesign\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;

class SettingsController extends Controller {
	private IConfig $config;

	public function __construct(
		string $appName,
		IRequest $request,
		IConfig $config
	) {
		parent::__construct($appName, $request);
		$this->config = $config;
	}

	/**
	 * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
	 */
	public function setTokenSet(string $tokenSet): JSONResponse {
		$validSets = ['rijkshuisstijl', 'utrecht', 'amsterdam', 'denhaag', 'rotterdam'];

		if (!in_array($tokenSet, $validSets)) {
			return new JSONResponse(['error' => 'Invalid token set'], 400);
		}

		$this->config->setAppValue(Application::APP_ID, 'token_set', $tokenSet);

		return new JSONResponse(['status' => 'ok', 'tokenSet' => $tokenSet]);
	}

	/**
	 * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
	 */
	public function getTokenSet(): JSONResponse {
		$tokenSet = $this->config->getAppValue(
			Application::APP_ID,
			'token_set',
			'rijkshuisstijl'
		);

		return new JSONResponse(['tokenSet' => $tokenSet]);
	}
}
