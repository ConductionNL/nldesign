<?php

/**
 * Unit tests for SettingsController's Marianne (French State typeface)
 * acknowledgement gate endpoints.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @spec openspec/specs/marianne-font/spec.md
 */

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Controller;

use OCA\NLDesign\Controller\SettingsController;
use OCA\NLDesign\Service\AppThemingService;
use OCA\NLDesign\Service\ComplianceReportService;
use OCA\NLDesign\Service\EmailThemingService;
use OCA\NLDesign\Service\GroupThemingService;
use OCA\NLDesign\Service\ThemingAuditService;
use OCA\NLDesign\Service\ThemingService;
use OCA\NLDesign\Service\TokenSetPreviewService;
use OCA\NLDesign\Service\TokenSetService;
use OCA\NLDesign\Service\UpstreamFreshnessService;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\IConfig;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Covers openspec/specs/marianne-font/spec.md "Marianne Activation Requires
 * an Admin Acknowledgement Gate": default off, POST persists, admin-only
 * posture on both routed methods (non-admin rejection is enforced by that
 * attribute, verified here the same way SettingsControllerDarkVariantsTest
 * does).
 */
class SettingsControllerMarianneTest extends TestCase
{

    /**
     * In-memory app config store backing the IConfig mock.
     *
     * @var array<string, string>
     */
    private array $configStore = [];

    /**
     * Build a SettingsController wired to the in-memory config store, with
     * stubbed sibling dependencies not exercised by the Marianne endpoints.
     *
     * @return SettingsController The controller under test.
     */
    private function makeController(): SettingsController
    {
        $config = $this->createMock(IConfig::class);
        $config->method('getAppValue')->willReturnCallback(
            function (string $app, string $key, $default = '') {
                return ($this->configStore[$key] ?? $default);
            }
        );
        $config->method('setAppValue')->willReturnCallback(
            function (string $app, string $key, $value) {
                $this->configStore[$key] = $value;
            }
        );

        return new SettingsController(
            'nldesign',
            $this->createMock(IRequest::class),
            $config,
            $this->createMock(TokenSetService::class),
            $this->createMock(ThemingService::class),
            $this->createMock(TokenSetPreviewService::class),
            $this->createMock(AppThemingService::class),
            $this->createMock(ComplianceReportService::class),
            $this->createMock(ThemingAuditService::class),
            $this->createMock(EmailThemingService::class),
            $this->createMock(UpstreamFreshnessService::class),
            $this->createMock(GroupThemingService::class)
        );
    }//end makeController()

    /**
     * Both Marianne routes carry #[AuthorizedAdminSetting] — non-admin
     * requests are rejected by that posture before the method body runs.
     */
    public function testBothEndpointsCarryAuthorizedAdminSetting(): void
    {
        foreach (['getMarianneEnabled', 'setMarianneEnabled'] as $method) {
            $reflection = new ReflectionMethod(SettingsController::class, $method);
            $attributes = $reflection->getAttributes(AuthorizedAdminSetting::class);
            $this->assertNotEmpty($attributes, "$method must carry #[AuthorizedAdminSetting]");
        }
    }//end testBothEndpointsCarryAuthorizedAdminSetting()

    /**
     * A fresh install (no `marianne_enabled` config value) defaults to
     * disabled — Marianne must be inert until an admin explicitly opts in.
     */
    public function testDefaultIsDisabled(): void
    {
        $controller = $this->makeController();

        $data = $controller->getMarianneEnabled()->getData();

        $this->assertFalse($data['enabled']);
    }//end testDefaultIsDisabled()

    /**
     * A POST persists the enabled state, reflected by the subsequent GET,
     * and stores the raw appconfig value as '1'.
     */
    public function testTogglePersists(): void
    {
        $controller = $this->makeController();

        $setResponse = $controller->setMarianneEnabled(true);
        $this->assertSame(['status' => 'ok', 'enabled' => true], $setResponse->getData());

        $after = $controller->getMarianneEnabled()->getData();
        $this->assertTrue($after['enabled']);

        $this->assertSame('1', $this->configStore['marianne_enabled']);
    }//end testTogglePersists()

    /**
     * Disabling after an enable reverts to Inter — persisted as '0'.
     */
    public function testDisablingAfterEnablePersists(): void
    {
        $controller = $this->makeController();

        $controller->setMarianneEnabled(true);
        $controller->setMarianneEnabled(false);

        $this->assertSame('0', $this->configStore['marianne_enabled']);
        $this->assertFalse($controller->getMarianneEnabled()->getData()['enabled']);
    }//end testDisablingAfterEnablePersists()
}//end class
