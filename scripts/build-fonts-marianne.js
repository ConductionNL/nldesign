#!/usr/bin/env node
/**
 * Build script for the Marianne (French State typeface) font files.
 *
 * Materializes the 8 Marianne `woff2` files (Light, Regular, Medium, Bold,
 * each with an italic variant) from `@gouvfr/dsfr@1.15.1`
 * (`dist/fonts/Marianne-*.woff2`), the official French government Design
 * System (DSFR) package, into `css/systems/lasuite/fonts/marianne/`.
 *
 * `@gouvfr/dsfr` is a devDependency consumed ONLY by this build script —
 * nothing under js/ or lib/ imports it; nldesign carries no runtime
 * dependency on the package (see openspec/specs/marianne-font/spec.md,
 * "DSFR is a build-only dependency"). Only the woff2 variants are copied —
 * DSFR also ships `.woff` fallbacks, deliberately not bundled (CSP-clean
 * self-hosting needs only the modern woff2 format, per tasks.md #1.3).
 *
 * Marianne is a legally-restricted asset (Etalab Open Licence 2.0, reserved
 * for French State administrations — see MARIANNE-LICENCE.md at the
 * repository root). Bundling the files is lawful for Conduction's French
 * government customers; activating them at runtime additionally requires an
 * admin to tick the `marianne_enabled` acknowledgement gate (see
 * lib/Controller/SettingsController.php and
 * css/systems/lasuite/marianne.css) — this script only materializes the
 * inert files, it never touches that gate.
 *
 * Mirrors scripts/build-fonts.js's graceful-degradation pattern: if
 * `@gouvfr/dsfr` is not installed (it is a build-only devDependency, not
 * always present), this script warns and exits successfully rather than
 * failing the composite `npm run build` — the files this app SHIPS were
 * already committed directly (see css/systems/lasuite/fonts/marianne/),
 * this script only re-materializes them from a fresh DSFR install when one
 * is available.
 */

const fs = require('fs');
const path = require('path');

const DEST_DIR = path.join(__dirname, '..', 'css', 'systems', 'lasuite', 'fonts', 'marianne');
const DSFR_FONTS_DIR = path.join(__dirname, '..', 'node_modules', '@gouvfr', 'dsfr', 'dist', 'fonts');

// The authoritative set is whatever DSFR 1.15.1 ships under this exact name
// pattern — this list is what the app currently bundles, not a hardcoded
// upstream count (openspec/specs/marianne-font/spec.md).
const MARIANNE_WOFF2_FILES = [
	'Marianne-Light.woff2',
	'Marianne-Light_Italic.woff2',
	'Marianne-Regular.woff2',
	'Marianne-Regular_Italic.woff2',
	'Marianne-Medium.woff2',
	'Marianne-Medium_Italic.woff2',
	'Marianne-Bold.woff2',
	'Marianne-Bold_Italic.woff2',
];

if (!fs.existsSync(DEST_DIR)) {
	fs.mkdirSync(DEST_DIR, { recursive: true });
	console.log('✓ Created css/systems/lasuite/fonts/marianne directory');
}

let copiedCount = 0;
if (fs.existsSync(DSFR_FONTS_DIR)) {
	MARIANNE_WOFF2_FILES.forEach((file) => {
		const sourcePath = path.join(DSFR_FONTS_DIR, file);
		const destPath = path.join(DEST_DIR, file);
		if (fs.existsSync(sourcePath)) {
			fs.copyFileSync(sourcePath, destPath);
			copiedCount++;
		}
	});
	console.log(`✓ Copied ${copiedCount} Marianne font files to css/systems/lasuite/fonts/marianne/`);
} else {
	console.log('⚠ Warning: @gouvfr/dsfr not found in node_modules — skipping Marianne font refresh.');
	console.log('  The already-committed files under css/systems/lasuite/fonts/marianne/ are unaffected.');
	console.log('  To refresh from a fresh DSFR release: npm install --no-save @gouvfr/dsfr@1.15.1 && npm run build:fonts:marianne');
}

console.log('\n✅ Marianne font build step complete.');
console.log(`   ${copiedCount} font files copied (0 is expected/harmless when @gouvfr/dsfr is not installed).`);
