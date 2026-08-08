#!/usr/bin/env node

/**
 * Accept only the explicitly mitigated image-size build advisories.
 */

import fs from 'node:fs';
import { spawnSync } from 'node:child_process';

const knownAdvisories = new Set([
	'https://github.com/advisories/GHSA-w3rx-r6r6-pgpr',
	'https://github.com/advisories/GHSA-5p2g-fcmc-qvqq',
]);

if (process.argv.length > 3) {
	console.error('Usage: check-build-audit.mjs [audit-report.json]');
	process.exit(2);
}

function loadAuditReport() {
	if (process.argv[2]) {
		return JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
	}

	const executable = process.platform === 'win32' ? 'npm.cmd' : 'npm';
	const result = spawnSync(executable, ['audit', '--json'], {
		encoding: 'utf8',
		maxBuffer: 16 * 1024 * 1024,
	});
	if (result.error || (result.status !== 0 && result.status !== 1)) {
		throw result.error || new Error(`npm audit failed with status ${result.status}`);
	}

	return JSON.parse(result.stdout);
}

const report = loadAuditReport();
if (!report || typeof report !== 'object' || Array.isArray(report)) {
	throw new Error('npm audit did not return an object.');
}
if (report.error) {
	throw new Error(`npm audit failed: ${report.error.summary || 'unknown error'}`);
}

const vulnerabilities = report.vulnerabilities;
if (!vulnerabilities || typeof vulnerabilities !== 'object' || Array.isArray(vulnerabilities)) {
	throw new Error('npm audit did not return a vulnerability map.');
}
const decisions = new Map();

function isKnownImageSizeChain(packageName, visiting = new Set()) {
	if (decisions.has(packageName)) {
		return decisions.get(packageName);
	}
	if (visiting.has(packageName)) {
		return false;
	}

	const vulnerability = vulnerabilities[packageName];
	if (!vulnerability
		|| vulnerability.severity !== 'high'
		|| !Array.isArray(vulnerability.via)
		|| vulnerability.via.length === 0
	) {
		decisions.set(packageName, false);
		return false;
	}

	const nextVisiting = new Set(visiting);
	nextVisiting.add(packageName);
	const known = vulnerability.via.every((cause) => {
		if (typeof cause === 'string') {
			return isKnownImageSizeChain(cause, nextVisiting);
		}

		return cause
			&& typeof cause === 'object'
			&& cause.name === 'image-size'
			&& cause.severity === 'high'
			&& knownAdvisories.has(cause.url);
	});
	decisions.set(packageName, known);
	return known;
}

const packageNames = Object.keys(vulnerabilities);
const unexpected = packageNames.filter((packageName) => !isKnownImageSizeChain(packageName));
if (unexpected.length > 0) {
	console.error(`Unexpected documentation build advisories: ${unexpected.sort().join(', ')}`);
	process.exit(1);
}

console.log(
	packageNames.length === 0
		? 'Documentation build dependency audit is clean.'
		: `Accepted ${packageNames.length} package nodes caused only by the two explicitly mitigated image-size advisories.`
);
