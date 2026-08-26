<?php

/**
 * Unit tests for CssParserService::parseDarkBlock().
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @spec openspec/specs/dark-mode/spec.md
 */

declare(strict_types=1);

namespace OCA\Thematiq\Tests\Unit\Service;

use OCA\Thematiq\Service\CssParserService;
use PHPUnit\Framework\TestCase;

/**
 * Covers the hand-authored dark-block extraction contract (tasks.md#task-1.3,
 * task-5.2): present, absent, extra-braces-nearby, and malformed CSS.
 */
class CssParserServiceTest extends TestCase {

	/**
	 * The parser under test.
	 *
	 * @var CssParserService
	 */
	private CssParserService $parser;

	/**
	 * Set up the parser.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->parser = new CssParserService();
	}//end setUp()

	/**
	 * A present dark block yields its declarations.
	 */
	public function testDarkBlockPresent(): void {
		$css = ":root {\n\t--nldesign-color-primary: #154273;\n}\n\n"
			. "@media (prefers-color-scheme: dark) {\n"
			. "\t:root {\n"
			. "\t\t--nldesign-color-primary: #4844AD;\n"
			. "\t\t--nldesign-color-background: #171717;\n"
			. "\t}\n"
			. '}';

		$result = $this->parser->parseDarkBlock(css: $css);

		$this->assertSame(
			[
				'--nldesign-color-primary' => '#4844AD',
				'--nldesign-color-background' => '#171717',
			],
			$result
		);
	}//end testDarkBlockPresent()

	/**
	 * A token set with no dark block returns an empty map — never throws.
	 */
	public function testDarkBlockAbsent(): void {
		$css = ":root {\n\t--nldesign-color-primary: #154273;\n}";

		$this->assertSame([], $this->parser->parseDarkBlock(css: $css));
	}//end testDarkBlockAbsent()

	/**
	 * Unrelated brace blocks elsewhere in the file (a light `:root {}` block,
	 * another `@media` rule) do not confuse extraction of the dark block.
	 */
	public function testDarkBlockWithNestedBracesElsewhereInFile(): void {
		$css = ":root {\n\t--nldesign-color-primary: #154273;\n}\n\n"
			. "@media (min-width: 768px) {\n\t.foo { color: red; }\n}\n\n"
			. "@media (prefers-color-scheme: dark) {\n"
			. "\t:root {\n"
			. "\t\t--nldesign-color-primary: #4844AD;\n"
			. "\t}\n"
			. '}';

		$result = $this->parser->parseDarkBlock(css: $css);

		$this->assertSame(['--nldesign-color-primary' => '#4844AD'], $result);
	}//end testDarkBlockWithNestedBracesElsewhereInFile()

	/**
	 * Malformed CSS (unclosed dark block) degrades to an empty map, never an exception.
	 */
	public function testMalformedDarkBlockDegradesToEmpty(): void {
		$css = "@media (prefers-color-scheme: dark) {\n\t:root {\n\t\t--nldesign-color-primary: #4844AD;";

		$this->assertSame([], $this->parser->parseDarkBlock(css: $css));
	}//end testMalformedDarkBlockDegradesToEmpty()

	/**
	 * Empty input degrades to an empty map.
	 */
	public function testEmptyInputDegradesToEmpty(): void {
		$this->assertSame([], $this->parser->parseDarkBlock(css: ''));
	}//end testEmptyInputDegradesToEmpty()
}//end class
