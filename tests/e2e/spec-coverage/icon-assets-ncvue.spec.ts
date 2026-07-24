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

const ICON_BASE = '/custom_apps/nldesign/img/icons'
const LOGO_BASE = '/custom_apps/nldesign/img/logos'

// One representative icon per bundled pack (names are the public API).
const PACK_ICONS = [
	`${ICON_BASE}/rvo/rvo-aangifte-ondernemers.svg`,
	`${ICON_BASE}/open-gemeenten/og-afval.svg`,
	`${ICON_BASE}/den-haag/dh-arrows-arrow-left.svg`,
]

test.describe('icon assets sourced from nc-vue', () => {
	for (const path of PACK_ICONS) {
		test(`serves ${path.split('/').slice(-2).join('/')} as valid SVG`, async ({ request }) => {
			const res = await request.get(path)
			expect(res.status()).toBe(200)

			const body = await res.text()
			expect(body).toContain('<svg')
			expect(body).toContain('</svg>')
		})
	}

	test('a mapped legacy PascalCase name still resolves during the deprecation release', async ({ request }) => {
		const res = await request.get(`${ICON_BASE}/ArrowBackward.svg`)
		expect(res.status()).toBe(200)
		expect(await res.text()).toContain('<svg')
	})

	test('an unmapped Amsterdam-only icon name is gone (404), not silently served', async ({ request }) => {
		// `Airplane` existed only in the proprietary Amsterdam set and has no
		// alias mapping — it must 404 rather than resolve to unrelated artwork.
		const res = await request.get(`${ICON_BASE}/Airplane.svg`, { failOnStatusCode: false })
		expect(res.status()).toBe(404)
	})

	test('organisation logos remain served (they are huisstijl assets, not icons)', async ({ request }) => {
		const res = await request.get(`${LOGO_BASE}/rijkshuisstijl.svg`, { failOnStatusCode: false })
		expect(res.status()).toBe(200)
		expect(await res.text()).toContain('<svg')
	})
})
