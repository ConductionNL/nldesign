#!/usr/bin/env node

/**
 * Exercise the parser-free Markdown and image-format boundaries.
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

function runChecker() {
    return spawnSync(process.execPath, [checkerPath, fixtureRoot], { encoding: 'utf8' });
}

function assertRejected(fileName, content, expectedMessage) {
    const fixturePath = path.join(fixtureRoot, fileName);
    fs.writeFileSync(fixturePath, content);
    const result = runChecker();
    const output = `${result.stdout}\n${result.stderr}`;
    assert.notEqual(result.status, 0, `asset checker accepted ${fileName}:\n${output}`);
    assert.match(output, expectedMessage);
    fs.unlinkSync(fixturePath);
}

try {
    for (const [fileName, content, expectedMessage] of fixtures) {
        assertRejected(fileName, content, expectedMessage);
    }

    assertRejected(
        'inline-local.md',
        '![Diagram](./diagram.png)\n',
        /local Markdown images are disabled/,
    );
    assertRejected(
        'root-local.md',
        '![Diagram](/img/diagram.svg)\n',
        /local Markdown images are disabled/,
    );
    assertRejected(
        'reference-local.md',
        '![Diagram][diagram]\n\n[diagram]: ../diagram.png\n',
        /local Markdown images are disabled/,
    );
    assertRejected(
        'duplicate-reference-local.md',
        [
            '![Diagram][diagram]',
            '',
            '[diagram]: ../diagram.png',
            '[diagram]: https://example.test/diagram.png',
            '',
        ].join('\n'),
        /local Markdown images are disabled/,
    );
    assertRejected(
        'file-protocol.md',
        '![Diagram](file:///tmp/diagram.png)\n',
        /local Markdown images are disabled/,
    );
    assertRejected(
        'pathname-protocol.md',
        '![Diagram](pathname:///img/diagram.svg)\n',
        /local Markdown images are disabled/,
    );

    const allowedMarkdownPath = path.join(fixtureRoot, 'allowed.md');
    fs.writeFileSync(
        allowedMarkdownPath,
        [
            '![Remote](https://example.test/diagram.png)',
            '',
            '`![Code sample](./not-an-image.png)`',
            '',
            '```md',
            '![Fenced sample](./not-an-image.png)',
            '```',
            '',
        ].join('\n'),
    );
    const allowedResult = runChecker();
    assert.equal(
        allowedResult.status,
        0,
        `asset checker rejected safe Markdown:\n${allowedResult.stdout}\n${allowedResult.stderr}`,
    );
} finally {
    fs.rmSync(fixtureRoot, { recursive: true, force: true });
}

console.log('Documentation asset checker enforced image format and Markdown image boundaries.');
