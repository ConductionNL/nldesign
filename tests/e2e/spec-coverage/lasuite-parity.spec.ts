/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @e2e openspec/specs/lasuite-parity/spec.md
 *
 * Component-parity check for the `lasuite` and `cunningham` design systems:
 * renders real Nextcloud elements under each active set and asserts their
 * COMPUTED styles equal the Cunningham reference values sourced directly
 * from css/systems/lasuite/{defaults,brand-override,bridge,element-
 * overrides}.css — i.e. "does the CSS this app ships actually apply as
 * authored", not an independently-invented pixel spec.
 *
 * SCOPE HONESTY: nldesign's own admin panel is vanilla JS (no Vue), and its
 * element-overrides.css styles only a handful of real NC selectors
 * concretely (button.primary, input[type=text], #header, .modal-container,
 * plus the shared body font-family rule). For each of the five elements
 * below, the reference table only lists the properties this app's CSS
 * ACTUALLY sets for that selector — asserting an un-sourced property (e.g.
 * a button's padding, which nldesign never declares and NC's own Cn* button
 * component owns) would test Nextcloud/Vue-library internals unrelated to
 * this app's own claims, and would be brittle across NC versions.
 *
 * GLOBAL STATE: activating `lasuite`/`cunningham` mutates the INSTANCE-WIDE
 * active token set (same class of mutation as tests/e2e/workflows/*). The
 * prior token set is snapshotted in beforeAll and RESTORED in afterAll.
 *
 * LOAD-FRAGILE INSTANCE (issue #181): runs `mode: 'serial'`, one `test()`
 * per element, small and batchable — a flake isolates to one element, not
 * the whole suite.
 */
import { test, expect, Page } from '@playwright/test'
import { requestToken, getTokenSet, setTokenSet } from '../workflows/_helpers'

const THEMING_URL = '/settings/admin/theming'

type StyleRef = Partial<{
	backgroundColor: string // hex, e.g. '#4844ad'
	color: string // hex
	borderColor: string // hex
	borderWidth: string // e.g. '1px'
	borderStyle: string // e.g. 'solid'
	borderRadius: string // e.g. '4px'
	fontFamily: string[] // lower-cased family list, e.g. ['marianne', 'inter', 'sans-serif']
	fontWeight: string // e.g. '600'
}>

interface ElementRef {
	/** Human-readable element name for assertion messages. */
	name: string
	/** CSS selector for the element under test (must exist on THEMING_URL). */
	selector: string
	ref: StyleRef
}

// Reference values sourced from css/systems/lasuite/{defaults,brand-override,
// bridge,element-overrides}.css. brand-override.css redeclares La Suite's FULL
// deployed colour system — not just the brand ramp but the violet-TINTED
// gray/white/black neutrals and its vivid semantic palettes (generated from the
// deployed build; see scripts/generate-lasuite-tokens.mjs --override). So the
// grays differ per set: the lasuite bundle loads brand-override.css (violet-
// tinted), the cunningham bundle does not (neutral npm base). White (gray-000)
// is #ffffff in both.
// Structural hairlines (header rule, sidebar edge) are gray-100 — the divider
// step measured on the live La Suite app (2026-07-27); the theme previously
// used the darker gray-200.
const GRAY_100: Record<'lasuite' | 'cunningham', string> = {
	lasuite: '#e2e2ea', // brand-override.css — deployed violet-tinted (hairline borders)
	cunningham: '#e1e2e5', // defaults.css neutral npm base
}
const GRAY_300: Record<'lasuite' | 'cunningham', string> = {
	lasuite: '#a9a9bf', // brand-override.css — deployed violet-tinted (input borders)
	cunningham: '#a7acb2', // defaults.css neutral npm base
}
const GRAY_000 = '#ffffff' // --lasuite-color-gray-000 (white — identical in both sets)
const BORDER_RADIUS = '4px' // --lasuite-border-radius (literal — no upstream token)
const FONT_STACK = ['marianne', 'inter', 'sans-serif'] // --lasuite-font-family

const BRAND_650: Record<'lasuite' | 'cunningham', string> = {
	lasuite: '#4844ad', // brand-override.css — deployed violet (logo / button hover)
	cunningham: '#1a509f', // defaults.css blue base (brand-override.css not loaded)
}

// La Suite's .c__button--brand--primary fills with brand-550 at REST and
// brand-650 on HOVER (verified 2026-07-26 against the shipped Cunningham CSS via
// a local computed-style comparison). Filling brand-650 at rest previously made
// our button-at-rest match La Suite's HOVER — one step too dark.
const BRAND_550: Record<'lasuite' | 'cunningham', string> = {
	lasuite: '#5e5cd0', // brand-override.css — deployed violet (primary button rest)
	cunningham: '#1167d4', // defaults.css blue base
}
// On-brand button text is brand-050 (a pale violet-white), not pure white.
const BRAND_050: Record<'lasuite' | 'cunningham', string> = {
	lasuite: '#eef1fa', // brand-override.css — deployed violet
	cunningham: '#eaf1fb', // defaults.css blue base
}

function referenceTable(set: 'lasuite' | 'cunningham'): ElementRef[] {
	const brand650 = BRAND_650[set]
	const brand550 = BRAND_550[set]
	const brand050 = BRAND_050[set]
	const gray100 = GRAY_100[set]
	const gray300 = GRAY_300[set]
	return [
		{
			name: 'primary button',
			// #nldesign-group-theming-save ships class="button primary" in
			// templates/settings/admin.php, matching element-overrides.css's
			// `button[class*="primary"]` / `button.primary` rules. Fill and
			// border are brand-550 at REST (brand-650 on hover); text is brand-050.
			selector: '#nldesign-group-theming-save',
			ref: {
				backgroundColor: brand550,
				color: brand050,
				borderColor: brand550,
				borderRadius: BORDER_RADIUS,
			},
		},
		{
			name: 'text input',
			// #nldesign-upload-name is a real input[type="text"] in
			// templates/settings/admin.php.
			selector: '#nldesign-upload-name',
			ref: {
				borderColor: gray300,
				borderWidth: '1px',
				borderStyle: 'solid',
				borderRadius: BORDER_RADIUS,
				fontFamily: FONT_STACK,
			},
		},
		{
			name: 'header app name',
			// #header .header-appname is Nextcloud's own header app-name
			// label — always present in stock chrome.
			selector: '#header .header-appname',
			ref: {
				color: brand650,
				fontWeight: '600',
			},
		},
		{
			name: 'header bar',
			selector: '#header',
			ref: {
				backgroundColor: GRAY_000,
				borderColor: gray100, // border-bottom-color
				borderWidth: '1px', // border-bottom-width
				borderStyle: 'solid', // border-bottom-style
			},
		},
		{
			name: 'audit-log table',
			// #nldesign-audit-table is a real, always-rendered <table> in
			// templates/settings/admin.php (theming audit log).
			selector: '#nldesign-audit-table',
			ref: {
				// The only property nldesign's CSS actually sources for a bare
				// <table>: font-family, inherited through the shared body/
				// .app-content font-family rule (element-overrides.css). No
				// nldesign rule targets table borders/background/padding, so
				// they are intentionally NOT asserted here.
				fontFamily: FONT_STACK,
			},
		},
	]
}

/** #rrggbb → 'rgb(r, g, b)' — getComputedStyle always reports colours as rgb(). */
function hexToRgb(hex: string): string {
	const h = hex.replace('#', '')
	const r = parseInt(h.slice(0, 2), 16)
	const g = parseInt(h.slice(2, 4), 16)
	const b = parseInt(h.slice(4, 6), 16)
	return `rgb(${r}, ${g}, ${b})`
}

/** Normalise a computed font-family list for comparison: split, trim, lower-case, strip quotes. */
function normaliseFontFamily(computed: string): string[] {
	return computed.split(',').map((f) => f.trim().replace(/^['"]|['"]$/g, '').toLowerCase())
}

interface ComputedSubset {
	backgroundColor: string
	color: string
	borderColor: string
	borderWidth: string
	borderStyle: string
	borderRadius: string
	fontFamily: string
	fontWeight: string
}

/** Read the subset of computed styles this spec ever compares, for one element. */
async function readComputedStyle(page: Page, selector: string): Promise<ComputedSubset> {
	return page.evaluate((sel) => {
		const el = document.querySelector(sel)
		if (!el) throw new Error(`Element not found: ${sel}`)
		const cs = getComputedStyle(el)
		return {
			backgroundColor: cs.backgroundColor,
			color: cs.color,
			// For elements whose reference only cares about ONE border edge
			// (header's border-bottom, input's full border), reading the
			// bottom edge is a superset-safe choice: for elements with a
			// uniform border (input) top/right/bottom/left are identical, so
			// borderBottomColor equals the value a full `border` shorthand
			// check would report too.
			borderColor: cs.borderBottomColor,
			borderWidth: cs.borderBottomWidth,
			borderStyle: cs.borderBottomStyle,
			borderRadius: cs.borderRadius,
			fontFamily: cs.fontFamily,
			fontWeight: cs.fontWeight,
		}
	}, selector)
}

/**
 * Compare computed styles against a reference, asserting only the
 * properties present on `ref`. On mismatch, throws naming the exact
 * property and the expected-vs-actual delta.
 */
function assertMatchesReference(elementName: string, computed: ComputedSubset, ref: StyleRef): void {
	if (ref.backgroundColor !== undefined) {
		expect(computed.backgroundColor, `${elementName}: background-color — expected ${ref.backgroundColor} (${hexToRgb(ref.backgroundColor)}), got ${computed.backgroundColor}`)
			.toBe(hexToRgb(ref.backgroundColor))
	}
	if (ref.color !== undefined) {
		expect(computed.color, `${elementName}: color — expected ${ref.color} (${hexToRgb(ref.color)}), got ${computed.color}`)
			.toBe(hexToRgb(ref.color))
	}
	if (ref.borderColor !== undefined) {
		expect(computed.borderColor, `${elementName}: border-color — expected ${ref.borderColor} (${hexToRgb(ref.borderColor)}), got ${computed.borderColor}`)
			.toBe(hexToRgb(ref.borderColor))
	}
	if (ref.borderWidth !== undefined) {
		expect(computed.borderWidth, `${elementName}: border-width — expected ${ref.borderWidth}, got ${computed.borderWidth}`)
			.toBe(ref.borderWidth)
	}
	if (ref.borderStyle !== undefined) {
		expect(computed.borderStyle, `${elementName}: border-style — expected ${ref.borderStyle}, got ${computed.borderStyle}`)
			.toBe(ref.borderStyle)
	}
	if (ref.borderRadius !== undefined) {
		expect(computed.borderRadius, `${elementName}: border-radius — expected ${ref.borderRadius}, got ${computed.borderRadius}`)
			.toBe(ref.borderRadius)
	}
	if (ref.fontFamily !== undefined) {
		const actual = normaliseFontFamily(computed.fontFamily)
		expect(actual, `${elementName}: font-family — expected [${ref.fontFamily.join(', ')}], got [${actual.join(', ')}]`)
			.toEqual(ref.fontFamily)
	}
	if (ref.fontWeight !== undefined) {
		expect(computed.fontWeight, `${elementName}: font-weight — expected ${ref.fontWeight}, got ${computed.fontWeight}`)
			.toBe(ref.fontWeight)
	}
}

let baselineTokenSet: string | null = null

test.describe('lasuite-parity', () => {
	test.describe.configure({ mode: 'serial' })

	// @e2e exclude openspec/specs/lasuite-parity/spec.md#mismatch-names-the-property-and-delta
	// Proven by manually reverting a reference value and observing the
	// Playwright assertion message during development (see PR description);
	// asserting a DELIBERATE failure is not something a passing CI suite can
	// carry permanently.

	test.beforeAll(async ({ browser }) => {
		const page = await browser.newPage()
		await page.goto(THEMING_URL)
		await page.waitForLoadState('networkidle')
		const token = await requestToken(page)
		baselineTokenSet = await getTokenSet(page, token)
		await page.close()
	})

	test.afterAll(async ({ browser }) => {
		if (baselineTokenSet === null) return
		const page = await browser.newPage()
		await page.goto(THEMING_URL)
		await page.waitForLoadState('networkidle')
		const token = await requestToken(page)
		await setTokenSet(page, token, baselineTokenSet)
		await page.close()
	})

	for (const set of ['lasuite', 'cunningham'] as const) {
		for (const element of referenceTable(set)) {
			test(`${set}: ${element.name} matches the Cunningham reference`, async ({ page }) => {
				await page.goto(THEMING_URL)
				await page.waitForLoadState('networkidle')
				const token = await requestToken(page)
				await setTokenSet(page, token, set)
				await page.reload()
				await page.waitForLoadState('networkidle')

				await page.waitForSelector(element.selector, { timeout: 15_000 })
				const computed = await readComputedStyle(page, element.selector)
				assertMatchesReference(`${set} ${element.name}`, computed, element.ref)
			})
		}
	}

	test('unified-search modal, when present, matches the shared radius token', async ({ page }) => {
		// .modal-container is styled defensively by element-overrides.css
		// (theming NC-core's OWN native modal component) but nldesign's own
		// vanilla-JS admin panel never renders one itself — so this check
		// must trigger a REAL NC-core modal to have anything to assert.
		// Unified search is the most stable, single-click native trigger
		// already referenced by this app's own CSS
		// (#header .unified-search__button in element-overrides.css). If the
		// installed NC version does not surface `.modal-container` for it
		// (implementation detail that varies by NC release), skip rather
		// than fail on something unrelated to this app's theming code.
		const token = await requestToken(page)
		await setTokenSet(page, token, 'lasuite')
		await page.goto(THEMING_URL)
		await page.waitForLoadState('networkidle')

		const searchButton = page.locator('#header .unified-search__button')
		if (await searchButton.count() === 0) {
			test.skip(true, 'unified-search trigger not present in header on this NC version')
			return
		}
		await searchButton.click()

		const modal = page.locator('.modal-container')
		try {
			await modal.first().waitFor({ state: 'visible', timeout: 5_000 })
		} catch {
			test.skip(true, '.modal-container did not appear for unified search on this NC version')
			return
		}

		const borderRadius = await modal.first().evaluate((el) => getComputedStyle(el).borderRadius)
		expect(borderRadius, `modal: border-radius — expected ${BORDER_RADIUS}, got ${borderRadius}`).toBe(BORDER_RADIUS)

		// Best-effort cleanup — close the dialog so it doesn't linger for the
		// next test in this serial run.
		await page.keyboard.press('Escape').catch(() => {})
	})
})
