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
			use: {
				storageState: 'tests/e2e/.auth/admin.json',
			},
		},
	],
})
