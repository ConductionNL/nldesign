<?php

/**
 * NL Design profile-state mutation guard.
 *
 * @category Infrastructure
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/ConductionNL/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Infrastructure\Nextcloud;

use OCP\IAppConfig;
use OCP\Lock\ILockingProvider;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Serialize profile writes and invalidate Nextcloud's app-config cache before
 * the compare step.
 */
final class ProfileStateMutationGuard
{
    private const PROFILE_STATE_LOCK = 'nldesign/profile-state';

    /**
     * Constructor.
     *
     * @param IAppConfig       $globalAppConfig Public cache-invalidation API.
     * @param ILockingProvider $lockingProvider Distributed locking provider.
     * @param LoggerInterface  $logger          Application logger.
     */
    public function __construct(
        private IAppConfig $globalAppConfig,
        private ILockingProvider $lockingProvider,
        private LoggerInterface $logger
    ) {
    }//end __construct()

    /**
     * Run one operation after acquiring the shared lock and clearing stale
     * request-local and shared app-config caches.
     *
     * @param callable(): array<string, mixed> $operation Locked operation.
     *
     * @return array<string, mixed> Operation or infrastructure failure result.
     */
    public function run(callable $operation): array
    {
        try {
            $this->lockingProvider->acquireLock(
                path: self::PROFILE_STATE_LOCK,
                type: ILockingProvider::LOCK_EXCLUSIVE,
                readablePath: 'NL Design profile state'
            );
        } catch (Throwable $exception) {
            $this->logger->warning(
                'NL Design profile state lock could not be acquired.',
                ['exception' => $exception]
            );
            return ['status' => 'lock_unavailable'];
        }

        try {
            try {
                $this->globalAppConfig->clearCache();
                return $operation();
            } catch (Throwable $exception) {
                $this->logger->error(
                    'NL Design profile state could not be refreshed or changed.',
                    ['exception' => $exception]
                );
                return ['status' => 'state_unavailable'];
            }
        } finally {
            try {
                $this->lockingProvider->releaseLock(
                    path: self::PROFILE_STATE_LOCK,
                    type: ILockingProvider::LOCK_EXCLUSIVE
                );
            } catch (Throwable $exception) {
                $this->logger->warning(
                    'NL Design profile state lock could not be released cleanly.',
                    ['exception' => $exception]
                );
            }
        }//end try
    }//end run()
}//end class
