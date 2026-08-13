/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * La Suite bridge cascade — which colour actually WINS, in a real browser.
 *
 * WHY THIS EXISTS. The lasuite bundle is a five-file cascade
 * (fonts → defaults → brand-override → bridge → element-overrides) in which
 * the SAME token is declared twice on purpose: `defaults.css` is the published
 * BLUE Cunningham base, shared with the `cunningham` design system, and
 * `brand-override.css` redeclares 343 tokens to the deployed VIOLET values.
 * Every scenario below is a claim about which declaration survives.
 *
 * A grep cannot answer that. Both values are present in the sources by design,
 * so a text assertion is green whichever one wins — and "which one wins" is the
 * entire requirement. Only a cascade resolves it.
 *
 * NO NEXTCLOUD INSTANCE. The stylesheets plus a synthetic DOM are the whole
 * system under test, following the pattern established by
 * `lasuite-radius-scale.spec.ts`. That is not a convenience: it means these
 * tests cannot be reddened — or greened — by whatever happens to be deployed on
 * a shared dev instance. (Measured during authoring: the shared container
 * degraded to >300s on a plain `curl /settings/user`, which made every
 * instance-backed test in this repo unrunnable. These kept running.)
 *
 * EVERY fixture asserts a DEAD-FIXTURE GUARD first. If a stylesheet silently
 * failed to load, the `:root` fallbacks alone would satisfy several assertions
 * below for entirely the wrong reason.
 */

import { test, expect, type Page } from '@playwright/test'
import { readFileSync } from 'fs'
import { resolve } from 'path'

const root = resolve(__dirname, '../../..')
const read = (p: string): string => readFileSync(resolve(root, p), 'utf8')

/** Nextcloud's own values for the variables the bridge must NOT touch. */
const NC_SENTINELS = {
	'--color-main-background': '#fffffe',
	'--color-main-background-rgb': '255, 255, 254',
	'--color-main-background-translucent': 'rgba(255, 255, 254, 0.97)',
	'--color-background-plain': '#00679e',
	'--background-invert-if-dark': 'no',
	'--background-invert-if-bright': 'invert(100%)',
}

/**
 * Mount the lasuite cascade. `withOverride: false` reproduces the `cunningham`
 * bundle — the same files minus the violet layer — which is what the
 * blue-base scenarios are about.
 */
async function mountCascade(
	page: Page,
	opts: { withOverride: boolean },
): Promise<void> {
	const layers = [
		// fonts.css is first in the declared bundle order and is the ONLY file
		// that defines `--lasuite-font-family`. Omitting it left the fixture on
		// Times New Roman and made the font scenario fail for a fixture reason.
		read('css/systems/lasuite/fonts.css'),
		read('css/systems/lasuite/defaults.css'),
		...(opts.withOverride
			? [read('css/systems/lasuite/brand-override.css')]
			: []),
		read('css/systems/lasuite/bridge.css'),
		read('css/systems/lasuite/element-overrides.css'),
	]

	const sentinels = Object.entries(NC_SENTINELS)
		.map(([k, v]) => `${k}: ${v};`)
		.join('\n  ')

	await page.setContent(`<!doctype html>
<html><head><style>
:root {
  --border-radius: 4px;
  --border-radius-large: 8px;
  --border-radius-element: 8px;
  --color-border: #ededed;
  ${sentinels}
}
${layers.join('\n')}
.icon-material { font-family: "Material Design Icons"; }
code { font-family: monospace; }
</style></head>
<body data-themes="lasuite">
  <button id="btn" class="button-vue button-vue--vue-primary">primary</button>
  <input id="txt" type="text" />
  <span id="mdi" class="icon-material">&#xF0004;</span>
  <code id="code">x</code>
</body></html>`)
}

/** Resolve custom properties on <body> through the real cascade. */
async function tokens(page: Page, names: string[]): Promise<Record<string, string>> {
	return page.evaluate((list) => {
		const cs = getComputedStyle(document.body)
		const out: Record<string, string> = {}
		for (const n of list) {
			out[n] = cs.getPropertyValue(n).trim().toLowerCase()
		}
		return out
	}, names)
}

/**
 * DEAD-FIXTURE GUARD. Prove the bundle is actually in force by reading a token
 * only the bridge sets. Without this, a fixture whose <style> failed to parse
 * would still satisfy "the dark-compatibility sentinels survived".
 */
async function expectBundleInForce(page: Page): Promise<void> {
	const probe = await tokens(page, [
		'--nldesign-color-primary',
		'--lasuite-color-brand-650',
	])
	expect(
		probe['--nldesign-color-primary'],
		'dead-fixture guard: the bridge layer must be in force',
	).not.toBe('')
	expect(
		probe['--lasuite-color-brand-650'],
		'dead-fixture guard: the defaults layer must be in force',
	).not.toBe('')
}

/** sRGB relative luminance of a `#rrggbb` / `rgb(...)` colour. */
function luminance(value: string): number {
	const hex = value.match(/^#([0-9a-f]{6})$/i)
	const rgb = value.match(/rgba?\(\s*(\d+)[,\s]+(\d+)[,\s]+(\d+)/i)
	let r: number
	let g: number
	let b: number
	if (hex !== null) {
		r = parseInt(hex[1].slice(0, 2), 16)
		g = parseInt(hex[1].slice(2, 4), 16)
		b = parseInt(hex[1].slice(4, 6), 16)
	} else if (rgb !== null) {
		r = Number(rgb[1])
		g = Number(rgb[2])
		b = Number(rgb[3])
	} else {
		throw new Error(`unparseable colour: "${value}"`)
	}
	const lin = (c: number): number => {
		const s = c / 255
		return s <= 0.03928 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4
	}
	return 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b)
}

/** WCAG contrast ratio between two colours. */
function contrast(a: string, b: string): number {
	const la = luminance(a)
	const lb = luminance(b)
	return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05)
}

test.describe('lasuite cascade — the deployed violet bundle', () => {
	test.beforeEach(async ({ page }) => {
		await mountCascade(page, { withOverride: true })
		await expectBundleInForce(page)
	})

	// @e2e lasuite-stack::primary-maps-to-la-suite-brand
	// @e2e lasuite-stack::violet-override-wins-the-cascade-for-lasuite
	// @e2e lasuite-parity::deployed-cascade-resolves-to-violet
	test('--color-primary resolves to the violet brand-650, and brand-600 to the violet ramp', async ({
		page,
	}) => {
		const t = await tokens(page, [
			'--color-primary',
			'--nldesign-color-primary',
			'--lasuite--globals--colors--brand-600',
		])

		// The override beats the blue base for the RENDERED primary…
		expect(
			t['--color-primary'],
			'--color-primary must be the violet brand-650',
		).toBe('#4844ad')
		// …through the documented chain --color-primary <- --nldesign-color-primary
		// <- --lasuite-color-brand-650, so assert the intermediate too. If only
		// the endpoint were checked, a hardcoded literal in the bridge would pass.
		expect(t['--nldesign-color-primary']).toBe('#4844ad')
		// …and the raw generated token carries the violet ramp, NOT the blue base.
		expect(
			t['--lasuite--globals--colors--brand-600'],
			'brand-600 must be the violet #534fc2, not the blue base #0659c5',
		).toBe('#534fc2')
	})

	// @e2e lasuite-stack::primary-maps-to-la-suite-brand
	test('--color-primary-text keeps AA contrast against the brand', async ({
		page,
	}) => {
		const t = await tokens(page, ['--color-primary', '--color-primary-text'])
		const ratio = contrast(t['--color-primary'], t['--color-primary-text'])
		expect(
			ratio,
			`--color-primary-text (${t['--color-primary-text']}) on --color-primary `
				+ `(${t['--color-primary']}) must clear WCAG AA 4.5:1, got ${ratio.toFixed(2)}`,
		).toBeGreaterThanOrEqual(4.5)
	})

	// @e2e lasuite-stack::dark-compatibility-variables-untouched
	test('the bridge declares none of the dark-compatibility variables', async ({
		page,
	}) => {
		const t = await tokens(page, Object.keys(NC_SENTINELS))

		// Each sentinel is a value Nextcloud itself would set. If the bridge
		// declared any of them, the cascade would replace the sentinel and dark
		// mode would break (REQ-CSS-007). Reading them back unchanged is the
		// only way to show a NON-declaration; grepping for an absent line proves
		// nothing about what the cascade does.
		for (const [name, expected] of Object.entries(NC_SENTINELS)) {
			expect(
				t[name],
				`${name} must carry Nextcloud's own value, untouched`,
			).toBe(expected.toLowerCase())
		}
	})

	// @e2e lasuite-stack::icon-fonts-survive
	test('icon and monospace fonts survive the body-inherited font stack', async ({
		page,
	}) => {
		const fonts = await page.evaluate(() => ({
			body: getComputedStyle(document.body).fontFamily,
			mdi: getComputedStyle(document.getElementById('mdi') as Element)
				.fontFamily,
			code: getComputedStyle(document.getElementById('code') as Element)
				.fontFamily,
		}))

		// The body carries La Suite's stack by inheritance (ADR-CSS-001)…
		expect(fonts.body.toLowerCase()).toContain('inter')
		// …but a universal `!important` font rule would have overwritten these
		// two, which is the regression the ADR forbids.
		expect(fonts.mdi, 'the MDI glyph font must be preserved').toContain(
			'Material Design Icons',
		)
		expect(fonts.code, 'the monospace stack must be preserved').toContain(
			'monospace',
		)
	})

	// NOT ANCHORED, deliberately — see `lasuite-stack::controls-carry-la-suite-radii`.
	//
	// That scenario requires FOUR things of a primary button: brand background,
	// white text, 4px radius, and a darker brand-scale step on hover. The
	// existing `lasuite-radius-scale.spec.ts` covers the radius only, and this
	// fixture measures the button background as brand-550 (#5e5cd0), not the
	// brand-650 (#4844ad) that `--color-primary` resolves to — so "its
	// background MUST be the brand color" is not something this fixture can
	// honestly assert without first establishing WHICH ramp step the scenario
	// means. Anchoring on the radius alone would claim the whole scenario.
	//
	// Left as a gate-19 finding rather than annotated. The measurement is
	// recorded here so the next person starts from it instead of re-deriving it.
})

test.describe('lasuite cascade — the shared BLUE base, without the violet layer', () => {
	test.beforeEach(async ({ page }) => {
		// The `cunningham` bundle: the same files minus brand-override.css.
		await mountCascade(page, { withOverride: false })
		await expectBundleInForce(page)
	})

	// @e2e lasuite-parity::base-is-the-blue-cunningham-base-not-the-deployed-violet
	// @e2e lasuite-parity::blue-base-resolves-without-the-violet-override
	test('without the override the base is blue, and --color-primary is its brand-650', async ({
		page,
	}) => {
		const t = await tokens(page, [
			'--lasuite--globals--colors--brand-600',
			'--color-primary',
		])

		// defaults.css is the shared blue Cunningham base and must stay blue —
		// the `cunningham` design system loads it too.
		expect(
			t['--lasuite--globals--colors--brand-600'],
			'the generated base must be the published Cunningham blue',
		).toBe('#0659c5')

		// --color-primary derives from brand-650, a DIFFERENT scale step from
		// brand-600. Pinning both is what distinguishes "the blue base loaded"
		// from "the bridge happens to read the token I checked".
		expect(
			t['--color-primary'],
			"--color-primary must be the blue base's brand-650 #1a509f",
		).toBe('#1a509f')
	})

	// @e2e lasuite-parity::override-is-sourced-and-separate-not-generated
	test('the violet values live only in the override layer, not in the generated base', async () => {
		// Comments must be stripped before asserting. defaults.css's provenance
		// header NAMES the deployed violet values in prose ("brand-600 #534fc2")
		// precisely to document what it is not — so a raw substring search
		// reports the generated base hard-codes violet when it does no such
		// thing. Assert on DECLARATIONS.
		const stripComments = (css: string): string =>
			css.replace(/\/\*[\s\S]*?\*\//g, '')
		const declares = (css: string, token: string, value: string): boolean =>
			new RegExp(`${token}\\s*:\\s*${value}\\s*;`, 'i').test(
				stripComments(css),
			)

		const defaults = read('css/systems/lasuite/defaults.css')
		const override = read('css/systems/lasuite/brand-override.css')
		const brand600 = '--lasuite--globals--colors--brand-600'

		// Paired with the rendered assertion above: that one shows the base
		// RESOLVES blue, this one shows the violet literal is not declared in
		// the generated file at all — it would be a dead declaration there, and
		// it would make the file non-re-derivable by the generator.
		expect(
			declares(defaults, brand600, '#534fc2'),
			'the generated base must not declare the deployed violet brand-600',
		).toBe(false)
		expect(
			declares(defaults, brand600, '#0659c5'),
			'the generated base must declare the published Cunningham blue',
		).toBe(true)
		expect(
			declares(override, brand600, '#534fc2'),
			'the override layer is where the violet brand-600 belongs',
		).toBe(true)
	})
})
