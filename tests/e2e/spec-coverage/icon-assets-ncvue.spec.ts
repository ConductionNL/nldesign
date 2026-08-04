/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @e2e openspec/specs/icon-assets/spec.md
 *
 * Icons come from @conduction/nextcloud-vue, not the proprietary Amsterdam
 * set: the three EUPL/CC0 packs serve, mapped legacy names still resolve for
 * one deprecation release, and unmapped Amsterdam-only names are gone.
 */
import { test, expect } from '@playwright/test'

// The app's web root is resolved from the running instance, never hardcoded.
//
// These two constants used to read `/custom_apps/nldesign/img/...`, which is
// only where apps live on the docker dev image. CI checks the app out into
// `apps/nldesign`, so that URL matched nothing on disk, fell through the front
// controller into index.php, matched no route, and came back as an HTML 404 —
// and `expect(res.status()).toBe(200)` then reported "the icon is not served"
// when the icon was in fact present under a different prefix. All five icon
// assertions in this file failed that way on run 30889958278.
//
// `OC.filePath()` is Nextcloud's own resolver for exactly this and is correct
// under both layouts. It needs a browser page, so the bases are filled in by
// beforeAll and read inside the test bodies — the `for` loop below runs at
// collection time, before any hook, which is why the paths are relative here.
let ICON_BASE = ''
let LOGO_BASE = ''

// One representative icon per bundled pack (names are the public API).
const PACK_ICONS = [
	'rvo/rvo-aangifte-ondernemers.svg',
	'open-gemeenten/og-afval.svg',
	'den-haag/dh-arrows-arrow-left.svg',
]

test.describe('icon assets sourced from nc-vue', () => {
	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto('/settings/user')
		const bases = await page.evaluate(() => {
			const oc = (window as unknown as { OC?: { filePath?: (a: string, t: string, f: string) => string } }).OC
			if (oc === undefined || typeof oc.filePath !== 'function') {
				throw new Error('OC.filePath() is unavailable — cannot resolve the app web root.')
			}
			return { icons: oc.filePath('nldesign', 'img', 'icons'), logos: oc.filePath('nldesign', 'img', 'logos') }
		})
		ICON_BASE = bases.icons
		LOGO_BASE = bases.logos
		expect(ICON_BASE, 'icon base must resolve').toBeTruthy()
		expect(LOGO_BASE, 'logo base must resolve').toBeTruthy()
		await page.close()
	})

	for (const rel of PACK_ICONS) {
		test(`serves ${rel} as valid SVG`, async ({ request }) => {
			const res = await request.get(`${ICON_BASE}/${rel}`)
			expect(res.status(), `${ICON_BASE}/${rel}`).toBe(200)

			const body = await res.text()
			expect(body).toContain('<svg')
			expect(body).toContain('</svg>')
		})
	}

	test('a mapped legacy PascalCase name still resolves during the deprecation release', async ({ request }) => {
		const res = await request.get(`${ICON_BASE}/ArrowBackward.svg`)
		expect(res.status(), `${ICON_BASE}/ArrowBackward.svg`).toBe(200)
		expect(await res.text()).toContain('<svg')
	})

	test('an unmapped Amsterdam-only icon name is gone (404), not silently served', async ({ request }) => {
		// `Airplane` existed only in the proprietary Amsterdam set and has no
		// alias mapping — it must 404 rather than resolve to unrelated artwork.
		//
		// NB this is the one assertion in the file that the wrong base URL made
		// PASS, for the wrong reason: everything 404s under a prefix that does
		// not exist. It only means anything now that the assertions above prove
		// the same base does serve.
		const res = await request.get(`${ICON_BASE}/Airplane.svg`, { failOnStatusCode: false })
		expect(res.status()).toBe(404)
	})

	test('organisation logos remain served (they are huisstijl assets, not icons)', async ({ request }) => {
		const res = await request.get(`${LOGO_BASE}/rijkshuisstijl.svg`, { failOnStatusCode: false })
		expect(res.status(), `${LOGO_BASE}/rijkshuisstijl.svg`).toBe(200)
		expect(await res.text()).toContain('<svg')
	})
})
