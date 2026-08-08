#!/usr/bin/env node

/**
 * Exercise the documentation build-audit allowlist.
 */

import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const projectRoot = path.dirname(fileURLToPath(import.meta.url));
const checkerPath = path.join(projectRoot, 'check-build-audit.mjs');
const fixtureRoot = fs.mkdtempSync(path.join(os.tmpdir(), 'nldesign-build-audit-'));
const reportPath = path.join(fixtureRoot, 'audit.json');
const knownAdvisory = {
	name: 'image-size',
	severity: 'high',
	url: 'https://github.com/advisories/GHSA-w3rx-r6r6-pgpr',
};

function runCheck(report, expectedStatus, expectedMessage) {
	fs.writeFileSync(reportPath, JSON.stringify(report));
	const result = spawnSync(process.execPath, [checkerPath, reportPath], { encoding: 'utf8' });
	const output = `${result.stdout}\n${result.stderr}`;
	assert.equal(result.status, expectedStatus, output);
	assert.match(output, expectedMessage);
}

try {
	runCheck({ vulnerabilities: {} }, 0, /dependency audit is clean/);
	runCheck({ vulnerabilities: [] }, 1, /vulnerability map/);
	runCheck(
		{
			vulnerabilities: {
				'image-size': { severity: 'high', via: [knownAdvisory] },
				'@docusaurus/mdx-loader': { severity: 'high', via: ['image-size'] },
			},
		},
		0,
		/Accepted 2 package nodes/
	);
	runCheck(
		{
			vulnerabilities: {
				unexpected: {
					severity: 'high',
					via: [{ ...knownAdvisory, url: 'https://github.com/advisories/GHSA-xxxx-xxxx-xxxx' }],
				},
			},
		},
		1,
		/Unexpected documentation build advisories/
	);
	runCheck(
		{
			vulnerabilities: {
				'image-size': { severity: 'critical', via: [{ ...knownAdvisory, severity: 'critical' }] },
			},
		},
		1,
		/Unexpected documentation build advisories/
	);
} finally {
	fs.rmSync(fixtureRoot, { recursive: true, force: true });
}

console.log('Documentation build-audit allowlist rejected unknown and escalated advisories.');
