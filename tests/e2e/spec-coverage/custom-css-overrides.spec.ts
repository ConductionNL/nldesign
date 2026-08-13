/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @e2e openspec/specs/custom-css-overrides/spec.md
 *
 * @e2e exclude openspec/specs/custom-css-overrides/spec.md
 * CSS file persistence / backend spec — scenarios cover file write semantics,
 * CSS cascade order, PHP endpoint internals, and filesystem state; no distinct
 * UI surface beyond the token editor already covered by admin-settings and
 * token-editor-ui tests.
 *
 * All scenarios are excluded at the spec level.
 */
import { test, expect } from '@playwright/test'

const THEMING_URL = '/settings/admin/theming'

test.describe('custom-css-overrides', () => {
	// @e2e exclude openspec/specs/custom-css-overrides/spec.md#file-does-not-exist-on-fresh-install
	// Filesystem/fresh-install state — not DOM-testable.

	// @e2e exclude openspec/specs/custom-css-overrides/spec.md#file-exists-with-custom-tokens
	// Requires custom-overrides.css to contain known tokens — environment state not guaranteed.

	// @e2e exclude openspec/specs/custom-css-overrides/spec.md#custom-override-wins-over-nl-design-token-set
	// CSS cascade priority — requires mutating custom-overrides.css to test.

	// @e2e exclude openspec/specs/custom-css-overrides/spec.md#missing-file-does-not-break-stack
	// Filesystem state + CSS stack loading — not DOM-testable.

	// @e2e exclude openspec/specs/custom-css-overrides/spec.md#file-is-written-by-the-save-endpoint
	// File write assertion — requires clicking Save and reading filesystem.

	// @e2e exclude openspec/specs/custom-css-overrides/spec.md#no-custom-tokens-results-in-empty-overrides-block
	// File content assertion — not DOM-testable.

	// @e2e exclude openspec/specs/custom-css-overrides/spec.md#read-current-overrides
	// API response assertion (GET /api/overrides) — not UI.

	// @e2e exclude openspec/specs/custom-css-overrides/spec.md#write-new-overrides
	// API mutation (POST /api/overrides) — mutates shared env state.

	// @e2e exclude openspec/specs/custom-css-overrides/spec.md#write-fails-due-to-filesystem-permissions
	// Server error path — not DOM-testable without breaking filesystem permissions.

	// @e2e exclude openspec/specs/custom-css-overrides/spec.md#token-overrides-survive-app-reinstall-if-css-directory-is-preserved
	// App lifecycle + filesystem state — not DOM-testable.

	// @e2e exclude openspec/specs/custom-css-overrides/spec.md#resetting-to-defaults-requires-only-file-deletion
	// Filesystem state reset — not DOM-testable.

	// Smoke: token editor Save button is present (the UI entry point for custom-overrides.css writes)
	test(// @e2e openspec/specs/custom-css-overrides/spec.md#file-is-written-by-the-save-endpoint
	'Token editor Save overrides button is present (custom-overrides.css write entry point)', async ({
		page,
	}) => {
		await page.goto(THEMING_URL)
		await page.waitForLoadState('domcontentloaded')
		const saveBtn = page.locator('button:has-text("Save overrides")')
		await expect(saveBtn).toBeVisible()
	})
})
