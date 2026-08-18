<?php

/**
 * NL Design Dark Palette Service.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category  Service
 * @package   OCA\NLDesign
 * @author    Conduction <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://codeberg.org/Conduction/nldesign
 *
 * @spec openspec/specs/dark-mode/spec.md
 */

declare(strict_types=1);

namespace OCA\NLDesign\Service;

use OCP\App\IAppManager;
use Psr\Log\LoggerInterface;

/**
 * Derives, verifies, and materialises dark-mode CSS variants for NL Design
 * token sets.
 *
 * NL Design System ships no dark mode, so every token set is light-only.
 * This service derives a dark palette algorithmically (preserve hue, invert
 * the lightness scale), verifies every WCAG-checked pair still reaches AA
 * 4.5:1 via {@see ContrastService}, honours hand-authored
 * `@media (prefers-color-scheme: dark)` overrides in the token set file
 * (which always win and are never rewritten), and renders the result as a
 * static `css/tokens/dark/{id}.css` file. Generation is build/install-time
 * only — never per request.
 *
 * Token classification note: the existing {@see TokenRegistry} covers the
 * `--color-*` custom-overrides editor (a distinct concern, mapped onto core
 * Nextcloud variable names), not the `--nldesign-*` token-set primitives this
 * service derives. Classification here is therefore a dedicated, local
 * background/text split driven by the `--nldesign-*` naming convention
 * (see {@see self::isTextClass()}), not a reuse of TokenRegistry.
 *
 * @spec openspec/specs/dark-mode/spec.md
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)     - This class is the full dark-palette
 * derivation + WCAG verification/repair + dual-scope CSS rendering + generation/freshness
 * pipeline (RGB<->HSL colour math, the binary-search repair loop, hand-authored override
 * merging, and file I/O). Every method is small, single-purpose, and independently unit
 * tested; splitting further would fragment one cohesive, well-tested algorithm across
 * multiple classes without reducing real complexity (see DesignTokensMapper/
 * ComplianceReportService/UpstreamFreshnessService for the same precedent in this app).
 * @SuppressWarnings(PHPMD.TooManyMethods)           - see the ExcessiveClassLength rationale above.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity) - see the ExcessiveClassLength rationale above.
 */
class DarkPaletteService {

	/**
	 * The generated-file format/algorithm version, embedded in the header
	 * comment so a future algorithm change can force regeneration.
	 */
	public const GENERATOR_VERSION = 1;

	/**
	 * The guaranteed-passing near-white snap value (matches NC's own dark
	 * theme main-text colour, `DarkTheme::getCSSVariables()`).
	 */
	private const NEAR_WHITE = '#EBEBEB';

	/**
	 * The guaranteed-passing near-black snap value (matches NC's own dark
	 * theme main-background colour).
	 */
	private const NEAR_BLACK = '#111111';

	/**
	 * Design systems that are never eligible for dark-variant generation:
	 * `none` has no `--nldesign-*` tokens to darken (stock Nextcloud handles
	 * its own dark theme), and `high-contrast` is a AAA black-on-white set
	 * whose purpose auto-darkening would defeat (hand-authored dark blocks
	 * remain possible for it).
	 *
	 * @var string[]
	 */
	private const SKIPPED_DESIGN_SYSTEMS = ['none', 'high-contrast'];

	/**
	 * Maximum outer verify/repair rounds (a fix to one pair's shared token
	 * can perturb another pair sharing that token, e.g. `--nldesign-color-primary`
	 * is the background of one fixed pair and the foreground of another).
	 */
	private const MAX_REPAIR_ROUNDS = 4;

	/**
	 * Maximum binary-search steps per failing pair.
	 */
	private const MAX_BINARY_STEPS = 8;

	/**
	 * The initial lightness step for the binary search, halved each round.
	 */
	private const INITIAL_STEP = 0.25;

	/**
	 * The WCAG contrast service (relative-luminance math + colour parsing).
	 *
	 * @var ContrastService
	 */
	private ContrastService $contrast;

	/**
	 * The CSS custom-property parser (also used for dark-block extraction).
	 *
	 * @var CssParserService
	 */
	private CssParserService $parser;

	/**
	 * The app manager for resolving the app's filesystem path.
	 *
	 * @var IAppManager
	 */
	private IAppManager $appManager;

	/**
	 * The logger — every contrast-repair snap and every degrade (unwritable
	 * directory, write failure) is logged as a warning, never an exception.
	 *
	 * @var LoggerInterface
	 */
	private LoggerInterface $logger;

	/**
	 * Constructor.
	 *
	 * @param ContrastService $contrast The WCAG contrast service.
	 * @param CssParserService $parser The CSS custom-property parser.
	 * @param IAppManager $appManager The app manager.
	 * @param LoggerInterface $logger The logger.
	 */
	public function __construct(
		ContrastService $contrast,
		CssParserService $parser,
		IAppManager $appManager,
		LoggerInterface $logger,
	) {
		$this->contrast = $contrast;
		$this->parser = $parser;
		$this->appManager = $appManager;
		$this->logger = $logger;
	}//end __construct()

	/**
	 * Whether a design system id is eligible for dark-variant generation.
	 *
	 * @param string $designSystemId The design system id.
	 *
	 * @return bool True when eligible.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	public function isEligible(string $designSystemId): bool {
		return (in_array($designSystemId, self::SKIPPED_DESIGN_SYSTEMS, true) === false);
	}//end isEligible()

	/**
	 * Discover every token set id on disk (shipped and custom), mirroring
	 * {@see TokenSetService::getAvailableTokenSets()}'s filesystem scan.
	 *
	 * @return string[] The discovered token set ids, alphabetically sorted.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	public function discoverAllSetIds(): array {
		$tokensDir = $this->appManager->getAppPath('nldesign') . '/css/tokens';
		if (is_dir($tokensDir) === false) {
			return [];
		}

		$files = scandir($tokensDir);
		if ($files === false) {
			$files = [];
		}

		$ids = [];
		foreach ($files as $file) {
			$fullPath = $tokensDir . '/' . $file;
			if (is_file($fullPath) === true && str_ends_with($file, '.css') === true) {
				$ids[] = basename($file, '.css');
			}
		}

		sort($ids);

		return $ids;
	}//end discoverAllSetIds()

	/**
	 * Resolve the effective light `--nldesign-*` declarations for a set:
	 * `css/systems/nldesign/defaults.css` layered under `css/tokens/{id}.css`,
	 * with a `theming.background_color` fallback for the UI-contrast pair
	 * when the set declares no explicit background token. Mirrors the
	 * resolution {@see ShippedTokenSetAuditService::resolveDeclarations()}
	 * (and the preview service) already use.
	 *
	 * @param string $appPath The app root path.
	 * @param string $setId The token set id.
	 * @param array<string, mixed> $theming The set's theming metadata (for the background fallback).
	 *
	 * @return array<string, string> The merged declarations.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	private function resolveLightDeclarations(string $appPath, string $setId, array $theming = []): array {
		$declarations = $this->parseFile(filePath: $appPath . '/css/systems/nldesign/defaults.css');

		$tokenFile = $appPath . '/css/tokens/' . $setId . '.css';
		if (is_file($tokenFile) === true) {
			$declarations = array_merge($declarations, $this->parseFile(filePath: $tokenFile));
		}

		if (isset($declarations['--nldesign-color-background']) === false
			&& isset($theming['background_color']) === true
			&& is_string($theming['background_color']) === true
		) {
			$declarations['--nldesign-color-background'] = $theming['background_color'];
		}

		return $declarations;
	}//end resolveLightDeclarations()

	/**
	 * Derive the dark declarations for every literal color token in the
	 * supplied light declaration map. Non-color tokens (sizes, radii, fonts,
	 * urls, `var()` aliases) are silently excluded — they inherit the light
	 * layer's value via normal cascade.
	 *
	 * @param array<string, string> $lightDeclarations The effective light declarations.
	 *
	 * @return array<string, string> The derived dark declarations (color tokens only).
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	public function deriveDarkDeclarations(array $lightDeclarations): array {
		$dark = [];

		foreach ($lightDeclarations as $token => $value) {
			if (str_ends_with($token, '-rgb') === true) {
				// Regenerated after the main pass, from the derived base token.
				continue;
			}

			$rgb = $this->contrast->parseColor(value: $value);
			if ($rgb === null) {
				// Unparseable (gradient, var(), keyword, size, font stack, url()) — skip.
				continue;
			}

			$dark[$token] = $this->deriveColorToken(token: $token, rgb: $rgb, lightDeclarations: $lightDeclarations);
		}

		return $this->regenerateRgbCompanions(lightDeclarations: $lightDeclarations, darkDeclarations: $dark);
	}//end deriveDarkDeclarations()

	/**
	 * Derive one color token's dark value.
	 *
	 * @param string $token The token name.
	 * @param array{0:int,1:int,2:int} $rgb The token's light RGB value.
	 * @param array<string, string> $lightDeclarations The full light declaration map (for the brand-primary exception).
	 *
	 * @return string The derived dark hex value.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	private function deriveColorToken(string $token, array $rgb, array $lightDeclarations): string {
		if ($this->isBrandPrimaryToken(token: $token) === true
			&& $this->brandPrimaryPasses(lightDeclarations: $lightDeclarations) === true
		) {
			// Brand primary exception: the gemeente brand colour already
			// works on dark, so keep the light value untouched.
			return $this->rgbToHex(rgb: $rgb);
		}

		[$hue, $saturation, $lightness] = $this->rgbToHsl(rgb: $rgb);

		$isText = $this->isTextClass(token: $token);
		$invertedL = (1.0 - $lightness);

		$darkLightness = (0.08 + (0.84 * $invertedL));
		if ($isText === true) {
			$darkLightness = $this->clamp(value: $invertedL, min: 0.62, max: 0.92);
		}

		$darkSaturation = $saturation;
		if ($isText === false) {
			// Desaturate background-class surfaces only, to avoid neon walls.
			$darkSaturation = ($saturation * 0.9);
		}

		$darkRgb = $this->hslToRgb(hue: $hue, saturation: $darkSaturation, lightness: $darkLightness);

		return $this->rgbToHex(rgb: $darkRgb);
	}//end deriveColorToken()

	/**
	 * Whether a token is the brand-primary token or its hover variant (the
	 * only tokens eligible for the "keep the light value" exception).
	 *
	 * @param string $token The token name.
	 *
	 * @return bool True for `--nldesign-color-primary` or `--nldesign-color-primary-hover`.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	private function isBrandPrimaryToken(string $token): bool {
		return ($token === '--nldesign-color-primary' || $token === '--nldesign-color-primary-hover');
	}//end isBrandPrimaryToken()

	/**
	 * Whether the light `--nldesign-color-primary` / `-primary-text` pair
	 * already passes WCAG AA 4.5:1 — the brand-primary exception condition.
	 *
	 * @param array<string, string> $lightDeclarations The full light declaration map.
	 *
	 * @return bool True when the light pair already passes (or cannot be evaluated;
	 *              an unevaluated pair is treated as "does not obviously fail", so the
	 *              original brand colour is preserved by default).
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	private function brandPrimaryPasses(array $lightDeclarations): bool {
		$background = ($lightDeclarations['--nldesign-color-primary'] ?? null);
		$foreground = ($lightDeclarations['--nldesign-color-primary-text'] ?? null);
		if ($background === null || $foreground === null) {
			return true;
		}

		$bgRgb = $this->contrast->parseColor(value: $background);
		$fgRgb = $this->contrast->parseColor(value: $foreground);
		if ($bgRgb === null || $fgRgb === null) {
			return true;
		}

		return ($this->contrast->ratio(first: $fgRgb, second: $bgRgb) >= 4.5);
	}//end brandPrimaryPasses()

	/**
	 * Whether a token name is text-class (its dark value must land LIGHT)
	 * as opposed to background-class (its dark value must land DARK).
	 *
	 * A binary split driven by the `--nldesign-*` naming convention: any
	 * token whose name contains "text" (`-text`, `-text-error`, the bare
	 * `--nldesign-color-text`, …) is text-class; everything else (fills,
	 * surfaces, borders, the brand primary, status colours) is
	 * background-class. See the class docblock for why this does not reuse
	 * {@see TokenRegistry}.
	 *
	 * @param string $token The token name.
	 *
	 * @return bool True when the token is text-class.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	private function isTextClass(string $token): bool {
		return (str_contains($token, 'text') === true);
	}//end isTextClass()

	/**
	 * Regenerate `--{base}-rgb` companion declarations from their already-derived
	 * base token, for every `-rgb` key present in the light declarations.
	 *
	 * @param array<string, string> $lightDeclarations The light declarations (to find which `-rgb` keys exist).
	 * @param array<string, string> $darkDeclarations The derived dark declarations so far.
	 *
	 * @return array<string, string> The dark declarations with `-rgb` companions added.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	private function regenerateRgbCompanions(array $lightDeclarations, array $darkDeclarations): array {
		foreach (array_keys($lightDeclarations) as $token) {
			if (str_ends_with($token, '-rgb') === false) {
				continue;
			}

			$baseToken = substr($token, 0, -4);
			if (isset($darkDeclarations[$baseToken]) === false) {
				continue;
			}

			$rgb = $this->contrast->parseColor(value: $darkDeclarations[$baseToken]);
			if ($rgb === null) {
				continue;
			}

			$darkDeclarations[$token] = $rgb[0] . ', ' . $rgb[1] . ', ' . $rgb[2];
		}

		return $darkDeclarations;
	}//end regenerateRgbCompanions()

	/**
	 * Run the WCAG verification/repair loop over a candidate dark
	 * declaration map. Every evaluable {@see ContrastService::check()} pair
	 * that is not protected (hand-authored) is repaired by adjusting the
	 * foreground's lightness away from the background (bounded binary
	 * search); if still failing it snaps to a guaranteed-passing near-white
	 * or near-black value. Protected (hand-authored) tokens are NEVER
	 * adjusted — a failing pair involving one only produces a warning.
	 *
	 * @param array<string, string> $declarations The candidate dark declarations.
	 * @param string[] $protectedTokens Token names that MUST NOT be rewritten (hand-authored overrides).
	 *
	 * @return array{declarations: array<string, string>, warnings: array<int, array<string, mixed>>}
	 *                                                                                                The (possibly repaired) declarations and the final warning list.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	public function verifyAndRepair(array $declarations, array $protectedTokens = []): array {
		$protected = array_flip($protectedTokens);

		for ($round = 0; $round < self::MAX_REPAIR_ROUNDS; $round++) {
			$warnings = $this->contrast->check(declarations: $declarations);
			$fixableFound = false;

			foreach ($warnings as $warning) {
				if (($warning['unevaluated'] ?? false) === true) {
					continue;
				}

				[$fgName, $bgName] = $this->splitPairLabel(label: $warning['pair']);
				if (isset($protected[$fgName]) === true || isset($protected[$bgName]) === true) {
					// Hand-authored failure: warn only, never rewritten.
					continue;
				}

				$fixableFound = true;
				$declarations[$fgName] = $this->repairForeground(
					fgValue: $declarations[$fgName],
					bgValue: $declarations[$bgName],
					threshold: $warning['threshold']
				);
			}//end foreach

			if ($fixableFound === false) {
				break;
			}
		}//end for

		$finalWarnings = $this->contrast->check(declarations: $declarations);

		return [
			'declarations' => $declarations,
			'warnings' => $finalWarnings,
		];
	}//end verifyAndRepair()

	/**
	 * Split a `ContrastService::check()` pair label ("fg vs bg") back into
	 * its two token names.
	 *
	 * @param string $label The pair label, e.g. "--nldesign-color-primary-text vs --nldesign-color-primary".
	 *
	 * @return array{0: string, 1: string} The [foreground, background] token names.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	private function splitPairLabel(string $label): array {
		$parts = explode(' vs ', $label, 2);

		return [($parts[0] ?? ''), ($parts[1] ?? '')];
	}//end splitPairLabel()

	/**
	 * Repair a failing foreground/background pair: binary-search the
	 * foreground's lightness away from the background (bounded iteration,
	 * step halving from {@see self::INITIAL_STEP}); if no candidate in the
	 * search passes, snap to whichever of near-white/near-black yields the
	 * higher ratio and log a warning naming the pair and the snap.
	 *
	 * @param string $fgValue The current foreground value.
	 * @param string $bgValue The background value (never modified here).
	 * @param float $threshold The AA threshold this pair must reach.
	 *
	 * @return string The repaired (or snapped) foreground hex value.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	private function repairForeground(string $fgValue, string $bgValue, float $threshold): string {
		$fgRgb = $this->contrast->parseColor(value: $fgValue);
		$bgRgb = $this->contrast->parseColor(value: $bgValue);
		if ($fgRgb === null || $bgRgb === null) {
			// Unevaluated — nothing literal to adjust.
			return $fgValue;
		}

		[$hue, $saturation, $lightness] = $this->rgbToHsl(rgb: $fgRgb);
		[, , $bgLightness] = $this->rgbToHsl(rgb: $bgRgb);

		$direction = -1.0;
		if ($lightness >= $bgLightness) {
			$direction = 1.0;
		}

		$step = self::INITIAL_STEP;
		$candidateL = $lightness;
		$passed = false;

		for ($i = 0; $i < self::MAX_BINARY_STEPS; $i++) {
			$candidateL = $this->clamp(value: ($candidateL + ($direction * $step)), min: 0.0, max: 1.0);
			$candidateRgb = $this->hslToRgb(hue: $hue, saturation: $saturation, lightness: $candidateL);

			if ($this->contrast->ratio(first: $candidateRgb, second: $bgRgb) >= $threshold) {
				$passed = true;
				break;
			}

			$step = ($step / 2);
		}

		if ($passed === true) {
			return $this->rgbToHex(rgb: $this->hslToRgb(hue: $hue, saturation: $saturation, lightness: $candidateL));
		}

		return $this->snapForeground(bgRgb: $bgRgb, threshold: $threshold, originalFg: $fgValue);
	}//end repairForeground()

	/**
	 * Snap a foreground to whichever of near-white/near-black passes (or
	 * comes closest to passing) against the background, logging a warning.
	 *
	 * @param array{0:int,1:int,2:int} $bgRgb The background RGB.
	 * @param float $threshold The AA threshold.
	 * @param string $originalFg The original (failing) foreground value, for the log message.
	 *
	 * @return string The snapped hex value.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	private function snapForeground(array $bgRgb, float $threshold, string $originalFg): string {
		$whiteRgb = ($this->contrast->parseColor(value: self::NEAR_WHITE) ?? [235, 235, 235]);
		$blackRgb = ($this->contrast->parseColor(value: self::NEAR_BLACK) ?? [17, 17, 17]);

		$whiteRatio = $this->contrast->ratio(first: $whiteRgb, second: $bgRgb);
		$blackRatio = $this->contrast->ratio(first: $blackRgb, second: $bgRgb);

		$chosen = self::NEAR_WHITE;
		if ($blackRatio >= $whiteRatio) {
			$chosen = self::NEAR_BLACK;
		}

		$this->logger->warning(
			'NL Design dark-variant contrast repair could not reach {threshold}:1 within the bounded '
			. 'search; snapped {original} to {chosen}.',
			[
				'threshold' => $threshold,
				'original' => $originalFg,
				'chosen' => $chosen,
			]
		);

		return $chosen;
	}//end snapForeground()

	/**
	 * Load a token set's `design_system` and `theming` metadata, defaulting
	 * to `nldesign`/empty for ids absent from `token-sets.json` (custom
	 * uploads have no manifest entry there and are always `nldesign`).
	 *
	 * @param string $appPath The app root path.
	 * @param string $setId The token set id.
	 *
	 * @return array{design_system: string, theming: array<string, mixed>} The resolved metadata.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	private function loadSetMeta(string $appPath, string $setId): array {
		$manifestPath = $appPath . '/token-sets.json';
		if (is_readable($manifestPath) === false) {
			return ['design_system' => 'nldesign', 'theming' => []];
		}

		$manifest = json_decode((string)file_get_contents($manifestPath), true);
		if (is_array($manifest) === false) {
			return ['design_system' => 'nldesign', 'theming' => []];
		}

		foreach ($manifest as $entry) {
			if (is_array($entry) === true && ($entry['id'] ?? null) === $setId) {
				$theming = [];
				if (is_array($entry['theming'] ?? null) === true) {
					$theming = $entry['theming'];
				}

				return [
					'design_system' => (string)($entry['design_system'] ?? 'nldesign'),
					'theming' => $theming,
				];
			}
		}

		return ['design_system' => 'nldesign', 'theming' => []];
	}//end loadSetMeta()

	/**
	 * Build the full dark CSS content for one token set: derive, apply
	 * hand-authored overrides (which win and are protected from repair),
	 * run the verification loop, emit the `logo_dark` override when present,
	 * and render the dual-scoped file.
	 *
	 * @param string $setId The token set id.
	 *
	 * @return array{css: string, warnings: array<int, array<string, mixed>>}|null
	 *                                                                             The rendered CSS + warnings, or null when the set is ineligible or has no source file.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	public function generateForSet(string $setId): ?array {
		$appPath = $this->appManager->getAppPath('nldesign');
		$meta = $this->loadSetMeta(appPath: $appPath, setId: $setId);

		if ($this->isEligible(designSystemId: $meta['design_system']) === false) {
			return null;
		}

		$tokenFile = $appPath . '/css/tokens/' . $setId . '.css';
		if (is_file($tokenFile) === false) {
			return null;
		}

		$tokenCss = (string)file_get_contents($tokenFile);

		$light = $this->resolveLightDeclarations(appPath: $appPath, setId: $setId, theming: $meta['theming']);
		$derived = $this->deriveDarkDeclarations(lightDeclarations: $light);
		$overrides = $this->parser->parseDarkBlock(css: $tokenCss);

		$merged = array_merge($derived, $overrides);

		if (isset($meta['theming']['logo_dark']) === true && is_string($meta['theming']['logo_dark']) === true) {
			$merged['--nldesign-logo-url'] = "url('" . $this->relativeDarkLogoPath(logoDarkPath: $meta['theming']['logo_dark']) . "')";
		}

		$repaired = $this->verifyAndRepair(declarations: $merged, protectedTokens: array_keys($overrides));

		ksort($repaired['declarations']);

		$sourceHash = 'sha256:' . hash(algo: 'sha256', data: $tokenCss);
		$css = $this->renderDarkCss(setId: $setId, declarations: $repaired['declarations'], sourceHash: $sourceHash);

		return ['css' => $css, 'warnings' => $repaired['warnings']];
	}//end generateForSet()

	/**
	 * Build the dark-scoped logo url()'s path, relative to `css/tokens/dark/`.
	 *
	 * @param string $logoDarkPath The app-relative `logo_dark` path (e.g. `img/logos/x-dark.svg`).
	 *
	 * @return string The path as it must appear inside `url(...)` from `css/tokens/dark/{set}.css`.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	private function relativeDarkLogoPath(string $logoDarkPath): string {
		// nldesign is served from a custom_apps URL prefix (the fleet's
		// standard deployment — no /apps/nldesign/ alias exists there), so
		// css/tokens/{set}.css (2 levels under the app root: css/ →
		// tokens/) needs 2 ".." to reach img/, not 3 — verified live against
		// a running custom-app install (css/tokens/rijkshuisstijl.css's
		// old 3-".." value 404'd; 2 resolves correctly).
		// css/tokens/dark/{set}.css is one directory deeper, so it needs one more.
		return '../../../' . $logoDarkPath;
	}//end relativeDarkLogoPath()

	/**
	 * Render the dual-scoped dark CSS file: a `@media (prefers-color-scheme:
	 * dark)` block excluding any explicit theme choice (auto theme + anonymous
	 * pages), and an unconditional `body[data-theme-dark], body[data-themes*=dark]`
	 * block for an explicit dark choice. Exact selector shape verified against
	 * the NC 34 server checkout (design.md §1/§2).
	 *
	 * @param string $setId The token set id.
	 * @param array<string, string> $declarations The final (sorted) dark declarations.
	 * @param string $sourceHash The `sha256:...` hash of the source token file.
	 *
	 * @return string The rendered CSS.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	public function renderDarkCss(string $setId, array $declarations, string $sourceHash): string {
		$lines = [];
		$lines[] = '/* GENERATED by nldesign DarkPaletteService v' . self::GENERATOR_VERSION
			. ' from tokens/' . $setId . '.css (' . $sourceHash . ') — do not edit */';
		$lines[] = '@media (prefers-color-scheme: dark) {';
		$lines[] = '	body:not([data-theme-light]):not([data-theme-dark]):not([data-theme-light-highcontrast]):not([data-theme-dark-highcontrast]) {';

		foreach ($declarations as $token => $value) {
			$lines[] = '		' . $token . ': ' . $value . ';';
		}

		$lines[] = '	}';
		$lines[] = '}';
		$lines[] = 'body[data-theme-dark],';
		$lines[] = 'body[data-themes*=dark] {';

		foreach ($declarations as $token => $value) {
			$lines[] = '	' . $token . ': ' . $value . ';';
		}

		$lines[] = '}';

		return implode("\n", $lines) . "\n";
	}//end renderDarkCss()

	/**
	 * Generate and write (or skip) the dark variant for one token set,
	 * handling freshness (source-hash comparison), directory creation, and
	 * write failures — all degrading to a logged warning, never an
	 * exception (theming is presentation, never allowed to fatal the app).
	 *
	 * @param string $setId The token set id.
	 * @param bool $force Regenerate even when the existing file is fresh.
	 *
	 * @return array{written: bool, skipped: bool, reason: string, warnings: array<int, array<string, mixed>>}
	 *                                                                                                         The outcome.
	 *
	 * @SuppressWarnings(PHPMD.BooleanArgumentFlag) - `$force` mirrors the standard CLI
	 * `--force` convention (also `occ nldesign:generate-dark-variants --force`); a
	 * skip-if-fresh/force-regenerate toggle on one write path, not two responsibilities.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	public function generateAndWrite(string $setId, bool $force = false): array {
		$appPath = $this->appManager->getAppPath('nldesign');
		$tokenFile = $appPath . '/css/tokens/' . $setId . '.css';
		$darkFile = $appPath . '/css/tokens/dark/' . $setId . '.css';

		$meta = $this->loadSetMeta(appPath: $appPath, setId: $setId);
		if ($this->isEligible(designSystemId: $meta['design_system']) === false) {
			return ['written' => false, 'skipped' => true, 'reason' => 'ineligible', 'warnings' => []];
		}

		if (is_file($tokenFile) === false) {
			return ['written' => false, 'skipped' => true, 'reason' => 'source-missing', 'warnings' => []];
		}

		$sourceHash = 'sha256:' . hash(algo: 'sha256', data: (string)file_get_contents($tokenFile));

		if ($force === false && is_file($darkFile) === true && $this->isFresh(darkFile: $darkFile, sourceHash: $sourceHash) === true) {
			return ['written' => false, 'skipped' => true, 'reason' => 'fresh', 'warnings' => []];
		}

		$generated = $this->generateForSet(setId: $setId);
		if ($generated === null) {
			return ['written' => false, 'skipped' => true, 'reason' => 'ineligible', 'warnings' => []];
		}

		$darkDir = $appPath . '/css/tokens/dark';
		if ($this->ensureDarkDirWritable(darkDir: $darkDir) === false) {
			$this->logger->warning(
				'NL Design dark-variant directory {dir} is not writable; skipping "{set}" (light-only).',
				['dir' => $darkDir, 'set' => $setId]
			);

			return ['written' => false, 'skipped' => true, 'reason' => 'not-writable', 'warnings' => $generated['warnings']];
		}

		if ($this->writeAtomic(path: $darkFile, contents: $generated['css']) === false) {
			$this->logger->warning('NL Design dark-variant write failed for "{set}".', ['set' => $setId]);

			return ['written' => false, 'skipped' => false, 'reason' => 'write-failed', 'warnings' => $generated['warnings']];
		}

		return ['written' => true, 'skipped' => false, 'reason' => '', 'warnings' => $generated['warnings']];
	}//end generateAndWrite()

	/**
	 * Ensure `css/tokens/dark/` exists and is writable, creating it (without
	 * an error-control operator — a pre-check on the parent directory avoids
	 * needing one) when it does not already exist.
	 *
	 * @param string $darkDir The absolute `css/tokens/dark` path.
	 *
	 * @return bool True when the directory exists and is writable afterwards.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	private function ensureDarkDirWritable(string $darkDir): bool {
		if (is_dir($darkDir) === false) {
			$parentDir = dirname($darkDir);
			if (is_dir($parentDir) === true && is_writable($parentDir) === true) {
				mkdir($darkDir, 0775, true);
			}
		}

		return (is_dir($darkDir) === true && is_writable($darkDir) === true);
	}//end ensureDarkDirWritable()

	/**
	 * Generate (or skip) every discovered token set's dark variant.
	 *
	 * @param bool $force Regenerate even fresh files.
	 *
	 * @return array<string, array{written: bool, skipped: bool, reason: string, warnings: array<int, array<string, mixed>>}>
	 *                                                                                                                        The per-set outcome, keyed by set id.
	 *
	 * @SuppressWarnings(PHPMD.BooleanArgumentFlag) - see generateAndWrite()'s rationale; this
	 * simply fans the same `--force` convention out over every discovered set.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	public function generateAll(bool $force = false): array {
		$results = [];
		foreach ($this->discoverAllSetIds() as $setId) {
			$results[$setId] = $this->generateAndWrite(setId: $setId, force: $force);
		}

		return $results;
	}//end generateAll()

	/**
	 * Delete a custom set's dark variant file, if any (best-effort, never throws).
	 *
	 * @param string $setId The custom set id.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	public function deleteDarkVariant(string $setId): void {
		$darkFile = $this->appManager->getAppPath('nldesign') . '/css/tokens/dark/' . $setId . '.css';
		if (is_file($darkFile) === true && is_writable($darkFile) === true) {
			unlink($darkFile);
		}
	}//end deleteDarkVariant()

	/**
	 * Whether an existing dark file's embedded source hash matches the
	 * current source hash (i.e. the source token file has not changed).
	 *
	 * @param string $darkFile The existing dark CSS file path.
	 * @param string $sourceHash The current `sha256:...` source hash.
	 *
	 * @return bool True when the file is fresh.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	private function isFresh(string $darkFile, string $sourceHash): bool {
		$header = file_get_contents($darkFile, false, null, 0, 512);
		if ($header === false) {
			return false;
		}

		return str_contains($header, $sourceHash);
	}//end isFresh()

	/**
	 * Write a file atomically via a temp file + rename.
	 *
	 * @param string $path The destination path.
	 * @param string $contents The content to write.
	 *
	 * @return bool True on success.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	private function writeAtomic(string $path, string $contents): bool {
		$tmpPath = $path . '.tmp';
		if (file_put_contents($tmpPath, $contents) === false) {
			return false;
		}

		if (rename($tmpPath, $path) === false) {
			if (file_exists($tmpPath) === true) {
				unlink($tmpPath);
			}

			return false;
		}

		return true;
	}//end writeAtomic()

	/**
	 * Parse a CSS file's `:root`-scoped declarations into a map (empty when absent/unreadable).
	 *
	 * @param string $filePath The absolute file path.
	 *
	 * @return array<string, string> The parsed declarations.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	private function parseFile(string $filePath): array {
		if (is_file($filePath) === false) {
			return [];
		}

		$content = file_get_contents($filePath);
		if ($content === false) {
			return [];
		}

		return ($this->parser->parseDeclarations(content: $content) ?? []);
	}//end parseFile()

	/**
	 * Convert an RGB triple to [hue, saturation, lightness] (hue in degrees
	 * 0-360, saturation/lightness in [0, 1]).
	 *
	 * @param array{0:int,1:int,2:int} $rgb The RGB triple (0-255 each).
	 *
	 * @return array{0: float, 1: float, 2: float} The [hue, saturation, lightness] triple.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	private function rgbToHsl(array $rgb): array {
		$r = ($rgb[0] / 255);
		$g = ($rgb[1] / 255);
		$b = ($rgb[2] / 255);

		$max = max($r, $g, $b);
		$min = min($r, $g, $b);
		$l = (($max + $min) / 2);

		if ($max === $min) {
			return [0.0, 0.0, $l];
		}

		$delta = ($max - $min);

		$s = ($delta / ($max + $min));
		if ($l > 0.5) {
			$s = ($delta / (2 - $max - $min));
		}

		$h = $this->hueSector(r: $r, g: $g, b: $b, max: $max, delta: $delta);

		// Use fmod(), not `%` — the hue is a float, and PHP's `%` silently
		// truncates both operands to int, which would corrupt sub-degree hue
		// precision (and break the "hue preserved" contract).
		$h = fmod($h * 60, 360);
		if ($h < 0) {
			$h += 360;
		}

		return [$h, $s, $l];
	}//end rgbToHsl()

	/**
	 * Compute the pre-normalisation hue sector value (before the `* 60` scale
	 * and `fmod(…, 360)` wrap) for the standard RGB->HSL conversion. Early
	 * returns instead of an if/else-if/else chain, one sector per channel
	 * being the maximum.
	 *
	 * @param float $r The red channel, normalised to [0, 1].
	 * @param float $g The green channel, normalised to [0, 1].
	 * @param float $b The blue channel, normalised to [0, 1].
	 * @param float $max The maximum of r/g/b.
	 * @param float $delta The max-min spread.
	 *
	 * @return float The pre-normalisation hue sector value.
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	private function hueSector(float $r, float $g, float $b, float $max, float $delta): float {
		if ($max === $g) {
			return ((($b - $r) / $delta) + 2);
		}

		if ($max === $b) {
			return ((($r - $g) / $delta) + 4);
		}

		// $max === $r.
		$gLessThanB = 0;
		if ($g < $b) {
			$gLessThanB = 6;
		}

		return ((($g - $b) / $delta) + $gLessThanB);
	}//end hueSector()

	/**
	 * Convert [hue, saturation, lightness] to an RGB triple.
	 *
	 * @param float $hue Hue in degrees [0, 360).
	 * @param float $saturation Saturation in [0, 1].
	 * @param float $lightness Lightness in [0, 1].
	 *
	 * @return array{0:int,1:int,2:int} The RGB triple (0-255 each).
	 *
	 * @spec openspec/specs/dark-mode/spec.md
	 */
	private function hslToRgb(float $hue, float $saturation, float $lightness): array {
		$saturation = $this->clamp(value: $saturation, min: 0.0, max: 1.0);
		$lightness = $this->clamp(value: $lightness, min: 0.0, max: 1.0);

		if ($saturation === 0.0) {
			$v = (int)round($lightness * 255);

			return [$v, $v, $v];
		}

		$q = (($lightness + $saturation) - ($lightness * $saturation));
		if ($lightness < 0.5) {
			$q = ($lightness * (1 + $saturation));
		}

		$p = ((2 * $lightness) - $q);

		// Use fmod(), not `%` — see the rgbToHsl() note above.
		$hNorm = (fmod($hue, 360) / 360);
		if ($hNorm < 0) {
			$hNorm += 1;
		}

		$r = $this->hueToRgbChannel(p: $p, q: $q, t: ($hNorm + (1 / 3)));
		$g = $this->hueToRgbChannel(p: $p, q: $q, t: $hNorm);
		$b = $this->hueToRgbChannel(p: $p, q: $q, t: ($hNorm - (1 / 3)));

		return [
			(int)round($r * 255),
			(int)round($g * 255),
			(int)round($b * 255),
		];
	}//end hslToRgb()

	/**
	 * One channel of the standard HSL->RGB conversion.
	 *
	 * @param float $p The P intermediate.
	 * @param float $q The Q intermediate.
	 * @param float $t The hue-shifted normalised position.
	 *
	 * @return float The channel value in [0, 1].
	 */
	private function hueToRgbChannel(float $p, float $q, float $t): float {
		if ($t < 0) {
			$t += 1;
		}

		if ($t > 1) {
			$t -= 1;
		}

		if ($t < (1 / 6)) {
			return ($p + (($q - $p) * 6 * $t));
		}

		if ($t < 0.5) {
			return $q;
		}

		if ($t < (2 / 3)) {
			return ($p + (($q - $p) * ((2 / 3) - $t) * 6));
		}

		return $p;
	}//end hueToRgbChannel()

	/**
	 * Convert an RGB triple to a lowercase `#rrggbb` hex string.
	 *
	 * @param array{0:int,1:int,2:int} $rgb The RGB triple.
	 *
	 * @return string The hex colour string.
	 */
	private function rgbToHex(array $rgb): string {
		return sprintf(
			'#%02x%02x%02x',
			$this->clampInt(value: $rgb[0]),
			$this->clampInt(value: $rgb[1]),
			$this->clampInt(value: $rgb[2])
		);
	}//end rgbToHex()

	/**
	 * Clamp a float into [min, max].
	 *
	 * @param float $value The value.
	 * @param float $min The minimum.
	 * @param float $max The maximum.
	 *
	 * @return float The clamped value.
	 */
	private function clamp(float $value, float $min, float $max): float {
		return max($min, min($max, $value));
	}//end clamp()

	/**
	 * Clamp an int channel into [0, 255].
	 *
	 * @param int $value The channel value.
	 *
	 * @return int The clamped value.
	 */
	private function clampInt(int $value): int {
		return max(0, min(255, $value));
	}//end clampInt()
}//end class
