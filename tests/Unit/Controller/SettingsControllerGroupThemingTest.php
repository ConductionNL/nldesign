<?php

/**
 * Unit tests for SettingsController's group theming endpoints.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @spec openspec/specs/per-group-theming/spec.md
 */

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Controller;

use OCA\NLDesign\Controller\SettingsController;
use OCA\NLDesign\Service\AppThemingService;
use OCA\NLDesign\Service\ComplianceReportService;
use OCA\NLDesign\Service\EmailThemingService;
use OCA\NLDesign\Service\Exception\GroupThemingValidationException;
use OCA\NLDesign\Service\GroupThemingService;
use OCA\NLDesign\Service\ThemingAuditService;
use OCA\NLDesign\Service\ThemingService;
use OCA\NLDesign\Service\TokenSetPreviewService;
use OCA\NLDesign\Service\TokenSetService;
use OCA\NLDesign\Service\UpstreamFreshnessService;
use OCP\IConfig;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;

/**
 * Covers openspec/specs/per-group-theming/spec.md "Group Theming Admin
 * Endpoints" — payload shapes and 422 validation-error propagation.
 */
class SettingsControllerGroupThemingTest extends TestCase
{

    /**
     * Build a controller with a stubbed GroupThemingService and inert mocks
     * for every other constructor dependency (unused by these tests).
     *
     * @param GroupThemingService $groupThemingService The stubbed service.
     * @param TokenSetService     $tokenSetService      The token set service (defaults to an inert mock).
     *
     * @return SettingsController The controller under test.
     */
    private function makeController(
        GroupThemingService $groupThemingService,
        ?TokenSetService $tokenSetService=null
    ): SettingsController {
        return new SettingsController(
            'nldesign',
            $this->createMock(IRequest::class),
            $this->createMock(IConfig::class),
            ($tokenSetService ?? $this->createMock(TokenSetService::class)),
            $this->createMock(ThemingService::class),
            $this->createMock(TokenSetPreviewService::class),
            $this->createMock(AppThemingService::class),
            $this->createMock(ComplianceReportService::class),
            $this->createMock(ThemingAuditService::class),
            $this->createMock(EmailThemingService::class),
            $this->createMock(UpstreamFreshnessService::class),
            $groupThemingService
        );
    }//end makeController()

    /**
     * getGroupTheming() returns the mapping alongside the group and
     * token-set option lists.
     */
    public function testGetGroupThemingReturnsMappingAndOptionLists(): void
    {
        $groupThemingService = $this->createMock(GroupThemingService::class);
        $groupThemingService->method('getMapping')->willReturn(
            [['group' => 'gemeente-a', 'tokenSet' => 'amsterdam']]
        );
        $groupThemingService->method('getAvailableGroups')->willReturn(
            [['id' => 'gemeente-a', 'displayName' => 'Gemeente A']]
        );

        $tokenSetService = $this->createMock(TokenSetService::class);
        $tokenSetService->method('getAvailableTokenSets')->willReturn(
            [['id' => 'amsterdam', 'name' => 'Amsterdam']]
        );

        $controller = $this->makeController($groupThemingService, $tokenSetService);
        $response   = $controller->getGroupTheming();
        $data       = $response->getData();

        $this->assertSame([['group' => 'gemeente-a', 'tokenSet' => 'amsterdam']], $data['mapping']);
        $this->assertSame([['id' => 'gemeente-a', 'displayName' => 'Gemeente A']], $data['groups']);
        $this->assertSame([['id' => 'amsterdam', 'name' => 'Amsterdam']], $data['tokenSets']);
    }//end testGetGroupThemingReturnsMappingAndOptionLists()

    /**
     * setGroupTheming() persists the ordered mapping and returns it on success.
     */
    public function testSetGroupThemingReturnsPersistedMappingOnSuccess(): void
    {
        $groupThemingService = $this->createMock(GroupThemingService::class);
        $groupThemingService->method('getMapping')->willReturn([]);
        $groupThemingService->expects($this->once())
            ->method('setMapping')
            ->with([['group' => 'gemeente-a', 'tokenSet' => 'amsterdam']])
            ->willReturn([['group' => 'gemeente-a', 'tokenSet' => 'amsterdam']]);

        $controller = $this->makeController($groupThemingService);
        $response   = $controller->setGroupTheming([['group' => 'gemeente-a', 'tokenSet' => 'amsterdam']]);

        $this->assertSame(200, $response->getStatus());
        $data = $response->getData();
        $this->assertSame('ok', $data['status']);
        $this->assertSame([['group' => 'gemeente-a', 'tokenSet' => 'amsterdam']], $data['mapping']);
    }//end testSetGroupThemingReturnsPersistedMappingOnSuccess()

    /**
     * A validation failure surfaces as HTTP 422 naming the offending entry
     * and reason.
     */
    public function testSetGroupThemingReturns422OnValidationFailure(): void
    {
        $badEntry = ['group' => 'nonexistent', 'tokenSet' => 'amsterdam'];

        $groupThemingService = $this->createMock(GroupThemingService::class);
        $groupThemingService->method('getMapping')->willReturn([]);
        $groupThemingService->method('setMapping')
            ->willThrowException(new GroupThemingValidationException($badEntry, 'Group "nonexistent" does not exist.'));

        $controller = $this->makeController($groupThemingService);
        $response   = $controller->setGroupTheming([$badEntry]);

        $this->assertSame(422, $response->getStatus());
        $data = $response->getData();
        $this->assertSame('invalid_mapping', $data['error']);
        $this->assertSame($badEntry, $data['entry']);
        $this->assertSame('Group "nonexistent" does not exist.', $data['reason']);
    }//end testSetGroupThemingReturns422OnValidationFailure()
}//end class
