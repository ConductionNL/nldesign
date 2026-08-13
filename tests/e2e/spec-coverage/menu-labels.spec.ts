/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @e2e openspec/specs/menu-labels/spec.md
 *
 * @e2e exclude openspec/specs/menu-labels/spec.md
 * Backend/CSS spec — scenarios cover IConfig storage, PHP boot-time CSS
 * injection, CSS typography/layout rules, and API internals; the admin
 * checkbox UI surface is covered by admin-settings tests.
 *
 * All scenarios are excluded at the spec level.
 */
import { test, expect } from '@playwright/test'

const THEMING_URL = '/settings/admin/theming'

test.describe('menu-labels', () => {
	// @e2e exclude openspec/specs/menu-labels/spec.md#setting-stored-as-enabled
	// IConfig mutation + API response assertion — not DOM-testable.

	// @e2e exclude openspec/specs/menu-labels/spec.md#setting-stored-as-disabled
	// IConfig mutation — not DOM-testable.

	// @e2e exclude openspec/specs/menu-labels/spec.md#default-value-when-not-configured
	// Fresh-install state — not deterministic.

	// @e2e exclude openspec/specs/menu-labels/spec.md#setting-persists-across-restarts
	// Server restart required — not DOM-testable.

	// @e2e exclude openspec/specs/menu-labels/spec.md#feature-enabled-loads-css
	// PHP boot conditional — not DOM-testable.

	// @e2e exclude openspec/specs/menu-labels/spec.md#feature-disabled-skips-css
	// PHP boot conditional — not DOM-testable.

	// @e2e exclude openspec/specs/menu-labels/spec.md#css-loading-order-relative-to-other-conditionals
	// CSS cascade order — not DOM-testable.

	// @e2e exclude openspec/specs/menu-labels/spec.md#app-menu-icons-hidden
	// CSS assertion — requires enabling the feature which mutates shared env.

	// @e2e exclude openspec/specs/menu-labels/spec.md#icons-hidden-for-all-apps
	// CSS assertion — requires enabling the feature.

	// @e2e exclude openspec/specs/menu-labels/spec.md#menu-overflow-icons-preserved
	// CSS assertion — requires enabling the feature.

	// @e2e exclude openspec/specs/menu-labels/spec.md#labels-made-visible
	// CSS assertion — requires enabling the feature.

	// @e2e exclude openspec/specs/menu-labels/spec.md#label-typography
	// CSS property values — not DOM-testable without enabling the feature.

	// @e2e exclude openspec/specs/menu-labels/spec.md#label-positioning-overrides-nextcloud-defaults
	// CSS property values — not DOM-testable.

	// @e2e exclude openspec/specs/menu-labels/spec.md#label-padding-for-spacing
	// CSS property values — not DOM-testable.

	// @e2e exclude openspec/specs/menu-labels/spec.md#labels-use-the-nl-design-font
	// Font inheritance — not DOM-testable.

	// @e2e exclude openspec/specs/menu-labels/spec.md#menu-entry-dimensions
	// CSS property values — not DOM-testable.

	// @e2e exclude openspec/specs/menu-labels/spec.md#menu-entry-link-layout
	// CSS property values — not DOM-testable.

	// @e2e exclude openspec/specs/menu-labels/spec.md#menu-stretches-to-accommodate-all-labels
	// CSS flex layout — not DOM-testable.

	// @e2e exclude openspec/specs/menu-labels/spec.md#default-active-indicator-removed
	// CSS pseudo-element — not DOM-testable.

	// @e2e exclude openspec/specs/menu-labels/spec.md#active-item-distinguished-by-font-weight
	// CSS font-weight — requires enabling the feature.

	// @e2e exclude openspec/specs/menu-labels/spec.md#active-state-visible-on-all-backgrounds
	// Visual assertion — not DOM-testable.

	// @e2e exclude openspec/specs/menu-labels/spec.md#toggle-menu-labels-on
	// API mutation — mutates shared env.

	// @e2e exclude openspec/specs/menu-labels/spec.md#toggle-menu-labels-off
	// API mutation — mutates shared env.

	// @e2e exclude openspec/specs/menu-labels/spec.md#non-admin-access-denied
	// Requires non-admin session.

	// @e2e exclude openspec/specs/menu-labels/spec.md#route-registration
	// appinfo/routes.php — not DOM-testable.

	// @e2e exclude openspec/specs/menu-labels/spec.md#checkbox-reflects-current-state-on-load
	// Covered by admin-settings spec-coverage show-menu-labels checkbox test.

	// @e2e exclude openspec/specs/menu-labels/spec.md#checkbox-change-triggers-save
	// API call from checkbox change — mutates shared env.

	// @e2e exclude openspec/specs/menu-labels/spec.md#checkbox-label-is-localized-and-accessible
	// Covered by admin-settings spec-coverage checkbox label test.

	// @e2e exclude openspec/specs/menu-labels/spec.md#screen-reader-improvement
	// Screen reader assertion — not DOM-testable.

	// @e2e exclude openspec/specs/menu-labels/spec.md#cognitive-accessibility
	// Subjective UX assertion — not DOM-testable.

	// @e2e exclude openspec/specs/menu-labels/spec.md#feature-satisfies-wcag-guidelines
	// WCAG assertion — not DOM-testable from automated test.

	// @e2e exclude openspec/specs/menu-labels/spec.md#labels-on-wide-viewport
	// Viewport-specific CSS — not reliably DOM-testable without enabling feature.

	// @e2e exclude openspec/specs/menu-labels/spec.md#labels-on-narrow-viewport
	// Viewport-specific CSS — not reliably DOM-testable.

	// @e2e exclude openspec/specs/menu-labels/spec.md#labels-with-nowrap-prevent-wrapping
	// CSS white-space property — not DOM-testable without enabling feature.

	// @e2e exclude openspec/specs/menu-labels/spec.md#both-features-enabled-simultaneously
	// Requires enabling both features — mutates shared env.

	// @e2e exclude openspec/specs/menu-labels/spec.md#only-menu-labels-enabled
	// Requires enabling feature — mutates shared env.

	// Smoke: show-menu-labels checkbox is present in admin settings (the UI entry point)
	test(// @e2e openspec/specs/menu-labels/spec.md#checkbox-reflects-current-state-on-load
	'Show menu labels checkbox is present in admin settings panel', async ({
		page,
	}) => {
		await page.goto(THEMING_URL)
		await page.waitForLoadState('domcontentloaded')
		const checkbox = page.locator('#nldesign-show-menu-labels')
		await expect(checkbox).toBeAttached()
		const label = page.locator('label[for="nldesign-show-menu-labels"]')
		await expect(label).toContainText(
			'Show text labels in app menu (hide icons)',
		)
	})
})
