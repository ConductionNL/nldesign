/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Unit tests for scripts/generate-lasuite-tokens.mjs — the pure parsing /
 * renaming / rendering functions the La Suite token generator is built from.
 * Exercises the reversible --c-- ⇄ --lasuite-- prefix swap, the light/dark
 * block extraction (the source ships tokens across TWO top-level blocks —
 * `html { ... }` and `.cunningham-theme--dark { ... }` — see the module's
 * own header comment for why), and the closed compatibility-alias list,
 * against small fixture strings rather than the full installed package (fast
 * and does not depend on node_modules content beyond the module import).
 *
 * @spec openspec/specs/lasuite-parity/spec.md
 */

import { describe, it, expect } from 'vitest'
import {
	renameCToLasuite,
	lowercaseHex,
	extractBlock,
	parseDeclarations,
	generate,
	COMPAT_ALIASES,
} from '../../scripts/generate-lasuite-tokens.mjs'

describe('renameCToLasuite', () => {
	it('renames a bare --c-- custom-property name, preserving all -- separators', () => {
		expect(renameCToLasuite('--c--globals--colors--brand-600')).toBe(
			'--lasuite--globals--colors--brand-600',
		)
	})

	it('renames every --c-- occurrence inside a value (var() references)', () => {
		expect(renameCToLasuite('var(--c--globals--colors--brand-600)')).toBe(
			'var(--lasuite--globals--colors--brand-600)',
		)
	})

	it('is reversible by swapping the leading token back', () => {
		const original = '--c--components--button--primary--background-color'
		const renamed = renameCToLasuite(original)
		expect(renamed.replace('--lasuite--', '--c--')).toBe(original)
	})

	it('leaves text with no --c-- occurrence unchanged', () => {
		expect(renameCToLasuite('4px')).toBe('4px')
		expect(renameCToLasuite('#0659c5')).toBe('#0659c5')
	})
})

describe('lowercaseHex', () => {
	it('lower-cases a 6-digit hex colour', () => {
		expect(lowercaseHex('#0659C5')).toBe('#0659c5')
	})

	it('lower-cases a 3-digit hex colour', () => {
		expect(lowercaseHex('#FFF')).toBe('#fff')
	})

	it('leaves non-hex values (var(), keywords, lengths) unchanged', () => {
		expect(lowercaseHex('var(--lasuite--globals--colors--brand-600)')).toBe(
			'var(--lasuite--globals--colors--brand-600)',
		)
		expect(lowercaseHex('4px')).toBe('4px')
		expect(lowercaseHex('transparent')).toBe('transparent')
	})
})

describe('extractBlock', () => {
	const fixture = `html {
	--c--globals--colors--brand-600: #0659C5;
	--c--globals--colors--brand-650: #1A509F;
}
.cunningham-theme--dark{
	--c--globals--colors--brand-600: #0659C5;
	--c--globals--colors--brand-650: #204A87;
}
.clr-brand-600 { color: var(--c--globals--colors--brand-600); }
`

	it('extracts the light "html { ... }" block by exact selector match', () => {
		const block = extractBlock(fixture, /^html$/)
		expect(block).toContain('--c--globals--colors--brand-600: #0659C5;')
		expect(block).not.toContain('.clr-brand-600')
	})

	it('extracts the ".cunningham-theme--dark { ... }" block even with no space before "{"', () => {
		const block = extractBlock(fixture, /^\.cunningham-theme--dark$/)
		expect(block).toContain('--c--globals--colors--brand-650: #204A87;')
	})

	it('returns null when the selector is not present', () => {
		expect(extractBlock(fixture, /^\.does-not-exist$/)).toBeNull()
	})
})

describe('parseDeclarations', () => {
	it('parses every --c--<path>: <value>; declaration on its own line', () => {
		const block = `
	--c--globals--colors--brand-600: #0659C5;
	--c--globals--colors--brand-650: #1A509F;
`
		const decls = parseDeclarations(block)
		expect(decls).toEqual([
			{ name: '--c--globals--colors--brand-600', value: '#0659C5' },
			{ name: '--c--globals--colors--brand-650', value: '#1A509F' },
		])
	})

	it('ignores non-declaration lines (selectors, comments, blank lines)', () => {
		const block = `
	/* a comment */
	--c--globals--colors--brand-600: #0659C5;

	.not-a-declaration { color: red; }
`
		const decls = parseDeclarations(block)
		expect(decls).toEqual([{ name: '--c--globals--colors--brand-600', value: '#0659C5' }])
	})

	it('handles a var() reference as the value', () => {
		const block = '\t--c--contextuals--content--logo1: var(--c--globals--colors--logo-1);\n'
		const decls = parseDeclarations(block)
		expect(decls).toEqual([
			{ name: '--c--contextuals--content--logo1', value: 'var(--c--globals--colors--logo-1)' },
		])
	})
})

describe('generate', () => {
	const sourceCss = `html {
	--c--globals--colors--brand-600: #0659C5;
	--c--globals--colors--brand-650: #1A509F;
}
.cunningham-theme--dark{
	--c--globals--colors--brand-600: #0659C5;
	--c--globals--colors--brand-650: #204A87;
}
.clr-brand-600 { color: var(--c--globals--colors--brand-600); }
`

	function build() {
		return generate({ sourceCss, packageVersion: '3.0.0', generationDate: '2026-07-24' })
	}

	it('emits one --lasuite--* token for every --c--* declaration in both blocks', () => {
		const out = build()
		expect(out).toContain(':root {')
		expect(out).toContain('--lasuite--globals--colors--brand-600: #0659c5;')
		expect(out).toContain('.cunningham-theme--dark {')
		expect(out).toContain('--lasuite--globals--colors--brand-650: #204a87;')
	})

	it('records the source package, version and token count in a provenance header', () => {
		const out = build()
		expect(out).toContain('@openfun/cunningham-tokens@3.0.0')
		expect(out).toContain('MIT licence')
		expect(out).toContain('Token count: 4 (2 light + 2 dark')
		expect(out).toContain('Generated: 2026-07-24')
	})

	it('appends the closed compatibility-alias block with every listed alias', () => {
		const out = build()
		for (const entry of COMPAT_ALIASES) {
			expect(out).toContain(`${entry.alias}:`)
		}
		// The one literal alias (no upstream border-radius token exists).
		expect(out).toContain('--lasuite-border-radius: 4px;')
		// A var()-derived alias resolves to its canonical token.
		expect(out).toContain('--lasuite-color-brand-650: var(--lasuite--globals--colors--brand-650);')
	})

	it('is deterministic — regenerating from the same input yields byte-identical output', () => {
		expect(build()).toBe(build())
	})

	it('throws a clear error when the light block is missing from the source', () => {
		expect(() =>
			generate({ sourceCss: '.cunningham-theme--dark{ --c--x: 1; }', packageVersion: '3.0.0', generationDate: '2026-07-24' }),
		).toThrow(/light "html/)
	})

	it('throws a clear error when the dark block is missing from the source', () => {
		expect(() =>
			generate({ sourceCss: 'html{ --c--x: 1; }', packageVersion: '3.0.0', generationDate: '2026-07-24' }),
		).toThrow(/dark ".cunningham-theme--dark/)
	})
})
