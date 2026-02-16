<?php

declare(strict_types=1);

return [
	'routes' => [
		['name' => 'settings#getAvailableTokenSets', 'url' => '/settings/tokensets', 'verb' => 'GET'],
		['name' => 'settings#setTokenSet', 'url' => '/settings/tokenset', 'verb' => 'POST'],
		['name' => 'settings#getTokenSet', 'url' => '/settings/tokenset', 'verb' => 'GET'],
		['name' => 'settings#setSloganSetting', 'url' => '/settings/slogan', 'verb' => 'POST'],
		['name' => 'settings#setMenuLabelsSetting', 'url' => '/settings/menulabels', 'verb' => 'POST'],
		['name' => 'settings#getThemingValues', 'url' => '/settings/theming', 'verb' => 'GET'],
		['name' => 'settings#updateThemingValues', 'url' => '/settings/theming', 'verb' => 'POST'],
	],
];
