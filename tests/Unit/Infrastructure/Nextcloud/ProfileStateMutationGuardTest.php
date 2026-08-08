<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Infrastructure\Nextcloud;

use OCA\NLDesign\Infrastructure\Nextcloud\ProfileStateMutationGuard;
use OCP\IAppConfig;
use OCP\Lock\ILockingProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ProfileStateMutationGuardTest extends TestCase
{
    public function testLocksClearsCacheRunsOperationAndReleasesInOrder(): void
    {
        $events = [];
        $config = $this->createMock(IAppConfig::class);
        $config->method('clearCache')->willReturnCallback(
            static function (bool $reload=false) use (&$events): void {
                $events[] = 'cache';
            }
        );
        $lock = $this->createMock(ILockingProvider::class);
        $lock->method('acquireLock')->willReturnCallback(
            static function () use (&$events): void {
                $events[] = 'acquire';
            }
        );
        $lock->method('releaseLock')->willReturnCallback(
            static function () use (&$events): void {
                $events[] = 'release';
            }
        );

        $result = $this->createGuard(config: $config, lock: $lock)->run(
            operation: static function () use (&$events): array {
                $events[] = 'operation';
                return ['status' => 'ok'];
            }
        );

        self::assertSame(['status' => 'ok'], $result);
        self::assertSame(['acquire', 'cache', 'operation', 'release'], $events);
    }//end testLocksClearsCacheRunsOperationAndReleasesInOrder()

    public function testAcquireFailureDoesNotRefreshOrRunOrRelease(): void
    {
        $config = $this->createMock(IAppConfig::class);
        $config->expects(self::never())->method('clearCache');
        $lock = $this->createMock(ILockingProvider::class);
        $lock->method('acquireLock')->willThrowException(new \RuntimeException('Unavailable'));
        $lock->expects(self::never())->method('releaseLock');
        $operationCalled = false;

        $result = $this->createGuard(config: $config, lock: $lock)->run(
            operation: static function () use (&$operationCalled): array {
                $operationCalled = true;
                return ['status' => 'ok'];
            }
        );

        self::assertSame(['status' => 'lock_unavailable'], $result);
        self::assertFalse($operationCalled);
    }//end testAcquireFailureDoesNotRefreshOrRunOrRelease()

    public function testCacheFailureReleasesWithoutRunningOperation(): void
    {
        $config = $this->createMock(IAppConfig::class);
        $config->method('clearCache')->willThrowException(new \RuntimeException('Unavailable'));
        $lock = $this->createMock(ILockingProvider::class);
        $lock->expects(self::once())->method('releaseLock');
        $operationCalled = false;

        $result = $this->createGuard(config: $config, lock: $lock)->run(
            operation: static function () use (&$operationCalled): array {
                $operationCalled = true;
                return ['status' => 'ok'];
            }
        );

        self::assertSame(['status' => 'state_unavailable'], $result);
        self::assertFalse($operationCalled);
    }//end testCacheFailureReleasesWithoutRunningOperation()

    public function testOperationFailureReleasesAndFailsClosed(): void
    {
        $config = $this->createMock(IAppConfig::class);
        $lock   = $this->createMock(ILockingProvider::class);
        $lock->expects(self::once())->method('releaseLock');

        $result = $this->createGuard(config: $config, lock: $lock)->run(
            operation: static function (): array {
                throw new \RuntimeException('Unexpected state error');
            }
        );

        self::assertSame(['status' => 'state_unavailable'], $result);
    }//end testOperationFailureReleasesAndFailsClosed()

    private function createGuard(IAppConfig $config, ILockingProvider $lock): ProfileStateMutationGuard
    {
        return new ProfileStateMutationGuard(
            globalAppConfig: $config,
            lockingProvider: $lock,
            logger: new NullLogger()
        );
    }//end createGuard()
}//end class
