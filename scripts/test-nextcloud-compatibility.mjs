#!/usr/bin/env node

/**
 * Guard the shared, range-gated Nextcloud CSS adapter contract.
 */

import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const compatibilityRoot = path.join(projectRoot, 'css', 'compatibility');
const expectedProperties = [
	'--font-face',
	'--color-primary',
	'--color-primary-text',
	'--color-primary-hover',
	'--color-primary-element',
	'--color-primary-element-text',
	'--color-primary-element-hover',
];

const adapterPath = path.join(compatibilityRoot, 'nextcloud-core-v1.css');
assert.ok(fs.existsSync(adapterPath), 'the shared Nextcloud core contract must be packaged');

const source = fs.readFileSync(adapterPath, 'utf8');
const properties = Array.from(source.matchAll(/^\s+(--[a-z0-9-]+):/gm), (match) => match[1]);

assert.deepEqual(properties, expectedProperties, 'the shared contract must retain the bounded projection');
assert.match(source, /body:not\(\[data-theme-opendyslexic\]\)/);
assert.match(source, /\[data-theme-light-highcontrast\]/);
assert.match(source, /\[data-theme-dark-highcontrast\]/);
assert.match(source, /@media not \(prefers-contrast: more\)/);
assert.doesNotMatch(source, /(?:!important|url\(|#[a-z][a-z0-9_-]*|\.[a-z][a-z0-9_-]*)/i);

assert.deepEqual(
	fs.readdirSync(compatibilityRoot).filter((file) => file.endsWith('.css')).sort(),
	['nextcloud-core-v1.css'],
	'equivalent supported majors must not grow duplicate CSS contracts'
);

assert.equal(
	fs.existsSync(path.join(projectRoot, 'css', 'theme.css')),
	false,
	'the unversioned load-bearing theme adapter must not return'
);

console.log('Nextcloud shared CSS adapter contract passed.');
