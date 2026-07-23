<?php

/**
 * Unit tests for DarkPaletteService.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @spec openspec/specs/dark-mode/spec.md
 */

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Service;

use OCA\NLDesign\Service\ContrastService;
use OCA\NLDesign\Service\CssParserService;
use OCA\NLDesign\Service\DarkPaletteService;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Covers derivation with known color fixtures, the WCAG verification loop,
 * hand-authored precedence, generation idempotence, and the real shipped
 * `rijkshuisstijl`/`amsterdam` token sets (tasks.md#task-5.1).
 */
class DarkPaletteServiceTest extends TestCase
{

    /**
     * The WCAG contrast service (real instance — exact math matters here).
     *
     * @var ContrastService
     */
    private ContrastService $contrast;

    /**
     * The service under test (constructed per-test against either a temp
     * fixture dir or the real worktree app path).
     *
     * @var DarkPaletteService
     */
    private DarkPaletteService $service;

    /**
     * The temp app directory standing in for the nldesign app path.
     *
     * @var string
     */
    private string $appDir;

    /**
     * Set up a temp app dir with a minimal defaults.css + tokens/ layout.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->contrast = new ContrastService();
        $this->appDir   = sys_get_temp_dir().'/nldesign-dark-test-'.uniqid();
        mkdir($this->appDir.'/css/systems/nldesign', 0777, true);
        mkdir($this->appDir.'/css/tokens', 0777, true);

        file_put_contents(
            $this->appDir.'/css/systems/nldesign/defaults.css',
            ":root {\n\t--nldesign-color-primary: #154273;\n\t--nldesign-color-primary-text: #ffffff;\n}\n"
        );

        $this->service = $this->makeService(appDir: $this->appDir);
    }//end setUp()

    /**
     * Remove the temp app dir after each test.
     */
    protected function tearDown(): void
    {
        $this->rrmdir($this->appDir);
        parent::tearDown();
    }//end tearDown()

    /**
     * Build a DarkPaletteService against a given app dir.
     *
     * @param string $appDir The app root path to resolve via the mocked IAppManager.
     *
     * @return DarkPaletteService The constructed service.
     */
    private function makeService(string $appDir): DarkPaletteService
    {
        $appManager = $this->createMock(IAppManager::class);
        $appManager->method('getAppPath')->willReturn($appDir);

        return new DarkPaletteService(
            $this->contrast,
            new CssParserService(),
            $appManager,
            $this->createMock(LoggerInterface::class)
        );
    }//end makeService()

    /**
     * Recursively remove a directory tree.
     *
     * @param string $dir The directory to remove.
     *
     * @return void
     */
    private function rrmdir(string $dir): void
    {
        if (is_dir($dir) === false) {
            return;
        }

        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir.'/'.$entry;
            if (is_dir($path) === true) {
                $this->rrmdir($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }//end rrmdir()

    /**
     * Extract the HSL hue (degrees) of a hex colour, for hue-preservation assertions.
     *
     * @param string $hex The hex colour.
     *
     * @return float The hue in degrees.
     */
    private function hueOf(string $hex): float
    {
        $rgb = $this->contrast->parseColor(value: $hex);
        $r   = ($rgb[0] / 255);
        $g   = ($rgb[1] / 255);
        $b   = ($rgb[2] / 255);
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);

        if ($max === $min) {
            return 0.0;
        }

        $delta = ($max - $min);
        if ($max === $r) {
            $h = ((($g - $b) / $delta) + (($g < $b) ? 6 : 0));
        } else if ($max === $g) {
            $h = ((($b - $r) / $delta) + 2);
        } else {
            $h = ((($r - $g) / $delta) + 4);
        }

        $h = fmod($h * 60, 360);

        return ($h < 0) ? ($h + 360) : $h;
    }//end hueOf()

    /**
     * Hue is preserved (within 2°) for a background-class token that is NOT
     * the brand-primary exception token.
     */
    public function testHuePreservedForBackgroundToken(): void
    {
        $derived = $this->service->deriveDarkDeclarations(['--nldesign-color-header-background' => '#154273']);

        $sourceHue  = $this->hueOf(hex: '#154273');
        $derivedHue = $this->hueOf(hex: $derived['--nldesign-color-header-background']);

        $delta = abs($sourceHue - $derivedHue);
        $delta = min($delta, (360 - $delta));

        $this->assertLessThan(2.0, $delta);
    }//end testHuePreservedForBackgroundToken()

    /**
     * The brand-primary exception keeps `--nldesign-color-primary` at its
     * light value (hue delta 0) when the light primary/primary-text pair
     * already passes AA — the rijkshuisstijl blue stays recognisable.
     */
    public function testBrandPrimaryExceptionKeepsRecognisableBlue(): void
    {
        $derived = $this->service->deriveDarkDeclarations(
            [
                '--nldesign-color-primary'      => '#154273',
                '--nldesign-color-primary-text' => '#ffffff',
            ]
        );

        $this->assertSame('#154273', $derived['--nldesign-color-primary']);
    }//end testBrandPrimaryExceptionKeepsRecognisableBlue()

    /**
     * A failing brand primary (light pair below AA) is NOT exempted — it
     * gets the normal background-class dark transform instead of being kept as-is.
     */
    public function testFailingBrandPrimaryIsNotExempted(): void
    {
        $derived = $this->service->deriveDarkDeclarations(
            [
                // Pale yellow vs white text: fails AA badly in light mode too.
                '--nldesign-color-primary'      => '#ffff99',
                '--nldesign-color-primary-text' => '#ffffff',
            ]
        );

        $this->assertNotSame('#ffff99', $derived['--nldesign-color-primary']);
    }//end testFailingBrandPrimaryIsNotExempted()

    /**
     * A light background token (L≈0.96) derives to a dark lightness in [0.08, 0.16].
     */
    public function testLightBackgroundDerivesToDarkLightness(): void
    {
        $derived = $this->service->deriveDarkDeclarations(['--nldesign-color-background' => '#F5F6F7']);

        $l = $this->lightnessOf(hex: $derived['--nldesign-color-background']);
        $this->assertGreaterThanOrEqual(0.08, $l);
        $this->assertLessThanOrEqual(0.16, $l);
    }//end testLightBackgroundDerivesToDarkLightness()

    /**
     * A dark-ish text token (L≈0.2) derives to a light lightness in [0.62, 0.92].
     */
    public function testDarkTextDerivesToLightLightness(): void
    {
        $derived = $this->service->deriveDarkDeclarations(['--nldesign-color-text' => '#333333']);

        $l = $this->lightnessOf(hex: $derived['--nldesign-color-text']);
        $this->assertGreaterThanOrEqual(0.62, $l);
        $this->assertLessThanOrEqual(0.92, $l);
    }//end testDarkTextDerivesToLightLightness()

    /**
     * Lightness helper for the fixture assertions above.
     *
     * @param string $hex The hex colour.
     *
     * @return float The HSL lightness in [0, 1].
     */
    private function lightnessOf(string $hex): float
    {
        $rgb = $this->contrast->parseColor(value: $hex);

        return ((max($rgb) + min($rgb)) / 2 / 255);
    }//end lightnessOf()

    /**
     * Non-color tokens (size, font-family) never appear in the dark output.
     */
    public function testNonColorTokensPassThroughUntouched(): void
    {
        $derived = $this->service->deriveDarkDeclarations(
            [
                '--nldesign-size-lint'   => '48px',
                '--nldesign-font-family' => "'Fira Sans', sans-serif",
            ]
        );

        $this->assertArrayNotHasKey('--nldesign-size-lint', $derived);
        $this->assertArrayNotHasKey('--nldesign-font-family', $derived);
    }//end testNonColorTokensPassThroughUntouched()

    /**
     * A `var()` alias and a gradient are unparseable and are skipped, no exception.
     */
    public function testUnparseableValuesAreSkipped(): void
    {
        $derived = $this->service->deriveDarkDeclarations(
            [
                '--nldesign-component-button-color' => 'var(--nldesign-color-primary)',
                '--nldesign-color-weird-gradient'    => 'linear-gradient(90deg, #fff, #000)',
            ]
        );

        $this->assertSame([], $derived);
    }//end testUnparseableValuesAreSkipped()

    /**
     * `-rgb` companion tokens are regenerated from their derived base token.
     */
    public function testRgbCompanionsMatchDerivedBase(): void
    {
        $derived = $this->service->deriveDarkDeclarations(
            [
                '--nldesign-color-error'     => '#d52b1e',
                '--nldesign-color-error-rgb' => '213, 43, 30',
            ]
        );

        $expectedRgb = $this->contrast->parseColor(value: $derived['--nldesign-color-error']);
        $this->assertSame(implode(', ', $expectedRgb), $derived['--nldesign-color-error-rgb']);
    }//end testRgbCompanionsMatchDerivedBase()

    /**
     * A pathological pair (bright yellow bg / white fg) is repaired by the
     * verification loop to a passing pair (snap fallback).
     */
    public function testPathologicalPairIsRepairedToPassing(): void
    {
        $result = $this->service->verifyAndRepair(
            [
                '--nldesign-color-primary'      => '#FFFF00',
                '--nldesign-color-primary-text' => '#FFFFFF',
            ]
        );

        $this->assertSame([], $this->contrast->check(declarations: $result['declarations']));
        $this->assertNotSame('#FFFFFF', $result['declarations']['--nldesign-color-primary-text']);
    }//end testPathologicalPairIsRepairedToPassing()

    /**
     * A hand-authored (protected) failing pair is emitted unchanged and
     * produces a warning — never rewritten by the loop.
     */
    public function testHandAuthoredFailurePreservedAndWarned(): void
    {
        // Identical fg/bg -> ratio exactly 1:1, unambiguously below the 4.5:1
        // threshold (mirrors the spec scenario's "measures 3.2:1" example).
        $declarations = [
            '--nldesign-color-primary'      => '#4844AD',
            '--nldesign-color-primary-text' => '#4844AD',
        ];

        $result = $this->service->verifyAndRepair(
            declarations: $declarations,
            protectedTokens: ['--nldesign-color-primary', '--nldesign-color-primary-text']
        );

        $this->assertSame('#4844AD', $result['declarations']['--nldesign-color-primary']);
        $this->assertSame('#4844AD', $result['declarations']['--nldesign-color-primary-text']);
        $this->assertNotEmpty($result['warnings']);
    }//end testHandAuthoredFailurePreservedAndWarned()

    /**
     * A passing pair produces no warnings and is left untouched.
     */
    public function testPassingPairProducesNoWarnings(): void
    {
        $result = $this->service->verifyAndRepair(
            [
                '--nldesign-color-primary'      => '#154273',
                '--nldesign-color-primary-text' => '#ffffff',
            ]
        );

        $this->assertSame([], $result['warnings']);
    }//end testPassingPairProducesNoWarnings()

    /**
     * `renderDarkCss()` emits the exact dual-scope selector shape from
     * design.md §2: the four `:not()` exclusions and the explicit
     * `body[data-theme-dark], body[data-themes*=dark]` block.
     */
    public function testRenderDarkCssSelectorShape(): void
    {
        $css = $this->service->renderDarkCss(
            setId: 'amsterdam',
            declarations: ['--nldesign-color-primary' => '#4844ad'],
            sourceHash: 'sha256:deadbeef'
        );

        $this->assertStringContainsString('@media (prefers-color-scheme: dark) {', $css);
        $this->assertStringContainsString(
            'body:not([data-theme-light]):not([data-theme-dark]):not([data-theme-light-highcontrast]):not([data-theme-dark-highcontrast]) {',
            $css
        );
        $this->assertStringContainsString('body[data-theme-dark],', $css);
        $this->assertStringContainsString('body[data-themes*=dark] {', $css);
        $this->assertStringContainsString('--nldesign-color-primary: #4844ad;', $css);
        $this->assertStringContainsString('sha256:deadbeef', $css);
        $this->assertStringNotContainsString('!important', $css);
    }//end testRenderDarkCssSelectorShape()

    /**
     * `!important` is never used — body-level scope already out-specifies the
     * light `:root` layer without it.
     */
    public function testGeneratedCssNeverUsesImportant(): void
    {
        $css = $this->service->renderDarkCss(setId: 'x', declarations: ['--nldesign-color-primary' => '#000000'], sourceHash: 'sha256:abc');
        $this->assertStringNotContainsString('!important', $css);
    }//end testGeneratedCssNeverUsesImportant()

    /**
     * Eligibility: `none` and `high-contrast` design systems are excluded;
     * everything else is eligible.
     */
    public function testEligibility(): void
    {
        $this->assertFalse($this->service->isEligible(designSystemId: 'none'));
        $this->assertFalse($this->service->isEligible(designSystemId: 'high-contrast'));
        $this->assertTrue($this->service->isEligible(designSystemId: 'nldesign'));
        $this->assertTrue($this->service->isEligible(designSystemId: 'lasuite'));
    }//end testEligibility()

    /**
     * `generateForSet()` merges hand-authored dark-block overrides over the
     * derived declarations — the override wins.
     */
    public function testGenerateForSetHandAuthoredOverrideWins(): void
    {
        file_put_contents(
            $this->appDir.'/token-sets.json',
            json_encode([['id' => 'example', 'design_system' => 'nldesign']])
        );
        file_put_contents(
            $this->appDir.'/css/tokens/example.css',
            ":root {\n\t--nldesign-color-primary: #154273;\n\t--nldesign-color-primary-text: #ffffff;\n}\n\n"
            ."@media (prefers-color-scheme: dark) {\n\t:root {\n\t\t--nldesign-color-primary: #4844AD;\n\t}\n}\n"
        );

        $generated = $this->service->generateForSet(setId: 'example');

        $this->assertNotNull($generated);
        $this->assertStringContainsString('--nldesign-color-primary: #4844AD;', $generated['css']);
    }//end testGenerateForSetHandAuthoredOverrideWins()

    /**
     * `generateForSet()` returns null for an ineligible design system
     * (`none`) — no dark output is built at all.
     */
    public function testGenerateForSetSkipsIneligibleDesignSystem(): void
    {
        file_put_contents(
            $this->appDir.'/token-sets.json',
            json_encode([['id' => 'stock', 'design_system' => 'none']])
        );
        file_put_contents($this->appDir.'/css/tokens/stock.css', ":root {\n\t--nldesign-color-primary: #0082c9;\n}\n");

        $this->assertNull($this->service->generateForSet(setId: 'stock'));
    }//end testGenerateForSetSkipsIneligibleDesignSystem()

    /**
     * `generateAndWrite()` writes the file, and a second call without
     * `--force` is a no-op (fresh-file skip) — idempotence.
     */
    public function testGenerateAndWriteIsIdempotentWithoutForce(): void
    {
        file_put_contents(
            $this->appDir.'/token-sets.json',
            json_encode([['id' => 'idem', 'design_system' => 'nldesign']])
        );
        file_put_contents($this->appDir.'/css/tokens/idem.css', ":root {\n\t--nldesign-color-primary: #154273;\n}\n");

        $first = $this->service->generateAndWrite(setId: 'idem');
        $this->assertTrue($first['written']);

        $darkFile     = $this->appDir.'/css/tokens/dark/idem.css';
        $firstWritten = file_get_contents($darkFile);

        $second = $this->service->generateAndWrite(setId: 'idem');
        $this->assertFalse($second['written']);
        $this->assertTrue($second['skipped']);
        $this->assertSame('fresh', $second['reason']);
        $this->assertSame($firstWritten, file_get_contents($darkFile));
    }//end testGenerateAndWriteIsIdempotentWithoutForce()

    /**
     * `--force` rewrites despite a fresh hash.
     */
    public function testGenerateAndWriteForceRewrites(): void
    {
        file_put_contents(
            $this->appDir.'/token-sets.json',
            json_encode([['id' => 'idem', 'design_system' => 'nldesign']])
        );
        file_put_contents($this->appDir.'/css/tokens/idem.css', ":root {\n\t--nldesign-color-primary: #154273;\n}\n");

        $this->service->generateAndWrite(setId: 'idem');
        $result = $this->service->generateAndWrite(setId: 'idem', force: true);

        $this->assertTrue($result['written']);
    }//end testGenerateAndWriteForceRewrites()

    /**
     * `discoverAllSetIds()` finds every `.css` file directly under
     * `css/tokens/` (not the `dark/` subdirectory itself).
     */
    public function testDiscoverAllSetIds(): void
    {
        file_put_contents($this->appDir.'/css/tokens/one.css', ':root { --nldesign-color-primary: #000; }');
        file_put_contents($this->appDir.'/css/tokens/two.css', ':root { --nldesign-color-primary: #000; }');
        mkdir($this->appDir.'/css/tokens/dark', 0777, true);

        $ids = $this->service->discoverAllSetIds();

        $this->assertContains('one', $ids);
        $this->assertContains('two', $ids);
        $this->assertNotContains('dark', $ids);
    }//end testDiscoverAllSetIds()

    /**
     * Deleting a custom set's dark variant removes the file (best-effort, no
     * exception when it never existed).
     */
    public function testDeleteDarkVariant(): void
    {
        mkdir($this->appDir.'/css/tokens/dark', 0777, true);
        file_put_contents($this->appDir.'/css/tokens/dark/custom-x.css', '/* generated */');

        $this->service->deleteDarkVariant(setId: 'custom-x');
        $this->assertFileDoesNotExist($this->appDir.'/css/tokens/dark/custom-x.css');

        // Deleting again (already gone) must not throw.
        $this->service->deleteDarkVariant(setId: 'custom-x');
        $this->addToAssertionCount(1);
    }//end testDeleteDarkVariant()

    /**
     * `logo_dark` theming metadata is emitted as a dark-scoped
     * `--nldesign-logo-url` override, relative to `css/tokens/dark/`.
     */
    public function testLogoDarkEmitsRelativeLogoUrlOverride(): void
    {
        file_put_contents(
            $this->appDir.'/token-sets.json',
            json_encode(
                [
                    [
                        'id'            => 'withlogo',
                        'design_system' => 'nldesign',
                        'theming'       => ['logo_dark' => 'img/logos/withlogo-dark.svg'],
                    ],
                ]
            )
        );
        file_put_contents($this->appDir.'/css/tokens/withlogo.css', ":root {\n\t--nldesign-color-primary: #154273;\n}\n");

        $generated = $this->service->generateForSet(setId: 'withlogo');

        $this->assertNotNull($generated);
        $this->assertStringContainsString(
            "--nldesign-logo-url: url('../../../../img/logos/withlogo-dark.svg');",
            $generated['css']
        );
    }//end testLogoDarkEmitsRelativeLogoUrlOverride()

    /**
     * Real shipped-set integration: `rijkshuisstijl` and `amsterdam`'s
     * generated dark declarations pass every evaluable ContrastService pair
     * at its AA threshold — the verification-loop contract, exercised
     * against the actual worktree files (not synthetic fixtures).
     *
     * @dataProvider shippedSetProvider
     *
     * @param string $setId The shipped token set id.
     */
    public function testRealShippedSetsPassContrastAfterGeneration(string $setId): void
    {
        $realAppRoot = dirname(__DIR__, 3);
        if (is_file($realAppRoot.'/css/tokens/'.$setId.'.css') === false) {
            $this->markTestSkipped('Real app tree not available at '.$realAppRoot);
        }

        $service   = $this->makeService(appDir: $realAppRoot);
        $generated = $service->generateForSet(setId: $setId);

        $this->assertNotNull($generated);

        foreach ($generated['warnings'] as $warning) {
            $this->assertTrue(
                ($warning['unevaluated'] ?? false) === true,
                'Unexpected non-unevaluated contrast warning for '.$setId.': '.$warning['pair'].' = '.($warning['ratio'] ?? 'n/a')
            );
        }
    }//end testRealShippedSetsPassContrastAfterGeneration()

    /**
     * Data provider for {@see self::testRealShippedSetsPassContrastAfterGeneration()}.
     *
     * @return array<string, array{0: string}> The shipped set ids to exercise.
     */
    public static function shippedSetProvider(): array
    {
        return [
            'rijkshuisstijl' => ['rijkshuisstijl'],
            'amsterdam'      => ['amsterdam'],
        ];
    }//end shippedSetProvider()
}//end class
