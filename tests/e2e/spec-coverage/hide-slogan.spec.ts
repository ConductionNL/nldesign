/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @e2e openspec/specs/hide-slogan/spec.md
 *
 * @e2e exclude openspec/specs/hide-slogan/spec.md
 * Backend/CSS/login-page spec — scenarios cover IConfig storage, PHP
 * boot-time CSS injection, CSS selector behaviour on the login page, and
 * API internals; the admin checkbox UI surface is covered by admin-settings
 * tests.
 *
 * All scenarios are excluded at the spec level.
 */
import { test, expect } from '@playwright/test'

const THEMING_URL = '/settings/admin/theming'

test.describe('hide-slogan', () => {

	// @e2e exclude openspec/specs/hide-slogan/spec.md#setting-stored-as-enabled
	// IConfig mutation + API response assertion — not DOM-testable.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#setting-stored-as-disabled
	// IConfig mutation — not DOM-testable.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#default-value-when-not-configured
	// Fresh-install state — not deterministic in shared env.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#setting-persists-across-app-restarts
	// Server restart required — not DOM-testable.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#feature-enabled-loads-css
	// PHP boot conditional — not DOM-testable.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#feature-disabled-skips-css
	// PHP boot conditional — not DOM-testable.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#css-loading-position-in-cascade
	// CSS cascade order — not DOM-testable.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#footer-element-hidden-with-display-none
	// Login page CSS — only visible on the login page, not admin session.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#multiple-selector-coverage-for-robustness
	// CSS selector coverage — not DOM-testable from admin session.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#slogan-visible-when-feature-disabled
	// Login page assertion — not observable from admin session.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#non-login-page-footers-unaffected
	// CSS scope assertion — not reliably testable from admin session.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#other-guest-box-elements-unaffected
	// CSS scope on login page — not testable from admin session.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#login-page-layout-preserved
	// Login page layout — not testable from admin session.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#true-boolean-converted-to-string-1
	// PHP boolean conversion — not DOM-testable.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#false-boolean-converted-to-string-0
	// PHP boolean conversion — not DOM-testable.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#boot-phase-reads-and-compares-correctly
	// PHP boot logic — not DOM-testable.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#toggle-slogan-hiding-on
	// API mutation (POST /settings/slogan) — mutates shared env state.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#toggle-slogan-hiding-off
	// API mutation — mutates shared env state.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#non-admin-access-denied
	// Requires non-admin session — test environment only has admin.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#route-registration
	// appinfo/routes.php — not DOM-testable.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#both-hiding-mechanisms-applied
	// CSS property values — not DOM-testable without enabling the feature.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#accessibility-tree-impact
	// Screen reader / display:none interaction — not DOM-testable.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#print-stylesheet-compatibility
	// Print media — not DOM-testable.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#checkbox-reflects-current-state-on-load
	// Covered by admin-settings spec — hide-slogan checkbox presence test.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#checkbox-change-triggers-save
	// API call from checkbox change — mutates shared env.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#checkbox-label-is-localized
	// Covered by admin-settings checkbox-label-text-and-accessibility test.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#rijkshuisstijl-login-page-compliance
	// Login page + specific token set — not testable from admin session.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#municipality-login-page-compliance
	// Login page + specific token set — not testable from admin session.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#feature-works-with-all-token-sets
	// Cross-token-set login page assertion — not testable from admin session.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#setting-change-not-immediate
	// Boot-time CSS injection timing — not DOM-testable without changing state.

	// @e2e exclude openspec/specs/hide-slogan/spec.md#admin-sees-effect-by-navigating-to-login-page
	// Requires enabling the setting first — would mutate shared env.

	// Smoke: hide-slogan checkbox is present in admin settings (the UI entry point for this feature)
	test(
		// @e2e openspec/specs/hide-slogan/spec.md#checkbox-reflects-current-state-on-load
		'Hide slogan checkbox is present in admin settings panel',
		async ({ page }) => {
			await page.goto(THEMING_URL)
			await page.waitForLoadState('networkidle')
			const checkbox = page.locator('#nldesign-hide-slogan')
			await expect(checkbox).toBeAttached()
			const label = page.locator('label[for="nldesign-hide-slogan"]')
			await expect(label).toContainText('Hide Nextcloud slogan/payoff on login page')
		},
	)

})
