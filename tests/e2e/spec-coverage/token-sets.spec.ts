/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @e2e openspec/specs/token-sets/spec.md
 *
 * @e2e exclude openspec/specs/token-sets/spec.md
 * Backend/filesystem/API spec — scenarios cover TokenSetService PHP logic,
 * manifest parsing, IConfig storage, path-traversal checks, and route
 * configuration; the admin dropdown UI surface is covered by admin-settings
 * tests.
 *
 * All scenarios are excluded at the spec level.
 */
import { test, expect } from '@playwright/test'

const THEMING_URL = '/settings/admin/theming'

test.describe('token-sets', () => {

	// @e2e exclude openspec/specs/token-sets/spec.md#token-sets-discovered-from-filesystem
	// PHP TokenSetService logic — not DOM-testable.

	// @e2e exclude openspec/specs/token-sets/spec.md#metadata-merged-from-manifest
	// PHP TokenSetService + JSON manifest — not DOM-testable.

	// @e2e exclude openspec/specs/token-sets/spec.md#css-file-exists-without-manifest-entry
	// PHP fallback naming — not DOM-testable.

	// @e2e exclude openspec/specs/token-sets/spec.md#manifest-entry-exists-without-css-file
	// PHP filesystem truth-of-source — not DOM-testable.

	// @e2e exclude openspec/specs/token-sets/spec.md#token-sets-sorted-alphabetically
	// PHP sort logic — not reliably DOM-testable without knowing all option names.

	// @e2e exclude openspec/specs/token-sets/spec.md#manifest-entry-with-full-metadata
	// JSON manifest structure — not DOM-testable.

	// @e2e exclude openspec/specs/token-sets/spec.md#manifest-is-malformed-json
	// PHP error handling — not DOM-testable.

	// @e2e exclude openspec/specs/token-sets/spec.md#manifest-is-missing
	// PHP fallback — not DOM-testable.

	// @e2e exclude openspec/specs/token-sets/spec.md#manifest-file-unreadable
	// PHP error path — not DOM-testable.

	// @e2e exclude openspec/specs/token-sets/spec.md#manifest-indexed-by-id
	// PHP manifest indexing — not DOM-testable.

	// @e2e exclude openspec/specs/token-sets/spec.md#no-token-set-configured-fresh-install
	// Fresh-install IConfig default — not deterministic in shared env.

	// @e2e exclude openspec/specs/token-sets/spec.md#token-set-persisted-via-api
	// API mutation (POST /settings/tokenset) — mutates shared env; covered by apply-dialog tests.

	// @e2e exclude openspec/specs/token-sets/spec.md#token-set-retrieved-via-api
	// API response assertion — not DOM-testable.

	// @e2e exclude openspec/specs/token-sets/spec.md#token-set-read-during-boot
	// PHP boot logic — not DOM-testable.

	// @e2e exclude openspec/specs/token-sets/spec.md#valid-token-set-selected
	// PHP validation — not DOM-testable.

	// @e2e exclude openspec/specs/token-sets/spec.md#invalid-token-set-rejected
	// API error response — not DOM-testable.

	// @e2e exclude openspec/specs/token-sets/spec.md#path-traversal-with-forward-slash-prevented
	// Security validation — not DOM-testable.

	// @e2e exclude openspec/specs/token-sets/spec.md#path-traversal-with-dot-dot-prevented
	// Security validation — not DOM-testable.

	// @e2e exclude openspec/specs/token-sets/spec.md#available-token-sets-api-endpoint
	// API response — not DOM-testable.

	// @e2e exclude openspec/specs/token-sets/spec.md#route-registration
	// appinfo/routes.php — not DOM-testable.

	// @e2e exclude openspec/specs/token-sets/spec.md#get-tokenset-route-registered
	// appinfo/routes.php — not DOM-testable.

	// @e2e exclude openspec/specs/token-sets/spec.md#available-tokensets-route-registered
	// appinfo/routes.php — not DOM-testable.

	// @e2e exclude openspec/specs/token-sets/spec.md#tokenset-preview-route-registered
	// appinfo/routes.php — not DOM-testable.

	// Smoke: token sets are loaded and the current selection is a valid token set ID
	test(
		// @e2e openspec/specs/token-sets/spec.md#token-set-persisted-via-api
		'Active token set is set and reflected in the dropdown selection',
		async ({ page }) => {
			await page.goto(THEMING_URL)
			await page.waitForLoadState('domcontentloaded')
			const select = page.locator('#nldesign-token-set-select')
			await expect(select).toBeVisible()
			const currentValue = await select.inputValue()
			expect(currentValue.trim().length).toBeGreaterThan(0)
			// The selected option must exist as a valid option in the select
			const selectedOption = select.locator(`option[value="${currentValue}"]`)
			await expect(selectedOption).toBeAttached()
		},
	)

})
