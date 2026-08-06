/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @e2e openspec/specs/lasuite-parity/spec.md
 *
 * SELECTOR LIVENESS — does every rule this theme ships actually match anything?
 *
 * Every visual defect found in the 2026-07-28/29 parity rounds had the same
 * root cause: a selector that reads plausibly, passes review, lints clean, and
 * matches NOTHING in the live DOM.
 *
 *   - `#app-navigation …`     Nextcloud 34 renders `#app-navigation-vue`.
 *                             12 of 13 rules were inert; the one live rule lost
 *                             on specificity to nc-vue's scoped `!important`,
 *                             so the active row kept its brand tint.
 *   - `#content.content`      Only the INNER shell carries the `content` class.
 *                             The outer shell kept Nextcloud's 8px inset and
 *                             16px radius — the body-colour frame — while the
 *                             inner one got a second 50px top margin, opening
 *                             the band under the header.
 *   - `.button-vue--vue-tertiary`
 *                             NC34 emits BOTH that and `button-vue--tertiary`.
 *                             Measured on one page: 60 plain vs 1 `--vue-`.
 *                             The block listed only `--vue-`, so it styled one
 *                             button and missed sixty.
 *
 * None of these fail loudly. Nothing in the unit suite can see them, because
 * they are only wrong RELATIVE TO A RENDERED PAGE. This spec is the check that
 * would have caught all three on the day they were written.
 *
 * UNION SEMANTICS. A selector is judged against the union of every surface
 * below, not each one individually — a rule for the Files grid is legitimately
 * absent on Calendar. It fails only when it matches nothing ANYWHERE.
 *
 * FAIL-CLOSED ALLOWLIST. Selectors that genuinely cannot match on any surveyed
 * surface (transient overlays, deliberate cross-version fallbacks) must be
 * listed in ALLOWED below WITH A REASON, in the same spirit as the `@spec
 * exclude` gates. Adding a bare pattern with no reason is not permitted: the
 * point is that dead selectors become a decision someone made on purpose,
 * rather than something nobody noticed.
 *
 * GLOBAL STATE: reads only. Requires the `lasuite` set to be active — the spec
 * skips (rather than silently passing) when element-overrides.css is absent,
 * because a guard that quietly measures nothing is the exact failure mode it
 * exists to prevent.
 */

import { expect, test } from '@playwright/test'

/** Surfaces surveyed. Each contributes its DOM to the union. */
const SURFACES: Array<{ name: string; path: string }> = [
	{ name: 'files', path: '/apps/files/' },
	{ name: 'contacts', path: '/apps/contacts/' },
	{ name: 'mail', path: '/apps/mail/' },
	{ name: 'calendar', path: '/apps/calendar/' },
	{ name: 'settings', path: '/settings/user' },
	{ name: 'dashboard', path: '/apps/dashboard/' },
]

/**
 * Selectors permitted to match nothing on every surveyed surface.
 * Each entry is a substring or /regex/ plus the reason it cannot match.
 */
const ALLOWED: Array<{ pattern: RegExp; reason: string }> = [
	{
		pattern: /^#app-navigation(?![-\w])/,
		reason: 'Deliberate pre-NC34 fallback; NC34 renders #app-navigation-vue. Kept so the theme still applies on older servers.',
	},
	{
		pattern: /#content\.content|#app-content(?!-vue)/,
		reason: 'Cross-version shell fallbacks. NC34 uses #content (no class) + #content-vue + #app-content-vue.',
	},
	{
		pattern: /nav\.app-menu/,
		reason: 'NC34 renders the app menu through a different structure; kept for older servers.',
	},
	{
		pattern: /\.header-appname|\.header-left|\.header-right|\.menutoggle|\.unified-search__button|\.header-start \.icon-vue/,
		reason: 'Pre-Vue header classes retained as fallbacks for older Nextcloud releases.',
	},
	{
		pattern: /^(button|input)\.(primary|secondary)\b/,
		reason: 'Pre-Vue button classes; NC34 emits .button-vue--* which the adjacent wildcard rules catch.',
	},
	{
		pattern: /--active/,
		reason: 'BEM --active variant; NC34 uses .active. Both spellings are carried deliberately.',
	},
	{
		pattern: /\.modal-container|\.popover|\.dropdown|\.oc-dialog|\.toastify/,
		reason: 'Transient overlays — only in the DOM while open, never on a page at rest.',
	},
	{
		pattern: /\.list-item__wrapper\.active/,
		reason: 'Alternate selection spelling; NC34 uses .list-item__wrapper--active. Both carried.',
	},
	{
		pattern: /app-navigation--close/,
		reason: 'Collapsed-sidebar state; the class only exists while the navigation is collapsed.',
	},
	{
		pattern: /^(textarea|select|\.button)$|^input\[type=|^h[4-6]$/,
		reason: 'Base-layer rules for plain form controls and minor headings. The surveyed surfaces are '
			+ 'Vue apps that render their own components instead, but these still apply on form-bearing '
			+ 'admin pages and inside dialogs.',
	},
	{
		pattern: /unified-search__input/,
		reason: 'Pre-NC34 unified-search markup, retained as a fallback for older servers.',
	},
]

function allowedReason(selector: string): string | null {
	for (const { pattern, reason } of ALLOWED) {
		if (pattern.test(selector)) return reason
	}
	return null
}

test.describe('lasuite selector liveness', () => {
	test('every element-overrides selector matches something on at least one surface', async ({ page }) => {
		// Six surfaces, each a full Vue app boot. test.slow() alone is not enough
		// on a loaded dev instance, so the budget is set explicitly.
		test.setTimeout(300_000)

		const liveEverywhere = new Set<string>()
		let allSelectors: string[] = []
		let sheetSeenOnce = false

		const unreachable: string[] = []

		for (const surface of SURFACES) {
			// A surface that will not load on a loaded dev box must not fail the
			// guard: union semantics mean a missing surface can only make the
			// result MORE conservative (fewer selectors proven live), never produce
			// a false accusation. Unreachable surfaces are reported, not fatal —
			// a guard that breaks when the instance is slow gets switched off, and
			// then it protects nothing.
			try {
				await page.goto(surface.path, { waitUntil: 'domcontentloaded', timeout: 45_000 })
			} catch {
				unreachable.push(surface.name)
				continue
			}
			// Vue apps mount after load; give the shell a moment to render.
			await page.waitForTimeout(2500)

			const result = await page.evaluate(() => {
				const sheet = [...document.styleSheets].find(
					(s) => s.href && /systems\/lasuite\/element-overrides\.css/.test(s.href),
				)
				if (!sheet) return null

				let rules: CSSRule[]
				try {
					rules = [...sheet.cssRules]
				} catch {
					return null
				}

				const selectors: string[] = []
				const live: string[] = []

				for (const rule of rules) {
					const styleRule = rule as CSSStyleRule
					if (!styleRule.selectorText) continue
					for (const raw of styleRule.selectorText.split(',')) {
						const sel = raw.trim()
						if (!sel) continue
						selectors.push(sel)
						// Pseudo-elements and interaction states can never match at rest;
						// probe the element they hang off instead.
						const probe = sel
							.replace(/::[a-z-]+(\([^)]*\))?/gi, '')
							.replace(
								/:(hover|focus|focus-within|focus-visible|active|visited|target|placeholder|disabled|checked)\b(\([^)]*\))?/gi,
								'',
							)
							.trim()
						if (!probe) continue
						try {
							if (document.querySelectorAll(probe).length > 0) live.push(sel)
						} catch {
							/* invalid probe after stripping — reported as dead */
						}
					}
				}
				return { selectors, live }
			})

			if (result === null) continue
			sheetSeenOnce = true
			allSelectors = [...new Set([...allSelectors, ...result.selectors])]
			result.live.forEach((s) => liveEverywhere.add(s))
		}

		test.skip(
			!sheetSeenOnce,
			'lasuite element-overrides.css was not served on any surface — activate the lasuite token set to run this guard',
		)

		if (unreachable.length > 0) {
			// Surfaced rather than swallowed: a run that silently surveyed half the
			// surfaces looks identical to a clean one otherwise.
			console.warn(`[selector-liveness] surfaces that did not load: ${unreachable.join(', ')}`)
		}
		expect(
			unreachable.length,
			`Too few surfaces loaded (${unreachable.join(', ')}) — the union would be too narrow to trust`,
		).toBeLessThan(SURFACES.length - 1)

		const unexplainedDead = allSelectors
			.filter((sel) => !liveEverywhere.has(sel))
			.filter((sel) => allowedReason(sel) === null)
			.sort()

		expect(
			unexplainedDead,
			`These selectors matched NOTHING on any surveyed surface. Either fix them against the real DOM, `
				+ `or add them to ALLOWED with the reason they cannot match:\n  ${unexplainedDead.join('\n  ')}`,
		).toEqual([])
	})

	test('the two shells the theme resizes are actually present and full-bleed', async ({ page }) => {
		// Regression lock for the band-and-frame defect specifically: it is not
		// enough that the selectors match — the boxes have to reach the edges,
		// or the body colour shows through again.
		await page.goto('/apps/files/', { waitUntil: 'domcontentloaded' })
		await page.waitForTimeout(2500)

		const geometry = await page.evaluate(() => {
			const shell = document.querySelector('#content-vue') as HTMLElement | null
			if (!shell) return null
			const r = shell.getBoundingClientRect()
			return { x: Math.round(r.x), width: Math.round(r.width), viewport: window.innerWidth }
		})

		test.skip(geometry === null, '#content-vue not present on this Nextcloud version')

		expect(geometry!.x, 'the content shell must start at the window edge, not inset').toBe(0)
		expect(
			geometry!.width,
			'the content shell must span the full viewport, or body colour shows down the side',
		).toBe(geometry!.viewport)
	})
})
