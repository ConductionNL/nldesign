<?php

/**
 * ContrastService::evaluate() unit tests.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @spec openspec/specs/app-token-set-selection/spec.md
 */

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit;

use OCA\NLDesign\Service\ContrastService;
use PHPUnit\Framework\TestCase;

/**
 * Known-ratio fixtures for `text` and `ui` roles (compliant and
 * non-compliant), `unevaluated` for non-literal values, and the
 * never-a-verdict response-shape contract (design.md decisions 2/4).
 */
class ContrastServiceEvaluateTest extends TestCase {

	private ContrastService $service;

	protected function setUp(): void {
		parent::setUp();
		$this->service = new ContrastService();
	}//end setUp()

	/**
	 * A compliant `text` candidate (white on the rijkshuisstijl primary blue,
	 * well above 4.5:1) passes at the text threshold.
	 */
	public function testCompliantTextCandidatePasses(): void {
		$results = $this->service->evaluate(
			candidates: [
				['name' => 'primary-text', 'value' => '#ffffff', 'role' => 'text'],
			],
			background: '#154273'
		);

		$this->assertCount(1, $results);
		$this->assertSame('primary-text', $results[0]['name']);
		$this->assertSame(4.5, $results[0]['threshold']);
		$this->assertSame('AA', $results[0]['level']);
		$this->assertTrue($results[0]['pass']);
		$this->assertArrayNotHasKey('unevaluated', $results[0]);
		$this->assertGreaterThanOrEqual(4.5, $results[0]['ratio']);
	}//end testCompliantTextCandidatePasses()

	/**
	 * A non-compliant `text` candidate (low-contrast grey on white) fails at
	 * the text threshold — `pass` MUST be false, not omitted or true.
	 */
	public function testNonCompliantTextCandidateFails(): void {
		$results = $this->service->evaluate(
			candidates: [
				['name' => 'low-contrast', 'value' => '#f0f0f0', 'role' => 'text'],
			],
			background: '#ffffff'
		);

		$this->assertFalse($results[0]['pass']);
		$this->assertLessThan(4.5, $results[0]['ratio']);
	}//end testNonCompliantTextCandidateFails()

	/**
	 * A `ui` candidate is evaluated at the 3.0:1 threshold, not 4.5:1.
	 */
	public function testUiCandidateUsesUiThreshold(): void {
		$results = $this->service->evaluate(
			candidates: [
				['name' => 'accent', 'value' => '#e8f0f8', 'role' => 'ui'],
			],
			background: '#f5f6f7'
		);

		$this->assertSame(3.0, $results[0]['threshold']);
	}//end testUiCandidateUsesUiThreshold()

	/**
	 * Multiple candidates against one shared background each get their own
	 * result, matching design.md's worked example (primary/text vs
	 * accent/ui).
	 */
	public function testMultipleCandidatesAgainstOneBackground(): void {
		$results = $this->service->evaluate(
			candidates: [
				['name' => 'primary', 'value' => '#154273', 'role' => 'text'],
				['name' => 'accent', 'value' => '#e8f0f8', 'role' => 'ui'],
			],
			background: '#F5F6F7'
		);

		$this->assertCount(2, $results);
		$this->assertSame('primary', $results[0]['name']);
		$this->assertSame(4.5, $results[0]['threshold']);
		$this->assertSame('accent', $results[1]['name']);
		$this->assertSame(3.0, $results[1]['threshold']);
	}//end testMultipleCandidatesAgainstOneBackground()

	/**
	 * A non-literal candidate value (`var(--token)`) MUST be reported
	 * `unevaluated: true`, `ratio: null`, and MUST NOT be reported as
	 * passing.
	 */
	public function testNonLiteralCandidateValueIsUnevaluatedNeverPassing(): void {
		$results = $this->service->evaluate(
			candidates: [
				['name' => 'themed', 'value' => 'var(--some-token)', 'role' => 'text'],
			],
			background: '#ffffff'
		);

		$this->assertTrue($results[0]['unevaluated']);
		$this->assertNull($results[0]['ratio']);
		$this->assertFalse($results[0]['pass']);
	}//end testNonLiteralCandidateValueIsUnevaluatedNeverPassing()

	/**
	 * A non-literal background also produces `unevaluated` results for every
	 * candidate — an unresolvable background is never silently ignored.
	 */
	public function testNonLiteralBackgroundIsUnevaluated(): void {
		$results = $this->service->evaluate(
			candidates: [
				['name' => 'primary', 'value' => '#154273', 'role' => 'text'],
			],
			background: 'var(--nldesign-color-background)'
		);

		$this->assertTrue($results[0]['unevaluated']);
		$this->assertNull($results[0]['ratio']);
	}//end testNonLiteralBackgroundIsUnevaluated()

	/**
	 * The response shape never carries a `blocked`, `allowed`, or `verdict`
	 * key — nldesign reports facts only (design.md decisions 2/4).
	 */
	public function testResponseNeverCarriesABlockingVerdictKey(): void {
		$results = $this->service->evaluate(
			candidates: [
				['name' => 'low-contrast', 'value' => '#f0f0f0', 'role' => 'text'],
			],
			background: '#ffffff'
		);

		$this->assertArrayNotHasKey('blocked', $results[0]);
		$this->assertArrayNotHasKey('allowed', $results[0]);
		$this->assertArrayNotHasKey('verdict', $results[0]);
	}//end testResponseNeverCarriesABlockingVerdictKey()

	/**
	 * `check()` (the fixed-pair, upload-time method) is unaffected by the
	 * new `evaluate()` generalisation — same PAIRS-driven behaviour as
	 * before this change.
	 */
	public function testCheckRemainsUnaffectedByEvaluate(): void {
		$warnings = $this->service->check([
			'--nldesign-color-primary-text' => '#ffffff',
			'--nldesign-color-primary' => '#154273',
			'--nldesign-color-background' => '#ffffff',
		]);

		$this->assertIsArray($warnings);
	}//end testCheckRemainsUnaffectedByEvaluate()
}//end class
