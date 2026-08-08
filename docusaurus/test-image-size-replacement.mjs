#!/usr/bin/env node

/**
 * Prove that Docusaurus resolves the local parser-free compatibility package.
 */

import assert from 'node:assert/strict';
import fs from 'node:fs';
import { createRequire } from 'node:module';

import { imageSizeFromFile } from 'image-size/fromFile';

const require = createRequire(import.meta.url);
const packagePath = require.resolve('image-size/package.json');
const packageMetadata = JSON.parse(fs.readFileSync(packagePath, 'utf8'));
const mdxLoaderEntry = require.resolve('@docusaurus/mdx-loader');
const requireFromMdxLoader = createRequire(mdxLoaderEntry);
const packagePathFromMdxLoader = requireFromMdxLoader.resolve('image-size/package.json');

assert.equal(packageMetadata.version, '2.0.3-nldesign.1');
assert.equal(packageMetadata.nldesignParserFreeReplacement, true);
assert.equal(
    fs.realpathSync(packagePathFromMdxLoader),
    fs.realpathSync(packagePath),
    'Docusaurus must not resolve a nested image-size parser package',
);
assert.deepEqual(
    await imageSizeFromFile('/path/that/must/not/be/read'),
    {},
    'the compatibility package must not inspect files',
);

console.log('Docusaurus resolves the parser-free NL Design image-size replacement.');
