/**
 * @vitest-environment jsdom
 *
 * Unit tests for js/admin.js — the Marianne (French State typeface)
 * acknowledgement gate UI:
 * - the checkbox POSTs its checked state to /settings/marianne
 * - the gate section's visibility follows the selected token set's
 *   data-design-system attribute (shown only for lasuite)
 *
 * Mirrors the black-box, real-DOM-event style of tests/vitest/admin-dark-mode.spec.js.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @spec openspec/specs/marianne-font/spec.md
 */

import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

/** Build the minimal settings-page DOM the script expects, per token-set config. */
/**
 * Stand in for Nextcloud's initial-state channel.
 *
 * js/admin.js reads its server data through `OCP.InitialState.loadState()`
 * (ADR-004), so a fixture has to hand it over the same way the settings page
 * does. The `data-*` attributes these fixtures used to carry no longer reach
 * the script, and the template no longer emits them.
 *
 * @param {object} state Initial-state keys as provided by lib/Settings/Admin.php.
 */
function installInitialState(state) {
	global.OCP = Object.assign(global.OCP || {}, {
		InitialState: {
			loadState: (app, key, fallback) => (
				Object.prototype.hasOwnProperty.call(state, key) ? state[key] : fallback
			),
		},
	})
}

function buildDom(tokenSets, currentTokenSet, marianneChecked) {
	installInitialState({ tokenSets, currentTokenSet, activePreview: null, iconPackSource: '' })
	document.body.innerHTML = `
		<div id="nldesign-settings" class="section">
			<select id="nldesign-token-set-select" name="nldesign-token-set">
				${tokenSets.map((ts) => `<option value="${ts.id}" data-design-system="${ts.designSystem || 'nldesign'}"${ts.id === currentTokenSet ? ' selected' : ''}>${ts.name}</option>`).join('')}
			</select>
			<span id="nldesign-design-system-badge"></span>
			<div class="nldesign-marianne-gate" id="nldesign-marianne-gate">
				<input type="checkbox" id="nldesign-marianne-enabled"${marianneChecked ? ' checked' : ''}>
			</div>
			<input type="checkbox" id="nldesign-hide-slogan">
			<input type="checkbox" id="nldesign-show-menu-labels">
			<input type="checkbox" id="nldesign-dark-variants">
		</div>
		<div class="nldesign-preview" id="nldesign-preview"></div>
		<div class="nldesign-app-theming" id="nldesign-app-theming">
			<div id="nldesign-app-theming-list"></div>
			<button type="button" id="nldesign-app-theming-save">Save</button>
			<span id="nldesign-app-theming-feedback"></span>
		</div>
	`
}

/** Minimal OC / t / n globals admin.js reads at load and call time. */
function installGlobals() {
	global.t = (app, text, params) => {
		if (params === undefined) {
			return text
		}
		return Object.keys(params).reduce((acc, key) => acc.replace('{' + key + '}', params[key]), text)
	}
	global.n = (app, singular, plural, count) => (count === 1 ? singular : plural)
	global.OC = {
		generateUrl: (url) => url,
		linkTo: (app, path) => path,
		requestToken: 'test-token',
		Notification: { showTemporary: vi.fn() },
		dialogs: { confirm: vi.fn() },
	}
}

/** Route a fetch() call to canned JSON based on a URL substring; records POST bodies. */
function installFetchRouter(routes, postLog) {
	global.fetch = vi.fn((url, options) => {
		if (options && options.method === 'POST' && postLog) {
			postLog.push({ url, body: options.body })
		}
		for (const [match, body] of routes) {
			if (url.indexOf(match) !== -1) {
				return Promise.resolve({ status: 200, json: () => Promise.resolve(body) })
			}
		}
		return Promise.resolve({ status: 200, json: () => Promise.resolve({}) })
	})
}

/** Flush pending promise chains. */
async function flush(rounds = 8) {
	for (let i = 0; i < rounds; i++) {
		await new Promise((resolve) => setTimeout(resolve, 0))
	}
}

/** (Re-)import js/admin.js as a fresh module instance, running its IIFE against the current DOM. */
async function loadAdminScript() {
	vi.resetModules()
	await import('../../js/admin.js?t=' + Math.random())
	await flush()
}

const LASUITE_SET = { id: 'lasuite', name: 'La Suite numérique', designSystem: 'lasuite', theming: { primary_color: '#4844AD', background_color: '#ffffff' } }
const NLDESIGN_SET = { id: 'rijkshuisstijl', name: 'Rijkshuisstijl', designSystem: 'nldesign', theming: { primary_color: '#154273', background_color: '#ffffff' } }

describe('admin.js Marianne gate', () => {
	beforeEach(() => {
		installGlobals()
	})

	afterEach(() => {
		document.body.innerHTML = ''
		vi.restoreAllMocks()
	})

	it('POSTs the checked state to /settings/marianne', async () => {
		const postLog = []
		buildDom([LASUITE_SET], 'lasuite', false)
		installFetchRouter([], postLog)

		await loadAdminScript()

		const checkbox = document.getElementById('nldesign-marianne-enabled')
		checkbox.checked = true
		checkbox.dispatchEvent(new window.Event('change', { bubbles: true }))

		await flush()

		const post = postLog.find((entry) => entry.url.indexOf('/settings/marianne') !== -1)
		expect(post).toBeTruthy()
		expect(JSON.parse(post.body)).toEqual({ enabled: true })
	})

	it('POSTs false when unticked', async () => {
		const postLog = []
		buildDom([LASUITE_SET], 'lasuite', true)
		installFetchRouter([], postLog)

		await loadAdminScript()

		const checkbox = document.getElementById('nldesign-marianne-enabled')
		checkbox.checked = false
		checkbox.dispatchEvent(new window.Event('change', { bubbles: true }))

		await flush()

		const post = postLog.find((entry) => entry.url.indexOf('/settings/marianne') !== -1)
		expect(post).toBeTruthy()
		expect(JSON.parse(post.body)).toEqual({ enabled: false })
	})

	it('shows the gate on initial load when lasuite is the current token set', async () => {
		buildDom([LASUITE_SET], 'lasuite', false)
		installFetchRouter([])

		await loadAdminScript()

		const gate = document.getElementById('nldesign-marianne-gate')
		expect(gate.style.display).not.toBe('none')
	})

	it('hides the gate on initial load for a non-lasuite token set', async () => {
		buildDom([NLDESIGN_SET], 'rijkshuisstijl', false)
		installFetchRouter([])

		await loadAdminScript()

		const gate = document.getElementById('nldesign-marianne-gate')
		expect(gate.style.display).toBe('none')
	})

	it('toggles gate visibility live when the token set selection changes', async () => {
		buildDom([NLDESIGN_SET, LASUITE_SET], 'rijkshuisstijl', false)
		installFetchRouter([
			['tokenset-preview', { error: 'not applicable' }],
			['/settings/tokenset', { status: 'ok' }],
		])

		await loadAdminScript()

		const gate = document.getElementById('nldesign-marianne-gate')
		expect(gate.style.display).toBe('none')

		const select = document.getElementById('nldesign-token-set-select')
		select.value = 'lasuite'
		select.dispatchEvent(new window.Event('change', { bubbles: true }))

		await flush()

		expect(gate.style.display).not.toBe('none')
	})
})
