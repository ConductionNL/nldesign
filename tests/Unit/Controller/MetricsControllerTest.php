<?php

/**
 * Unit tests for MetricsController's admin-only auth posture.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @spec openspec/specs/prometheus-metrics/spec.md
 */

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Controller;

use OCA\NLDesign\Controller\MetricsController;
use OCA\NLDesign\Service\CustomOverridesService;
use OCA\NLDesign\Service\TokenSetService;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\TextPlainResponse;
use OCP\IConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * Verifies `metrics#index` falls through to the Nextcloud
 * `SecurityMiddleware` admin-only default (ADR-006), per
 * `openspec/specs/prometheus-metrics/spec.md` REQ-PROM-001
 * "Metrics endpoint requires an authenticated admin session".
 *
 * There is no real `SecurityMiddleware`/route-dispatch layer available in
 * this standalone PHPUnit harness (no live Nextcloud server), so a live
 * 401-on-unauthenticated-request assertion is not possible here — that is
 * covered by the deferred manual curl check in tasks.md#task-4.2. What IS
 * statically verifiable and regression-proof in this harness is the
 * precondition the admin-only default depends on: `index()` must carry
 * neither `#[PublicPage]` nor `#[NoAdminRequired]`. If either attribute is
 * ever (re)added, Nextcloud's `SecurityMiddleware` would bypass the
 * admin-only check entirely — so asserting their absence is the correct,
 * fast-failing proxy for the auth posture.
 */
class MetricsControllerTest extends TestCase
{

    /**
     * The controller under test.
     *
     * @var MetricsController
     */
    private MetricsController $controller;

    /**
     * Set up the controller with mocked dependencies.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $config = $this->createMock(IConfig::class);
        $config->method('getAppValue')->willReturn('0');
        $config->method('getSystemValueString')->willReturn('29.0.0');

        $tokenSetService = $this->createMock(TokenSetService::class);
        $tokenSetService->method('getAvailableTokenSets')->willReturn([]);

        $overridesService = $this->createMock(CustomOverridesService::class);
        $overridesService->method('read')->willReturn([]);

        $this->controller = new MetricsController(
            'nldesign',
            $this->createMock(\OCP\IRequest::class),
            $config,
            $tokenSetService,
            $overridesService,
            $this->createMock(LoggerInterface::class)
        );
    }//end setUp()

    /**
     * `index()` MUST NOT carry `#[PublicPage]` — its presence would make the
     * endpoint reachable by any unauthenticated caller, defeating the
     * admin-auth requirement (ADR-006 / REQ-PROM-001).
     */
    public function testIndexHasNoPublicPageAttribute(): void
    {
        $method = new ReflectionMethod(MetricsController::class, 'index');

        $this->assertCount(
            0,
            $method->getAttributes(PublicPage::class),
            '#[PublicPage] must be absent so the SecurityMiddleware admin-only default applies.'
        );
    }//end testIndexHasNoPublicPageAttribute()

    /**
     * `index()` MUST NOT carry `#[NoAdminRequired]` — its presence would
     * allow any authenticated (non-admin) user through, still short of the
     * admin-only requirement (ADR-006 / REQ-PROM-001).
     */
    public function testIndexHasNoNoAdminRequiredAttribute(): void
    {
        $method = new ReflectionMethod(MetricsController::class, 'index');

        $this->assertCount(
            0,
            $method->getAttributes(NoAdminRequired::class),
            '#[NoAdminRequired] must be absent so the SecurityMiddleware admin-only default applies.'
        );
    }//end testIndexHasNoNoAdminRequiredAttribute()

    /**
     * The CSRF exemption is orthogonal to the admin-auth requirement and
     * MUST remain — a Prometheus scraper authenticates via HTTP Basic/an
     * app-password and cannot present a CSRF token.
     */
    public function testIndexDeclaresNoCsrfRequired(): void
    {
        $method = new ReflectionMethod(MetricsController::class, 'index');

        $this->assertStringContainsString(
            '@NoCSRFRequired',
            (string) $method->getDocComment(),
            'index() must remain CSRF-exempt even though it now requires an admin session.'
        );
    }//end testIndexDeclaresNoCsrfRequired()

    /**
     * Sanity/regression check: removing `#[PublicPage]` only changes the
     * auth posture enforced by middleware, never invoked when calling the
     * method directly — the metrics body itself must still be produced
     * unchanged.
     */
    public function testIndexStillReturnsMetricsBody(): void
    {
        $response = $this->controller->index();

        $this->assertInstanceOf(TextPlainResponse::class, $response);
        $this->assertStringContainsString('nldesign_up 1', $response->render());
    }//end testIndexStillReturnsMetricsBody()
}//end class
