#!/usr/bin/env node

/**
 * Keep the Docusaurus image parser away from formats with known loop bugs.
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const projectRoot = path.dirname(fileURLToPath(import.meta.url));
const requestedRoots = process.argv.slice(2);
const sourceRoots = requestedRoots.length > 0
    ? requestedRoots.map((sourceRoot) => path.resolve(sourceRoot))
    : [
        path.resolve(projectRoot, '..', 'docs'),
        path.join(projectRoot, 'static'),
        path.join(projectRoot, 'src'),
    ];
const blockedExtensions = new Set(['.avif', '.heic', '.heif', '.icns', '.jxl']);
const blockedFtypBrands = new Set(['avif', 'heic', 'heix', 'hevc', 'hevx', 'mif1', 'msf1']);
const failures = [];

function blockedFormatFromHeader(entryPath) {
    const descriptor = fs.openSync(entryPath, 'r');
    try {
        const header = Buffer.alloc(16);
        const bytesRead = fs.readSync(descriptor, header, 0, header.length, 0);
        const bytes = header.subarray(0, bytesRead);
        if (bytes.subarray(0, 4).toString('ascii') === 'icns') {
            return 'ICNS';
        }
        if (bytes.length >= 2 && bytes[0] === 0xff && bytes[1] === 0x0a) {
            return 'JXL codestream';
        }

        const boxType = bytes.subarray(4, 8).toString('ascii');
        if (boxType === 'JXL ') {
            return 'JXL container';
        }
        if (boxType === 'ftyp'
            && blockedFtypBrands.has(bytes.subarray(8, 12).toString('ascii'))
        ) {
            return 'HEIF/HEIC/AVIF container';
        }
    } finally {
        fs.closeSync(descriptor);
    }

    return null;
}

function inspect(entryPath) {
	const stat = fs.lstatSync(entryPath);
	if (stat.isSymbolicLink()) {
		failures.push(`documentation source must not be a symlink: ${entryPath}`);
		return;
	}

	if (stat.isDirectory()) {
		for (const entry of fs.readdirSync(entryPath)) {
			inspect(path.join(entryPath, entry));
		}
		return;
	}

    if (stat.isFile()) {
        if (blockedExtensions.has(path.extname(entryPath).toLowerCase())) {
            failures.push(`blocked image extension in documentation source: ${entryPath}`);
        }
        const blockedFormat = blockedFormatFromHeader(entryPath);
        if (blockedFormat !== null) {
            failures.push(`blocked ${blockedFormat} signature in documentation source: ${entryPath}`);
        }
    }
}

for (const sourceRoot of sourceRoots) {
	if (fs.existsSync(sourceRoot)) {
		inspect(sourceRoot);
	}
}

if (failures.length > 0) {
	console.error('Documentation asset validation failed:');
	for (const failure of failures) {
		console.error(`  - ${failure}`);
	}
	process.exit(1);
}

console.log('Documentation assets exclude symlinks and blocked image extensions/signatures.');
