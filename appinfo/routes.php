<?php

declare(strict_types=1);

return [
	'routes' => [
		['name' => 'settings#setTokenSet', 'url' => '/settings/tokenset', 'verb' => 'POST'],
		['name' => 'settings#getTokenSet', 'url' => '/settings/tokenset', 'verb' => 'GET'],
		['name' => 'settings#setSloganSetting', 'url' => '/settings/slogan', 'verb' => 'POST'],
		['name' => 'settings#setMenuLabelsSetting', 'url' => '/settings/menulabels', 'verb' => 'POST'],
		['name' => 'settings#getOverrides', 'url' => '/settings/overrides', 'verb' => 'GET'],
		['name' => 'settings#setOverrides', 'url' => '/settings/overrides', 'verb' => 'POST'],
	],
];
