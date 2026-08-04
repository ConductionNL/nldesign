/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Playwright config for nldesign.
 * Base URL: http://localhost:8080 (override with NEXTCLOUD_URL env var).
 * globalSetup logs in once and saves session to tests/e2e/.auth/admin.json.
 */
import { defineConfig } from '@playwright/test'
import * as path from 'path'

export default defineConfig({
	testDir: './tests/e2e',
	globalSetup: path.resolve(__dirname, 'tests/e2e/global-setup.ts'),
	timeout: 30_000,
	expect: { timeout: 10_000 },
	fullyParallel: false,
	retries: 0,
	workers: 1,
	reporter: [
		['list'],
		['html', { open: 'never', outputFolder: 'tests/e2e/playwright-report' }],
	],
	outputDir: 'tests/e2e/test-results',

	use: {
		baseURL: process.env.NEXTCLOUD_URL || 'http://localhost:8080',
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
		storageState: 'tests/e2e/.auth/admin.json',
	},

	projects: [
		{
			name: 'chromium',
			// Visual specs run only under the opt-in `visual` project (GAP-5).
			testIgnore: ['**/visual/**'],
			use: {
				storageState: 'tests/e2e/.auth/admin.json',
			},
		},
		// Visual-regression project (GAP-5). Opt-in / non-gating:
		//   PW_VISUAL=1 npx playwright test --project visual
		//   PW_VISUAL=1 npx playwright test --project visual --update-snapshots
		// Fixed viewport + authenticated session => deterministic shots.
		// Baselines live in tests/e2e/visual/*-snapshots/ and ARE committed.
		//
		// PLATFORM CAVEAT: PNG baselines are host-font/GPU specific, so a CI
		// Linux runner will not byte-match a dev-container baseline; the visual
		// project must regenerate its baselines in-CI before it can gate.
		//
		// The env gate is what makes "opt-in" true. It was not: the project was
		// declared unconditionally, and `npx playwright test` — which is exactly
		// what the shared CI job runs, with no `--project` — runs EVERY declared
		// project. `testIgnore: ['**/visual/**']` on chromium only stops chromium
		// picking the files up; it does nothing about the visual project running
		// them itself. So the first CI execution of this suite failed on
		// `theming-admin.png` with "Expected an image 1751px by 800px, received
		// 1280px by 800px" — a baseline captured on another host, gating a job
		// its own documentation says it cannot gate (run 30889958278).
		//
		// Not a skip and not a deleted test: with PW_VISUAL=1 the project is
		// declared and runs exactly as before. What changed is that the default
		// invocation no longer silently includes it.
		...(process.env.PW_VISUAL === '1'
			? [{
				name: 'visual',
				testMatch: /visual\/.*\.visual\.spec\.ts$/,
				use: {
					viewport: { width: 1280, height: 800 },
					storageState: 'tests/e2e/.auth/admin.json',
				},
				timeout: 90_000,
			}]
			: []),
	],
})
