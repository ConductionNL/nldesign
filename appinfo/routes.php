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
		// Custom token overrides CRUD.
		['name' => 'settings#getOverrides', 'url' => '/settings/overrides', 'verb' => 'GET'],
		['name' => 'settings#setOverrides', 'url' => '/settings/overrides', 'verb' => 'POST'],
		// Import/export.
		['name' => 'settings#exportOverrides', 'url' => '/settings/overrides/export', 'verb' => 'GET'],
		['name' => 'settings#importOverrides', 'url' => '/settings/overrides/import', 'verb' => 'POST'],
		// Token set preview for apply dialog.
		['name' => 'settings#getTokenSetPreview', 'url' => '/settings/tokenset-preview/{tokenSetId}', 'verb' => 'GET'],
	],
];
