<?php

/**
 * Audit-trail tests for the freeform custom CSS controller.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category  Test
 * @package   OCA\Thematiq
 * @author    Conduction <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://github.com/ConductionNL/thematiq
 *
 * @spec openspec/specs/custom-css-freeform/spec.md
 * @spec openspec/specs/theming-audit/spec.md#requirement-complete-call-site-coverage
 */

declare(strict_types=1);

namespace OCA\Thematiq\Tests\Unit\Controller;

use OCA\Thematiq\Controller\CustomCssController;
use OCA\Thematiq\Service\CustomCssService;
use OCA\Thematiq\Service\ThemingAuditService;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;

/**
 * Freeform CSS is the one theming surface where an administrator can author
 * arbitrary rules, so BOTH outcomes have to leave a trail: a successful write
 * and a rejected one. A rejection that is not logged would let someone probe
 * the validator silently.
 */
class CustomCssControllerAuditTest extends TestCase {

	/**
	 * The request mock.
	 *
	 * @var IRequest&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $request;

	/**
	 * The freeform CSS service mock.
	 *
	 * @var CustomCssService&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $customCssService;

	/**
	 * The audit log mock.
	 *
	 * @var ThemingAuditService&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $auditService;

	/**
	 * Set up mocks.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->request = $this->createMock(IRequest::class);
		$this->customCssService = $this->createMock(CustomCssService::class);
		$this->auditService = $this->createMock(ThemingAuditService::class);

	}//end setUp()

	/**
	 * Build the controller under test.
	 *
	 * @return CustomCssController The controller.
	 */
	private function buildController(): CustomCssController {
		return new CustomCssController(
			'nldesign',
			$this->request,
			$this->customCssService,
			$this->auditService
		);

	}//end buildController()

	/**
	 * A successful write is audit-logged.
	 *
	 * @return void
	 */
	public function testSuccessfulWriteIsAudited(): void {
		$this->request->method('getParams')->willReturn(['css' => '.a { color: red; }', 'enabled' => true]);
		$this->customCssService->method('read')->willReturn('');
		$this->customCssService->method('write')->willReturn([]);
		$this->customCssService->method('isEnabled')->willReturn(true);

		$this->auditService->expects($this->once())
			->method('log')
			->with($this->equalTo('custom_css_written'), $this->anything());

		$response = $this->buildController()->setCustomCss();

		$this->assertSame(200, $response->getStatus());

	}//end testSuccessfulWriteIsAudited()

	/**
	 * A REJECTED submission is audit-logged too, and returns 422.
	 *
	 * @return void
	 */
	public function testRejectedSubmissionIsAudited(): void {
		$this->request->method('getParams')->willReturn(['css' => '@import url(x);']);
		$this->customCssService->method('read')->willReturn('');
		$this->customCssService->method('write')->willReturn(['@import and @charset are not allowed.']);

		$this->auditService->expects($this->once())
			->method('log')
			->with($this->equalTo('custom_css_rejected'), $this->anything());

		$response = $this->buildController()->setCustomCss();

		$this->assertSame(422, $response->getStatus());
		$this->assertArrayHasKey('errors', $response->getData());

	}//end testRejectedSubmissionIsAudited()

	/**
	 * A rejected submission must not flip the enable flag.
	 *
	 * @return void
	 */
	public function testRejectionDoesNotEnableTheLayer(): void {
		$this->request->method('getParams')->willReturn(['css' => '@import url(x);', 'enabled' => true]);
		$this->customCssService->method('read')->willReturn('');
		$this->customCssService->method('write')->willReturn(['@import and @charset are not allowed.']);

		$this->customCssService->expects($this->never())->method('setEnabled');

		$this->buildController()->setCustomCss();

	}//end testRejectionDoesNotEnableTheLayer()

	/**
	 * A non-string payload is refused before anything is written.
	 *
	 * @return void
	 */
	public function testNonStringPayloadIsRefused(): void {
		$this->request->method('getParams')->willReturn(['css' => ['not', 'a', 'string']]);

		$this->customCssService->expects($this->never())->method('write');

		$response = $this->buildController()->setCustomCss();

		$this->assertSame(400, $response->getStatus());

	}//end testNonStringPayloadIsRefused()

}//end class
