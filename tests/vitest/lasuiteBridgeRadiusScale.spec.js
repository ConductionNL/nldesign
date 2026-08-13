/**
 * SPDX-FileCopyrightText: 2026 Conduction / NL Design System Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Guards the La Suite bridge's border-radius mapping.
 *
 * `--lasuite-border-radius` (4px) is the one literal in the token set. It was
 * observed on Cunningham's BUTTON and INPUT — a CONTROL radius — because
 * Cunningham bakes radii into compiled component CSS rather than exposing a
 * custom property (see the COMPATIBILITY ALIASES note in defaults.css).
 *
 * Mapping it onto Nextcloud's CONTAINER token (`--border-radius-large`, NC
 * default 8px) collapsed NC's radius hierarchy onto a single value. On a card
 * drawn with a 1px gray-100 hairline that shrank the corner arcs to roughly two
 * antialiased pixels: the straight edges stayed crisp while the corners
 * visibly went missing.
 *
 * The `--color-*` audit in tests/css/check-lasuite-bridge-coverage.js does not
 * cover radius tokens, so this is the only thing standing between that mapping
 * and a silent reintroduction.
 */

import fs from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'
import { describe, it, expect } from 'vitest'

const here = path.dirname(fileURLToPath(import.meta.url))
const BRIDGE = path.resolve(here, '../../css/systems/lasuite/bridge.css')

/**
 * Whether bridge.css actively forces an NC token onto the La Suite radius.
 * Commented-out lines do not count as a mapping.
 *
 * @param {string} css The bridge stylesheet source.
 * @param {string} token The NC custom property name, without the leading `--`.
 * @return {boolean} True when an active declaration maps the token.
 */
function mapsToLasuiteRadius(css, token) {
	return css
		.split('\n')
		.filter(
			(line) => !line.trim().startsWith('*') && !line.trim().startsWith('/*'),
		)
		.some((line) =>
			new RegExp(`^\\s*--${token}:\\s*var\\(--lasuite-border-radius\\)`).test(
				line,
			),
		)
}

describe('La Suite bridge — border-radius scale', () => {
	const css = fs.readFileSync(BRIDGE, 'utf8')

	// The control scale is what 4px was actually measured on.
	it.each(['border-radius', 'border-radius-small', 'border-radius-element'])(
		'maps the control-scale token --%s onto the La Suite radius',
		(token) => {
			expect(mapsToLasuiteRadius(css, token)).toBe(true)
		},
	)

	it('does NOT force the container token --border-radius-large onto the control radius', () => {
		expect(mapsToLasuiteRadius(css, 'border-radius-large')).toBe(false)
	})

	it('leaves the wider container tokens untouched', () => {
		// Never mapped, and must stay that way — they are container-scale too.
		expect(mapsToLasuiteRadius(css, 'border-radius-container')).toBe(false)
		expect(mapsToLasuiteRadius(css, 'border-radius-rounded')).toBe(false)
	})
})
