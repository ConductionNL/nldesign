/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * La Suite radius scale — container vs control, in a real browser.
 *
 * WHY THIS EXISTS, AND WHY IT NEEDS A BROWSER.
 *
 * `--lasuite-border-radius` (4px) is the one literal in the token set, and it
 * was observed on Cunningham's BUTTON and INPUT — a CONTROL radius. The bridge
 * also mapped it onto `--border-radius-large`, Nextcloud's CONTAINER radius
 * (cards, widget surfaces; NC default 8px), collapsing the radius hierarchy
 * onto one value. On a card drawn with a 1px gray-100 hairline the resulting
 * corner arcs span roughly two antialiased pixels and read as missing.
 *
 * The unit spec (tests/vitest/lasuiteBridgeRadiusScale.spec.js) asserts the
 * bridge's SOURCE — that the container token is not force-mapped. That is a
 * text assertion about a stylesheet. It cannot tell you what a browser
 * actually computes once the cascade, the `!important`s and the
 * `body[data-themes]` selector have all had their say — which is exactly where
 * the original bug lived, and exactly what a first attempt at measuring it got
 * wrong (a plain `body` override loses to `body[data-themes]`, and silently
 * reports "the fix does nothing").
 *
 * So this loads the REAL bridge.css into a real Chromium and reads
 * getComputedStyle. No Nextcloud instance: the stylesheet plus a synthetic DOM
 * is the whole system under test, which also means it cannot be broken by
 * whatever happens to be deployed on a shared dev instance.
 */

import { test, expect } from '@playwright/test'
import { readFileSync } from 'fs'
import { resolve } from 'path'

const root = resolve(__dirname, '../../..')

const read = (p: string) => readFileSync(resolve(root, p), 'utf8')

/**
 * A page carrying La Suite's own token definitions plus the NC-facing bridge,
 * with `data-themes` on <body> exactly as the deployed shell sets it — the
 * attribute the bridge's own selector keys on.
 */
async function mountBridge(page: import('@playwright/test').Page) {
	const defaults = read('css/systems/lasuite/defaults.css')
	const bridge = read('css/systems/lasuite/bridge.css')
	const elementOverrides = read('css/systems/lasuite/element-overrides.css')

	await page.setContent(`<!doctype html>
<html><head><style>
:root {
  /* Nextcloud's own container/control radii, as core ships them. The bridge
     either leaves these alone or overrides them; that is the thing under test. */
  --border-radius: 4px;
  --border-radius-small: 2px;
  --border-radius-large: 8px;
  --border-radius-element: 8px;
  --color-border: #ededed;
  --color-main-background: #fff;
}
${defaults}
${bridge}
${elementOverrides}
.card { border: 1px solid var(--color-border); border-radius: var(--border-radius-large); }
</style></head>
<body data-themes="lasuite">
  <div class="card" id="card">card</div>
  <button id="btn">button</button>
  <input id="txt" type="text" />
  <div class="modal-container" id="modal">modal</div>
</body></html>`)
}

const radiusOf = (page: import('@playwright/test').Page, sel: string) =>
	page.locator(sel).evaluate((el) => getComputedStyle(el).borderTopLeftRadius)

test.describe('La Suite radius scale', () => {
	test.beforeEach(async ({ page }) => {
		await mountBridge(page)
	})

	test("a CONTAINER keeps Nextcloud's 8px radius", async ({ page }) => {
		// The regression this guards: 4px here means the container token was
		// force-mapped onto the control radius again, and card corners vanish.
		expect(await radiusOf(page, '#card')).toBe('8px')
	})

	test("CONTROLS still carry La Suite's 4px radius", async ({ page }) => {
		// The other half. If unmapping the container token had also loosened the
		// control radius, the app would stop looking like La Suite — so this is
		// the assertion that keeps the fix honest rather than merely green.
		expect(await radiusOf(page, '#btn')).toBe('4px')
		expect(await radiusOf(page, '#txt')).toBe('4px')
		expect(await radiusOf(page, '#modal')).toBe('4px')
	})

	test('the container and control radii are genuinely DIFFERENT', async ({
		page,
	}) => {
		// States the property directly, so a future change that collapses the
		// scale onto one value fails here even if both numbers happen to move
		// together. The whole defect was a flattened hierarchy, not a wrong
		// number.
		const card = await radiusOf(page, '#card')
		const button = await radiusOf(page, '#btn')
		expect(card).not.toBe(button)
	})

	test('the bridge still governs the theme (guard against a dead fixture)', async ({
		page,
	}) => {
		// If bridge.css failed to load, or `body[data-themes]` stopped matching,
		// every assertion above would pass for the wrong reason — the NC
		// defaults in :root alone would give 8px containers and the controls
		// would simply be unstyled. Prove the bridge is actually in force by
		// reading a token only it sets.
		const borderColour = await page
			.locator('body')
			.evaluate((el) =>
				getComputedStyle(el).getPropertyValue('--color-border').trim(),
			)
		expect(borderColour).not.toBe('#ededed')
		expect(borderColour).not.toBe('')
	})
})
