/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * DEEP, DATA-DEPENDENT workflow: token-override apply + persistence.
 *
 * Unlike the spec-coverage tests (which assert the editor *renders*), this
 * workflow proves the theming FEATURE end-to-end:
 *   - editing a token in the editor and clicking "Save overrides" actually
 *     writes the value to the backend (the generated custom-overrides.css and
 *     the GET /settings/overrides response now contain it), and
 *   - after a FRESH RELOAD the editor shows the persisted override (input value
 *     + custom badge) — i.e. the override survives a round-trip, and
 *   - resetting / clearing the override removes it from the backend.
 *
 * It also probes whether the saved override is REFLECTED in the live CSS
 * variable on the page (the "applies the theme" promise). See the fixme block —
 * this is a genuine product behaviour that does NOT currently work for editable
 * tokens under the active design system.
 *
 * GLOBAL STATE: this spec mutates css/custom-overrides.css (instance-wide
 * theming). The prior file content is snapshotted in beforeAll and RESTORED in
 * afterAll so the dev instance is never left re-themed.
 */
import { test, expect } from '@playwright/test'
import {
	openTheming,
	requestToken,
	getOverrides,
	setOverrides,
	getServedOverrideCss,
	liveCssVar,
} from './_helpers'

// A token we drive through the editor. --color-primary is a real, prominent
// theming token and is present in the editor's "login" tab.
const TEST_TOKEN = '--color-primary'
const TEST_VALUE = '#a1b2c3' // deliberate, easily-recognised, easily-reverted value

let baselineOverrides: Record<string, string> = {}

test.describe('workflow: token-apply persistence', () => {
	test.describe.configure({ mode: 'serial', timeout: 90_000 })

	test.beforeAll(async ({ browser }) => {
		// Snapshot the current persisted overrides so afterAll can restore them.
		const page = await browser.newPage()
		await openTheming(page)
		const token = await requestToken(page)
		baselineOverrides = await getOverrides(page, token)
		await page.close()
	})

	test.afterAll(async ({ browser }) => {
		// CRITICAL: restore the instance to its prior override state.
		const page = await browser.newPage()
		await openTheming(page)
		const token = await requestToken(page)
		await setOverrides(page, token, baselineOverrides)
		const restored = await getOverrides(page, token)
		expect(restored).toEqual(baselineOverrides)
		await page.close()
	})

	test('editing a token + Save overrides PERSISTS to the backend (file + GET)', async ({ page }) => {
		await openTheming(page)
		const token = await requestToken(page)

		// Drive the real editor UI: type the value into the --color-primary text field.
		const textField = page.locator(`.nldesign-color-text[data-token="${TEST_TOKEN}"]`)
		await expect(textField).toBeVisible()
		await textField.fill(TEST_VALUE)

		// The editor marks the row dirty (custom badge appears) — UI feedback.
		await expect(
			page.locator(`[data-token-row="${TEST_TOKEN}"] .nldesign-token-custom-badge`),
		).toHaveCount(1)
		await expect(page.locator('#nldesign-save-status')).toHaveText(/Unsaved changes/i)

		// Click the real Save overrides button.
		await page.locator('#nldesign-save-btn').click()
		// Dirty status clears on success.
		await expect(page.locator('#nldesign-save-status')).toHaveText('', { timeout: 10_000 })

		// (a) Backend write: GET /settings/overrides now returns the value.
		const persisted = await getOverrides(page, token)
		expect(persisted[TEST_TOKEN], 'override should be persisted in backend GET').toBe(TEST_VALUE)

		// (a') The generated custom-overrides.css file on disk contains the declaration.
		const css = await getServedOverrideCss(page)
		expect(css, 'custom-overrides.css should contain the saved declaration').toContain(`${TEST_TOKEN}: ${TEST_VALUE};`)
	})

	test('after a FRESH RELOAD the editor shows the persisted override', async ({ page }) => {
		// Fresh navigation — proves the value survives a round trip, not just in-memory state.
		await openTheming(page)

		const textField = page.locator(`.nldesign-color-text[data-token="${TEST_TOKEN}"]`)
		await expect(textField).toHaveValue(TEST_VALUE)

		// The custom badge is rendered for a persisted (non-default) value.
		await expect(
			page.locator(`[data-token-row="${TEST_TOKEN}"] .nldesign-token-custom-badge`),
		).toHaveCount(1)
	})

	/*
	 * BUG (flagged): the SAVED override is NOT reflected in the live CSS variable.
	 *
	 * The "applies the theme to the instance" promise: after saving an override
	 * for --color-primary and reloading, getComputedStyle(:root)['--color-primary']
	 * should equal the override value. It does NOT.
	 *
	 * Root cause (verified empirically + in source): every editable token is
	 * re-declared with `!important` by css/systems/nldesign/{theme,overrides,
	 * element-overrides}.css, which load when the active token set uses the
	 * nldesign design system (the default for all sets except "nextcloud").
	 * custom-overrides.css is loaded last but WITHOUT !important, so it loses the
	 * cascade. Even under the stock "nextcloud" set, Nextcloud's own theming
	 * stylesheet overrides --color-primary. Net effect: the token editor persists
	 * overrides that have NO visible effect on the running instance for core
	 * tokens. This is the core "applied theme not reflected" defect.
	 *
	 * Remove .fixme once custom-overrides.css emits !important (or the nldesign
	 * layer stops clobbering editable tokens) so saved overrides win the cascade.
	 */
	test.fixme('saved override is reflected in the LIVE CSS variable after reload', async ({ page }) => {
		await openTheming(page)
		const live = await liveCssVar(page, TEST_TOKEN)
		expect(live.toLowerCase()).toBe(TEST_VALUE.toLowerCase())
	})

	test('clearing the override REMOVES it from the backend (reset path)', async ({ page }) => {
		await openTheming(page)
		const token = await requestToken(page)

		// Sanity: the override is present before reset.
		const before = await getOverrides(page, token)
		expect(before[TEST_TOKEN]).toBe(TEST_VALUE)

		// Click the row's reset button (↺) to clear the custom value, then Save.
		await page.locator(`.nldesign-reset-btn[data-token="${TEST_TOKEN}"]`).click()
		await page.locator('#nldesign-save-btn').click()
		await expect(page.locator('#nldesign-save-status')).toHaveText('', { timeout: 10_000 })

		// Backend no longer carries the override.
		const after = await getOverrides(page, token)
		expect(after[TEST_TOKEN], 'override should be removed after reset+save').toBeUndefined()

		// The served file no longer contains the declaration.
		const css = await getServedOverrideCss(page)
		expect(css).not.toContain(`${TEST_TOKEN}: ${TEST_VALUE};`)
	})
})
