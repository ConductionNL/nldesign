<?php

declare(strict_types=1);

namespace OCA\NLDesign\Settings;

use OCA\NLDesign\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Settings\ISettings;

class Admin implements ISettings {
	private IConfig $config;
	private IL10N $l;

	public function __construct(IConfig $config, IL10N $l) {
		$this->config = $config;
		$this->l = $l;
	}

	public function getForm(): TemplateResponse {
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

		return new TemplateResponse(Application::APP_ID, 'settings/admin', [
			'tokenSets' => $tokenSets,
			'currentTokenSet' => $currentTokenSet,
		]);
	}

	public function getSection(): string {
		return 'theming';
	}

	public function getPriority(): int {
		return 50;
	}
}
