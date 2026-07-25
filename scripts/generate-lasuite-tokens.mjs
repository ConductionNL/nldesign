#!/usr/bin/env node

/**
 * Generate the La Suite numérique (Cunningham) token defaults layer.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * Reads the installed `@openfun/cunningham-tokens` devDependency's
 * `dist/cunningham-tokens.css` (MIT licensed) and emits
 * `css/systems/lasuite/defaults.css`: every `--c--*` Cunningham custom
 * property, renamed to `--lasuite--*` via a documented, reversible prefix
 * swap (`--c--<rest>` ⇄ `--lasuite--<rest>` — a prefix swap only, every `--`
 * hierarchy separator preserved, no segment collapsing).
 *
 * SOURCE STRUCTURE (important — read before touching the block count below):
 * the upstream file declares its tokens across TWO top-level blocks, not one:
 *   - `html { ... }`                  — the light/base values (584 tokens)
 *   - `.cunningham-theme--dark { ... }` — the dark-theme redeclarations (583
 *     tokens; every light name except `--c--contextuals--background--text--
 *     primary`, which the source only defines once)
 * 584 + 583 = **1167** — the token count this generator (and the
 * lasuite-parity spec) refers to throughout. It is NOT 1167 distinct names;
 * flattening both blocks into one `:root` would silently let the dark
 * redeclarations clobber the light base (last-write-wins in one selector),
 * which is wrong (`--lasuite--globals--colors--brand-600` would stop being
 * the published blue `#0659C5`) and would throw away real upstream fidelity
 * (175 of the dark tokens carry genuinely different values, not just a
 * shorthand notation). This generator therefore mirrors the source's own
 * two-block shape: a `:root` block with the 584 light tokens (the ACTIVE
 * base every consumer of this file reads — nothing in nldesign currently
 * applies the `.cunningham-theme--dark` class, so that block is inert,
 * upstream-faithful reference data, not wired into nldesign's own dark-mode
 * system) and a `.cunningham-theme--dark` block with the 583 dark
 * redeclarations, keeping the two concerns distinct instead of losing one.
 *
 * A closed, explicitly-listed compatibility-alias block follows, mapping the
 * short `--lasuite-*` (single dash) names `css/systems/lasuite/bridge.css`
 * and `element-overrides.css` read to their canonical `--lasuite--*`
 * (double dash) tokens above, so those two existing consumers keep resolving
 * without a rewrite.
 *
 * Usage:
 *   node scripts/generate-lasuite-tokens.mjs [outputPath]
 *   node scripts/generate-lasuite-tokens.mjs --check
 *
 * outputPath (positional arg, falls back to LASUITE_TOKENS_OUTPUT env var)
 * defaults to the committed css/systems/lasuite/defaults.css — the drift
 * guard (`npm run test:lasuite-tokens`) passes a temp path instead via
 * --check (which generates to a temp file internally and diffs it against
 * the committed file, printing the first differing tokens and exiting
 * non-zero on any difference — mirrors tests/l10n/check-l10n-completeness.js's
 * check/--write shape).
 */

import { readFileSync, writeFileSync, mkdtempSync } from 'fs'
import { join, dirname, resolve } from 'path'
import { fileURLToPath } from 'url'
import { tmpdir } from 'os'

const __filename = fileURLToPath(import.meta.url)
const __dirname = dirname(__filename)
const REPO_ROOT = resolve(__dirname, '..')

const PACKAGE_DIR = join(REPO_ROOT, 'node_modules', '@openfun', 'cunningham-tokens')
const SOURCE_CSS_PATH = join(PACKAGE_DIR, 'dist', 'cunningham-tokens.css')
const PACKAGE_JSON_PATH = join(PACKAGE_DIR, 'package.json')
const DEFAULT_OUTPUT_PATH = join(REPO_ROOT, 'css', 'systems', 'lasuite', 'defaults.css')

/**
 * The reversible mapping rule: a prefix swap only, every `--` hierarchy
 * separator preserved, no segment collapsing.
 *   --c--<rest>  ⇄  --lasuite--<rest>
 * Applied identically to property names AND to `var(--c--...)` references
 * inside values, since both are just occurrences of the same substring.
 */
export function renameCToLasuite(text) {
	return text.replaceAll('--c--', '--lasuite--')
}

/**
 * Lower-case hex colour literals (#RGB / #RRGGBB / #RRGGBBAA) for visual
 * consistency with the rest of the app's hand-authored token files (which
 * are lower-case throughout). No other value transformation is applied —
 * var() references, keywords, lengths and numbers pass through unchanged.
 */
export function lowercaseHex(value) {
	return value.replace(/#[0-9A-Fa-f]{3,8}\b/g, (m) => m.toLowerCase())
}

/**
 * Extract the top-level block whose selector matches `selectorPattern`
 * (a RegExp tested against the exact selector text, e.g. /^html$/ or
 * /^\.cunningham-theme--dark$/) from `cssText`, returning its raw inner
 * text (between the outermost matching `{` and `}`). Brace-balanced so it
 * would also work if a value ever contained nested braces (none currently
 * do in the light/dark blocks — declarations are single-line
 * `--c--<path>: <value>;`).
 */
export function extractBlock(cssText, selectorPattern) {
	// Find each "<selector>{" occurrence and test the selector text.
	const re = /([^{}]+)\{/g
	let match
	while ((match = re.exec(cssText)) !== null) {
		const selector = match[1].trim()
		if (!selectorPattern.test(selector)) continue

		const start = match.index + match[0].length
		let depth = 1
		let i = start
		for (; i < cssText.length && depth > 0; i++) {
			if (cssText[i] === '{') depth++
			else if (cssText[i] === '}') depth--
		}
		if (depth !== 0) {
			throw new Error(`extractBlock: unbalanced braces for selector "${selector}"`)
		}
		return cssText.slice(start, i - 1)
	}
	return null
}

/**
 * Parse `--c--<path>: <value>;` declarations out of a block's raw inner
 * text. Only matches lines that are themselves a custom-property
 * declaration (ignores the block's own indentation/whitespace). Returns an
 * array of `{ name, value }` with `name` still in `--c--` form (renaming
 * happens in the caller so this function stays a pure, testable parser).
 */
export function parseDeclarations(blockText) {
	const declarations = []
	const lines = blockText.split('\n')
	const lineRe = /^\s*(--c--[a-zA-Z0-9-]+)\s*:\s*(.+?);\s*$/
	for (const line of lines) {
		const m = line.match(lineRe)
		if (m) {
			declarations.push({ name: m[1], value: m[2] })
		}
	}
	return declarations
}

/**
 * Rename + hex-lower-case + alphabetically sort a list of `{ name, value }`
 * declarations (still in `--c--` form). Sorting is by canonical
 * `--lasuite--*` name — deterministic and stable across regenerations.
 */
function renameAndSort(declarations) {
	return declarations
		.map((d) => ({
			name: renameCToLasuite(d.name),
			value: lowercaseHex(renameCToLasuite(d.value)),
		}))
		.sort((a, b) => (a.name < b.name ? -1 : a.name > b.name ? 1 : 0))
}

/**
 * Render a `{ selector, declarations }` list of `{name, value}` pairs
 * (already renamed) as `\t${name}: ${value};` lines, WITHOUT the wrapping
 * selector braces — callers assemble the final `selector { ... }` block so
 * more than one declaration group (e.g. canonical tokens + compatibility
 * aliases) can share a single `:root` selector. stylelint's
 * `no-duplicate-selectors` rule would otherwise flag two separate `:root`
 * rules in the same file.
 */
function renderDeclarationLines(declarations) {
	return declarations.map(({ name, value }) => `\t${name}: ${value};`)
}

/**
 * The closed, explicitly-listed compatibility-alias table: short
 * `--lasuite-*` (single dash) name → either a canonical `--lasuite--*`
 * (double dash) token to `var()`-reference, or (for `--lasuite-border-radius`
 * only) a literal value, because Cunningham's published token file does not
 * expose a border-radius custom property at all (component radii are baked
 * into its compiled component CSS, never surfaced as a `--c--*` token) — the
 * 4px value is the observed Cunningham button/input radius, carried forward
 * from the previous hand-curated defaults.css and cross-checked against the
 * live docs.numerique.gouv.fr bundle.
 *
 * Every other alias below was verified to exist as a real
 * `--c--globals--colors--*` token in the installed package before being
 * added here — this list is reviewed, not guessed.
 */
export const COMPAT_ALIASES = [
	{ alias: '--lasuite-color-brand-050', canonical: '--lasuite--globals--colors--brand-050' },
	{ alias: '--lasuite-color-brand-100', canonical: '--lasuite--globals--colors--brand-100' },
	{ alias: '--lasuite-color-brand-650', canonical: '--lasuite--globals--colors--brand-650' },
	{ alias: '--lasuite-color-brand-750', canonical: '--lasuite--globals--colors--brand-750' },
	{ alias: '--lasuite-color-error-550', canonical: '--lasuite--globals--colors--error-550' },
	{ alias: '--lasuite-color-error-650', canonical: '--lasuite--globals--colors--error-650' },
	{ alias: '--lasuite-color-gray-000', canonical: '--lasuite--globals--colors--gray-000' },
	{ alias: '--lasuite-color-gray-025', canonical: '--lasuite--globals--colors--gray-025' },
	{ alias: '--lasuite-color-gray-050', canonical: '--lasuite--globals--colors--gray-050' },
	{ alias: '--lasuite-color-gray-100', canonical: '--lasuite--globals--colors--gray-100' },
	{ alias: '--lasuite-color-gray-200', canonical: '--lasuite--globals--colors--gray-200' },
	{ alias: '--lasuite-color-gray-300', canonical: '--lasuite--globals--colors--gray-300' },
	{ alias: '--lasuite-color-gray-500', canonical: '--lasuite--globals--colors--gray-500' },
	{ alias: '--lasuite-color-gray-900', canonical: '--lasuite--globals--colors--gray-900' },
	{ alias: '--lasuite-color-info-550', canonical: '--lasuite--globals--colors--info-550' },
	{ alias: '--lasuite-color-info-650', canonical: '--lasuite--globals--colors--info-650' },
	{ alias: '--lasuite-color-success-550', canonical: '--lasuite--globals--colors--success-550' },
	{ alias: '--lasuite-color-success-650', canonical: '--lasuite--globals--colors--success-650' },
	{ alias: '--lasuite-color-warning-550', canonical: '--lasuite--globals--colors--warning-550' },
	{ alias: '--lasuite-color-warning-650', canonical: '--lasuite--globals--colors--warning-650' },
	{ alias: '--lasuite-border-radius', literal: '4px' },
]

function renderAliasLines() {
	const lines = [
		'',
		'\t/* ===================================================================',
		'\t * COMPATIBILITY ALIASES',
		'\t * Short --lasuite-* (single dash) names read by css/systems/lasuite/',
		'\t * bridge.css and element-overrides.css, mapped to their canonical',
		'\t * --lasuite--* (double dash) tokens above. Closed, explicitly-listed,',
		'\t * reviewed list — see COMPAT_ALIASES in scripts/generate-lasuite-',
		'\t * tokens.mjs. --lasuite-border-radius is the one literal: Cunningham',
		'\t * does not expose a border-radius custom property (baked into its',
		'\t * compiled component CSS instead), so 4px is carried forward from the',
		'\t * observed Cunningham button/input radius, not derived from a token.',
		'\t * --lasuite-font-family is defined separately in fonts.css (not here)',
		'\t * because it depends on the self-hosting/licensing story, not on any',
		'\t * generated Cunningham value.',
		'\t * =================================================================== */',
	]
	for (const entry of COMPAT_ALIASES) {
		if (entry.literal !== undefined) {
			lines.push(`\t${entry.alias}: ${entry.literal};`)
		} else {
			lines.push(`\t${entry.alias}: var(${entry.canonical});`)
		}
	}
	return lines
}

/**
 * Read the installed package's resolved version from its own package.json.
 */
function readPackageVersion() {
	const pkg = JSON.parse(readFileSync(PACKAGE_JSON_PATH, 'utf-8'))
	return pkg.version
}

/**
 * Build the full generated defaults.css text from the source Cunningham CSS.
 *
 * @param {string} sourceCss   Raw text of dist/cunningham-tokens.css.
 * @param {string} packageVersion Resolved @openfun/cunningham-tokens version.
 * @param {string} generationDate ISO date (YYYY-MM-DD) to stamp the header with.
 */
export function generate({ sourceCss, packageVersion, generationDate }) {
	const lightBlockText = extractBlock(sourceCss, /^html$/)
	const darkBlockText = extractBlock(sourceCss, /^\.cunningham-theme--dark$/)
	if (lightBlockText === null) {
		throw new Error('generate: could not find the light "html { ... }" block in the source CSS')
	}
	if (darkBlockText === null) {
		throw new Error('generate: could not find the dark ".cunningham-theme--dark { ... }" block in the source CSS')
	}

	const lightDeclarations = renameAndSort(parseDeclarations(lightBlockText))
	const darkDeclarations = renameAndSort(parseDeclarations(darkBlockText))
	const totalCount = lightDeclarations.length + darkDeclarations.length

	// Both the canonical light tokens AND the compatibility aliases must
	// live in the SAME :root selector — stylelint's no-duplicate-selectors
	// rule flags two separate `:root { ... }` rules in one file, and there
	// is no functional reason to split them (aliases resolve identically
	// either way; var() lookups are cascade-based, not declaration-order-
	// based, so appending them after the canonical tokens in one rule is
	// both lint-clean and semantically unambiguous).
	const rootLines = [':root {', ...renderDeclarationLines(lightDeclarations), ...renderAliasLines(), '}']
	const darkLines = ['.cunningham-theme--dark {', ...renderDeclarationLines(darkDeclarations), '}']

	const header = [
		'/**',
		' * La Suite numérique — Cunningham Token Defaults Layer (GENERATED)',
		' *',
		' * Do not hand-edit — regenerate with:',
		' *   node scripts/generate-lasuite-tokens.mjs',
		' * A committed-file drift check runs as `npm run test:lasuite-tokens`.',
		' *',
		` * Source package: @openfun/cunningham-tokens@${packageVersion} (MIT licence)`,
		' *   https://www.npmjs.com/package/@openfun/cunningham-tokens',
		' *   Cunningham is the design system behind La Suite numérique',
		' *   (Docs/Meet/Chat) — https://github.com/suitenumerique/cunningham',
		' * Read from: dist/cunningham-tokens.css',
		` * Generated: ${generationDate}`,
		` * Token count: ${totalCount} (${lightDeclarations.length} light + ${darkDeclarations.length} dark —`,
		' *   see the module header comment in generate-lasuite-tokens.mjs for',
		' *   why the source splits into these two blocks instead of one).',
		' * Mapping rule: --c--<rest> ⇄ --lasuite--<rest> — a prefix swap only,',
		' *   every "--" hierarchy separator preserved, no segment collapsing',
		' *   (e.g. --c--globals--colors--brand-600 →',
		' *   --lasuite--globals--colors--brand-600). Reversible by swapping the',
		' *   leading token back.',
		' *',
		' * BASE, NOT DEPLOYED THEME: this file is the published Cunningham',
		' * BLUE base (brand-600 #0659c5). The deployed La Suite VIOLET theme',
		' * (brand-600 #534fc2, brand-650/logo #4844ad) is a separate,',
		' * hand-authored, sourced override — see brand-override.css — layered',
		' * after this file in the lasuite design-system bundle. This file must',
		' * never hard-code the violet values.',
		' *',
		' * Only the `:root` (light) block below is active in the nldesign',
		' * lasuite/cunningham bundles today; `.cunningham-theme--dark` is',
		' * carried for upstream fidelity but nothing currently applies that',
		' * class — it is not wired into nldesign\'s own dark-mode system.',
		' */',
		'',
	].join('\n')

	return [header, rootLines.join('\n'), '', darkLines.join('\n'), ''].join('\n')
}

/**
 * CLI entry point.
 */
function main() {
	const args = process.argv.slice(2)
	const checkMode = args.includes('--check')
	const positional = args.find((a) => !a.startsWith('--'))
	const outputPath = positional
		? resolve(process.cwd(), positional)
		: (process.env.LASUITE_TOKENS_OUTPUT ? resolve(process.cwd(), process.env.LASUITE_TOKENS_OUTPUT) : DEFAULT_OUTPUT_PATH)

	const sourceCss = readFileSync(SOURCE_CSS_PATH, 'utf-8')
	const packageVersion = readPackageVersion()
	const generationDate = (process.env.LASUITE_TOKENS_GENERATION_DATE || new Date().toISOString().slice(0, 10))

	const output = generate({ sourceCss, packageVersion, generationDate })

	if (!checkMode) {
		writeFileSync(outputPath, output)
		console.log(`[generate-lasuite-tokens] wrote ${outputPath}`)
		return
	}

	// --check: generate into a temp file, diff against the committed file
	// (ignoring only the "Generated: <date>" line, which legitimately
	// changes every run), report, exit non-zero on any real difference.
	const committedPath = DEFAULT_OUTPUT_PATH
	let committed
	try {
		committed = readFileSync(committedPath, 'utf-8')
	} catch {
		console.error(`[test:lasuite-tokens] committed file not found: ${committedPath}`)
		process.exit(1)
	}

	const stripDate = (text) => text.replace(/^ \* Generated: .*$/m, ' * Generated: <normalised>')
	const normalisedCommitted = stripDate(committed)
	const normalisedGenerated = stripDate(output)

	if (normalisedCommitted === normalisedGenerated) {
		console.log('[test:lasuite-tokens] OK — committed defaults.css matches the generator output.')
		process.exit(0)
	}

	const tmpDir = mkdtempSync(join(tmpdir(), 'lasuite-tokens-'))
	const tmpPath = join(tmpDir, 'defaults.css')
	writeFileSync(tmpPath, output)

	console.error('[test:lasuite-tokens] DRIFT DETECTED — committed defaults.css does not match the generator.')
	const committedLines = normalisedCommitted.split('\n')
	const generatedLines = normalisedGenerated.split('\n')
	const maxLines = Math.max(committedLines.length, generatedLines.length)
	let printed = 0
	for (let i = 0; i < maxLines && printed < 20; i++) {
		if (committedLines[i] !== generatedLines[i]) {
			console.error(`  line ${i + 1}:`)
			console.error(`    committed:  ${committedLines[i] ?? '(missing)'}`)
			console.error(`    generated:  ${generatedLines[i] ?? '(missing)'}`)
			printed++
		}
	}
	console.error(`[test:lasuite-tokens] full generated output written to ${tmpPath} for inspection`)
	console.error(`  diff ${tmpPath} ${committedPath}`)
	process.exit(1)
}

if (import.meta.url === `file://${process.argv[1]}`) {
	main()
}
