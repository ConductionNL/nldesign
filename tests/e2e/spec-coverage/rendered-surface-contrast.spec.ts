/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Rendered-surface contrast guard (nldesign#268).
 *
 * The unit tests in tests/Unit/ check the arithmetic of the token values and
 * the presence of the stylesheet rules. Neither can see what the browser
 * actually paints, and this defect lived entirely in that gap: the token values
 * were individually fine, the stylesheets were all present, and the app still
 * added four serious WCAG AA failures that stock Nextcloud does not have,
 * because a blanket `!important` text rule outranked Nextcloud's own
 * light-on-dark pairings and a saturated fill was painted into a token whose
 * NC 34 contract is a pale one.
 *
 * Measured on a Nextcloud 34 instance, changing ONLY whether nldesign was
 * enabled — the numbers this file exists to keep at zero:
 *
 *   /settings/user    .preview-card__header > span   1.75:1
 *   /apps/dashboard/  #app-dashboard > h2            2.91:1
 *   /apps/dashboard/  secondary button label         2.91:1
 *   /settings/admin   .notecard--success             3.94:1
 *   /settings/admin   .notecard--info   (x2)         3.95:1
 *
 * and on the `hoog-contrast` set — whose entire purpose is contrast — 2.44:1
 * and 2.06:1.
 *
 * The contrast maths is inlined rather than pulled from axe-core so this spec
 * adds no dependency and no `enable-axe` decision. It agrees with axe to two
 * decimals on every node above; the WCAG formula is short enough that a copy is
 * cheaper than a runtime that has its own failure modes (.github#351).
 */
import { test, expect, Page } from '@playwright/test'
import { requestToken, getTokenSet, setTokenSet, THEMING_URL } from '../workflows/_helpers'

/** WCAG 2.2 AA floors. */
const AA_NORMAL = 4.5
const AA_LARGE = 3.0

/**
 * Surfaces Nextcloud paints with a saturated colour, each of which regressed.
 *
 * `min` is the floor for that node's own font size — the dashboard heading is
 * large bold text, so 3.0 is the correct floor for it, not 4.5. Using 4.5
 * everywhere would be a stricter test that fails on compliant markup.
 */
const SURFACES: Array<{ route: string, selector: string, min: number, why: string }> = [
	{ route: '/settings/user', selector: '.preview-card__header', min: AA_NORMAL, why: 'theme preview card header on --color-primary' },
	{ route: '/apps/dashboard/', selector: '#app-dashboard > h2', min: AA_LARGE, why: 'dashboard greeting on the plain background' },
	{ route: '/settings/admin', selector: '.notecard--success', min: AA_NORMAL, why: 'NcNoteCard success fill' },
	{ route: '/settings/admin', selector: '.notecard--info', min: AA_NORMAL, why: 'NcNoteCard info fill' },
]

/**
 * Compute the effective contrast ratio of an element, resolving a transparent
 * background up the ancestor chain the way a browser composites it.
 *
 * Three distinct outcomes, deliberately not two:
 *   - `null`        the element is absent. NOT a pass; an absent node and a
 *                   compliant one must not look the same.
 *   - `undetermined` a background IMAGE is painted somewhere in the chain, so
 *                   there is no single background colour to measure against.
 *                   Also NOT a pass, and not a failure either — asserting on it
 *                   would turn "we cannot see this" into a red build. axe-core
 *                   classifies the same nodes as `incomplete` for the same
 *                   reason. Found the hard way: on the hoog-contrast set the
 *                   dashboard greeting sits on Nextcloud's illustrated
 *                   background, and a walker blind to images reads it as
 *                   #ffffff on #ffffff — a 1:1 "failure" that is nothing but
 *                   the measurement's own blind spot.
 *   - a ratio       the real thing.
 */
type ContrastResult = { ratio: number, fg: string, bg: string } | { undetermined: string } | null

async function contrastOf(page: Page, selector: string): Promise<ContrastResult> {
	return await page.evaluate((sel) => {
		const container = document.querySelector(sel) as HTMLElement | null
		if (!container) return null

		// Measure the node that actually HOLDS the text, not the container that
		// happens to paint the fill. A container's own `color` is inert when its
		// text lives in a child, and reading it produces confident nonsense: on
		// the hoog-contrast set `.preview-card__header` computes #000000 on a
		// #000000 fill — a "1:1 failure" on an element that renders no text,
		// while the child span it delegates to is white and perfectly legible.
		const hasOwnText = (e: Element) =>
			[...e.childNodes].some(n => n.nodeType === Node.TEXT_NODE && (n.textContent || '').trim().length > 0)
		const el = (hasOwnText(container)
			? container
			: [...container.querySelectorAll('*')].find(hasOwnText) as HTMLElement | undefined) ?? container

		for (let probe: HTMLElement | null = el; probe; probe = probe.parentElement) {
			const cs = getComputedStyle(probe)
			if (cs.backgroundImage && cs.backgroundImage !== 'none') {
				return { undetermined: `background-image on ${probe.tagName.toLowerCase()}${probe.id ? '#' + probe.id : ''}` }
			}
			if (cs.backgroundColor && !/rgba\(\d+, \d+, \d+, 0\)/.test(cs.backgroundColor)) break
		}

		const parse = (c: string): [number, number, number, number] => {
			const m = c.match(/rgba?\(([\d.]+),\s*([\d.]+),\s*([\d.]+)(?:,\s*([\d.]+))?\)/)
			if (!m) return [255, 255, 255, 1]
			return [Number(m[1]), Number(m[2]), Number(m[3]), m[4] === undefined ? 1 : Number(m[4])]
		}
		const lum = ([r, g, b]: number[]): number => {
			const f = (v: number) => {
				const s = v / 255
				return s <= 0.03928 ? s / 12.92 : Math.pow((s + 0.055) / 1.055, 2.4)
			}
			return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b)
		}

		// Walk up until an opaque background is found, compositing alpha layers.
		let node: HTMLElement | null = el
		let bg: [number, number, number] = [255, 255, 255]
		const stack: Array<[number, number, number, number]> = []
		while (node) {
			const c = parse(getComputedStyle(node).backgroundColor)
			if (c[3] > 0) stack.push(c)
			if (c[3] === 1) { bg = [c[0], c[1], c[2]]; break }
			node = node.parentElement
		}
		for (let i = stack.length - 2; i >= 0; i--) {
			const [r, g, b, a] = stack[i]
			bg = [r * a + bg[0] * (1 - a), g * a + bg[1] * (1 - a), b * a + bg[2] * (1 - a)]
		}

		const fg = parse(getComputedStyle(el).color)
		const composedFg: [number, number, number] = [
			fg[0] * fg[3] + bg[0] * (1 - fg[3]),
			fg[1] * fg[3] + bg[1] * (1 - fg[3]),
			fg[2] * fg[3] + bg[2] * (1 - fg[3]),
		]

		const l1 = lum(composedFg)
		const l2 = lum(bg)
		const ratio = (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05)
		const hex = (c: number[]) => '#' + c.map(v => Math.round(v).toString(16).padStart(2, '0')).join('')
		return { ratio: Math.round(ratio * 100) / 100, fg: hex(composedFg), bg: hex(bg) }
	}, selector)
}

/** The token sets driven here: the default, and the one whose name promises contrast. */
const TOKEN_SETS = ['rijkshuisstijl', 'hoog-contrast']

test.describe('rendered-surface contrast', () => {

	let baselineTokenSet = ''

	test.beforeAll(async ({ browser }) => {
		// `browser.newContext()` does NOT inherit the project's storageState —
		// without this the context is anonymous and the POST below is rejected
		// in a way that only surfaces as a later, unrelated failure.
		const ctx = await browser.newContext({ storageState: 'tests/e2e/.auth/admin.json' })
		const page = await ctx.newPage()
		await page.goto(THEMING_URL)
		baselineTokenSet = await getTokenSet(page, await requestToken(page))
		await ctx.close()
	})

	test.afterAll(async ({ browser }) => {
		if (!baselineTokenSet) return
		const ctx = await browser.newContext({ storageState: 'tests/e2e/.auth/admin.json' })
		const page = await ctx.newPage()
		await page.goto(THEMING_URL)
		await setTokenSet(page, await requestToken(page), baselineTokenSet)
		await ctx.close()
	})

	for (const tokenSet of TOKEN_SETS) {
		test(
			`no painted surface falls below WCAG AA on the ${tokenSet} token set`,
			async ({ page }) => {
				await page.goto(THEMING_URL)
				await setTokenSet(page, await requestToken(page), tokenSet)

				const failures: string[] = []
				const measured: string[] = []
				const undetermined: string[] = []

				for (const surface of SURFACES) {
					await page.goto(surface.route)
					await page.waitForLoadState('domcontentloaded')
					await page.waitForTimeout(1500)

					const result = await contrastOf(page, surface.selector)
					if (result === null) {
						// Not a pass. A surface that stopped rendering cannot be
						// reported as compliant; say so and let the run go red.
						failures.push(`${surface.route} ${surface.selector} — NOT PRESENT (${surface.why})`)
						continue
					}
					if ('undetermined' in result) {
						undetermined.push(`${surface.selector} (${result.undetermined})`)
						continue
					}

					measured.push(`${surface.selector} ${result.ratio}:1 (${result.fg} on ${result.bg})`)
					if (result.ratio < surface.min) {
						failures.push(
							`${surface.route} ${surface.selector} — ${result.ratio}:1 `
							+ `(${result.fg} on ${result.bg}), needs ${surface.min}:1 — ${surface.why}`,
						)
					}
				}

				// A run in which everything came back "undetermined" would print
				// green while having checked nothing at all.
				expect(
					measured.length,
					`no surface was measurable on ${tokenSet} — the selectors have gone stale, or every `
					+ `background became an image. Undetermined: ${undetermined.join(', ') || 'none'}`,
				).toBeGreaterThan(0)
				expect(
					failures,
					`nldesign paints text below WCAG AA on the ${tokenSet} token set:\n  ${failures.join('\n  ')}\n\n`
					+ `Measured: ${measured.join(' | ')}`,
				).toEqual([])
			},
		)
	}

	test(
		'every status note card carries its body text at AA, whatever variants ship',
		async ({ page }) => {
			await page.goto('/settings/admin')
			await page.waitForLoadState('domcontentloaded')
			await page.waitForTimeout(1500)

			const variants = await page.evaluate(() =>
				[...new Set([...document.querySelectorAll('[class*="notecard--"]')]
					.flatMap(e => [...e.classList].filter(c => c.startsWith('notecard--'))))])

			expect(variants.length, 'no note cards on /settings/admin — the sweep measured nothing').toBeGreaterThan(0)

			const failures: string[] = []
			let measured = 0
			for (const variant of variants) {
				const result = await contrastOf(page, `.${variant}`)
				if (result === null || 'undetermined' in result) continue
				measured++
				if (result.ratio < AA_NORMAL) {
					failures.push(`.${variant} — ${result.ratio}:1 (${result.fg} on ${result.bg})`)
				}
			}

			expect(measured, 'note card variants were found but none was measurable').toBeGreaterThan(0)

			expect(failures, `note card fills below WCAG AA:\n  ${failures.join('\n  ')}`).toEqual([])
		},
	)
})
