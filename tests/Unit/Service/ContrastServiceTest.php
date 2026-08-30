<?php

/**
 * Unit tests for ContrastService.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @spec openspec/changes/custom-token-set-upload/tasks.md#task-5.3
 */

declare(strict_types=1);

namespace OCA\Thematiq\Tests\Unit\Service;

use OCA\Thematiq\Service\ContrastService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the WCAG 2.1 contrast service.
 *
 * Covers tasks.md#task-5.3: known-ratio fixtures, the boundary 4.5:1, and the
 * `unevaluated` path for non-literal values.
 */
class ContrastServiceTest extends TestCase {

	/**
	 * The service under test.
	 *
	 * @var ContrastService
	 */
	private ContrastService $contrast;

	/**
	 * Set up the service before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->contrast = new ContrastService();
	}//end setUp()

	/**
	 * Black on white is the maximum 21:1 ratio.
	 */
	public function testBlackOnWhiteIsMaxRatio(): void {
		$ratio = $this->contrast->ratio(first: [0, 0, 0], second: [255, 255, 255]);
		$this->assertEqualsWithDelta(21.0, $ratio, 0.01);
	}//end testBlackOnWhiteIsMaxRatio()

	/**
	 * Identical colours give a 1:1 ratio.
	 */
	public function testIdenticalColoursAreOneToOne(): void {
		$ratio = $this->contrast->ratio(first: [120, 120, 120], second: [120, 120, 120]);
		$this->assertEqualsWithDelta(1.0, $ratio, 0.001);
	}//end testIdenticalColoursAreOneToOne()

	/**
	 * A compliant primary/primary-text pair produces no warnings.
	 */
	public function testCompliantPairProducesNoWarning(): void {
		// #154273 on #ffffff is well above 4.5:1.
		$warnings = $this->contrast->check(
			declarations: [
				'--nldesign-color-primary' => '#154273',
				'--nldesign-color-primary-text' => '#ffffff',
			]
		);

		$this->assertSame([], $warnings);
	}//end testCompliantPairProducesNoWarning()

	/**
	 * A low-contrast pair produces a warning carrying the ratio and threshold.
	 */
	public function testLowContrastPairProducesWarning(): void {
		// #cccccc text on #ffffff primary is ~1.6:1 — well below 4.5:1.
		$warnings = $this->contrast->check(
			declarations: [
				'--nldesign-color-primary' => '#ffffff',
				'--nldesign-color-primary-text' => '#cccccc',
			]
		);

		$this->assertCount(1, $warnings);
		$this->assertSame(4.5, $warnings[0]['threshold']);
		$this->assertSame('AA', $warnings[0]['level']);
		$this->assertLessThan(4.5, $warnings[0]['ratio']);
		$this->assertArrayNotHasKey('unevaluated', $warnings[0]);
	}//end testLowContrastPairProducesWarning()

	/**
	 * A non-literal value (var()) is reported as unevaluated, never as passing.
	 */
	public function testNonLiteralValueIsUnevaluated(): void {
		$warnings = $this->contrast->check(
			declarations: [
				'--nldesign-color-primary' => 'var(--some-other)',
				'--nldesign-color-primary-text' => '#ffffff',
			]
		);

		$this->assertCount(1, $warnings);
		$this->assertTrue($warnings[0]['unevaluated']);
		$this->assertNull($warnings[0]['ratio']);
	}//end testNonLiteralValueIsUnevaluated()

	/**
	 * parseColor handles #rgb, #rrggbb and rgb()/rgba() literals.
	 */
	public function testParseColorSupportsCommonLiterals(): void {
		$this->assertSame([0, 0, 0], $this->contrast->parseColor(value: '#000'));
		$this->assertSame([255, 255, 255], $this->contrast->parseColor(value: '#ffffff'));
		$this->assertSame([21, 66, 115], $this->contrast->parseColor(value: '#154273'));
		$this->assertSame([0, 123, 199], $this->contrast->parseColor(value: 'rgb(0, 123, 199)'));
		$this->assertSame([0, 123, 199], $this->contrast->parseColor(value: 'rgba(0, 123, 199, 0.5)'));
	}//end testParseColorSupportsCommonLiterals()

	/**
	 * parseColor returns null for unparseable values.
	 */
	public function testParseColorReturnsNullForUnparseable(): void {
		$this->assertNull($this->contrast->parseColor(value: 'var(--x)'));
		$this->assertNull($this->contrast->parseColor(value: 'rebeccapurple'));
		$this->assertNull($this->contrast->parseColor(value: 'hsl(200, 50%, 50%)'));
	}//end testParseColorReturnsNullForUnparseable()

	/**
	 * A pair where only one token is present is silently skipped (not evaluable).
	 */
	public function testPartialPairIsSkipped(): void {
		$warnings = $this->contrast->check(
			declarations: ['--nldesign-color-primary' => '#154273']
		);

		$this->assertSame([], $warnings);
	}//end testPartialPairIsSkipped()
}//end class
