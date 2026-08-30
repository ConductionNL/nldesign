/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @e2e openspec/specs/extended-token-sets/spec.md
 *
 * @e2e exclude openspec/specs/extended-token-sets/spec.md
 * Token generation / filesystem / backend spec — scenarios describe
 * auto-generation scripts, nightly sync, manifest updates, and filesystem
 * discovery; the admin dropdown rendering is covered by admin-settings tests.
 *
 * All scenarios are excluded at the spec level.
 */
import { test, expect } from '@playwright/test'

const THEMING_URL = '/settings/admin/theming'

test.describe('extended-token-sets', () => {
	// @e2e exclude openspec/specs/extended-token-sets/spec.md#organization-with-complete-token-set
	// Requires selecting a specific token set and verifying CSS — mutates IConfig.

	// @e2e exclude openspec/specs/extended-token-sets/spec.md#organization-with-incomplete-token-set
	// Requires selecting a specific token set — mutates IConfig.

	// @e2e exclude openspec/specs/extended-token-sets/spec.md#new-organization-added-upstream
	// GitHub Actions / nightly sync workflow — not DOM-testable.

	// @e2e exclude openspec/specs/extended-token-sets/spec.md#token-generation-from-json
	// Script execution — not DOM-testable.

	// @e2e exclude openspec/specs/extended-token-sets/spec.md#token-naming-conversion
	// Script output assertion — not DOM-testable.

	// @e2e exclude openspec/specs/extended-token-sets/spec.md#organization-specific-palette-preservation
	// Script output assertion — not DOM-testable.

	// @e2e exclude openspec/specs/extended-token-sets/spec.md#admin-views-token-set-dropdown
	// Covered by admin-settings spec-coverage dropdown test.

	// @e2e exclude openspec/specs/extended-token-sets/spec.md#manifest-is-auto-updated
	// Script execution — not DOM-testable.

	// @e2e exclude openspec/specs/extended-token-sets/spec.md#token-set-validation
	// PHP controller validation — API-layer, not DOM-testable.

	// @e2e exclude openspec/specs/extended-token-sets/spec.md#available-token-sets-api
	// API response assertion — not DOM-testable.

	// @e2e exclude openspec/specs/extended-token-sets/spec.md#settings-page-shows-all-token-sets
	// Covered by admin-settings spec-coverage dropdown test (options > 5).

	// Smoke: dropdown has many options (verifies extended token set discovery is working)
	test(// @e2e openspec/specs/extended-token-sets/spec.md#settings-page-shows-all-token-sets
	'Token set dropdown lists many options (extended token set discovery works)', async ({
		page,
	}) => {
		await page.goto(THEMING_URL)
		await page.waitForLoadState('domcontentloaded')
		const select = page.locator('#nldesign-token-set-select')
		await expect(select).toBeVisible()
		// Should have many options reflecting extended token set support
		const count = await select.locator('option').count()
		expect(count).toBeGreaterThan(10)
	})
})
