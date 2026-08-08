<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\AppInfo;

use OCA\NLDesign\AppInfo\Application;
use OCA\NLDesign\Domain\Profile\ProfileCataloguePolicy;
use OCA\NLDesign\Listener\TemplateStylesListener;
use OCA\NLDesign\Service\TokenSetService;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeLoginTemplateRenderedEvent;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ApplicationTest extends TestCase
{
    public function testRegistersOnlyPublicProfileRuntimeServices(): void
    {
        $context = $this->createMock(IRegistrationContext::class);
        $context->expects(self::once())
            ->method('registerServiceAlias')
            ->with(ProfileCataloguePolicy::class, TokenSetService::class);

        $listeners = [];
        $context->expects(self::exactly(2))
            ->method('registerEventListener')
            ->willReturnCallback(
                static function (string $event, string $listener, int $priority=0) use (&$listeners): void {
                    $listeners[] = [$event, $listener, $priority];
                }
            );

        $application = (new ReflectionClass(Application::class))->newInstanceWithoutConstructor();
        $application->register(context: $context);

        self::assertSame(
            [
                [BeforeTemplateRenderedEvent::class, TemplateStylesListener::class, 0],
                [BeforeLoginTemplateRenderedEvent::class, TemplateStylesListener::class, 0],
            ],
            $listeners
        );
    }//end testRegistersOnlyPublicProfileRuntimeServices()

    public function testBootHasNoPrivateOrEagerRuntimeWork(): void
    {
        $context = $this->createMock(IBootContext::class);
        $context->expects(self::never())->method('getAppContainer');
        $context->expects(self::never())->method('getServerContainer');
        $context->expects(self::never())->method('injectFn');

        $application = (new ReflectionClass(Application::class))->newInstanceWithoutConstructor();
        $application->boot(context: $context);
    }//end testBootHasNoPrivateOrEagerRuntimeWork()
}//end class
