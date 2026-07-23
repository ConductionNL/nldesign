<?php

/**
 * Unit tests for EmailThemingService.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @spec openspec/specs/email-theming/spec.md
 */

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Service;

use OCA\NLDesign\Mail\NLDesignEMailTemplate;
use OCA\NLDesign\Service\EmailThemingService;
use OCA\NLDesign\Service\Exception\ConfigReadOnlyException;
use OCA\NLDesign\Service\Exception\FooterValidationException;
use OCA\NLDesign\Service\Exception\ForeignMailTemplateClassException;
use OCA\NLDesign\Service\TokenSetPreviewService;
use OCA\NLDesign\Service\TokenSetService;
use OCP\HintException;
use OCP\IConfig;
use OCP\IURLGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EmailThemingService.
 */
class EmailThemingServiceTest extends TestCase
{

    /**
     * The config mock.
     *
     * @var IConfig
     */
    private $config;

    /**
     * The token set service mock.
     *
     * @var TokenSetService
     */
    private $tokenSetService;

    /**
     * The token set preview service mock.
     *
     * @var TokenSetPreviewService
     */
    private $previewService;

    /**
     * The URL generator mock.
     *
     * @var IURLGenerator
     */
    private $urlGenerator;

    /**
     * The service under test.
     *
     * @var EmailThemingService
     */
    private EmailThemingService $service;

    /**
     * Set up mocks before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->config          = $this->createMock(IConfig::class);
        $this->tokenSetService = $this->createMock(TokenSetService::class);
        $this->previewService  = $this->createMock(TokenSetPreviewService::class);
        $this->urlGenerator    = $this->createMock(IURLGenerator::class);

        $this->service = new EmailThemingService(
            $this->config,
            $this->tokenSetService,
            $this->previewService,
            $this->urlGenerator
        );
    }//end setUp()

    /**
     * Enabling with a writable config.php and nothing configured writes the
     * system value.
     *
     * @return void
     */
    public function testEnableWritesSystemValue(): void
    {
        $this->config->method('getSystemValue')->willReturn('');
        $this->config->method('getSystemValueBool')->willReturn(false);
        $this->config->expects($this->once())
            ->method('setSystemValue')
            ->with('mail_template_class', NLDesignEMailTemplate::class);

        $this->service->enable();
    }//end testEnableWritesSystemValue()

    /**
     * Disabling deletes the system value.
     *
     * @return void
     */
    public function testDisableDeletesSystemValue(): void
    {
        $this->config->method('getSystemValue')->willReturn(NLDesignEMailTemplate::class);
        $this->config->method('getSystemValueBool')->willReturn(false);
        $this->config->expects($this->once())->method('deleteSystemValue')->with('mail_template_class');

        $this->service->disable();
    }//end testDisableDeletesSystemValue()

    /**
     * Enabling never clobbers a foreign mail_template_class — it throws instead.
     *
     * @return void
     */
    public function testEnableThrowsOnForeignClass(): void
    {
        $this->config->method('getSystemValue')->willReturn('OCA\\Enterprise\\Mail\\CustomTemplate');
        $this->config->method('getSystemValueBool')->willReturn(false);
        $this->config->expects($this->never())->method('setSystemValue');

        $this->expectException(ForeignMailTemplateClassException::class);
        $this->service->enable();
    }//end testEnableThrowsOnForeignClass()

    /**
     * A read-only config.php (via the config_is_read_only flag) throws
     * before attempting the write.
     *
     * @return void
     */
    public function testEnableThrowsOnReadOnlyFlag(): void
    {
        $this->config->method('getSystemValue')->willReturn('');
        $this->config->method('getSystemValueBool')->willReturn(true);
        $this->config->expects($this->never())->method('setSystemValue');

        $this->expectException(ConfigReadOnlyException::class);
        $this->service->enable();
    }//end testEnableThrowsOnReadOnlyFlag()

    /**
     * A filesystem-level read-only config.php the flag missed still throws
     * the typed exception, via the caught HintException from the write path.
     *
     * @return void
     */
    public function testEnableThrowsOnReadOnlyHintExceptionFromWrite(): void
    {
        $this->config->method('getSystemValue')->willReturn('');
        $this->config->method('getSystemValueBool')->willReturn(false);
        $this->config->method('setSystemValue')->willThrowException(new HintException('read-only', 'hint'));

        $this->expectException(ConfigReadOnlyException::class);
        $this->service->enable();
    }//end testEnableThrowsOnReadOnlyHintExceptionFromWrite()

    /**
     * Disabling a foreign class is a no-op — nldesign never touches it.
     *
     * @return void
     */
    public function testDisableLeavesForeignClassUntouched(): void
    {
        $this->config->method('getSystemValue')->willReturn('OCA\\Enterprise\\Mail\\CustomTemplate');
        $this->config->method('getSystemValueBool')->willReturn(false);
        $this->config->expects($this->never())->method('deleteSystemValue');

        $this->service->disable();
    }//end testDisableLeavesForeignClassUntouched()

    /**
     * A javascript: scheme URL is rejected before anything is persisted.
     *
     * @return void
     */
    public function testFooterUrlValidationRejectsJavascriptScheme(): void
    {
        $this->config->expects($this->never())->method('setAppValue');

        $this->expectException(FooterValidationException::class);
        $this->service->setFooterConfig('Org', 'javascript:alert(1)', '');
    }//end testFooterUrlValidationRejectsJavascriptScheme()

    /**
     * Non-URL junk is rejected.
     *
     * @return void
     */
    public function testFooterUrlValidationRejectsJunk(): void
    {
        $this->expectException(FooterValidationException::class);
        $this->service->setFooterConfig('Org', 'not a url', '');
    }//end testFooterUrlValidationRejectsJunk()

    /**
     * Valid http(s) URLs are accepted and persisted.
     *
     * @return void
     */
    public function testFooterUrlValidationAcceptsHttpsAndPersists(): void
    {
        $this->config->expects($this->exactly(3))->method('setAppValue');
        $this->config->method('getAppValue')->willReturnCallback(
            fn ($app, $key, $default) => match ($key) {
                'email_footer_org_name' => 'Org',
                'email_footer_accessibility_url' => 'https://example.org/a11y',
                'email_footer_privacy_url' => 'https://example.org/privacy',
                default => $default,
            }
        );

        $result = $this->service->setFooterConfig('Org', 'https://example.org/a11y', 'https://example.org/privacy');

        $this->assertSame('Org', $result['orgName']);
        $this->assertSame('https://example.org/a11y', $result['accessibilityUrl']);
        $this->assertSame('https://example.org/privacy', $result['privacyUrl']);
    }//end testFooterUrlValidationAcceptsHttpsAndPersists()

    /**
     * Empty URL values are always valid (omitted, never validated as a scheme).
     *
     * @return void
     */
    public function testFooterUrlValidationAllowsEmptyValues(): void
    {
        $this->config->method('getAppValue')->willReturn('');
        $result = $this->service->setFooterConfig('', '', '');
        $this->assertSame('', $result['accessibilityUrl']);
    }//end testFooterUrlValidationAllowsEmptyValues()

    /**
     * The active token set's manifest theming metadata is used verbatim
     * when present, and the logo resolves to an absolute URL.
     *
     * @return void
     */
    public function testGetActiveEmailThemeReturnsManifestTheming(): void
    {
        $this->config->method('getAppValue')->willReturnCallback(
            fn ($app, $key, $default) => ($key === 'token_set') ? 'amsterdam' : $default
        );
        $this->tokenSetService->method('getAvailableTokenSets')->willReturn(
            [
                [
                    'id'      => 'amsterdam',
                    'theming' => ['primary_color' => '#004699', 'logo' => 'img/logos/amsterdam.svg'],
                ],
            ]
        );
        $this->urlGenerator->method('imagePath')->with('nldesign', 'logos/amsterdam.svg')
            ->willReturn('/apps/nldesign/img/logos/amsterdam.svg');
        $this->urlGenerator->method('getAbsoluteURL')->willReturnCallback(
            fn (string $url) => 'https://cloud.example.com'.$url
        );

        $theme = $this->service->getActiveEmailTheme();

        $this->assertNotNull($theme);
        $this->assertSame('#004699', $theme['primaryColor']);
        $this->assertSame('#ffffff', $theme['primaryTextColor']);
        $this->assertSame('https://cloud.example.com/apps/nldesign/img/logos/amsterdam.svg', $theme['logoUrl']);
    }//end testGetActiveEmailThemeReturnsManifestTheming()

    /**
     * A token set without a `theming` block falls back to
     * TokenSetPreviewService::getResolvedColors().
     *
     * @return void
     */
    public function testGetActiveEmailThemeFallsBackToResolvedColorsWithoutTheming(): void
    {
        $this->config->method('getAppValue')->willReturnCallback(
            fn ($app, $key, $default) => ($key === 'token_set') ? 'utrecht-custom' : $default
        );
        $this->tokenSetService->method('getAvailableTokenSets')->willReturn([['id' => 'utrecht-custom']]);
        $this->previewService->method('getResolvedColors')->willReturn(
            [
                '--color-primary'      => '#24578F',
                '--color-primary-text' => '#ffffff',
            ]
        );

        $theme = $this->service->getActiveEmailTheme();

        $this->assertNotNull($theme);
        $this->assertSame('#24578F', $theme['primaryColor']);
        $this->assertSame('#ffffff', $theme['primaryTextColor']);
        $this->assertNull($theme['logoUrl']);
    }//end testGetActiveEmailThemeFallsBackToResolvedColorsWithoutTheming()

    /**
     * The stock `nextcloud` token set (unthemed) resolves to null.
     *
     * @return void
     */
    public function testGetActiveEmailThemeReturnsNullForNextcloud(): void
    {
        $this->config->method('getAppValue')->willReturnCallback(
            fn ($app, $key, $default) => ($key === 'token_set') ? 'nextcloud' : $default
        );

        $this->assertNull($this->service->getActiveEmailTheme());
    }//end testGetActiveEmailThemeReturnsNullForNextcloud()
}//end class
