<?php

declare(strict_types=1);

return [
	'routes' => [
		// Metrics — served by nldesign's own MetricsController (domain theme
		// metrics: token sets, custom overrides, theming syncs). Admin-only:
		// no #[PublicPage]/#[NoAdminRequired], so the SecurityMiddleware
		// default (admin session required) applies. A Prometheus scraper
		// must authenticate as an admin (e.g. an app password).
		['name' => 'metrics#index', 'url' => '/api/metrics', 'verb' => 'GET'],
		// Health — the `health#index` route name and `/api/health` URL are
		// unchanged, but the leaf HealthController service name is aliased to
		// the OpenRegister AppHost engine's GenericHealthController in
		// Application::register() (ADR-040). The engine reads the
		// observability.health block of src/manifest.json and owns the auth
		// posture (#[PublicPage] + #[NoCSRFRequired]) and the
		// {status, app, version, checks} contract.
		['name' => 'health#index', 'url' => '/api/health', 'verb' => 'GET'],

		['name' => 'settings#getAvailableTokenSets', 'url' => '/settings/tokensets', 'verb' => 'GET'],
		['name' => 'settings#setTokenSet', 'url' => '/settings/tokenset', 'verb' => 'POST'],
		['name' => 'settings#getTokenSet', 'url' => '/settings/tokenset', 'verb' => 'GET'],
		['name' => 'settings#setSloganSetting', 'url' => '/settings/slogan', 'verb' => 'POST'],
		['name' => 'settings#setMenuLabelsSetting', 'url' => '/settings/menulabels', 'verb' => 'POST'],
		['name' => 'settings#getThemingValues', 'url' => '/settings/theming', 'verb' => 'GET'],
		['name' => 'settings#updateThemingValues', 'url' => '/settings/theming', 'verb' => 'POST'],
		// Per-app theming exclusion list.
		['name' => 'settings#getAppTheming', 'url' => '/settings/app-theming', 'verb' => 'GET'],
		['name' => 'settings#setAppTheming', 'url' => '/settings/app-theming', 'verb' => 'POST'],
		// Upstream token freshness (opt-in daily background job status + dismissal).
		['name' => 'settings#getUpstreamFreshness', 'url' => '/settings/upstream-freshness', 'verb' => 'GET'],
		['name' => 'settings#setUpstreamFreshness', 'url' => '/settings/upstream-freshness', 'verb' => 'POST'],
		['name' => 'settings#dismissUpstreamNotice', 'url' => '/settings/upstream-freshness/dismiss', 'verb' => 'POST'],
		['name' => 'overrides#getOverrides', 'url' => '/settings/overrides', 'verb' => 'GET'],
		['name' => 'overrides#setOverrides', 'url' => '/settings/overrides', 'verb' => 'POST'],
		// Import/export.
		['name' => 'overrides#exportOverrides', 'url' => '/settings/overrides/export', 'verb' => 'GET'],
		['name' => 'overrides#importOverrides', 'url' => '/settings/overrides/import', 'verb' => 'POST'],
		// Token set preview for apply dialog.
		['name' => 'settings#getTokenSetPreview', 'url' => '/settings/tokenset-preview/{tokenSetId}', 'verb' => 'GET'],
		// Custom token set upload lifecycle.
		['name' => 'customTokenSet#upload', 'url' => '/settings/tokensets/upload', 'verb' => 'POST'],
		['name' => 'customTokenSet#list', 'url' => '/settings/tokensets/custom', 'verb' => 'GET'],
		['name' => 'customTokenSet#export', 'url' => '/settings/tokensets/custom/{id}/export', 'verb' => 'GET'],
		['name' => 'customTokenSet#delete', 'url' => '/settings/tokensets/custom/{id}', 'verb' => 'DELETE'],
		// Active-configuration WCAG contrast compliance evidence report (download).
		['name' => 'settings#complianceReport', 'url' => '/settings/compliance-report', 'verb' => 'GET'],
		// Custom font upload lifecycle (admin-only).
		['name' => 'font#upload', 'url' => '/settings/fonts/upload', 'verb' => 'POST'],
		['name' => 'font#list', 'url' => '/settings/fonts', 'verb' => 'GET'],
		['name' => 'font#delete', 'url' => '/settings/fonts/{id}', 'verb' => 'DELETE'],
		// Font serving — deliberately public (#[PublicPage] + #[NoCSRFRequired]
		// on FontController::serve()/css()): CSS `url()` font loads carry no
		// CSRF token and must also work on the pre-login page before any
		// session exists.
		['name' => 'font#serve', 'url' => '/fonts/{id}.woff2', 'verb' => 'GET'],
		['name' => 'font#css', 'url' => '/fonts/css', 'verb' => 'GET'],
		// Theming audit trail — admin-only (AuthorizedAdminSetting), no
		// #[PublicPage]/#[NoAdminRequired].
		['name' => 'audit#list', 'url' => '/settings/audit', 'verb' => 'GET'],
		['name' => 'audit#export', 'url' => '/settings/audit/export', 'verb' => 'GET'],
		// Email template theming — admin toggle + compliance footer config.
		['name' => 'settings#getEmailTheming', 'url' => '/settings/email-theming', 'verb' => 'GET'],
		['name' => 'settings#setEmailTheming', 'url' => '/settings/email-theming', 'verb' => 'POST'],
	],
];
