#!/usr/bin/env node

/**
 * Exercise security- and integrity-critical validator failure paths.
 */

import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const validatorPath = path.join(projectRoot, 'scripts', 'validate-token-sets.mjs');
const fixtureRoot = fs.mkdtempSync(path.join(os.tmpdir(), 'nldesign-validator-'));
const manifestSource = path.join(projectRoot, 'token-sets.json');
const manifestTarget = path.join(fixtureRoot, 'token-sets.json');
const stylesheetTarget = path.join(fixtureRoot, 'css', 'tokens', 'amsterdam.css');
const darkStylesheetTarget = path.join(fixtureRoot, 'css', 'tokens', 'senerawa.css');

function runValidator(expectedMessage) {
	const result = spawnSync(
		process.execPath,
		[validatorPath, fixtureRoot],
		{ encoding: 'utf8' }
	);
	const output = `${result.stdout}\n${result.stderr}`;
	assert.notEqual(result.status, 0, `validator unexpectedly accepted fixture:\n${output}`);
	assert.match(output, expectedMessage);
}

try {
	fs.mkdirSync(path.join(fixtureRoot, 'css'), { recursive: true });
	fs.cpSync(path.join(projectRoot, 'css', 'tokens'), path.join(fixtureRoot, 'css', 'tokens'), {
		recursive: true,
		errorOnExist: true,
	});
	fs.copyFileSync(manifestSource, manifestTarget);

	const originalManifest = fs.readFileSync(manifestTarget, 'utf8');
	const originalStylesheet = fs.readFileSync(stylesheetTarget, 'utf8');
	const originalDarkStylesheet = fs.readFileSync(darkStylesheetTarget, 'utf8');

	const catalogue = JSON.parse(originalManifest);
	catalogue.default_profile = 'amsterdam';
	fs.writeFileSync(manifestTarget, `${JSON.stringify(catalogue, null, 2)}\n`);
	runValidator(/default_profile must be null/);
	fs.writeFileSync(manifestTarget, originalManifest);

	fs.writeFileSync(
		stylesheetTarget,
		originalStylesheet.replace(
			/\n}/,
			'\n\t--nldesign-color-primary: #ec0000;\n}'
		)
	);
	runValidator(/duplicate ready declaration/);

	fs.writeFileSync(
		stylesheetTarget,
		originalStylesheet.replace(
			/\n}/,
			'\n\t--nldesign-unprojected: #000000;\n}'
		)
	);
	runValidator(/unprojected property/);

	fs.writeFileSync(
		stylesheetTarget,
		originalStylesheet.replace(
			/\n}/,
			'\n\tb\\\\61ckground: u\\\\72l(https://example.invalid/x);\n}'
		)
	);
	runValidator(/must not use CSS escapes/);

	fs.writeFileSync(
		stylesheetTarget,
		originalStylesheet.replace(
			'"Fira Sans",',
			'"Fira Sans", url("../../img/logos/profile.svg"),'
		)
	);
	runValidator(/ready profile must not contain URLs/);

	fs.writeFileSync(
		stylesheetTarget,
		originalStylesheet.replace(
			'--nldesign-color-primary-text: #ffffff;',
			'--nldesign-color-primary-text: #ec0000;'
		)
	);
	runValidator(/primary\/text contrast is below 4\.5:1/);
	fs.writeFileSync(stylesheetTarget, originalStylesheet);

	fs.writeFileSync(
		darkStylesheetTarget,
		originalDarkStylesheet.replace(/\n@media \(prefers-color-scheme: dark\) \{[\s\S]*\}\n$/, '\n')
	);
	runValidator(/declares \[data-theme-dark\] but not \[data-theme-default\]/);

	fs.writeFileSync(
		darkStylesheetTarget,
		originalDarkStylesheet.replace(
			/(@media[\s\S]*?--nldesign-color-primary: )#efd6ac;/,
			'$1#ffffff;'
		)
	);
	runValidator(/explicit-dark and system-dark\/default values differ/);
	fs.writeFileSync(darkStylesheetTarget, originalDarkStylesheet);

	fs.writeFileSync(path.join(fixtureRoot, 'css', 'tokens', 'unexpected.map'), '{}');
	runValidator(/unexpected token-directory entry/);
	fs.unlinkSync(path.join(fixtureRoot, 'css', 'tokens', 'unexpected.map'));
} finally {
	fs.rmSync(fixtureRoot, { recursive: true, force: true });
}

console.log('Profile catalogue validator rejected all adversarial fixtures.');
