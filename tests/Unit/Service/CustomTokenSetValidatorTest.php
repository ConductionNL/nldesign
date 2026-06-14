<?php

/**
 * Unit tests for CustomTokenSetValidator.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @spec openspec/changes/custom-token-set-upload/tasks.md#task-5.1
 */

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Service;

use OCA\NLDesign\Service\CustomTokenSetValidator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the CSS upload validator / re-serialiser.
 *
 * Covers the payload corpus called out in tasks.md#task-5.1: selector
 * smuggling, external url(), oversized hint, comments, and accepted
 * org-palette extras.
 */
class CustomTokenSetValidatorTest extends TestCase
{

    /**
     * The validator under test.
     *
     * @var CustomTokenSetValidator
     */
    private CustomTokenSetValidator $validator;

    /**
     * Set up the validator before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new CustomTokenSetValidator();
    }//end setUp()

    /**
     * Only --nldesign-* and --{slug}-* declarations are accepted; others are skipped.
     */
    public function testWhitelistSplitsAcceptedAndSkipped(): void
    {
        $declarations = [
            '--nldesign-color-primary'      => '#007bc7',
            '--nldesign-color-primary-text' => '#ffffff',
            '--gemeente-color-accent'       => '#ff0000',
            '--color-primary'               => '#123456',
            '--v-some-other'                => '#000000',
        ];

        $split = $this->validator->validateDeclarations(declarations: $declarations, slug: 'gemeente');

        $this->assertNotNull($split);
        $this->assertArrayHasKey('--nldesign-color-primary', $split['accepted']);
        $this->assertArrayHasKey('--gemeente-color-accent', $split['accepted'], 'org-palette extras must be accepted');
        $this->assertContains('--color-primary', $split['skipped']);
        $this->assertContains('--v-some-other', $split['skipped']);
        $this->assertCount(3, $split['accepted']);
        $this->assertCount(2, $split['skipped']);
    }//end testWhitelistSplitsAcceptedAndSkipped()

    /**
     * A forbidden value (external url) is a hard failure with HTTP 422.
     */
    public function testExternalUrlValueIsRejected(): void
    {
        $split = $this->validator->validateDeclarations(
            declarations: ['--nldesign-logo-url' => "url('https://evil.example/logo.svg')"],
            slug: 'x'
        );

        $this->assertNull($split);
        $error = $this->validator->getLastError();
        $this->assertSame(422, $error['status']);
        $this->assertSame('--nldesign-logo-url', $error['property']);
    }//end testExternalUrlValueIsRejected()

    /**
     * Relative url() and data: URIs are permitted.
     */
    public function testRelativeAndDataUrlsAreAccepted(): void
    {
        $this->assertFalse($this->validator->isForbiddenValue(value: "url('../../img/logos/custom.svg')"));
        $this->assertFalse($this->validator->isForbiddenValue(value: 'url(data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=)'));
    }//end testRelativeAndDataUrlsAreAccepted()

    /**
     * @import, expression(), javascript:, and raw markup are all forbidden.
     */
    public function testDangerousValuesAreForbidden(): void
    {
        $this->assertTrue($this->validator->isForbiddenValue(value: '@import url(x.css)'));
        $this->assertTrue($this->validator->isForbiddenValue(value: 'expression(alert(1))'));
        $this->assertTrue($this->validator->isForbiddenValue(value: 'javascript:alert(1)'));
        $this->assertTrue($this->validator->isForbiddenValue(value: '</style><script>x</script>'));
        $this->assertTrue($this->validator->isForbiddenValue(value: 'url(//evil.example/x.png)'));
    }//end testDangerousValuesAreForbidden()

    /**
     * Any selector other than :root (or any at-rule) is rejected pre-parse.
     */
    public function testDisallowedSelectorIsDetected(): void
    {
        $this->assertTrue($this->validator->hasDisallowedSelector(css: ':root { --nldesign-color-primary: #007bc7; } .header { color: red; }'));
        $this->assertTrue($this->validator->hasDisallowedSelector(css: '@import url(x.css); :root { --nldesign-color-primary: #007bc7; }'));
        $this->assertTrue($this->validator->hasDisallowedSelector(css: '@font-face { font-family: X; } :root {}'));
    }//end testDisallowedSelectorIsDetected()

    /**
     * A clean single-:root block (with comments) passes the selector guard.
     */
    public function testCleanRootBlockPassesSelectorGuard(): void
    {
        $css = "/* comment with .fake-selector { } inside */\n:root {\n  --nldesign-color-primary: #007bc7;\n}";
        $this->assertFalse($this->validator->hasDisallowedSelector(css: $css));
    }//end testCleanRootBlockPassesSelectorGuard()

    /**
     * An upload with no --nldesign-* declarations is a hard failure.
     */
    public function testEmptyAcceptedSetIsRejected(): void
    {
        $split = $this->validator->validateDeclarations(
            declarations: ['--color-primary' => '#123456'],
            slug: 'x'
        );

        $this->assertNull($split);
        $this->assertSame(422, $this->validator->getLastError()['status']);
    }//end testEmptyAcceptedSetIsRejected()

    /**
     * Serialization is generated from declarations only, in a single :root block.
     */
    public function testSerializeProducesCanonicalRootBlock(): void
    {
        $css = $this->validator->serialize(
            declarations: [
                '--nldesign-color-primary'      => '#007bc7',
                '--nldesign-color-primary-text' => '#ffffff',
            ]
        );

        $this->assertStringContainsString(':root {', $css);
        $this->assertStringContainsString('--nldesign-color-primary: #007bc7;', $css);
        $this->assertStringContainsString('--nldesign-color-primary-text: #ffffff;', $css);
        $this->assertStringEndsWith("}\n", $css);
        // No selector smuggling can survive serialisation.
        $this->assertStringNotContainsString('.header', $css);
    }//end testSerializeProducesCanonicalRootBlock()

    /**
     * The maximum size constant matches the specced 512 KB cap.
     */
    public function testMaxSizeIs512Kb(): void
    {
        $this->assertSame((512 * 1024), CustomTokenSetValidator::MAX_SIZE);
    }//end testMaxSizeIs512Kb()
}//end class
