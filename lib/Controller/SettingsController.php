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

	/**
	 * Set the hide slogan setting.
	 * 
	 * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
	 * @param bool $hideSlogan Whether to hide the slogan on login page.
	 * @return JSONResponse The response with the status.
	 */
	public function setSloganSetting(bool $hideSlogan): JSONResponse {
		$this->config->setAppValue(
			Application::APP_ID,
			'hide_slogan',
			$hideSlogan ? '1' : '0'
		);

		return new JSONResponse(['status' => 'ok', 'hideSlogan' => $hideSlogan]);
	}
}
