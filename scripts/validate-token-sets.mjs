#!/usr/bin/env node

/**
 * Validate the compiled profile catalogue and its CSS inventory.
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const defaultProjectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
if (process.argv.length > 3) {
	console.error('Usage: validate-token-sets.mjs [project-root]');
	process.exit(2);
}

let projectRoot;
try {
	projectRoot = fs.realpathSync(process.argv[2] || defaultProjectRoot);
} catch (error) {
	console.error(`Could not resolve the project root: ${error.message}`);
	process.exit(1);
}
const manifestPath = path.join(projectRoot, 'token-sets.json');
const tokenDirectory = path.join(projectRoot, 'css', 'tokens');
const idPattern = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;
const colorPattern = /^#[0-9a-f]{6}$/i;
const assetPattern = /^img\/(?:logos|backgrounds)\/[a-zA-Z0-9._-]+\.(?:svg|png|jpe?g|webp)$/i;
const cssAssetPattern = /^\.\.\/\.\.\/img\/(?:logos|backgrounds)\/[a-zA-Z0-9._-]+\.(?:svg|png|jpe?g|webp)$/i;
const unresolvedTokenPattern = /\{[a-zA-Z0-9._-]+\}/;
const unsafeUrlPattern = /url\([^)]*(?:https?:|data:|file:|javascript:)/i;
const bannedCssPattern = /@import|expression\s*\(|-moz-binding\s*:|behavior\s*:/i;
const textControlPattern = /[\u0000-\u001f\u007f]/u;
const manifestSchema = 'nldesign-profile-catalogue/v1';
const projectionId = 'nextcloud-core-v1';
const requiredProjectionProperties = [
	'--nldesign-color-primary',
	'--nldesign-color-primary-text',
	'--nldesign-color-primary-hover',
	'--nldesign-font-family',
];
const darkProjectionProperties = requiredProjectionProperties.filter(
	(property) => property !== '--nldesign-font-family'
);
const allowedCatalogueFields = new Set(['schema', 'default_profile', 'profiles']);
const allowedProfileFields = new Set(['id', 'name', 'description', 'status', 'projection', 'theming']);
const allowedReadyProperties = new Set(requiredProjectionProperties);
// Nextcloud writes five theme states onto <body>, not two. `data-theme-default`
// follows the operating system and is what every account has until someone opens
// Appearance settings, so a profile that declares only `[data-theme-dark]` leaves
// the majority of dark-mode users with light values projected onto a dark shell.
// `:root` already answers the light half of the default case.
const allowedReadySelectors = new Set([':root', '[data-theme-dark]', '[data-theme-default]']);

// The only at-rule a ready profile may use. Anything else — imports, font faces,
// container queries, supports — stays banned.
const allowedReadyAtRulePattern = /^@media\s*\(\s*prefers-color-scheme\s*:\s*dark\s*\)$/;
const nextcloudOwnedPropertyPattern = /^--(?:color-|font-face$|border-radius(?:-|$)|body-|header-|default-|animation-)/;
const maxIdLength = 64;
const maxNameLength = 160;
const maxDescriptionLength = 500;
const maxManifestBytes = 256 * 1024;
const maxAssetBytes = 2 * 1024 * 1024;
const maxReadyStylesheetBytes = 32 * 1024;
// Four light-mode declarations plus three dark colour overrides in each of the
// explicit-dark and system-dark/default branches. The font stack is invariant.
const maxReadyDeclarations = 10;
const allowedThemingFields = new Set([
	'primary_color',
	'background_color',
	'logo',
	'background',
]);
const errors = [];
const readyIds = new Set();
const readyPrimaryColors = new Map();

let tokenRoot;
try {
	const tokenDirectoryStat = fs.lstatSync(tokenDirectory);
	if (!tokenDirectoryStat.isDirectory() || tokenDirectoryStat.isSymbolicLink()) {
		throw new Error('css/tokens must be a regular directory');
	}
	const resolvedTokenDirectory = fs.realpathSync(tokenDirectory);
	if (!resolvedTokenDirectory.startsWith(`${projectRoot}${path.sep}`)) {
		throw new Error('css/tokens escapes the project root');
	}
	tokenRoot = `${resolvedTokenDirectory}${path.sep}`;
} catch (error) {
	console.error(`Could not validate the token directory: ${error.message}`);
	process.exit(1);
}

function readModeProperties(css) {
	const root = new Map();
	const darkOverrides = new Map();
	const defaultDarkOverrides = new Map();
	const seenDeclarations = new Set();
	const duplicateDeclarations = [];
	let hasDarkMode = false;
	let hasDefaultDarkMode = false;

	for (const block of css.matchAll(/([^{}]+)\{([^{}]*)\}/g)) {
		const selector = block[1].trim();
		if (!allowedReadySelectors.has(selector)) {
			continue;
		}

		const destination = selector === ':root'
			? root
			: selector === '[data-theme-dark]'
				? darkOverrides
				: defaultDarkOverrides;
		hasDarkMode ||= selector === '[data-theme-dark]';
		hasDefaultDarkMode ||= selector === '[data-theme-default]';
		for (const declaration of block[2].matchAll(/(--[a-zA-Z0-9-]+)\s*:\s*([^;]+);/g)) {
			const declarationKey = `${selector}\u0000${declaration[1]}`;
			if (seenDeclarations.has(declarationKey)) {
				duplicateDeclarations.push(`${selector} ${declaration[1]}`);
			}
			seenDeclarations.add(declarationKey);
			destination.set(declaration[1], declaration[2].trim());
		}
	}

	const modes = [['light', root]];
	if (hasDarkMode) {
		modes.push(['explicit dark', new Map([...root, ...darkOverrides])]);
	}
	if (hasDefaultDarkMode) {
		modes.push(['system dark', new Map([...root, ...defaultDarkOverrides])]);
	}

	return { modes, root, darkOverrides, defaultDarkOverrides, duplicateDeclarations };
}

function resolveHexColor(property, properties, seen = new Set()) {
	if (seen.has(property)) {
		return null;
	}
	seen.add(property);

	const value = properties.get(property);
	if (typeof value !== 'string') {
		return null;
	}
	if (colorPattern.test(value)) {
		return value.toLowerCase();
	}

	const reference = value.match(/^var\((--[a-zA-Z0-9-]+)\)$/);
	return reference ? resolveHexColor(reference[1], properties, seen) : null;
}

function relativeLuminance(color) {
	const channels = [1, 3, 5]
		.map((offset) => Number.parseInt(color.slice(offset, offset + 2), 16) / 255)
		.map((channel) => channel <= 0.04045
			? channel / 12.92
			: ((channel + 0.055) / 1.055) ** 2.4);

	return (0.2126 * channels[0]) + (0.7152 * channels[1]) + (0.0722 * channels[2]);
}

function contrastRatio(first, second) {
	const luminances = [relativeLuminance(first), relativeLuminance(second)]
		.sort((left, right) => right - left);
	return (luminances[0] + 0.05) / (luminances[1] + 0.05);
}

function report(condition, message) {
	if (!condition) {
		errors.push(message);
	}
}

let catalogue;
try {
	const manifestStat = fs.lstatSync(manifestPath);
	if (!manifestStat.isFile() || manifestStat.isSymbolicLink() || manifestStat.size > maxManifestBytes) {
		throw new Error('manifest must be a regular file no larger than 256 KiB');
	}
	catalogue = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
} catch (error) {
	console.error(`Could not parse token-sets.json: ${error.message}`);
	process.exit(1);
}

report(
	catalogue && typeof catalogue === 'object' && !Array.isArray(catalogue),
	'token-sets.json must contain a catalogue object.'
);
if (!catalogue || typeof catalogue !== 'object' || Array.isArray(catalogue)) {
	catalogue = {};
}

report(catalogue.schema === manifestSchema, `catalogue schema must be ${manifestSchema}.`);
for (const field of Object.keys(catalogue)) {
	report(allowedCatalogueFields.has(field), `catalogue contains unsupported field ${field}.`);
}
const defaultProfile = catalogue.default_profile;
report(
	defaultProfile === null,
	'catalogue default_profile must be null so activation always requires an administrator choice.'
);

let manifest = catalogue.profiles;
report(Array.isArray(manifest), 'catalogue profiles must be a JSON array.');
if (!Array.isArray(manifest)) {
	manifest = [];
}

const ids = new Set();
for (const [index, entry] of manifest.entries()) {
	const prefix = `entry ${index}`;
	report(entry && typeof entry === 'object' && !Array.isArray(entry), `${prefix} must be an object.`);
	if (!entry || typeof entry !== 'object' || Array.isArray(entry)) {
		continue;
	}

	const { id, name, description, status, projection, theming } = entry;
	for (const field of Object.keys(entry)) {
		report(allowedProfileFields.has(field), `${prefix} contains unsupported field ${field}.`);
	}
	report(
		typeof id === 'string' && id.length <= maxIdLength && idPattern.test(id),
		`${prefix} has an invalid id.`
	);
	if (typeof id !== 'string' || id.length > maxIdLength || !idPattern.test(id)) {
		continue;
	}

	report(!ids.has(id), `duplicate profile id: ${id}`);
	ids.add(id);
	report(status === 'ready' || status === 'source-only', `${id}: invalid profile status.`);
	if (status === 'ready') {
		readyIds.add(id);
		report(projection === projectionId, `${id}: ready profile must declare ${projectionId}.`);
	} else if (status === 'source-only') {
		report(projection === undefined, `${id}: source-only profile must not declare a projection.`);
		report(theming === undefined, `${id}: source-only profile must not declare Theming hints.`);
	}
	report(
		typeof name === 'string'
			&& name.trim() !== ''
			&& Buffer.byteLength(name, 'utf8') <= maxNameLength
			&& !textControlPattern.test(name),
		`${id}: name must be a non-empty string no longer than ${maxNameLength} characters.`
	);
	report(
		typeof description === 'string'
			&& description.trim() !== ''
			&& Buffer.byteLength(description, 'utf8') <= maxDescriptionLength
			&& !textControlPattern.test(description),
		`${id}: description must be a non-empty string no longer than ${maxDescriptionLength} characters.`
	);
	const cssPath = path.join(tokenDirectory, `${id}.css`);
	report(fs.existsSync(cssPath), `${id}: compiled CSS file is missing.`);
	if (fs.existsSync(cssPath)) {
		const fileStat = fs.lstatSync(cssPath);
		report(fileStat.isFile() && !fileStat.isSymbolicLink(), `${id}: compiled CSS must be a regular file.`);
		if (fileStat.isFile() && !fileStat.isSymbolicLink()) {
			const realCssPath = fs.realpathSync(cssPath);
			report(realCssPath.startsWith(tokenRoot), `${id}: compiled CSS escapes the token directory.`);

			const css = fs.readFileSync(cssPath, 'utf8');
			const executableCss = css.replace(/\/\*[\s\S]*?\*\//g, '');
			report(Buffer.byteLength(css, 'utf8') <= 96 * 1024, `${id}: compiled CSS exceeds 96 KiB.`);
			report(!unresolvedTokenPattern.test(executableCss), `${id}: compiled CSS contains an unresolved token placeholder.`);
			report(!unsafeUrlPattern.test(executableCss), `${id}: compiled CSS contains a remote, data, or executable URL.`);
			report(!bannedCssPattern.test(executableCss), `${id}: compiled CSS contains a banned construct.`);
			if (status === 'ready') {
				report(
					Buffer.byteLength(css, 'utf8') <= maxReadyStylesheetBytes,
					`${id}: ready projection exceeds the 32 KiB runtime budget.`
				);
				report(!/!important/i.test(executableCss), `${id}: ready profile must not use !important.`);
				report(!/\\/.test(executableCss), `${id}: ready profile must not use CSS escapes.`);
				report(!/url\s*\(/i.test(executableCss), `${id}: ready profile must not contain URLs.`);
				for (const atRule of executableCss.matchAll(/@[^{]*/g)) {
					report(
						allowedReadyAtRulePattern.test(atRule[0].trim()),
						`${id}: ready profile uses an unsupported at-rule: ${atRule[0].trim()}`
					);
				}

				// A dark palette that only answers [data-theme-dark] never reaches an
				// account on the default theme with a dark operating system — the
				// common case. Require the default branch alongside it.
				const declaresDark = /\[data-theme-dark\]\s*\{/.test(executableCss);
				const declaresDefaultDark = /@media\s*\(\s*prefers-color-scheme\s*:\s*dark\s*\)\s*\{[^}]*\[data-theme-default\]\s*\{/.test(
					executableCss
				);
				report(
					!declaresDark || declaresDefaultDark,
					`${id}: declares [data-theme-dark] but not [data-theme-default] under `
						+ '@media (prefers-color-scheme: dark), so default-theme accounts on a '
						+ 'dark system keep the light palette.'
				);

				for (const block of executableCss.matchAll(/(?:^|}|\{)\s*([^{}@]+?)\s*\{/g)) {
					for (const selector of block[1].split(',').map((value) => value.trim())) {
						if (selector === '') {
							continue;
						}

						report(
							allowedReadySelectors.has(selector),
							`${id}: ready profile uses an unsupported selector: ${selector}`
						);
					}
				}

				// The semantic surface remains the four allowed projection properties.
				// Count raw declarations as a separate size bound: dark colours must
				// legitimately repeat for explicit-dark and system-dark/default users.
				let declarationCount = 0;
				const projectedProperties = new Set();
				for (const declaration of executableCss.matchAll(/(?:^|[;{])\s*([a-zA-Z-][a-zA-Z0-9-]*)\s*:/g)) {
					declarationCount += 1;
					const property = declaration[1];
					projectedProperties.add(property);
					report(property.startsWith('--'), `${id}: ready profile contains a non-token property: ${property}`);
					report(
						allowedReadyProperties.has(property),
						`${id}: ready profile contains an unprojected property: ${property}`
					);
					report(
						!nextcloudOwnedPropertyPattern.test(property),
						`${id}: ready profile writes a Nextcloud-owned property directly: ${property}`
					);
				}
				report(
					projectedProperties.size <= allowedReadyProperties.size,
					`${id}: ready profile exceeds the four-property semantic projection budget.`
				);
				report(
					declarationCount <= maxReadyDeclarations,
					`${id}: ready profile exceeds the ${maxReadyDeclarations}-declaration projection budget.`
				);

				for (const property of requiredProjectionProperties) {
					const propertyPattern = new RegExp(`${property.replaceAll('-', '\\-')}\\s*:\\s*[^;]+;`);
					report(
						propertyPattern.test(executableCss),
						`${id}: ready profile is missing projection property ${property}.`
					);
				}

				const projection = readModeProperties(executableCss);
				for (const duplicate of projection.duplicateDeclarations) {
					report(false, `${id}: duplicate ready declaration: ${duplicate}`);
				}
				for (const property of requiredProjectionProperties) {
					report(
						projection.root.has(property),
						`${id}: light projection must declare ${property} exactly once in :root.`
					);
				}
				if (declaresDark) {
					const explicitDark = new Map([...projection.root, ...projection.darkOverrides]);
					const systemDark = new Map([...projection.root, ...projection.defaultDarkOverrides]);
					for (const property of darkProjectionProperties) {
						report(
							projection.darkOverrides.has(property),
							`${id}: explicit dark projection must override ${property}.`
						);
						report(
							projection.defaultDarkOverrides.has(property),
							`${id}: system-dark/default projection must override ${property}.`
						);

						const explicitValue = resolveHexColor(property, explicitDark);
						const systemValue = resolveHexColor(property, systemDark);
						report(
							explicitValue !== null && explicitValue === systemValue,
							`${id}: explicit-dark and system-dark/default values differ for ${property}.`
						);
					}
				}

				const fontFamily = projection.root.get('--nldesign-font-family');
				report(
					typeof fontFamily === 'string' && /(?:^|[,"'\s])Fira Sans(?:$|[,"'\s])/.test(fontFamily),
					`${id}: ready profile must include the bundled Fira Sans fallback in its font stack.`
				);

				for (const [mode, properties] of projection.modes) {
					const primary = resolveHexColor('--nldesign-color-primary', properties);
					const primaryText = resolveHexColor('--nldesign-color-primary-text', properties);
					const primaryHover = resolveHexColor('--nldesign-color-primary-hover', properties);
					report(
						primary !== null && primaryText !== null && primaryHover !== null,
						`${id}: ${mode} primary projection colors must resolve to six-digit hex values.`
					);
					if (primary !== null && primaryText !== null && primaryHover !== null) {
						if (mode === 'light') {
							readyPrimaryColors.set(id, primary);
						}
						report(
							contrastRatio(primary, primaryText) >= 4.5,
							`${id}: ${mode} primary/text contrast is below 4.5:1.`
						);
						report(
							contrastRatio(primaryHover, primaryText) >= 4.5,
							`${id}: ${mode} primary-hover/text contrast is below 4.5:1.`
						);
					}
				}
			}

			for (const match of executableCss.matchAll(/url\(\s*['"]?([^'")]+)['"]?\s*\)/gi)) {
				const asset = match[1];
				report(cssAssetPattern.test(asset), `${id}: CSS asset path is outside the allowlist: ${asset}`);
				if (cssAssetPattern.test(asset)) {
					const assetPath = path.resolve(tokenDirectory, asset);
					report(fs.existsSync(assetPath), `${id}: CSS asset is missing: ${asset}`);
					if (fs.existsSync(assetPath)) {
						const assetDirectory = path.dirname(assetPath);
						const assetRoot = `${fs.realpathSync(assetDirectory)}${path.sep}`;
						const realAssetPath = fs.realpathSync(assetPath);
						report(
							fs.lstatSync(assetPath).isFile()
								&& !fs.lstatSync(assetPath).isSymbolicLink()
								&& fs.lstatSync(assetPath).size <= maxAssetBytes
								&& !fs.lstatSync(assetDirectory).isSymbolicLink()
								&& assetRoot.startsWith(`${fs.realpathSync(projectRoot)}${path.sep}`)
								&& realAssetPath.startsWith(assetRoot),
							`${id}: CSS asset must be a contained regular file: ${asset}`
						);
					}
				}
			}
		}
	}

	if (theming !== undefined) {
		report(theming && typeof theming === 'object' && !Array.isArray(theming), `${id}: theming must be an object.`);
		if (theming && typeof theming === 'object' && !Array.isArray(theming)) {
			for (const [field, value] of Object.entries(theming)) {
				report(allowedThemingFields.has(field), `${id}: unsupported theming field ${field}.`);
				report(typeof value === 'string' && value !== '', `${id}: theming field ${field} must be a string.`);
					if (field === 'primary_color' || field === 'background_color') {
						report(typeof value === 'string' && colorPattern.test(value), `${id}: ${field} must be a six-digit hex color.`);
						if (field === 'primary_color'
							&& typeof value === 'string'
							&& colorPattern.test(value)
							&& readyPrimaryColors.has(id)
						) {
							report(
								value.toLowerCase() === readyPrimaryColors.get(id),
								`${id}: theming primary_color must match the light projection primary.`
							);
						}
					}
				if (field === 'logo' || field === 'background') {
					report(typeof value === 'string' && assetPattern.test(value), `${id}: ${field} has an unsafe asset path.`);
					if (typeof value === 'string' && assetPattern.test(value)) {
						const assetPath = path.join(projectRoot, value);
						report(fs.existsSync(assetPath), `${id}: theming asset ${value} is missing.`);
						if (fs.existsSync(assetPath)) {
							const assetDirectory = path.dirname(assetPath);
							const assetRoot = `${fs.realpathSync(assetDirectory)}${path.sep}`;
							report(
								fs.lstatSync(assetPath).isFile()
									&& !fs.lstatSync(assetPath).isSymbolicLink()
									&& fs.lstatSync(assetPath).size <= maxAssetBytes
									&& !fs.lstatSync(assetDirectory).isSymbolicLink()
									&& assetRoot.startsWith(`${fs.realpathSync(projectRoot)}${path.sep}`)
									&& fs.realpathSync(assetPath).startsWith(assetRoot),
								`${id}: theming asset must be a contained regular file: ${value}`
							);
						}
					}
				}
			}
		}
	}
}

report(ids.size > 0, 'The profile catalogue must not be empty.');
report(readyIds.size > 0, 'The profile catalogue must contain at least one ready profile.');
const tokenEntries = fs.readdirSync(tokenDirectory, { withFileTypes: true });
for (const entry of tokenEntries) {
	report(
		entry.isFile() && !entry.isSymbolicLink() && entry.name.endsWith('.css'),
		`unexpected token-directory entry: css/tokens/${entry.name}`
	);
}
const cssIds = tokenEntries
	.filter((entry) => entry.isFile() && !entry.isSymbolicLink() && entry.name.endsWith('.css'))
	.map((entry) => entry.name.slice(0, -4));
for (const cssId of cssIds) {
	report(ids.has(cssId), `orphan compiled stylesheet: css/tokens/${cssId}.css`);
}
report(
	cssIds.length === ids.size,
	`expected one compiled stylesheet per profile; found ${cssIds.length} stylesheets for ${ids.size} profiles.`
);

if (errors.length > 0) {
	console.error('Profile catalogue validation failed:');
	for (const error of errors) {
		console.error(`  - ${error}`);
	}
	process.exit(1);
}

console.log(
	`Validated ${ids.size} profile entries and ${cssIds.length} packaged stylesheets; ${readyIds.size} are ready.`
);
