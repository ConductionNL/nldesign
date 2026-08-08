#!/usr/bin/env node

/**
 * Guard browser integration details that are easy to miss in syntax-only tests.
 */

import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const adminSource = fs.readFileSync(path.join(projectRoot, 'js', 'admin.js'), 'utf8');

const csrfHeader = adminSource.indexOf('requesttoken: getNextcloudRequestToken()');
const nonGetBranch = adminSource.indexOf("if (method !== 'GET')");
assert.notEqual(csrfHeader, -1, 'admin requests must send Nextcloud\'s CSRF token');
assert.ok(
	csrfHeader < nonGetBranch,
	'Nextcloud requires the CSRF header on protected GET as well as mutation requests'
);
assert.match(
	adminSource,
	/window\.OC\?\.requestToken/,
	'the request-token integration must feature-detect the browser capability'
);
assert.match(
	adminSource,
	/typeof window\.OC\?\.generateUrl !== 'function'/,
	'the URL integration must feature-detect the browser capability'
);

assert.doesNotMatch(
	adminSource,
	/%s/,
	'Nextcloud JavaScript translations require named {placeholders}, not PHP-style %s markers'
);
assert.match(
	adminSource,
	/window\.OCP\?\.Toast/,
	'notifications must feature-detect the current Nextcloud toast API'
);
assert.match(
	adminSource,
	/window\.OC\?\.Notification\?\.showTemporary/,
	'notifications must retain a feature-detected legacy fallback'
);
assert.match(
	adminSource,
	/root\.dataset\.runtimeSupported === '1'/,
	'profile activation must consume the server-resolved compatibility capability'
);
assert.doesNotMatch(
	adminSource,
	/(?:runtime|nextcloud)(?:Major|Version)\s*(?:===|!==|>=|<=|>|<)/i,
	'browser integration must not branch on Nextcloud version numbers'
);
assert.match(
	adminSource,
	/uninstall\.textContent\s*=\s*t\('nldesign', 'Uninstall'\)/,
	'the uninstall action must keep a visible accessible label'
);

console.log('Admin browser integration contract passed.');
