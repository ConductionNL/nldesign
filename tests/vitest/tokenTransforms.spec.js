/**
 * SPDX-FileCopyrightText: 2026 Conduction / NL Design System Contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Unit tests for js/lib/tokenTransforms.js — the pure design-token / colour
 * transforms the admin settings page uses to derive preview palettes and
 * normalise colour-picker input. Exact-output assertions on every branch.
 */

import { describe, it, expect } from 'vitest'
import tokenTransforms from '../../js/lib/tokenTransforms.js'

const { darkenHex, getPreviewColors, normaliseColorForPicker, designSystemLabel } = tokenTransforms

describe('darkenHex', () => {
	it('darkens a #RRGGBB colour by the given fraction', () => {
		// 0xec = 236 → round(236 * 0.9) = 212 = 0xd4
		expect(darkenHex('#ec0000', 0.1)).toBe('#d40000')
	})

	it('returns black at fraction 1 and the same colour at fraction 0', () => {
		expect(darkenHex('#ffffff', 1)).toBe('#000000')
		expect(darkenHex('#1a2b3c', 0)).toBe('#1a2b3c')
	})

	it('clamps an out-of-range fraction into [0, 1]', () => {
		expect(darkenHex('#808080', 2)).toBe('#000000')
		expect(darkenHex('#808080', -1)).toBe('#808080')
	})

	it('returns the input unchanged for a non-#RRGGBB value', () => {
		expect(darkenHex('red', 0.1)).toBe('red')
		expect(darkenHex('#abc', 0.1)).toBe('#abc')
		expect(darkenHex('', 0.1)).toBe('')
	})
})

describe('getPreviewColors', () => {
	it('derives the palette from a token set primary_color', () => {
		const palette = getPreviewColors({ theming: { primary_color: '#0082c9' } })
		expect(palette.primary).toBe('#0082c9')
		expect(palette.primaryText).toBe('#ffffff')
		// hover is 10% darker
		expect(palette.primaryHover).toBe(darkenHex('#0082c9', 0.1))
	})

	it('falls back to a neutral dark when there is no theming metadata', () => {
		expect(getPreviewColors(null)).toEqual({
			primary: '#333333',
			primaryHover: darkenHex('#333333', 0.1),
			primaryText: '#ffffff',
		})
		expect(getPreviewColors({}).primary).toBe('#333333')
		expect(getPreviewColors({ theming: {} }).primary).toBe('#333333')
	})
})

describe('normaliseColorForPicker', () => {
	it('passes through a lower-cased #RRGGBB value', () => {
		expect(normaliseColorForPicker('#AABBCC')).toBe('#aabbcc')
	})

	it('expands a #RGB shorthand to #RRGGBB', () => {
		expect(normaliseColorForPicker('#abc')).toBe('#aabbcc')
		expect(normaliseColorForPicker('#F00')).toBe('#ff0000')
	})

	it('returns #000000 for empty / null / undefined', () => {
		expect(normaliseColorForPicker('')).toBe('#000000')
		expect(normaliseColorForPicker(null)).toBe('#000000')
		expect(normaliseColorForPicker(undefined)).toBe('#000000')
	})

	it('returns null for values needing browser resolution (named/rgb)', () => {
		// admin.js resolves these via a canvas; the pure helper signals "not pure".
		expect(normaliseColorForPicker('red')).toBeNull()
		expect(normaliseColorForPicker('rgb(1,2,3)')).toBeNull()
	})
})

describe('designSystemLabel', () => {
	it('maps known design-system ids', () => {
		expect(designSystemLabel('none')).toBe('Stock Nextcloud')
		expect(designSystemLabel('nldesign')).toBe('NL Design System')
	})

	it('returns the id itself for an unknown design system', () => {
		expect(designSystemLabel('amsterdam')).toBe('amsterdam')
	})
})
