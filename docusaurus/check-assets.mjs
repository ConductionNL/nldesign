#!/usr/bin/env node

/**
 * Keep documentation inputs within the parser-free image boundary.
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { fromMarkdown } from 'mdast-util-from-markdown';

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

function isLocalImageUrl(url) {
    const normalized = url.trim();
    if (normalized === '' || normalized.startsWith('pathname://')) {
        return true;
    }
    if (/^file:/i.test(normalized)) {
        return true;
    }

    return !normalized.startsWith('//')
        && !/^[a-z][a-z\d+.-]*:/i.test(normalized);
}

function inspectMarkdown(entryPath) {
    const tree = fromMarkdown(fs.readFileSync(entryPath, 'utf8'));
    const definitions = new Map();

    function collectDefinitions(node) {
        if (node.type === 'definition') {
            const identifier = node.identifier.toLowerCase();
            if (!definitions.has(identifier)) {
                definitions.set(identifier, node.url);
            }
        }
        if (Array.isArray(node.children)) {
            for (const child of node.children) {
                collectDefinitions(child);
            }
        }
    }

    function inspectNode(node) {
        let imageUrl = null;
        if (node.type === 'image') {
            imageUrl = node.url;
        } else if (node.type === 'imageReference') {
            imageUrl = definitions.get(node.identifier.toLowerCase()) ?? '';
        }

        if (imageUrl !== null && isLocalImageUrl(imageUrl)) {
            const line = node.position?.start?.line;
            const location = line === undefined ? entryPath : `${entryPath}:${line}`;
            failures.push(
                `local Markdown images are disabled because Docusaurus must not parse image files: ${location}`,
            );
        }

        if (Array.isArray(node.children)) {
            for (const child of node.children) {
                inspectNode(child);
            }
        }
    }

    collectDefinitions(tree);
    inspectNode(tree);
}

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
        if (['.md', '.mdx'].includes(path.extname(entryPath).toLowerCase())) {
            inspectMarkdown(entryPath);
        }
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

console.log(
    'Documentation assets exclude local Markdown images, symlinks, and blocked image extensions/signatures.',
);
