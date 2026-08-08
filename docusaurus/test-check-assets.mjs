#!/usr/bin/env node

/**
 * Exercise extension-independent rejection of vulnerable image formats.
 */

import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const projectRoot = path.dirname(fileURLToPath(import.meta.url));
const checkerPath = path.join(projectRoot, 'check-assets.mjs');
const fixtureRoot = fs.mkdtempSync(path.join(os.tmpdir(), 'nldesign-doc-assets-'));
const fixtures = [
    ['renamed-icns.png', Buffer.from('icns00000000', 'ascii'), /ICNS signature/],
    ['renamed-jxl-stream.png', Buffer.from([0xff, 0x0a, 0x00, 0x00]), /JXL codestream signature/],
    ['renamed-jxl-container.png', Buffer.from('\x00\x00\x00\x0cJXL \x0d\x0a\x87\x0a', 'binary'), /JXL container signature/],
    ['renamed-heic.png', Buffer.from('\x00\x00\x00\x18ftypheic\x00\x00\x00\x00', 'binary'), /HEIF\/HEIC\/AVIF container signature/],
    ['renamed-avif.png', Buffer.from('\x00\x00\x00\x18ftypavif\x00\x00\x00\x00', 'binary'), /HEIF\/HEIC\/AVIF container signature/],
];

try {
    for (const [fileName, content, expectedMessage] of fixtures) {
        const fixturePath = path.join(fixtureRoot, fileName);
        fs.writeFileSync(fixturePath, content);
        const result = spawnSync(process.execPath, [checkerPath, fixtureRoot], { encoding: 'utf8' });
        const output = `${result.stdout}\n${result.stderr}`;
        assert.notEqual(result.status, 0, `asset checker accepted ${fileName}:\n${output}`);
        assert.match(output, expectedMessage);
        fs.unlinkSync(fixturePath);
    }
} finally {
    fs.rmSync(fixtureRoot, { recursive: true, force: true });
}

console.log('Documentation asset checker rejected renamed vulnerable image formats.');
