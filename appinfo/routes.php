<?php

declare(strict_types=1);

return [
	'routes' => [
		['name' => 'settings#setTokenSet', 'url' => '/settings/tokenset', 'verb' => 'POST'],
		['name' => 'settings#deactivateTokenSet', 'url' => '/settings/deactivate', 'verb' => 'POST'],
		['name' => 'settings#getTokenSet', 'url' => '/settings/tokenset', 'verb' => 'GET'],
		['name' => 'settings#getThemingPlan', 'url' => '/settings/theming-plan', 'verb' => 'GET'],
		['name' => 'settings#rollbackTokenSet', 'url' => '/settings/rollback', 'verb' => 'POST'],
		['name' => 'settings#getProfileHistory', 'url' => '/settings/profile-history', 'verb' => 'GET'],
		['name' => 'profileLibrary#getProfiles', 'url' => '/api/v1/profiles', 'verb' => 'GET'],
		['name' => 'profileLibrary#installProfile', 'url' => '/api/v1/profiles/install', 'verb' => 'POST'],
		['name' => 'profileLibrary#uninstallProfile', 'url' => '/api/v1/profiles/uninstall', 'verb' => 'POST'],
		[
			'name'         => 'stylesheet#getProfile',
			'url'          => '/styles/profiles/{profileId}/{profileVersion}/{contentHash}',
			'verb'         => 'GET',
			'requirements' => [
				'profileId'      => '[a-z0-9]+(?:-[a-z0-9]+)*',
				'profileVersion' => '(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)'.
					'(?:-(?:(?!0\d+(?:\.|$))[0-9A-Za-z-]+)'.
					'(?:\.(?:(?!0\d+(?:\.|$))[0-9A-Za-z-]+))*)?',
				'contentHash'    => '[a-f0-9]{64}',
			],
		],
	],
];
