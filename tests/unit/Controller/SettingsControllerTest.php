<?php

/**
 * Unit tests for SettingsController.
 *
 * @category Test
 * @package  OCA\NLDesign\Tests\Unit\Controller
 *
 * @author    Conduction <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   AGPL-3.0-or-later https://www.gnu.org/licenses/agpl-3.0.html
 *
 * @version GIT: <git-id>
 *
 * @link https://github.com/ConductionNL/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Controller;

use OCA\NLDesign\AppInfo\Application;
use OCA\NLDesign\Controller\SettingsController;
use OCA\NLDesign\Service\CustomOverridesService;
use OCA\NLDesign\Service\ThemingService;
use OCA\NLDesign\Service\TokenSetPreviewService;
use OCA\NLDesign\Service\TokenSetService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SettingsController.
 */
class SettingsControllerTest extends TestCase
{

    /**
     * The controller under test.
     *
     * @var SettingsController
     */
    private SettingsController $controller;

    /**
     * Mock IRequest.
     *
     * @var IRequest&MockObject
     */
    private IRequest&MockObject $request;

    /**
     * Mock IConfig.
     *
     * @var IConfig&MockObject
     */
    private IConfig&MockObject $config;

    /**
     * Mock TokenSetService.
     *
     * @var TokenSetService&MockObject
     */
    private TokenSetService&MockObject $tokenSetService;

    /**
     * Mock ThemingService.
     *
     * @var ThemingService&MockObject
     */
    private ThemingService&MockObject $themingService;

    /**
     * Mock CustomOverridesService.
     *
     * @var CustomOverridesService&MockObject
     */
    private CustomOverridesService&MockObject $customOverridesService;

    /**
     * Mock TokenSetPreviewService.
     *
     * @var TokenSetPreviewService&MockObject
     */
    private TokenSetPreviewService&MockObject $tokenSetPreviewService;

    /**
     * Set up test fixtures.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->request                = $this->createMock(IRequest::class);
        $this->config                 = $this->createMock(IConfig::class);
        $this->tokenSetService        = $this->createMock(TokenSetService::class);
        $this->themingService         = $this->createMock(ThemingService::class);
        $this->customOverridesService = $this->createMock(CustomOverridesService::class);
        $this->tokenSetPreviewService = $this->createMock(TokenSetPreviewService::class);

        $this->controller = new SettingsController(
            appName: Application::APP_ID,
            request: $this->request,
            config: $this->config,
            tokenSetService: $this->tokenSetService,
            themingService: $this->themingService,
            customOverridesService: $this->customOverridesService,
            tokenSetPreviewService: $this->tokenSetPreviewService,
        );

    }//end setUp()

    /**
     * Test that getAvailableTokenSets() returns a JSONResponse with tokenSets key.
     *
     * @return void
     */
    public function testGetAvailableTokenSetsReturnsJsonResponse(): void
    {
        $available = ['rijkshuisstijl', 'utrecht', 'amsterdam'];

        $this->tokenSetService->expects($this->once())
            ->method('getAvailableTokenSets')
            ->willReturn($available);

        $result = $this->controller->getAvailableTokenSets();

        self::assertInstanceOf(JSONResponse::class, $result);
        self::assertArrayHasKey('tokenSets', $result->getData());
        self::assertSame($available, $result->getData()['tokenSets']);

    }//end testGetAvailableTokenSetsReturnsJsonResponse()

    /**
     * Test that getTokenSet() returns the configured token set from IConfig.
     *
     * @return void
     */
    public function testGetTokenSetReturnsConfiguredValue(): void
    {
        $this->config->expects($this->once())
            ->method('getAppValue')
            ->with(Application::APP_ID, 'token_set', 'rijkshuisstijl')
            ->willReturn('utrecht');

        $result = $this->controller->getTokenSet();

        self::assertInstanceOf(JSONResponse::class, $result);
        self::assertSame('utrecht', $result->getData()['tokenSet']);

    }//end testGetTokenSetReturnsConfiguredValue()

    /**
     * Test that setTokenSet() returns 400 when an invalid token set is given.
     *
     * @return void
     */
    public function testSetTokenSetReturns400ForInvalidTokenSet(): void
    {
        $this->tokenSetService->expects($this->once())
            ->method('isValidTokenSet')
            ->with('invalid-set')
            ->willReturn(false);

        $result = $this->controller->setTokenSet(tokenSet: 'invalid-set');

        self::assertInstanceOf(JSONResponse::class, $result);
        self::assertSame(400, $result->getStatus());
        self::assertArrayHasKey('error', $result->getData());

    }//end testSetTokenSetReturns400ForInvalidTokenSet()

    /**
     * Test that setTokenSet() saves the value and returns ok for a valid token set.
     *
     * @return void
     */
    public function testSetTokenSetSavesAndReturnsOkForValidTokenSet(): void
    {
        $this->tokenSetService->expects($this->once())
            ->method('isValidTokenSet')
            ->with('rijkshuisstijl')
            ->willReturn(true);

        $this->config->expects($this->once())
            ->method('setAppValue')
            ->with(Application::APP_ID, 'token_set', 'rijkshuisstijl');

        $result = $this->controller->setTokenSet(tokenSet: 'rijkshuisstijl');

        self::assertInstanceOf(JSONResponse::class, $result);
        self::assertSame('ok', $result->getData()['status']);

    }//end testSetTokenSetSavesAndReturnsOkForValidTokenSet()

}//end class
