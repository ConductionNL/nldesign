<?php

/**
 * Unit tests for MetricsController.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @spec openspec/changes/theming-audit-log/tasks.md#task-5.4
 */

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Controller;

use OCA\NLDesign\Controller\MetricsController;
use OCA\NLDesign\Service\CustomOverridesService;
use OCA\NLDesign\Service\TokenSetService;
use OCP\IConfig;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Covers tasks.md#task-5.4: nldesign_audit_entries_total is emitted with the
 * expected HELP/TYPE lines, its value comes from the `audit_entries_total`
 * app value (cast to int, default '0'), and it appears alongside the
 * pre-existing metric families rather than replacing them.
 */
class MetricsControllerTest extends TestCase
{

    /**
     * In-memory appconfig store: key => value.
     *
     * @var array<string, string>
     */
    private array $appConfig = [];

    /**
     * The controller under test.
     *
     * @var MetricsController
     */
    private MetricsController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $config = $this->createMock(IConfig::class);
        $config->method('getAppValue')->willReturnCallback(
            fn (string $app, string $key, $default='') => ($this->appConfig[$key] ?? $default)
        );
        $config->method('getSystemValueString')->willReturn('34.0.0');

        $tokenSetService = $this->createMock(TokenSetService::class);
        $tokenSetService->method('getAvailableTokenSets')->willReturn([]);

        $overridesService = $this->createMock(CustomOverridesService::class);
        $overridesService->method('read')->willReturn([]);

        $this->controller = new MetricsController(
            'nldesign',
            $this->createMock(IRequest::class),
            $config,
            $tokenSetService,
            $overridesService,
            $this->createMock(LoggerInterface::class)
        );
    }//end setUp()

    /**
     * The audit counter defaults to zero when no entry has ever been
     * written (fresh install / unset app value).
     */
    public function testAuditCounterDefaultsToZero(): void
    {
        $body = $this->controller->index()->render();

        $this->assertStringContainsString(
            '# HELP nldesign_audit_entries_total Total theming audit entries written',
            $body
        );
        $this->assertStringContainsString('# TYPE nldesign_audit_entries_total counter', $body);
        $this->assertStringContainsString('nldesign_audit_entries_total 0', $body);
    }//end testAuditCounterDefaultsToZero()

    /**
     * The audit counter reflects the current `audit_entries_total` app
     * value (cast to int).
     */
    public function testAuditCounterReflectsAppValue(): void
    {
        $this->appConfig['audit_entries_total'] = '12';

        $body = $this->controller->index()->render();

        $this->assertStringContainsString('nldesign_audit_entries_total 12', $body);
    }//end testAuditCounterReflectsAppValue()

    /**
     * The new counter is additive — pre-existing metric families still
     * appear in the same response.
     */
    public function testAuditCounterIsAdditiveToExistingFamilies(): void
    {
        $body = $this->controller->index()->render();

        $this->assertStringContainsString('nldesign_info{', $body);
        $this->assertStringContainsString('nldesign_up 1', $body);
        $this->assertStringContainsString('# TYPE nldesign_theming_syncs_total counter', $body);
    }//end testAuditCounterIsAdditiveToExistingFamilies()
}//end class
