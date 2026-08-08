#!/usr/bin/env node

/**
 * Copy the pinned Fira Sans package into the distributable app.
 */

const fs = require('fs');
const path = require('path');

const projectRoot = path.join(__dirname, '..');
const fontsDirectory = path.join(projectRoot, 'css', 'fonts');
const fontsCssPath = path.join(projectRoot, 'css', 'fonts.css');
const packageDirectory = path.join(
	projectRoot,
	'node_modules',
	'@fontsource',
	'fira-sans'
);
const sourceDirectory = path.join(packageDirectory, 'files');
const fontFiles = [
	'fira-sans-latin-400-normal.woff2',
	'fira-sans-latin-400-normal.woff',
	'fira-sans-latin-400-italic.woff2',
	'fira-sans-latin-400-italic.woff',
	'fira-sans-latin-700-normal.woff2',
	'fira-sans-latin-700-normal.woff',
	'fira-sans-latin-700-italic.woff2',
	'fira-sans-latin-700-italic.woff',
];
const licenseSource = path.join(packageDirectory, 'LICENSE');
const licenseTarget = path.join(fontsDirectory, 'OFL-1.1.txt');
const maxFontBytes = 2 * 1024 * 1024;
const minFontBytes = 1024;

function assertRegularDirectory(directoryPath, label) {
	const stat = fs.lstatSync(directoryPath);
	if (!stat.isDirectory() || stat.isSymbolicLink()) {
		throw new Error(`${label} must be a regular directory: ${directoryPath}`);
	}
}

function assertRegularFile(filePath, label, maxBytes) {
	const stat = fs.lstatSync(filePath);
	if (!stat.isFile() || stat.isSymbolicLink() || stat.size > maxBytes) {
		throw new Error(`${label} must be a bounded regular file: ${filePath}`);
	}
}

function assertSafeOutput(filePath) {
	if (!fs.existsSync(filePath)) {
		return;
	}

	const stat = fs.lstatSync(filePath);
	if (!stat.isFile() || stat.isSymbolicLink()) {
		throw new Error(`Generated output target must be a regular file: ${filePath}`);
	}
}

function assertFontSignature(filePath) {
	const stat = fs.lstatSync(filePath);
	if (stat.size < minFontBytes) {
		throw new Error(`Font source is unexpectedly small: ${filePath}`);
	}

	const descriptor = fs.openSync(filePath, 'r');
	try {
		const signature = Buffer.alloc(4);
		if (fs.readSync(descriptor, signature, 0, signature.length, 0) !== signature.length) {
			throw new Error(`Could not read the font signature: ${filePath}`);
		}
		const expected = filePath.endsWith('.woff2') ? 'wOF2' : 'wOFF';
		if (signature.toString('ascii') !== expected) {
			throw new Error(`Unexpected font signature in ${filePath}`);
		}
	} finally {
		fs.closeSync(descriptor);
	}
}

if (!fs.existsSync(sourceDirectory)) {
	throw new Error('Missing @fontsource/fira-sans. Run npm ci before npm run build.');
}
if (!fs.existsSync(licenseSource)) {
	throw new Error('The Fira Sans package is missing its required license notice.');
}

assertRegularDirectory(packageDirectory, 'Font package');
assertRegularDirectory(sourceDirectory, 'Font source directory');

const missingFiles = fontFiles.filter(
	(file) => !fs.existsSync(path.join(sourceDirectory, file))
);
if (missingFiles.length > 0) {
	throw new Error(`The font package is missing expected files: ${missingFiles.join(', ')}`);
}

for (const file of fontFiles) {
	const source = path.join(sourceDirectory, file);
	assertRegularFile(source, 'Font source', maxFontBytes);
	assertFontSignature(source);
}
assertRegularFile(licenseSource, 'Font licence', 64 * 1024);
const licenseText = fs.readFileSync(licenseSource, 'utf8');
if (!licenseText.includes('SIL OPEN FONT LICENSE Version 1.1')) {
	throw new Error('The Fira Sans package licence is not the expected SIL OFL 1.1 notice.');
}

const cssDirectory = path.dirname(fontsCssPath);
if (fs.lstatSync(cssDirectory).isSymbolicLink()) {
	throw new Error('The CSS output directory must not be a symbolic link.');
}
assertSafeOutput(fontsCssPath);

fs.mkdirSync(fontsDirectory, { recursive: true });
if (fs.lstatSync(fontsDirectory).isSymbolicLink()) {
	throw new Error('The font output directory must not be a symbolic link.');
}

const expectedOutputs = new Set([...fontFiles, path.basename(licenseTarget)]);
const unexpectedOutputs = fs.readdirSync(fontsDirectory)
	.filter((file) => !expectedOutputs.has(file));
if (unexpectedOutputs.length > 0) {
	throw new Error(`Unexpected generated font output: ${unexpectedOutputs.join(', ')}`);
}

for (const file of fontFiles) {
	const target = path.join(fontsDirectory, file);
	assertSafeOutput(target);
	fs.copyFileSync(path.join(sourceDirectory, file), target);
}
assertSafeOutput(licenseTarget);
fs.copyFileSync(licenseSource, licenseTarget);

const fontsCss = `/**
 * Fira Sans font files bundled with the app.
 */

@font-face {
	font-family: "Fira Sans";
	src: url("fonts/fira-sans-latin-400-normal.woff2") format("woff2"),
		url("fonts/fira-sans-latin-400-normal.woff") format("woff");
	font-weight: 400;
	font-style: normal;
	font-display: swap;
}

@font-face {
	font-family: "Fira Sans";
	src: url("fonts/fira-sans-latin-400-italic.woff2") format("woff2"),
		url("fonts/fira-sans-latin-400-italic.woff") format("woff");
	font-weight: 400;
	font-style: italic;
	font-display: swap;
}

@font-face {
	font-family: "Fira Sans";
	src: url("fonts/fira-sans-latin-700-normal.woff2") format("woff2"),
		url("fonts/fira-sans-latin-700-normal.woff") format("woff");
	font-weight: 700;
	font-style: normal;
	font-display: swap;
}

@font-face {
	font-family: "Fira Sans";
	src: url("fonts/fira-sans-latin-700-italic.woff2") format("woff2"),
		url("fonts/fira-sans-latin-700-italic.woff") format("woff");
	font-weight: 700;
	font-style: italic;
	font-display: swap;
}
`;

fs.writeFileSync(fontsCssPath, fontsCss);
console.log(
	`Copied ${fontFiles.length} bundled font files, their OFL notice, and regenerated css/fonts.css.`
);
