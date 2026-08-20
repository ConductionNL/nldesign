/**
 * SPDX-FileCopyrightText: 2026 Conduction / NL Design System Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Integrity of the generated Rotterdam token set.
 *
 * WHAT THIS GUARDS, AND WHY EACH CHECK EXISTS
 * -------------------------------------------
 * A token set can be present, parse cleanly, be listed in the catalogue and
 * still theme NOTHING. That is not hypothetical: before `rotterdam.css` was
 * regenerated, 44 of this repo's 48 sets defined only `--nldesign-*` colours,
 * so every NL Design System component rendered in the upstream default and
 * the only visible change was one accent colour. A test asserting the file
 * exists, or that it contains a hex string, passes in exactly that state.
 *
 * So the checks below assert the properties that actually distinguish a set
 * that themes from one that does not:
 *
 *   1. The component vocabulary is present — by NAMED token, not by count.
 *      A count passes while the wrong tokens are defined.
 *   2. Colours meet WCAG 2.2 AA where they carry text or draw a control.
 *   3. Rotterdam renders DIFFERENTLY from the shared role layer it was
 *      derived from. This is the check that catches "the generator ran but
 *      substituted nothing" — a state in which every other assertion here
 *      still passes.
 *   4. The contrast helper itself is shown to REJECT a known-bad pair, so a
 *      green run means the check ran rather than that it could not fail.
 */

import { describe, it, expect } from 'vitest'
import { readFileSync } from 'fs'
import { join, dirname } from 'path'
import { fileURLToPath } from 'url'

const TOKENS_DIR = join(
	dirname(fileURLToPath(import.meta.url)),
	'..',
	'..',
	'css',
	'tokens',
)

/**
 * Effective custom-property declarations of a token file.
 *
 * Last-wins, matching the CSS cascade within one flat block.
 *
 * @param {string} setName Token set id, e.g. 'rotterdam'.
 * @return {Object<string,string>} Property name → value.
 */
function tokensOf(setName) {
	const css = readFileSync(join(TOKENS_DIR, `${setName}.css`), 'utf8')
	const result = {}
	const pattern = /(--[A-Za-z0-9_-]+)\s*:\s*([^;}]+)[;}]/g
	let match

	while ((match = pattern.exec(css)) !== null) {
		result[match[1]] = match[2].trim()
	}

	return result
}

/**
 * Follow `var()` indirection until a literal value is reached.
 *
 * The RODS mapping is deliberately indirect — `--utrecht-link-color` points
 * at `--rods-color-base-green`, which points at `--rods-color-green-tint-01`.
 * Comparing the raw declaration would compare pointer text and would report
 * two sets as different because they spell the same colour differently.
 *
 * @param {Object<string,string>} tokens The token map.
 * @param {string} name Property to resolve.
 * @param {number} depth Recursion guard.
 * @return {string|null} The literal value, or null if it does not resolve.
 */
function resolve(tokens, name, depth = 0) {
	if (depth > 20) {
		return null
	}

	const value = tokens[name]
	if (value === undefined) {
		return null
	}

	const reference = value.match(/^var\(\s*(--[A-Za-z0-9_-]+)/)
	if (reference !== null) {
		return resolve(tokens, reference[1], depth + 1)
	}

	return value
}

/**
 * Relative luminance per WCAG 2.2.
 *
 * @param {string} hex A 6-digit hex colour.
 * @return {number} Relative luminance.
 */
function luminance(hex) {
	const clean = hex.trim().replace('#', '')
	const channels = [0, 2, 4].map((i) => parseInt(clean.slice(i, i + 2), 16) / 255)
	const linear = channels.map((c) =>
		c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4,
	)

	return 0.2126 * linear[0] + 0.7152 * linear[1] + 0.0722 * linear[2]
}

/**
 * Contrast ratio between two colours.
 *
 * @param {string} a First colour.
 * @param {string} b Second colour.
 * @return {number} Ratio between 1 and 21.
 */
function contrast(a, b) {
	const pair = [luminance(a), luminance(b)].sort((x, y) => y - x)

	return (pair[0] + 0.05) / (pair[1] + 0.05)
}

describe('rotterdam token set', () => {
	const rotterdam = tokensOf('rotterdam')

	describe('the contrast helper can fail', () => {
		// Without this, every ratio assertion below is unfalsifiable: a helper
		// that returned 21 for any input would make the whole suite green.
		it('rejects a pair that does not meet AA', () => {
			expect(contrast('#cccccc', '#ffffff')).toBeLessThan(4.5)
		})

		it('accepts a pair that does', () => {
			expect(contrast('#000000', '#ffffff')).toBeGreaterThanOrEqual(4.5)
		})
	})

	describe('the NL Design System component vocabulary is defined', () => {
		// Named individually. These are the components a public portal search
		// page renders; if any one is undefined it falls back to the upstream
		// default and that one control is silently un-branded.
		const required = [
			'--utrecht-document-font-family',
			'--utrecht-heading-1-font-size',
			'--utrecht-heading-2-font-size',
			'--utrecht-paragraph-color',
			'--utrecht-link-color',
			'--utrecht-link-hover-color',
			'--utrecht-button-primary-action-background-color',
			'--utrecht-button-primary-action-color',
			'--utrecht-textbox-border-color',
			'--utrecht-textbox-color',
			'--utrecht-form-control-border-color',
			'--utrecht-focus-outline-color',
			'--utrecht-card-background-color',
			'--ams-pagination-button-color',
		]

		it.each(required)('defines %s', (token) => {
			expect(
				resolve(rotterdam, token),
				`${token} does not resolve to a value`,
			).not.toBeNull()
		})
	})

	describe('WCAG 2.2 AA', () => {
		const white = '#ffffff'

		// 4.5:1 — SC 1.4.3, these carry text.
		const textPairs = [
			['--nldesign-color-text', white],
			['--nldesign-color-text-muted', white],
			['--nldesign-color-link', white],
			['--nldesign-color-link-hover', white],
			['--nldesign-color-link-visited', white],
			['--nldesign-color-error', white],
			['--nldesign-color-warning', white],
			['--nldesign-color-info', white],
		]

		it.each(textPairs)(
			'%s meets 4.5:1 against the page background',
			(token, background) => {
				const value = resolve(rotterdam, token)
				expect(value).not.toBeNull()
				expect(contrast(value, background)).toBeGreaterThanOrEqual(4.5)
			},
		)

		it('white text on the primary colour meets 4.5:1', () => {
			expect(
				contrast(
					resolve(rotterdam, '--nldesign-color-primary-text'),
					resolve(rotterdam, '--nldesign-color-primary'),
				),
			).toBeGreaterThanOrEqual(4.5)
		})

		it('white text on the header background meets 4.5:1', () => {
			expect(
				contrast(
					resolve(rotterdam, '--nldesign-color-header-text'),
					resolve(rotterdam, '--nldesign-color-header-background'),
				),
			).toBeGreaterThanOrEqual(4.5)
		})

		// 3:1 — SC 1.4.11, this one draws a control boundary.
		// `--nldesign-color-border` is deliberately NOT asserted: it is the
		// decorative rule (card edges, separators) and 1.4.11 does not reach
		// purely decorative styling. The distinction is recorded in
		// scripts/brands/rotterdam.json so it cannot be quietly reinterpreted
		// as "the border token fails and we ignored it".
		it('the control border meets 3:1 against the page background', () => {
			expect(
				contrast(resolve(rotterdam, '--nldesign-color-border-dark'), white),
			).toBeGreaterThanOrEqual(3)
		})

		it('the textbox border a visitor actually sees meets 3:1', () => {
			expect(
				contrast(
					resolve(rotterdam, '--utrecht-textbox-border-color'),
					white,
				),
			).toBeGreaterThanOrEqual(3)
		})
	})

	describe('it renders differently from the role layer it was derived from', () => {
		const vng = tokensOf('vng')

		// The failure this catches: the generator runs, emits a well-formed
		// file, and substitutes nothing — leaving a "Rotterdam" set that is
		// VNG in every visible respect. Every other assertion in this file
		// still passes in that state, which is precisely why this one exists.
		const mustDiffer = [
			'--nldesign-color-primary',
			'--nldesign-color-link-hover',
			'--nldesign-color-header-background',
			'--utrecht-link-color',
			'--utrecht-button-primary-action-background-color',
		]

		it.each(mustDiffer)('%s differs from vng', (token) => {
			const mine = resolve(rotterdam, token)
			const theirs = resolve(vng, token)

			expect(mine).not.toBeNull()
			expect(String(mine).toLowerCase()).not.toBe(String(theirs).toLowerCase())
		})

		it('carries no VNG brand blue anywhere in the file', () => {
			const css = readFileSync(
				join(TOKENS_DIR, 'rotterdam.css'),
				'utf8',
			).toLowerCase()
			// The role layer's own primary/link blues. Any survivor is a colour
			// that reads as Rotterdam and is not.
			for (const stray of ['#004488', '#003865', '#026596', '#009dda']) {
				expect(css, `${stray} survived the ramp substitution`).not.toContain(
					stray,
				)
			}
		})
	})

	describe('the file is structurally intact', () => {
		// The defect this caught for real: a value parser that ended a
		// declaration at the first `;` truncated
		// `url('data:image/svg+xml;base64,…')` mid-string, leaving an
		// unterminated quote that made every line after it unparseable. The
		// file was 3,197 lines long and correct for the first 2,987 of them,
		// so nothing about reading it suggested a problem.
		const css = readFileSync(join(TOKENS_DIR, 'rotterdam.css'), 'utf8')
		const declarations = css
			.split('\n')
			.filter((line) => /^\t--/.test(line) === true)

		it('emits every declaration on one line, terminated', () => {
			expect(declarations.length).toBeGreaterThan(3000)
			for (const line of declarations) {
				expect(
					line.endsWith(';'),
					`unterminated declaration: ${line.slice(0, 80)}`,
				).toBe(true)
			}
		})

		it('closes every quote and bracket it opens', () => {
			for (const line of declarations) {
				for (const quote of ['"', "'"]) {
					const count = line.split(quote).length - 1
					expect(
						count % 2,
						`odd number of ${quote} in: ${line.slice(0, 80)}`,
					).toBe(0)
				}

				const opened = line.split('(').length - 1
				const closed = line.split(')').length - 1
				expect(
					opened,
					`unbalanced parentheses in: ${line.slice(0, 80)}`,
				).toBe(closed)
			}
		})

		it('keeps data URIs whole', () => {
			// Truncation shows up as a data URI whose base64 payload is empty.
			const dataUris = [...css.matchAll(/url\('data:[^']*'\)/g)]
			expect(dataUris.length).toBeGreaterThan(0)
			for (const [uri] of dataUris) {
				expect(uri).toContain('base64,')
				expect(uri.length).toBeGreaterThan(100)
			}
		})
	})

	describe('every reference resolves', () => {
		it('leaves no more dangling var() references than the role layer already has', () => {
			const dangling = (setName) => {
				const css = readFileSync(join(TOKENS_DIR, `${setName}.css`), 'utf8')
				const defined = new Set(
					[...css.matchAll(/^\s*(--[A-Za-z0-9_-]+)\s*:/gm)].map(
						(m) => m[1],
					),
				)
				const referenced = new Set(
					[...css.matchAll(/var\(\s*(--[A-Za-z0-9_-]+)/g)].map(
						(m) => m[1],
					),
				)

				return [...referenced].filter((name) => defined.has(name) === false)
			}

			// Asserted as a subset rather than as zero: the shared role layer
			// ships eight dangling references of its own, and demanding zero
			// here would fail on debt this set inherited rather than created.
			const roleLayerDangling = new Set(dangling('vng'))
			for (const name of dangling('rotterdam')) {
				expect(
					roleLayerDangling.has(name),
					`${name} dangles in rotterdam but not in the role layer`,
				).toBe(true)
			}
		})
	})
})
