#!/usr/bin/env node

/**
 * Generate NL Design Token CSS Files
 *
 * Reads JSON token files from the nl-design-system/themes repository
 * and generates CSS token files with --nldesign-* prefixed variables.
 *
 * Usage:
 *   node scripts/generate-tokens.mjs /path/to/themes
 *
 * The themes repo should be cloned from:
 *   https://github.com/nl-design-system/themes
 *
 * Directory structure expected:
 *   proprietary/{org}-design-tokens/
 *     src/config.json              — metadata (fullName, name, prefix)
 *     src/brand/{orgname}/*.tokens.json  — brand/color tokens
 *     src/component/utrecht/*.tokens.json — component tokens
 *     src/common/*.tokens.json     — common tokens (optional)
 */

import { readFileSync, writeFileSync, existsSync, mkdirSync, readdirSync, statSync } from 'fs';
import { join, dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const TOKENS_OUTPUT_DIR = join(__dirname, '..', 'css', 'tokens');
const TOKEN_SETS_PATH = join(__dirname, '..', 'token-sets.json');

/**
 * Recursively find all *.tokens.json files in a directory.
 */
function findTokenFiles(dir) {
	const results = [];
	if (!existsSync(dir) || !statSync(dir).isDirectory()) return results;

	for (const entry of readdirSync(dir)) {
		const fullPath = join(dir, entry);
		const stat = statSync(fullPath);
		if (stat.isDirectory()) {
			results.push(...findTokenFiles(fullPath));
		} else if (entry.endsWith('.tokens.json')) {
			results.push(fullPath);
		}
	}
	return results;
}

/**
 * Flatten a nested token object into flat key-value pairs.
 * Leaf nodes have a "value" or "$value" property.
 * Skips tokens with empty values or reference values like "{org.token.path}".
 */
function flattenTokens(obj, prefix = '') {
	const result = [];

	for (const [key, value] of Object.entries(obj)) {
		// Skip metadata keys
		if (key.startsWith('$') || key === 'comment') continue;

		const currentPath = prefix ? `${prefix}-${key}` : key;

		if (value && typeof value === 'object' && !Array.isArray(value)) {
			if ('value' in value || '$value' in value) {
				const tokenValue = value.value ?? value.$value;
				// Skip empty, null, or reference values (e.g., "{leiden.color.primary}")
				if (tokenValue && typeof tokenValue === 'string' && tokenValue.trim() !== '') {
					if (tokenValue.startsWith('{') && tokenValue.endsWith('}')) {
						// This is a reference — skip it since we can't resolve it in plain CSS
						continue;
					}
					result.push({ path: currentPath, value: tokenValue });
				}
			} else {
				// Nested object, recurse
				result.push(...flattenTokens(value, currentPath));
			}
		}
	}

	return result;
}

/**
 * Determine the CSS variable name for a token.
 * Strips org/framework prefixes and applies --nldesign-* or --nldesign-component-*.
 */
function toCSSVar(tokenPath, orgPrefixes) {
	let cleanPath = tokenPath;

	// Remove known org/framework prefixes from the beginning
	const allPrefixes = [...orgPrefixes, 'utrecht', 'denhaag', 'den-haag', 'amsterdam', 'ams'];
	for (const prefix of allPrefixes) {
		if (cleanPath.toLowerCase().startsWith(prefix.toLowerCase() + '-')) {
			cleanPath = cleanPath.substring(prefix.length + 1);
			break;
		}
	}

	// Determine if this is a component token or a brand token
	// Component tokens are things like button-*, heading-*, textbox-*, etc.
	const componentPrefixes = [
		'button', 'textbox', 'textarea', 'form-field', 'form-select', 'form-fieldset',
		'form-input', 'heading', 'paragraph', 'link', 'table', 'badge', 'separator',
		'ordered-list', 'unordered-list', 'alert', 'breadcrumb', 'breadcrumb-nav',
		'checkbox', 'radio-button', 'select', 'data-list', 'document',
		'page-header', 'page-footer', 'page', 'navigation', 'skip-link',
		'status-badge', 'number-badge', 'badge-counter', 'icon', 'img', 'figure',
		'blockquote', 'code', 'pre-formatted', 'mark-up', 'list',
		'accordion', 'modal-dialog', 'avatar', 'description-list',
		'password-input', 'file-input', 'card',
	];

	const isComponent = componentPrefixes.some(
		(cp) => cleanPath.startsWith(cp + '-') || cleanPath === cp
	);

	if (isComponent) {
		return `--nldesign-component-${cleanPath}`;
	}

	return `--nldesign-${cleanPath}`;
}

/**
 * Extract org ID from directory name (e.g., "amsterdam-design-tokens" → "amsterdam").
 */
function extractOrgId(dirName) {
	return dirName
		.replace(/-design-tokens$/, '')
		.toLowerCase()
		.replace(/\s+/g, '-');
}

/**
 * Read config.json for an organization to get display name.
 */
function readOrgConfig(orgDir) {
	const configPath = join(orgDir, 'src', 'config.json');
	if (existsSync(configPath)) {
		try {
			return JSON.parse(readFileSync(configPath, 'utf-8'));
		} catch {
			return null;
		}
	}
	return null;
}

/**
 * Generate a CSS file for a single organization.
 */
function generateOrgCSS(orgId, displayName, tokens, orgPrefixes) {
	const lines = [
		`/**`,
		` * ${displayName} Design Tokens`,
		` *`,
		` * Auto-generated from nl-design-system/themes repository.`,
		` * Do not edit manually — changes will be overwritten by the sync workflow.`,
		` *`,
		` * Source: https://github.com/nl-design-system/themes`,
		` */`,
		``,
		`:root {`,
	];

	// Categorize tokens
	const nldesignTokens = [];
	const orgPaletteTokens = [];

	for (const token of tokens) {
		const cssVar = toCSSVar(token.path, orgPrefixes);
		const value = typeof token.value === 'string' ? token.value : String(token.value);

		nldesignTokens.push({ name: cssVar, value });

		// Also preserve as org-specific palette token if it starts with org prefix
		for (const prefix of orgPrefixes) {
			if (token.path.toLowerCase().startsWith(prefix.toLowerCase() + '-')) {
				const orgPath = token.path;
				orgPaletteTokens.push({ name: `--${orgPath}`, value });
				break;
			}
		}
	}

	// Deduplicate by CSS variable name (later entries win)
	const seen = new Map();
	for (const t of nldesignTokens) {
		seen.set(t.name, t);
	}
	const uniqueTokens = [...seen.values()];

	if (uniqueTokens.length > 0) {
		lines.push(`\t/* NL Design tokens */`);
		for (const t of uniqueTokens) {
			lines.push(`\t${t.name}: ${t.value};`);
		}
	}

	// Org palette (deduplicated)
	const seenOrg = new Map();
	for (const t of orgPaletteTokens) {
		seenOrg.set(t.name, t);
	}
	const uniqueOrgTokens = [...seenOrg.values()];

	if (uniqueOrgTokens.length > 0) {
		lines.push(``);
		lines.push(`\t/* ${displayName} palette */`);
		for (const t of uniqueOrgTokens) {
			lines.push(`\t${t.name}: ${t.value};`);
		}
	}

	lines.push(`}`);
	lines.push(``);

	return lines.join('\n');
}

/**
 * Main entry point.
 */
function main() {
	const themesPath = process.argv[2];

	if (!themesPath) {
		console.error('Usage: node scripts/generate-tokens.mjs /path/to/themes');
		console.error('');
		console.error('Clone the themes repo first:');
		console.error('  git clone https://github.com/nl-design-system/themes.git');
		process.exit(1);
	}

	const resolvedPath = resolve(themesPath);
	const proprietaryDir = join(resolvedPath, 'proprietary');

	if (!existsSync(proprietaryDir)) {
		console.error(`Error: proprietary/ directory not found at ${proprietaryDir}`);
		console.error('Make sure the path points to the root of the nl-design-system/themes repo.');
		process.exit(1);
	}

	// Ensure output directory exists
	if (!existsSync(TOKENS_OUTPUT_DIR)) {
		mkdirSync(TOKENS_OUTPUT_DIR, { recursive: true });
	}

	// Load existing token-sets.json for metadata preservation
	let existingManifest = {};
	if (existsSync(TOKEN_SETS_PATH)) {
		try {
			const raw = JSON.parse(readFileSync(TOKEN_SETS_PATH, 'utf-8'));
			if (Array.isArray(raw)) {
				for (const entry of raw) {
					existingManifest[entry.id] = entry;
				}
			}
		} catch {
			console.warn('Warning: Could not parse existing token-sets.json, starting fresh.');
		}
	}

	// Scan proprietary/ for organization directories
	const orgDirs = readdirSync(proprietaryDir).filter((name) => {
		const dirPath = join(proprietaryDir, name);
		return statSync(dirPath).isDirectory() && !name.startsWith('.');
	});

	console.log(`Found ${orgDirs.length} organizations in ${proprietaryDir}\n`);

	const manifest = [];
	let generated = 0;
	let skipped = 0;
	let warnings = 0;

	for (const orgDirName of orgDirs.sort()) {
		const orgDir = join(proprietaryDir, orgDirName);
		const orgId = extractOrgId(orgDirName);
		const config = readOrgConfig(orgDir);
		const displayName = config?.fullName || config?.name || orgId.replace(/-/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());

		// Build list of known prefixes for this org (used to strip from token paths)
		const orgPrefixes = [orgId, orgId.replace(/-/g, '')];
		if (config?.prefix) orgPrefixes.push(config.prefix);
		if (config?.name) orgPrefixes.push(config.name.toLowerCase());

		// Find all *.tokens.json files recursively under src/
		const srcDir = join(orgDir, 'src');
		const tokenFiles = findTokenFiles(srcDir);

		if (tokenFiles.length === 0) {
			console.log(`  SKIP ${orgDirName}: no *.tokens.json files found`);
			skipped++;
			continue;
		}

		// Parse all token files
		let allTokens = [];
		let errorCount = 0;

		for (const tokenFile of tokenFiles) {
			try {
				const raw = readFileSync(tokenFile, 'utf-8');
				const json = JSON.parse(raw);
				const tokens = flattenTokens(json);
				allTokens.push(...tokens);
			} catch (e) {
				const relPath = tokenFile.replace(orgDir + '/', '');
				console.warn(`  WARN ${orgDirName}: malformed JSON in ${relPath} — ${e.message}`);
				errorCount++;
				warnings++;
			}
		}

		if (errorCount > 0 && allTokens.length === 0) {
			console.warn(`  SKIP ${orgDirName}: all token files malformed`);
			skipped++;
			continue;
		}

		if (allTokens.length === 0) {
			console.log(`  SKIP ${orgDirName}: no concrete token values extracted (all may be references)`);
			skipped++;
			continue;
		}

		// Generate CSS
		const css = generateOrgCSS(orgId, displayName, allTokens, orgPrefixes);
		const outputFile = join(TOKENS_OUTPUT_DIR, `${orgId}.css`);
		writeFileSync(outputFile, css);
		generated++;

		console.log(`  OK   ${orgDirName} → ${orgId}.css (${allTokens.length} tokens from ${tokenFiles.length} files)`);

		// Build manifest entry, preserving existing metadata
		const existing = existingManifest[orgId] || {};
		manifest.push({
			id: orgId,
			name: existing.name || displayName,
			description: existing.description || `Design tokens for ${displayName}`,
		});
	}

	// Write token-sets.json
	writeFileSync(TOKEN_SETS_PATH, JSON.stringify(manifest, null, '\t') + '\n');

	console.log(`\nDone: ${generated} generated, ${skipped} skipped, ${warnings} warnings`);
	console.log(`Token sets manifest: ${TOKEN_SETS_PATH}`);
}

main();
