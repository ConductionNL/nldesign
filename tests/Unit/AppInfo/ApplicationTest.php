<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\AppInfo;

use OCA\NLDesign\AppInfo\Application;
use OCA\NLDesign\Domain\Profile\ProfileCataloguePolicy;
use OCA\NLDesign\Infrastructure\Nextcloud\Presentation\OcpNextcloudRuntimeProvider;
use OCA\NLDesign\Infrastructure\Nextcloud\Presentation\VersionedCoreSurfaceAdapter;
use OCA\NLDesign\Infrastructure\Profile\AppDataInstalledProfileRepository;
use OCA\NLDesign\Listener\TemplateStylesListener;
use OCA\NLDesign\Port\Profile\InstalledProfileRepository;
use OCA\NLDesign\Port\Presentation\CoreSurfaceAdapter;
use OCA\NLDesign\Port\Presentation\NextcloudRuntimeProvider;
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
        $aliases = [];
        $context->expects(self::exactly(4))
            ->method('registerServiceAlias')
            ->willReturnCallback(
                static function (string $alias, string $target) use (&$aliases): void {
                    $aliases[] = [$alias, $target];
                }
            );

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
                [ProfileCataloguePolicy::class, TokenSetService::class],
                [InstalledProfileRepository::class, AppDataInstalledProfileRepository::class],
                [NextcloudRuntimeProvider::class, OcpNextcloudRuntimeProvider::class],
                [CoreSurfaceAdapter::class, VersionedCoreSurfaceAdapter::class],
            ],
            $aliases
        );

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
