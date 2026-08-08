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
	],
];
