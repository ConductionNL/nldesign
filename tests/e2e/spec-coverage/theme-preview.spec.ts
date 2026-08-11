/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Theme preview ("proefdraaien") — the per-user preview lifecycle, its
 * isolation from every other user, and the banner that announces it.
 *
 * WHY THESE ARE BROWSER TESTS AND NOT UNIT TESTS
 * ----------------------------------------------
 * The whole point of this feature is that ONE user's page renders differently
 * from every other user's, decided per request at render time. That is a claim
 * about what a browser receives, and the only honest way to check it is to
 * render pages in two different authenticated sessions and compare. A unit test
 * on ThemePreviewService can show the config values are written; it cannot show
 * the stylesheet that actually reaches the previewer's <head> changed while the
 * other user's did not.
 *
 * STATE DISCIPLINE
 * ----------------
 * A preview is a per-USER value, so these tests are naturally isolated from
 * anything instance-wide — except `publish`, which is instance-wide by design.
 * That one test captures the active set first and restores it in a finally
 * block. afterEach clears any preview the test left behind, so an assertion
 * failure part-way through cannot leak a preview into the next test and make it
 * fail for the wrong reason.
 */
import { test, expect, type Browser, type Page } from '@playwright/test'

const APP = '/index.php/apps/nldesign'
const THEMING_URL = '/settings/admin/theming'

/** The set previewed throughout. Its display name is asserted in the banner. */
const PREVIEW_SET = 'amsterdam'
const PREVIEW_NAME = 'Gemeente Amsterdam'

const NONADMIN_USER = process.env.NC_NONADMIN_USER ?? 'e2enonadmin'
const NONADMIN_PASS = process.env.NC_NONADMIN_PASS ?? 'nldesign-e2e-nonadmin-pw'

/**
 * Log a user in through the real login form. See admin-only-enforcement.spec.ts
 * for why the redirect is observed by polling rather than with waitForURL.
 */
async function loginAs(browser: Browser, user: string, pass: string): Promise<{ page: Page, close: () => Promise<void> }> {
	const context = await browser.newContext({ storageState: undefined })
	const page = await context.newPage()
	await page.goto('/index.php/login', { waitUntil: 'domcontentloaded' })
	const userField = page.locator('input[name="user"]')
	await userField.waitFor({ state: 'visible', timeout: 30_000 })
	await userField.fill(user)
	await page.locator('input[name="password"]').fill(pass)
	await page.locator('button[type="submit"]').first().click({ noWaitAfter: true })
	const deadline = Date.now() + 60_000
	while (Date.now() < deadline) {
		if (!/\/login(\?|$|\/)/.test(page.url())) break
		await page.waitForTimeout(500)
	}
	if (/\/login(\?|$|\/)/.test(page.url())) {
		throw new Error(`Login failed for ${user} — still on ${page.url()}`)
	}
	return { page, close: async () => { await context.close() } }
}

/** Call an nldesign settings endpoint in-page with a valid CSRF token. */
async function api(page: Page, method: string, path: string, body?: unknown): Promise<{ status: number, json: any }> {
	return page.evaluate(async ({ method, path, body }) => {
		const headers: Record<string, string> = { requesttoken: (window as any).OC.requestToken }
		if (body !== undefined) headers['Content-Type'] = 'application/json'
		const res = await fetch(path, {
			method, headers,
			body: body === undefined ? undefined : JSON.stringify(body),
		})
		let json: any = null
		try { json = await res.json() } catch { json = null }
		return { status: res.status, json }
	}, { method, path, body })
}

/** Every nldesign stylesheet href in the current page's head. */
async function nldesignStyles(page: Page): Promise<string[]> {
	return page.evaluate(() =>
		[...document.querySelectorAll('link[rel=stylesheet]')]
			.map((l) => (l as HTMLLinkElement).href)
			.filter((h) => h.includes('/nldesign/')),
	)
}

/** Every nldesign script src in the current page. */
async function nldesignScripts(page: Page): Promise<string[]> {
	return page.evaluate(() =>
		[...document.querySelectorAll('script[src]')]
			.map((s) => (s as HTMLScriptElement).src)
			.filter((h) => h.includes('/nldesign/')),
	)
}

test.describe('theme preview', () => {
	test.afterEach(async ({ page }) => {
		// Clear any preview this test left behind. Uses the admin session; a
		// discard with no active preview is a no-op, so this is safe to run
		// unconditionally.
		try {
			await page.goto(THEMING_URL)
			await api(page, 'DELETE', `${APP}/settings/preview`)
		} catch {
			// The page may be closed already; nothing to clean up in that case.
		}
	})

	// @e2e openspec/specs/theme-preview/spec.md#starting-a-preview-writes-only-user-values
	test('starting a preview writes user values and leaves the instance-wide set alone', async ({ page }) => {
		await page.goto(THEMING_URL)
		const activeBefore = (await api(page, 'GET', `${APP}/settings/tokenset`)).json

		const start = await api(page, 'POST', `${APP}/settings/preview`, { tokenSet: PREVIEW_SET })
		expect(start.status, 'starting a preview must succeed for an admin').toBe(200)
		expect(start.json.tokenSet).toBe(PREVIEW_SET)

		// expiresAt ≈ now + 86400 (the spec's 24-hour window). Allow a generous
		// window so a slow instance cannot make this flake, but tight enough
		// that a wrong unit (ms vs s) or a missing offset still fails.
		const nowSec = Math.floor(Date.now() / 1000)
		expect(start.json.expiresAt).toBeGreaterThan(nowSec + 86_000)
		expect(start.json.expiresAt).toBeLessThan(nowSec + 86_800)

		// The instance-wide value must be untouched — this is the whole claim.
		const activeAfter = (await api(page, 'GET', `${APP}/settings/tokenset`)).json
		expect(activeAfter, 'a preview must not change the instance-wide active set')
			.toEqual(activeBefore)
	})

	// @e2e openspec/specs/theme-preview/spec.md#invalid-token-set-id-is-rejected
	test('an invalid token set id is rejected and starts no preview', async ({ page }) => {
		await page.goto(THEMING_URL)
		const res = await api(page, 'POST', `${APP}/settings/preview`, { tokenSet: 'does-not-exist' })
		expect(res.status, 'an unknown token set id must be a 400').toBe(400)

		// "and no user values MUST be written" — reload and confirm no banner,
		// which is the browser-visible consequence of a preview existing.
		await page.goto('/settings/user')
		await expect(page.locator('#nldesign-preview-banner')).toHaveCount(0)
	})

	// @e2e openspec/specs/theme-preview/spec.md#previewing-admin-sees-the-previewed-set-on-real-pages
	test('the previewing admin gets the previewed token set on a real page', async ({ page }) => {
		await page.goto(THEMING_URL)

		await page.goto('/settings/user')
		const before = await nldesignStyles(page)
		expect(before.some((h) => h.includes('rijkshuisstijl')),
			'the active set rijkshuisstijl must be loaded before previewing').toBe(true)

		await page.goto(THEMING_URL)
		expect((await api(page, 'POST', `${APP}/settings/preview`, { tokenSet: PREVIEW_SET })).status).toBe(200)

		await page.goto('/settings/user')
		const during = await nldesignStyles(page)
		expect(during.some((h) => h.includes(PREVIEW_SET)),
			`the previewed set ${PREVIEW_SET} must be loaded on a normal page`).toBe(true)
		expect(during.some((h) => h.includes('rijkshuisstijl')),
			'the previewed set must SUBSTITUTE the active one, not stack on top of it').toBe(false)

		// "custom overrides MUST still load last, unchanged" — the overrides
		// layer is the last nldesign stylesheet when present.
		const overrides = during.filter((h) => h.includes('custom') || h.includes('override'))
		if (overrides.length > 0) {
			expect(during.indexOf(overrides[overrides.length - 1]),
				'custom overrides must remain the last nldesign layer').toBe(during.length - 1)
		}
	})

	// @e2e openspec/specs/theme-preview/spec.md#banner-appears-on-all-themed-pages-for-the-previewer
	test('the banner appears on every themed page with keyboard-operable controls', async ({ page }) => {
		await page.goto(THEMING_URL)
		expect((await api(page, 'POST', `${APP}/settings/preview`, { tokenSet: PREVIEW_SET })).status).toBe(200)

		// "on all themed pages" — assert on more than one, or a banner wired to
		// the settings page alone would pass.
		for (const url of ['/apps/files/', '/apps/dashboard/', '/settings/user']) {
			await page.goto(url)
			const banner = page.locator('#nldesign-preview-banner')
			await banner.waitFor({ state: 'visible', timeout: 15_000 })

			await expect(banner, `banner must name the previewed set on ${url}`)
				.toContainText(PREVIEW_NAME)
			await expect(banner, `banner must say the preview is private on ${url}`)
				.toContainText(/only visible to you/i)
			// role="status" so a screen reader announces it (WCAG 4.1.3).
			await expect(banner).toHaveAttribute('role', 'status')

			// Publish and Discard must be reachable and operable by keyboard —
			// they are links/buttons, so they must be focusable, not div-onclick.
			const publish = banner.locator('.nldesign-preview-banner-publish')
			const discard = banner.locator('.nldesign-preview-banner-discard')
			await expect(publish).toBeVisible()
			await expect(discard).toBeVisible()
			// Focus each control and confirm it actually holds focus.
			//
			// RE-FOCUSING on each poll, rather than `await control.focus()` then
			// `expect(control).toBeFocused()`, is deliberate. The host app mounts
			// after the banner is appended and moves focus once while settling —
			// measured on /apps/files/, where a single focus() lands and is then
			// taken away. `toBeFocused()` retries the ASSERTION but never
			// re-issues focus(), so it spends its whole timeout re-reading a
			// state that can no longer change, and reports the control as
			// unfocusable when it is merely late.
			//
			// This still fails for a control that CANNOT take focus (verified by
			// setting tabindex="-1" on the publish link: the poll exhausts and
			// the test goes red), so it tests focusability, not patience.
			for (const control of [publish, discard]) {
				await expect.poll(async () => {
					await control.focus()
					return await control.evaluate((el) => el === document.activeElement)
				}, {
					message: 'banner controls must be keyboard-focusable',
					timeout: 10_000,
				}).toBe(true)
			}
		}
	})

	// @e2e openspec/specs/theme-preview/spec.md#no-banner-payload-without-a-preview
	test('no banner asset or payload is delivered without an active preview', async ({ page }) => {
		await page.goto(THEMING_URL)
		await api(page, 'DELETE', `${APP}/settings/preview`)

		await page.goto('/settings/user')
		const scripts = await nldesignScripts(page)
		const styles = await nldesignStyles(page)

		expect(scripts.some((s) => s.includes('preview-banner')),
			'preview-banner.js must not be served without a preview').toBe(false)
		expect(styles.some((s) => s.includes('preview-banner')),
			'preview-banner.css must not be served without a preview').toBe(false)

		// ...nor the initial-state payload the banner reads.
		const payload = await page.evaluate(() => {
			const el = document.querySelector('#initial-state-nldesign-preview')
			return el ? el.textContent : null
		})
		expect(payload, 'the preview initial-state payload must be absent').toBeNull()
	})

	// @e2e openspec/specs/theme-preview/spec.md#non-admin-users-are-never-affected
	test('a non-admin sees the instance-wide set and no banner while an admin previews', async ({ page, browser }) => {
		await page.goto(THEMING_URL)
		expect((await api(page, 'POST', `${APP}/settings/preview`, { tokenSet: PREVIEW_SET })).status).toBe(200)

		// Confirm the preview really is live for the admin, so a "non-admin is
		// unaffected" pass cannot come from the preview never having started.
		await page.goto('/settings/user')
		expect((await nldesignStyles(page)).some((h) => h.includes(PREVIEW_SET))).toBe(true)

		const other = await loginAs(browser, NONADMIN_USER, NONADMIN_PASS)
		try {
			await other.page.goto('/settings/user')
			const styles = await nldesignStyles(other.page)
			expect(styles.some((h) => h.includes('rijkshuisstijl')),
				'the non-admin must still get the instance-wide set').toBe(true)
			expect(styles.some((h) => h.includes(PREVIEW_SET)),
				"the non-admin must NOT receive the admin's previewed set").toBe(false)
			await expect(other.page.locator('#nldesign-preview-banner'),
				'the non-admin must see no preview banner').toHaveCount(0)
		} finally {
			await other.close()
		}
	})

	// @e2e openspec/specs/theme-preview/spec.md#discard-from-the-banner
	test('discarding from the banner restores the active set and removes the banner', async ({ page }) => {
		await page.goto(THEMING_URL)
		expect((await api(page, 'POST', `${APP}/settings/preview`, { tokenSet: PREVIEW_SET })).status).toBe(200)

		await page.goto('/settings/user')
		const banner = page.locator('#nldesign-preview-banner')
		await banner.waitFor({ state: 'visible', timeout: 15_000 })

		// Activate Discard the way a keyboard user would, not with a raw API
		// call — the scenario is about the BANNER's control doing the work.
		const discard = banner.locator('.nldesign-preview-banner-discard')
		await discard.focus()
		await page.keyboard.press('Enter')

		// The handler calls DELETE and reloads; wait for the banner to go.
		await expect(banner, 'the banner must disappear after Discard').toHaveCount(0, { timeout: 20_000 })

		await page.goto('/settings/user')
		const styles = await nldesignStyles(page)
		expect(styles.some((h) => h.includes('rijkshuisstijl')),
			'the instance-wide set must render again after Discard').toBe(true)
		expect(styles.some((h) => h.includes(PREVIEW_SET)),
			'the previewed set must be gone after Discard').toBe(false)
	})

	// @e2e openspec/specs/theme-preview/spec.md#publish-without-an-active-preview
	test('publishing with no active preview is refused and changes nothing', async ({ page }) => {
		await page.goto(THEMING_URL)
		await api(page, 'DELETE', `${APP}/settings/preview`)

		const before = (await api(page, 'GET', `${APP}/settings/tokenset`)).json
		const res = await api(page, 'POST', `${APP}/settings/preview/publish`)
		expect(res.status, 'publish without a preview must be a 400').toBe(400)

		const after = (await api(page, 'GET', `${APP}/settings/tokenset`)).json
		expect(after, 'a refused publish must not change the active set').toEqual(before)
	})

	// @e2e openspec/specs/theme-preview/spec.md#publish-promotes-preview-to-instance-wide-active
	test('publishing promotes the preview to the instance-wide active set', async ({ page }) => {
		await page.goto(THEMING_URL)
		// SettingsController::getTokenSet() returns {tokenSet}, and setTokenSet()
		// takes {tokenSet} — verified against lib/Controller/SettingsController.php
		// rather than guessed, because this value is what the finally block
		// restores. Asserting it is present means a shape change breaks this
		// test loudly instead of silently restoring `undefined`.
		const before = (await api(page, 'GET', `${APP}/settings/tokenset`)).json
		expect(before?.tokenSet, 'the active token set must be readable before publishing').toBeTruthy()
		const originalSet: string = before.tokenSet

		try {
			expect((await api(page, 'POST', `${APP}/settings/preview`, { tokenSet: PREVIEW_SET })).status).toBe(200)

			const pub = await api(page, 'POST', `${APP}/settings/preview/publish`)
			expect(pub.status, 'publish must succeed with an active preview').toBe(200)
			expect(pub.json.tokenSet).toBe(PREVIEW_SET)

			// Instance-wide now, not just for this session.
			const after = (await api(page, 'GET', `${APP}/settings/tokenset`)).json
			expect(JSON.stringify(after)).toContain(PREVIEW_SET)

			// ...and the caller's preview values are cleared, so no banner.
			await page.goto('/settings/user')
			await expect(page.locator('#nldesign-preview-banner'),
				'publishing must clear the preview, removing the banner').toHaveCount(0)
		} finally {
			// Restore the instance-wide set — this is the one test here that
			// mutates state every other test depends on.
			await page.goto(THEMING_URL)
			await api(page, 'POST', `${APP}/settings/tokenset`, { tokenSet: originalSet })
		}
	})
})
