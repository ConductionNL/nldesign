<?php

/**
 * Unit tests for ThemeInjectionListener.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @spec openspec/changes/render-event-injection/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Listener;

use OCA\NLDesign\Listener\ThemeInjectionListener;
use OCA\NLDesign\Service\AppThemingService;
use OCA\NLDesign\Service\CssInjectionService;
use OCP\AppFramework\Http\Events\BeforeLoginTemplateRenderedEvent;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\EventDispatcher\Event;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ThemeInjectionListener.
 */
class ThemeInjectionListenerTest extends TestCase
{

    /**
     * The CSS injection service mock.
     *
     * @var CssInjectionService&MockObject
     */
    private $cssInjectionService;

    /**
     * The per-app theming guard mock.
     *
     * @var AppThemingService&MockObject
     */
    private $appThemingService;

    /**
     * The request mock.
     *
     * @var IRequest&MockObject
     */
    private $request;

    /**
     * The listener under test.
     *
     * @var ThemeInjectionListener
     */
    private ThemeInjectionListener $listener;

    /**
     * Set up mocks before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->cssInjectionService = $this->createMock(CssInjectionService::class);
        $this->appThemingService   = $this->createMock(AppThemingService::class);
        $this->request             = $this->createMock(IRequest::class);
        $this->listener            = new ThemeInjectionListener(
            $this->cssInjectionService,
            $this->appThemingService,
            $this->request
        );

        // No blanket `isThemingDisabledFor()` default here: PHPUnit's
        // InvocationMocker resolves overlapping unconstrained stubs in
        // REGISTRATION order (the first-registered unconstrained stub wins
        // for every call), so a setUp()-level default would silently shadow
        // a test's own `->with($appId)->willReturn(true)`. Each test
        // configures it explicitly instead (an unconfigured bool-returning
        // mock method defaults to `false`, which is what the "not excluded"
        // tests below rely on).
    }//end setUp()

    /**
     * renderAs `user`/`guest`/`public`/`error` each reach `inject()` with the
     * matching context.
     *
     * @dataProvider renderAsProvider
     *
     * @param string $renderAs        The response's renderAs value.
     * @param string $expectedContext The context `inject()` must receive.
     */
    public function testRenderAsMapsToMatchingContext(string $renderAs, string $expectedContext): void
    {
        $response = new TemplateResponse('files', 'index', [], $renderAs);

        $this->cssInjectionService->expects($this->once())->method('inject')->with($expectedContext);

        $this->listener->handle(new BeforeTemplateRenderedEvent(true, $response));
    }//end testRenderAsMapsToMatchingContext()

    /**
     * renderAs => context cases.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function renderAsProvider(): array
    {
        return [
            'user'   => [TemplateResponse::RENDER_AS_USER, 'user'],
            'guest'  => [TemplateResponse::RENDER_AS_GUEST, 'guest'],
            'public' => [TemplateResponse::RENDER_AS_PUBLIC, 'public'],
            'error'  => [TemplateResponse::RENDER_AS_ERROR, 'error'],
        ];
    }//end renderAsProvider()

    /**
     * An unknown renderAs (e.g. `blank`) still injects — fail-open,
     * forward-compatibility.
     */
    public function testUnknownRenderAsStillInjects(): void
    {
        $response = new TemplateResponse('files', 'index', [], 'blank');

        $this->cssInjectionService->expects($this->once())->method('inject')->with('blank');

        $this->listener->handle(new BeforeTemplateRenderedEvent(true, $response));
    }//end testUnknownRenderAsStillInjects()

    /**
     * The login event injects with context `login` and never consults the
     * per-app guard.
     */
    public function testLoginEventInjectsWithLoginContextAndSkipsGuard(): void
    {
        $response = new TemplateResponse('core', 'login', [], TemplateResponse::RENDER_AS_GUEST);

        $this->appThemingService->expects($this->never())->method('isThemingDisabledFor');
        $this->appThemingService->expects($this->never())->method('resolveAppIdFromPath');
        $this->cssInjectionService->expects($this->once())->method('inject')->with('login');

        $this->listener->handle(new BeforeLoginTemplateRenderedEvent($response));
    }//end testLoginEventInjectsWithLoginContextAndSkipsGuard()

    /**
     * An excluded app (identified via the response's own app id) skips injection.
     */
    public function testExcludedResponseAppSkipsInjection(): void
    {
        $response = new TemplateResponse('calendar', 'index', [], TemplateResponse::RENDER_AS_USER);

        $this->appThemingService->method('isThemingDisabledFor')->with('calendar')->willReturn(true);
        $this->request->expects($this->never())->method('getPathInfo');
        $this->cssInjectionService->expects($this->never())->method('inject');

        $this->listener->handle(new BeforeTemplateRenderedEvent(true, $response));
    }//end testExcludedResponseAppSkipsInjection()

    /**
     * A non-excluded response app stays fully themed.
     */
    public function testNonExcludedResponseAppInjectsNormally(): void
    {
        $response = new TemplateResponse('files', 'index', [], TemplateResponse::RENDER_AS_USER);

        $this->appThemingService->method('isThemingDisabledFor')->with('files')->willReturn(false);
        $this->cssInjectionService->expects($this->once())->method('inject')->with('user');

        $this->listener->handle(new BeforeTemplateRenderedEvent(true, $response));
    }//end testNonExcludedResponseAppInjectsNormally()

    /**
     * An empty response app id falls back to the request path resolver.
     */
    public function testEmptyResponseAppFallsBackToPath(): void
    {
        $response = new TemplateResponse('', 'index', [], TemplateResponse::RENDER_AS_USER);

        $this->request->method('getPathInfo')->willReturn('/apps/calendar/');
        $this->appThemingService->method('resolveAppIdFromPath')->with('/apps/calendar/')->willReturn('calendar');
        $this->appThemingService->method('isThemingDisabledFor')->with('calendar')->willReturn(true);
        $this->cssInjectionService->expects($this->never())->method('inject');

        $this->listener->handle(new BeforeTemplateRenderedEvent(true, $response));
    }//end testEmptyResponseAppFallsBackToPath()

    /**
     * A `core`-attributed response app also falls back to the request path resolver.
     */
    public function testCoreResponseAppFallsBackToPath(): void
    {
        $response = new TemplateResponse('core', 'index', [], TemplateResponse::RENDER_AS_USER);

        $this->request->method('getPathInfo')->willReturn('/index.php/apps/calendar/');
        $this->appThemingService->method('resolveAppIdFromPath')
            ->with('/index.php/apps/calendar/')->willReturn('calendar');
        $this->appThemingService->method('isThemingDisabledFor')->with('calendar')->willReturn(true);
        $this->cssInjectionService->expects($this->never())->method('inject');

        $this->listener->handle(new BeforeTemplateRenderedEvent(true, $response));
    }//end testCoreResponseAppFallsBackToPath()

    /**
     * A throwing app-id resolver fails open to themed rather than propagating.
     */
    public function testResolverThrowingFailsOpenToThemed(): void
    {
        $response = new TemplateResponse('files', 'index', [], TemplateResponse::RENDER_AS_USER);

        $this->appThemingService->method('isThemingDisabledFor')->willThrowException(new \RuntimeException('boom'));
        $this->cssInjectionService->expects($this->once())->method('inject')->with('user');

        $this->listener->handle(new BeforeTemplateRenderedEvent(true, $response));
    }//end testResolverThrowingFailsOpenToThemed()

    /**
     * An event that is neither of the two handled types is a no-op — no
     * exception escapes, and injection never runs.
     */
    public function testUnrelatedEventIsNoOp(): void
    {
        $unrelated = $this->createMock(Event::class);

        $this->cssInjectionService->expects($this->never())->method('inject');

        $this->listener->handle($unrelated);
        $this->addToAssertionCount(1);
    }//end testUnrelatedEventIsNoOp()

    /**
     * A throwing CssInjectionService never lets the exception escape `handle()`.
     */
    public function testInjectionServiceThrowingNeverEscapes(): void
    {
        $response = new TemplateResponse('files', 'index', [], TemplateResponse::RENDER_AS_USER);

        $this->cssInjectionService->method('inject')->willThrowException(new \RuntimeException('boom'));

        $this->listener->handle(new BeforeTemplateRenderedEvent(true, $response));
        $this->addToAssertionCount(1);
    }//end testInjectionServiceThrowingNeverEscapes()

    /**
     * Double dispatch of the same event yields exactly two `inject()` calls
     * with the same arguments each time (idempotency lives in
     * `\OCP\Util::addStyle()`, not the listener) — no duplicated/skewed state.
     */
    public function testDoubleDispatchYieldsNoDuplicatedSideEffectsBeyondInject(): void
    {
        $response = new TemplateResponse('files', 'index', [], TemplateResponse::RENDER_AS_USER);

        $this->cssInjectionService->expects($this->exactly(2))->method('inject')->with('user');

        $event = new BeforeTemplateRenderedEvent(true, $response);
        $this->listener->handle($event);
        $this->listener->handle($event);
    }//end testDoubleDispatchYieldsNoDuplicatedSideEffectsBeyondInject()
}//end class
