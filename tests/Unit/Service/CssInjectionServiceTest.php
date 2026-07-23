<?php

/**
 * Unit tests for CssInjectionService.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @spec openspec/changes/render-event-injection/tasks.md#task-4.2
 */

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Service;

use OCA\NLDesign\Service\CssInjectionService;
use OCA\NLDesign\Service\CustomOverridesService;
use OCA\NLDesign\Service\DesignSystemService;
use OCA\NLDesign\Service\FontService;
use OCP\IConfig;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CssInjectionService.
 *
 * `emitStyle()`/`emitFontLink()` are the only two side-effecting calls in the
 * service (they delegate to `\OCP\Util::addStyle()`/`addHeader()`, which
 * requires a full Nextcloud bootstrap this suite does not have). They are
 * overridden via a partial mock so every test can assert the exact call
 * sequence without ever invoking the real static Nextcloud API.
 */
class CssInjectionServiceTest extends TestCase
{

    /**
     * The config mock.
     *
     * @var IConfig&MockObject
     */
    private $config;

    /**
     * The design system service mock.
     *
     * @var DesignSystemService&MockObject
     */
    private $designSystemService;

    /**
     * The custom overrides service mock.
     *
     * @var CustomOverridesService&MockObject
     */
    private $customOverridesService;

    /**
     * The font service mock.
     *
     * @var FontService&MockObject
     */
    private $fontService;

    /**
     * The URL generator mock.
     *
     * @var IURLGenerator&MockObject
     */
    private $urlGenerator;

    /**
     * Set up mocks before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->config                 = $this->createMock(IConfig::class);
        $this->designSystemService    = $this->createMock(DesignSystemService::class);
        $this->customOverridesService = $this->createMock(CustomOverridesService::class);
        $this->fontService            = $this->createMock(FontService::class);
        $this->urlGenerator           = $this->createMock(IURLGenerator::class);

        // No blanket `hasFonts()` default here: PHPUnit's InvocationMocker
        // resolves overlapping unconstrained stubs in REGISTRATION order (the
        // first-registered unconstrained stub wins for every call), so a
        // setUp()-level default would silently shadow a test's own
        // `willReturn(true)`. Each test configures `hasFonts()` explicitly
        // instead (an unconfigured bool-returning mock method defaults to
        // `false`, which is what every non-font test below relies on).
    }//end setUp()

    /**
     * Build the service under test as a partial mock that captures every
     * `emitStyle()`/`emitFontLink()` call (in order) instead of calling the
     * real static Nextcloud API.
     *
     * @param array<int, string> $styleLog Populated with each `emitStyle()` file, in call order.
     * @param array<int, string> $fontLog  Populated with each `emitFontLink()` url, in call order.
     *
     * @return CssInjectionService&MockObject The service under test.
     */
    private function buildService(array &$styleLog, array &$fontLog): CssInjectionService
    {
        $service = $this->getMockBuilder(CssInjectionService::class)
            ->setConstructorArgs(
                [
                    $this->config,
                    $this->designSystemService,
                    $this->customOverridesService,
                    $this->fontService,
                    $this->urlGenerator,
                ]
            )
            ->onlyMethods(['emitStyle', 'emitFontLink'])
            ->getMock();

        $service->method('emitStyle')->willReturnCallback(
            function (string $file) use (&$styleLog) {
                $styleLog[] = $file;
            }
        );
        $service->method('emitFontLink')->willReturnCallback(
            function (string $url) use (&$fontLog) {
                $fontLog[] = $url;
            }
        );

        return $service;
    }//end buildService()

    /**
     * Configure the config mock to resolve to a given appconfig value map,
     * defaulting `themed_contexts` to absent (all contexts themed).
     *
     * @param array<string, string> $overrides Appconfig key => value overrides.
     *
     * @return void
     */
    private function configureAppValues(array $overrides = []): void
    {
        $defaults = [
            'token_set'        => 'nextcloud',
            'hide_slogan'      => '0',
            'show_menu_labels' => '0',
            'themed_contexts'  => '[]',
        ];
        $values = array_merge($defaults, $overrides);

        $this->config->method('getAppValue')->willReturnCallback(
            function (string $app, string $key, string $default) use ($values) {
                return $values[$key] ?? $default;
            }
        );
    }//end configureAppValues()

    /**
     * The standard nldesign order: design-system stylesheets (declared
     * order), token set, icon/error contrast, custom-overrides — layers 1-8.
     */
    public function testStandardNldesignOrder(): void
    {
        $this->configureAppValues(['token_set' => 'rijkshuisstijl']);
        $this->designSystemService->method('getTokenSetMeta')->with('rijkshuisstijl')
            ->willReturn(['design_system' => 'nldesign']);
        $this->designSystemService->method('getDesignSystem')->with('nldesign')->willReturn(
            [
                'id'          => 'nldesign',
                'name'        => 'NL Design System',
                'description' => '',
                'stylesheets' => [
                    'systems/nldesign/fonts',
                    'systems/nldesign/defaults',
                    'systems/nldesign/utrecht-bridge',
                    'systems/nldesign/theme',
                    'systems/nldesign/overrides',
                    'systems/nldesign/element-overrides',
                ],
            ]
        );

        $styleLog = [];
        $fontLog  = [];
        $service  = $this->buildService(styleLog: $styleLog, fontLog: $fontLog);
        $service->inject('user');

        $this->assertSame(
            [
                'systems/nldesign/fonts',
                'systems/nldesign/defaults',
                'systems/nldesign/utrecht-bridge',
                'systems/nldesign/theme',
                'systems/nldesign/overrides',
                'systems/nldesign/element-overrides',
                'tokens/rijkshuisstijl',
                'icon-contrast',
                'error-contrast',
                'custom-overrides',
            ],
            $styleLog
        );
        $this->assertSame([], $fontLog);
        $this->customOverridesService->expects($this->never())->method('ensureExists');
    }//end testStandardNldesignOrder()

    /**
     * The "none" design system (stock Nextcloud) loads no layer 1-7
     * stylesheet and no token/contrast CSS, but custom-overrides still loads.
     */
    public function testNoneDesignSystemLoadsNoStylesheets(): void
    {
        $this->configureAppValues(['token_set' => 'nextcloud']);
        $this->designSystemService->method('getTokenSetMeta')->willReturn(['design_system' => 'none']);
        $this->designSystemService->method('getDesignSystem')->with('none')->willReturn(
            [
                'id'          => 'none',
                'name'        => 'No design system',
                'description' => '',
                'stylesheets' => [],
            ]
        );

        $styleLog = [];
        $fontLog  = [];
        $service  = $this->buildService(styleLog: $styleLog, fontLog: $fontLog);
        $service->inject('user');

        $this->assertSame(['custom-overrides'], $styleLog);
        $this->assertSame([], $fontLog);
    }//end testNoneDesignSystemLoadsNoStylesheets()

    /**
     * Custom overrides load after all design-system and token layers, and
     * `ensureExists()` runs before the stylesheet is emitted.
     */
    public function testCustomOverridesAlwaysLoadedLast(): void
    {
        $this->configureAppValues();
        $this->designSystemService->method('getTokenSetMeta')->willReturn(['design_system' => 'nldesign']);
        $this->designSystemService->method('getDesignSystem')->willReturn(
            [
                'id'          => 'nldesign',
                'name'        => 'NL Design System',
                'description' => '',
                'stylesheets' => ['systems/nldesign/fonts'],
            ]
        );

        $ensureExistsCalledBeforeStyle = false;
        $this->customOverridesService->expects($this->once())->method('ensureExists')
            ->willReturnCallback(
                function () use (&$ensureExistsCalledBeforeStyle) {
                    $ensureExistsCalledBeforeStyle = true;
                }
            );

        $styleLog = [];
        $fontLog  = [];
        $service  = $this->buildService(styleLog: $styleLog, fontLog: $fontLog);
        $service->inject('user');

        $this->assertTrue($ensureExistsCalledBeforeStyle);
        $this->assertSame(end($styleLog), 'custom-overrides');
    }//end testCustomOverridesAlwaysLoadedLast()

    /**
     * hide-slogan and show-menu-labels load last, after custom-overrides,
     * only when their respective appconfig flags are enabled.
     */
    public function testConditionalStylesheetsLoadedWhenEnabled(): void
    {
        $this->configureAppValues(['hide_slogan' => '1', 'show_menu_labels' => '1']);
        $this->designSystemService->method('getTokenSetMeta')->willReturn(['design_system' => 'nldesign']);
        $this->designSystemService->method('getDesignSystem')->willReturn(
            [
                'id'          => 'nldesign',
                'name'        => 'NL Design System',
                'description' => '',
                'stylesheets' => [],
            ]
        );

        $styleLog = [];
        $fontLog  = [];
        $service  = $this->buildService(styleLog: $styleLog, fontLog: $fontLog);
        $service->inject('user');

        $this->assertSame(
            ['tokens/nextcloud', 'icon-contrast', 'error-contrast', 'custom-overrides', 'hide-slogan', 'show-menu-labels'],
            $styleLog
        );
    }//end testConditionalStylesheetsLoadedWhenEnabled()

    /**
     * The conditional stylesheets are absent when their flags are disabled.
     */
    public function testConditionalStylesheetsAbsentWhenDisabled(): void
    {
        $this->configureAppValues();
        $this->designSystemService->method('getTokenSetMeta')->willReturn(['design_system' => 'nldesign']);
        $this->designSystemService->method('getDesignSystem')->willReturn(
            [
                'id'          => 'nldesign',
                'name'        => 'NL Design System',
                'description' => '',
                'stylesheets' => [],
            ]
        );

        $styleLog = [];
        $fontLog  = [];
        $service  = $this->buildService(styleLog: $styleLog, fontLog: $fontLog);
        $service->inject('user');

        $this->assertNotContains('hide-slogan', $styleLog);
        $this->assertNotContains('show-menu-labels', $styleLog);
    }//end testConditionalStylesheetsAbsentWhenDisabled()

    /**
     * Custom fonts inject a `<link>` header (not a static stylesheet) after
     * the token-set styles, only when at least one font is configured, and
     * never for the "none" design system.
     */
    public function testCustomFontsInjectedWhenConfigured(): void
    {
        $this->configureAppValues();
        $this->designSystemService->method('getTokenSetMeta')->willReturn(['design_system' => 'nldesign']);
        $this->designSystemService->method('getDesignSystem')->willReturn(
            [
                'id'          => 'nldesign',
                'name'        => 'NL Design System',
                'description' => '',
                'stylesheets' => [],
            ]
        );
        $this->fontService->method('hasFonts')->willReturn(true);
        $this->fontService->method('getRevision')->willReturn(3);
        $this->urlGenerator->method('linkToRoute')->with('nldesign.font.css')
            ->willReturn('https://example.test/apps/nldesign/fonts/css');

        $styleLog = [];
        $fontLog  = [];
        $service  = $this->buildService(styleLog: $styleLog, fontLog: $fontLog);
        $service->inject('user');

        $this->assertSame(['https://example.test/apps/nldesign/fonts/css?v=3'], $fontLog);
    }//end testCustomFontsInjectedWhenConfigured()

    /**
     * No font link is emitted for the "none" design system, even with fonts configured.
     */
    public function testCustomFontsNotInjectedForNoneDesignSystem(): void
    {
        $this->configureAppValues();
        $this->designSystemService->method('getTokenSetMeta')->willReturn(['design_system' => 'none']);
        $this->designSystemService->method('getDesignSystem')->willReturn(
            [
                'id'          => 'none',
                'name'        => 'No design system',
                'description' => '',
                'stylesheets' => [],
            ]
        );
        $this->fontService->method('hasFonts')->willReturn(true);

        $styleLog = [];
        $fontLog  = [];
        $service  = $this->buildService(styleLog: $styleLog, fontLog: $fontLog);
        $service->inject('user');

        $this->assertSame([], $fontLog);
    }//end testCustomFontsNotInjectedForNoneDesignSystem()

    /**
     * Absent `themed_contexts` themes every context (byte-identical default).
     */
    public function testAbsentThemedContextsThemesEveryContext(): void
    {
        $this->configureAppValues(['themed_contexts' => '[]']);
        $this->designSystemService->method('getTokenSetMeta')->willReturn(['design_system' => 'nldesign']);
        $this->designSystemService->method('getDesignSystem')->willReturn(
            [
                'id'          => 'nldesign',
                'name'        => 'NL Design System',
                'description' => '',
                'stylesheets' => [],
            ]
        );

        foreach (['user', 'login', 'guest', 'public', 'error'] as $context) {
            $styleLog = [];
            $fontLog  = [];
            $service  = $this->buildService(styleLog: $styleLog, fontLog: $fontLog);
            $service->inject($context);

            $this->assertContains('custom-overrides', $styleLog, 'context '.$context.' must be themed');
        }
    }//end testAbsentThemedContextsThemesEveryContext()

    /**
     * `["user"]` excludes every other configured context — `login` injects
     * nothing, `user` remains fully themed.
     */
    public function testConfiguredListExcludesUnlistedContexts(): void
    {
        $this->configureAppValues(['themed_contexts' => '["user"]']);
        $this->designSystemService->method('getTokenSetMeta')->willReturn(['design_system' => 'nldesign']);
        $this->designSystemService->method('getDesignSystem')->willReturn(
            [
                'id'          => 'nldesign',
                'name'        => 'NL Design System',
                'description' => '',
                'stylesheets' => [],
            ]
        );

        $loginStyleLog = [];
        $loginFontLog  = [];
        $loginService  = $this->buildService(styleLog: $loginStyleLog, fontLog: $loginFontLog);
        $loginService->inject('login');
        $this->assertSame([], $loginStyleLog);

        $userStyleLog = [];
        $userFontLog  = [];
        $userService  = $this->buildService(styleLog: $userStyleLog, fontLog: $userFontLog);
        $userService->inject('user');
        $this->assertContains('custom-overrides', $userStyleLog);
    }//end testConfiguredListExcludesUnlistedContexts()

    /**
     * Unparseable JSON in `themed_contexts` fails open to themed, without raising.
     */
    public function testInvalidJsonThemedContextsFailsOpen(): void
    {
        $this->configureAppValues(['themed_contexts' => 'not-json{']);
        $this->designSystemService->method('getTokenSetMeta')->willReturn(['design_system' => 'nldesign']);
        $this->designSystemService->method('getDesignSystem')->willReturn(
            [
                'id'          => 'nldesign',
                'name'        => 'NL Design System',
                'description' => '',
                'stylesheets' => [],
            ]
        );

        $styleLog = [];
        $fontLog  = [];
        $service  = $this->buildService(styleLog: $styleLog, fontLog: $fontLog);
        $service->inject('public');

        $this->assertContains('custom-overrides', $styleLog);
    }//end testInvalidJsonThemedContextsFailsOpen()

    /**
     * A non-array JSON value in `themed_contexts` also fails open to themed.
     */
    public function testNonArrayJsonThemedContextsFailsOpen(): void
    {
        $this->configureAppValues(['themed_contexts' => '"not-an-array"']);
        $this->designSystemService->method('getTokenSetMeta')->willReturn(['design_system' => 'nldesign']);
        $this->designSystemService->method('getDesignSystem')->willReturn(
            [
                'id'          => 'nldesign',
                'name'        => 'NL Design System',
                'description' => '',
                'stylesheets' => [],
            ]
        );

        $styleLog = [];
        $fontLog  = [];
        $service  = $this->buildService(styleLog: $styleLog, fontLog: $fontLog);
        $service->inject('guest');

        $this->assertContains('custom-overrides', $styleLog);
    }//end testNonArrayJsonThemedContextsFailsOpen()

    /**
     * An unrecognized context value (a future/unmapped `renderAs`) is always
     * themed, even when a configured list would otherwise exclude "user".
     */
    public function testUnknownContextAlwaysThemed(): void
    {
        $this->configureAppValues(['themed_contexts' => '["login"]']);
        $this->designSystemService->method('getTokenSetMeta')->willReturn(['design_system' => 'nldesign']);
        $this->designSystemService->method('getDesignSystem')->willReturn(
            [
                'id'          => 'nldesign',
                'name'        => 'NL Design System',
                'description' => '',
                'stylesheets' => [],
            ]
        );

        $styleLog = [];
        $fontLog  = [];
        $service  = $this->buildService(styleLog: $styleLog, fontLog: $fontLog);
        $service->inject('blank');

        $this->assertContains('custom-overrides', $styleLog);
    }//end testUnknownContextAlwaysThemed()
}//end class
