<?php

/**
 * Tests for the freeform custom CSS sanitiser.
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
 * @spec openspec/specs/custom-css-freeform/spec.md
 */

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Service;

use OCA\NLDesign\Service\CustomCssValidator;
use PHPUnit\Framework\TestCase;

/**
 * Every rule gets its own test: the validator is the only thing standing
 * between an administrator's free-text box and a stylesheet served on every
 * page of the instance.
 */
class CustomCssValidatorTest extends TestCase
{

    /**
     * The subject under test.
     *
     * @var CustomCssValidator
     */
    private CustomCssValidator $validator;


    /**
     * Set up the validator.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new CustomCssValidator();

    }//end setUp()


    /**
     * Ordinary theming CSS is accepted unchanged.
     *
     * @return void
     */
    public function testAcceptsOrdinaryCss(): void
    {
        $css = ".app-content { padding: 8px; }\n#header .logo { opacity: .9; }";

        $this->assertSame([], $this->validator->validate(css: $css));

    }//end testAcceptsOrdinaryCss()


    /**
     * Empty input is acceptable — it is how an admin clears the layer.
     *
     * @return void
     */
    public function testAcceptsEmptyCss(): void
    {
        $this->assertSame([], $this->validator->validate(css: ''));

    }//end testAcceptsEmptyCss()


    /**
     * @import pulls a remote stylesheet on every page load.
     *
     * @return void
     */
    public function testRejectsImport(): void
    {
        $errors = $this->validator->validate(css: '@import url("https://example.test/x.css");');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsStringIgnoringCase('@import', implode(' ', $errors));

    }//end testRejectsImport()


    /**
     * @charset can change how the remaining bytes are decoded.
     *
     * @return void
     */
    public function testRejectsCharset(): void
    {
        $this->assertNotEmpty($this->validator->validate(css: '@charset "utf-8";'));

    }//end testRejectsCharset()


    /**
     * External url() is the classic CSS exfiltration channel.
     *
     * @return void
     */
    public function testRejectsExternalUrl(): void
    {
        $errors = $this->validator->validate(css: '.x { background: url(https://evil.test/p.png); }');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsStringIgnoringCase('url()', implode(' ', $errors));

    }//end testRejectsExternalUrl()


    /**
     * Protocol-relative URLs reach another origin just as well as https://.
     *
     * @return void
     */
    public function testRejectsProtocolRelativeUrl(): void
    {
        $this->assertNotEmpty($this->validator->validate(css: '.x { background: url(//evil.test/p.png); }'));

    }//end testRejectsProtocolRelativeUrl()


    /**
     * Relative same-origin references stay usable for legitimate theming.
     *
     * @return void
     */
    public function testAcceptsRelativeUrl(): void
    {
        $this->assertSame([], $this->validator->validate(css: '.x { background: url(../img/logo.svg); }'));

    }//end testAcceptsRelativeUrl()


    /**
     * data: URIs cannot reach another origin, so they remain permitted.
     *
     * @return void
     */
    public function testAcceptsDataUri(): void
    {
        $css = '.x { background: url(data:image/gif;base64,R0lGODlhAQABAAAAACw=); }';

        $this->assertSame([], $this->validator->validate(css: $css));

    }//end testAcceptsDataUri()


    /**
     * Legacy script-execution vectors are refused.
     *
     * @return void
     */
    public function testRejectsScriptExecutionVectors(): void
    {
        $this->assertNotEmpty($this->validator->validate(css: '.x { width: expression(alert(1)); }'));
        $this->assertNotEmpty($this->validator->validate(css: '.x { behavior: url(x.htc); }'));
        $this->assertNotEmpty($this->validator->validate(css: '.x { -moz-binding: url(x.xml); }'));

    }//end testRejectsScriptExecutionVectors()


    /**
     * HTML breakout sequences are refused as defence in depth.
     *
     * @return void
     */
    public function testRejectsHtmlBreakout(): void
    {
        $this->assertNotEmpty($this->validator->validate(css: '.x { color: red; } </style><script>alert(1)</script>'));

    }//end testRejectsHtmlBreakout()


    /**
     * Reserved dark-mode variables cannot be set from freeform CSS.
     *
     * @return void
     */
    public function testRejectsReservedDarkModeVariables(): void
    {
        foreach (CustomCssValidator::RESERVED_VARIABLES as $reserved) {
            $errors = $this->validator->validate(css: ':root { '.$reserved.': #fff; }');

            $this->assertNotEmpty(
                $errors,
                $reserved.' must be rejected (REQ-CSS-007 dark-mode derivation).'
            );
        }

    }//end testRejectsReservedDarkModeVariables()


    /**
     * A reserved variable is caught under ANY selector, not just :root.
     *
     * @return void
     */
    public function testRejectsReservedVariableOutsideRoot(): void
    {
        $this->assertNotEmpty(
            $this->validator->validate(css: '.sneaky { --color-background-plain: #000; }')
        );

    }//end testRejectsReservedVariableOutsideRoot()


    /**
     * An unbalanced brace would swallow the rest of the cascade.
     *
     * @return void
     */
    public function testRejectsUnbalancedBraces(): void
    {
        $this->assertNotEmpty($this->validator->validate(css: '.x { color: red;'));
        $this->assertNotEmpty($this->validator->validate(css: '.x { color: red; } }'));

    }//end testRejectsUnbalancedBraces()


    /**
     * Braces inside strings and comments are not structural.
     *
     * @return void
     */
    public function testBracesInStringsAndCommentsAreIgnored(): void
    {
        $this->assertSame([], $this->validator->validate(css: '.x::after { content: "{"; }'));
        $this->assertSame([], $this->validator->validate(css: "/* a { brace */ .x { color: red; }"));

    }//end testBracesInStringsAndCommentsAreIgnored()


    /**
     * Oversized submissions are refused.
     *
     * @return void
     */
    public function testRejectsOversizedPayload(): void
    {
        $css = str_repeat('a', (CustomCssValidator::MAX_BYTES + 1));

        $errors = $this->validator->validate(css: $css);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsStringIgnoringCase('limit', implode(' ', $errors));

    }//end testRejectsOversizedPayload()


    /**
     * Validation is all-or-nothing and reports EVERY reason at once.
     *
     * @return void
     */
    public function testReportsEveryViolationAtOnce(): void
    {
        $css = '@import url("https://evil.test/a.css"); .x { background: url(https://evil.test/b.png); }';

        $errors = $this->validator->validate(css: $css);

        $this->assertGreaterThanOrEqual(2, count($errors), 'Both the @import and the external url() must be reported.');

    }//end testReportsEveryViolationAtOnce()


}//end class
