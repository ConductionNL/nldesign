/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Visual-regression baselines for nldesign's key surface (GAP-5).
 *
 * Run:    npx playwright test --project visual
 * Update: npx playwright test --project visual --update-snapshots
 *
 * nldesign has no in-app page route — it is an admin theming app whose UI
 * lives under Nextcloud's admin settings (/settings/admin/theming), so the
 * baselined surface is the theming admin page where nldesign injects its
 * token-set / slogan / menu-label controls.
 *
 * Baselines live in tests/e2e/visual/<spec>-snapshots/ and ARE committed.
 * See _visual-helpers.ts for the platform-rendering caveat.
 */
import { test } from '@playwright/test'
import { shootSurface } from './_visual-helpers'

const THEMING = '/settings/admin/theming'

test.describe('nldesign — visual baselines', () => {
	test('theming admin settings', async ({ page }) => {
		await shootSurface(page, THEMING, 'theming-admin.png')
	})
})
