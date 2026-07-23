<?php

/**
 * La Suite numérique design stack — manifest resolution, contrast and
 * license-compliance regression test.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category  Test
 * @package   OCA\NLDesign
 * @author    Conduction <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://codeberg.org/Conduction/nldesign
 *
 * @spec openspec/specs/css-architecture/spec.md
 * @spec openspec/specs/token-sets/spec.md
 */

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit;

use OCA\NLDesign\Service\ContrastService;
use OCA\NLDesign\Service\CssParserService;
use OCA\NLDesign\Service\DesignSystemService;
use OCA\NLDesign\Service\ShippedTokenSetAuditService;
use OCA\NLDesign\Service\TokenSetService;
use OCP\App\IAppManager;
use OCP\IConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Static + service-level regression test for the `lasuite` design system and
 * token set: manifest resolution in declared order, WCAG contrast, and the
 * license-compliance invariants (no Marianne bytes, no bundled logo).
 */
class LasuiteDesignStackTest extends TestCase
{

    /**
     * Repository root, derived from this test file's location.
     *
     * @return string
     */
    private function repoRoot(): string
    {
        return \dirname(__DIR__, 2);
    }

    /**
     * Build a DesignSystemService reading the real repository manifests.
     *
     * @return DesignSystemService
     */
    private function designSystemService(): DesignSystemService
    {
        $appManager = $this->createMock(IAppManager::class);
        $appManager->method('getAppPath')->willReturn($this->repoRoot());

        return new DesignSystemService($appManager);
    }//end designSystemService()

    /**
     * Build a TokenSetService reading the real repository manifests (no
     * custom-set appconfig entries).
     *
     * @return TokenSetService
     */
    private function tokenSetService(): TokenSetService
    {
        $appManager = $this->createMock(IAppManager::class);
        $appManager->method('getAppPath')->willReturn($this->repoRoot());

        $config = $this->createMock(IConfig::class);
        $config->method('getAppValue')->willReturn('{}');

        $audit = new ShippedTokenSetAuditService(new ContrastService(), new CssParserService());

        return new TokenSetService($appManager, $config, $this->createMock(LoggerInterface::class), $audit);
    }//end tokenSetService()

    /**
     * `DesignSystemService::getDesignSystem('lasuite')` resolves the four
     * stylesheets in the exact declared order.
     *
     * @spec openspec/specs/css-architecture/spec.md
     */
    public function testLasuiteDesignSystemResolvesFourStylesheetsInOrder(): void
    {
        $system = $this->designSystemService()->getDesignSystem('lasuite');

        $this->assertSame('lasuite', $system['id']);
        $this->assertSame(
            [
                'systems/lasuite/fonts',
                'systems/lasuite/defaults',
                'systems/lasuite/bridge',
                'systems/lasuite/element-overrides',
            ],
            $system['stylesheets'],
            'The lasuite bundle must declare exactly these four stylesheets, in this order.'
        );
    }//end testLasuiteDesignSystemResolvesFourStylesheetsInOrder()

    /**
     * Every stylesheet declared in the lasuite bundle has a backing CSS file
     * under css/systems/lasuite/, and the font binaries directory exists.
     *
     * @spec openspec/specs/css-architecture/spec.md
     */
    public function testDeclaredStylesheetsHaveBackingFiles(): void
    {
        $system = $this->designSystemService()->getDesignSystem('lasuite');

        foreach ($system['stylesheets'] as $stylesheet) {
            $path = $this->repoRoot().'/css/'.$stylesheet.'.css';
            $this->assertFileExists($path, "Declared stylesheet '{$stylesheet}' has no backing CSS file.");
        }

        $this->assertDirectoryExists(
            $this->repoRoot().'/css/systems/lasuite/fonts',
            'The lasuite font binaries directory must exist.'
        );
        $this->assertFileExists(
            $this->repoRoot().'/css/tokens/lasuite.css',
            'The lasuite Layer-3 token file must exist.'
        );
    }//end testDeclaredStylesheetsHaveBackingFiles()

    /**
     * `token-sets.json`'s `lasuite` entry declares `design_system: "lasuite"`
     * and `DesignSystemService::getTokenSetMeta()` surfaces it unchanged.
     *
     * @spec openspec/specs/token-sets/spec.md
     */
    public function testTokenSetMetaDeclaresLasuiteDesignSystem(): void
    {
        $meta = $this->designSystemService()->getTokenSetMeta('lasuite');

        $this->assertSame('lasuite', $meta['design_system'] ?? null);
        $this->assertSame('#4844AD', $meta['theming']['primary_color'] ?? null);
        $this->assertSame('#FFFFFF', $meta['theming']['background_color'] ?? null);
    }//end testTokenSetMetaDeclaresLasuiteDesignSystem()

    /**
     * `TokenSetService::getAvailableTokenSets()` surfaces the `lasuite` entry
     * with its design system and theming metadata (manifest/CSS-file pairing
     * intact — the entry only appears because css/tokens/lasuite.css exists).
     *
     * @spec openspec/specs/token-sets/spec.md
     */
    public function testTokenSetServiceSurfacesLasuiteEntry(): void
    {
        $sets = $this->tokenSetService()->getAvailableTokenSets();
        $byId = array_column($sets, null, 'id');

        $this->assertArrayHasKey('lasuite', $byId, 'The lasuite token set must be discovered.');
        $this->assertSame('lasuite', $byId['lasuite']['design_system']);
        $this->assertSame('#4844AD', $byId['lasuite']['theming']['primary_color']);
        $this->assertSame('#FFFFFF', $byId['lasuite']['theming']['background_color']);
        $this->assertArrayNotHasKey(
            'logo',
            $byId['lasuite']['theming'],
            'The lasuite theming block must not carry a logo key (no state logos bundled).'
        );
    }//end testTokenSetServiceSurfacesLasuiteEntry()

    /**
     * An unknown design system id still falls back safely (existing
     * behaviour) — regression guard that adding `lasuite` did not disturb it.
     *
     * @spec openspec/specs/css-architecture/spec.md
     */
    public function testUnknownDesignSystemStillFallsBackSafely(): void
    {
        $system = $this->designSystemService()->getDesignSystem('__does_not_exist__');

        $this->assertSame([], $system['stylesheets']);
    }//end testUnknownDesignSystemStillFallsBackSafely()

    /**
     * The La Suite brand colour (`--lasuite-color-brand-650` / `#4844AD`)
     * against white text clears WCAG AA (>= 4.5:1) with wide margin, per the
     * "Brand palette matches Cunningham" scenario.
     *
     * @spec openspec/specs/css-architecture/spec.md
     */
    public function testBrandPrimaryTextContrastMeetsAa(): void
    {
        $contrast = new ContrastService();

        $brand = $contrast->parseColor('#4844AD');
        $white = $contrast->parseColor('#FFFFFF');
        $this->assertNotNull($brand);
        $this->assertNotNull($white);

        $ratio = $contrast->ratio($brand, $white);

        $this->assertGreaterThanOrEqual(
            4.5,
            $ratio,
            sprintf('brand-650 (#4844AD) vs white must be >= 4.5:1 for WCAG AA text; got %.2f:1.', $ratio)
        );
    }//end testBrandPrimaryTextContrastMeetsAa()

    /**
     * The interactive status-fill steps the bridge actually maps onto
     * `--nldesign-color-{error,warning,success,info}` (Cunningham's own
     * "-550" background token, not the raw "-500" swatch) carry white text
     * at WCAG AA. Guards the deliberate -550-over-500 choice documented in
     * css/systems/lasuite/defaults.css.
     *
     * @spec openspec/specs/css-architecture/spec.md
     */
    public function testStatusFillsCarryWhiteTextAtAa(): void
    {
        $contrast = new ContrastService();
        $white    = $contrast->parseColor('#FFFFFF');
        $this->assertNotNull($white);

        $fills = [
            'error-550'   => '#D7010E',
            'warning-550' => '#BC4200',
            'success-550' => '#027B3E',
            'info-550'    => '#0069CF',
        ];

        foreach ($fills as $name => $hex) {
            $rgb = $contrast->parseColor($hex);
            $this->assertNotNull($rgb, "Could not parse {$name} ({$hex}).");

            $ratio = $contrast->ratio($rgb, $white);
            $this->assertGreaterThanOrEqual(
                4.5,
                $ratio,
                sprintf('%s (%s) vs white must be >= 4.5:1 for WCAG AA text; got %.2f:1.', $name, $hex, $ratio)
            );
        }
    }//end testStatusFillsCarryWhiteTextAtAa()

    /**
     * License compliance: css/systems/lasuite/fonts/ ships an OFL.txt licence
     * file and the expected Inter woff2 weights.
     *
     * @spec openspec/specs/css-architecture/spec.md
     */
    public function testFontsDirectoryCarriesOflLicenceAndExpectedWeights(): void
    {
        $fontsDir = $this->repoRoot().'/css/systems/lasuite/fonts';

        $this->assertFileExists($fontsDir.'/OFL.txt', 'css/systems/lasuite/fonts/ must contain an OFL.txt licence file.');

        $expected = [
            'inter-latin-400-normal.woff2',
            'inter-latin-400-italic.woff2',
            'inter-latin-500-normal.woff2',
            'inter-latin-600-normal.woff2',
            'inter-latin-700-normal.woff2',
            'inter-latin-700-italic.woff2',
        ];
        foreach ($expected as $file) {
            $this->assertFileExists($fontsDir.'/'.$file, "Expected Inter font file missing: {$file}");
        }
    }//end testFontsDirectoryCarriesOflLicenceAndExpectedWeights()

    /**
     * License compliance: no file anywhere in the app matches the name
     * Marianne (case-insensitive) — the French-state font is never shipped.
     *
     * @spec openspec/specs/css-architecture/spec.md
     */
    public function testNoMarianneFileExistsAnywhereInTheApp(): void
    {
        $root     = $this->repoRoot();
        $skipDirs = ['/vendor/', '/node_modules/', '/.git/'];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        $matches = [];
        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $path = $file->getPathname();

            foreach ($skipDirs as $skip) {
                if (str_contains($path, $skip) === true) {
                    continue 2;
                }
            }

            if (preg_match('/marianne/i', $file->getFilename()) === 1) {
                $matches[] = str_replace($root.'/', '', $path);
            }
        }

        $this->assertSame([], $matches, 'No file may match the name Marianne (case-insensitive): '.implode(', ', $matches));
    }//end testNoMarianneFileExistsAnywhereInTheApp()

    /**
     * License compliance: the `lasuite` entry in `token-sets.json` carries no
     * `logo` key (no La Suite / French-state logos are bundled).
     *
     * @spec openspec/specs/token-sets/spec.md
     */
    public function testTokenSetManifestEntryHasNoLogoKey(): void
    {
        $json = json_decode((string) file_get_contents($this->repoRoot().'/token-sets.json'), true);
        $this->assertIsArray($json, 'token-sets.json must decode to an array.');

        $byId = array_column($json, null, 'id');
        $this->assertArrayHasKey('lasuite', $byId, 'token-sets.json must contain a lasuite entry.');
        $this->assertArrayNotHasKey(
            'logo',
            $byId['lasuite']['theming'] ?? [],
            'The lasuite theming block must not declare a logo key.'
        );
    }//end testTokenSetManifestEntryHasNoLogoKey()

    /**
     * The bridge layer never sets the dark-mode-compatibility variables that
     * REQ-CSS-007 reserves for Nextcloud's own auto-derivation.
     *
     * @spec openspec/specs/css-architecture/spec.md
     */
    public function testBridgeNeverSetsDarkModeCompatibilityVariables(): void
    {
        $bridge = (string) file_get_contents($this->repoRoot().'/css/systems/lasuite/bridge.css');

        foreach (
            [
                '--color-main-background:',
                '--color-main-background-rgb:',
                '--color-main-background-translucent:',
                '--color-background-plain:',
                '--background-invert-if-dark:',
                '--background-invert-if-bright:',
            ] as $forbidden
        ) {
            $this->assertStringNotContainsString(
                $forbidden,
                $bridge,
                "bridge.css must not set {$forbidden} (REQ-CSS-007 dark-mode compatibility)."
            );
        }
    }//end testBridgeNeverSetsDarkModeCompatibilityVariables()

    /**
     * The fonts layer never declares a `url()` source for Marianne — only
     * `local()` — so the browser can never fetch Marianne bytes from this app.
     *
     * @spec openspec/specs/css-architecture/spec.md
     */
    public function testFontsLayerNeverUrlSourcesMarianne(): void
    {
        $fonts = (string) file_get_contents($this->repoRoot().'/css/systems/lasuite/fonts.css');

        $this->assertMatchesRegularExpression(
            '/--lasuite-font-family:\s*Marianne,\s*Inter,\s*sans-serif/',
            $fonts,
            'fonts.css must define --lasuite-font-family with Marianne first, Inter fallback.'
        );

        // Marianne must never appear inside a url() source.
        preg_match_all('/url\([^)]*\)/i', $fonts, $urlMatches);
        foreach ($urlMatches[0] as $url) {
            $this->assertDoesNotMatchRegularExpression(
                '/marianne/i',
                $url,
                "fonts.css must not url()-source Marianne: {$url}"
            );
        }
    }//end testFontsLayerNeverUrlSourcesMarianne()
}//end class
