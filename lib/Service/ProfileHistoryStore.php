<?php

/**
 * NL Design profile-history persistence.
 *
 * @category Service
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Service;

use JsonException;
use OCA\NLDesign\Domain\Profile\ProfileHistoryNormalizer;
use OCP\AppFramework\Services\IAppConfig;
use Psr\Log\LoggerInterface;

/**
 * Read and append bounded profile activation history.
 */
final class ProfileHistoryStore
{
    private const PROFILE_STATE_HISTORY_KEY = 'profile_state_history';
    private const MAX_HISTORY_ENTRIES       = 10;
    private const MAX_HISTORY_BYTES         = 32768;

    /**
     * Constructor.
     *
     * @param IAppConfig               $config     App-scoped config service.
     * @param LoggerInterface          $logger     Application logger.
     * @param ProfileHistoryNormalizer $normalizer History validator.
     */
    public function __construct(
        private IAppConfig $config,
        private LoggerInterface $logger,
        private ProfileHistoryNormalizer $normalizer
    ) {
    }//end __construct()

    /**
     * Read bounded activation history.
     *
     * @return array<int, array<string, mixed>> History entries.
     */
    public function getHistory(): array
    {
        $rawHistory = $this->config->getAppValueString(
            key: self::PROFILE_STATE_HISTORY_KEY,
            default: '[]'
        );
        if (strlen($rawHistory) > self::MAX_HISTORY_BYTES) {
            $this->logger->warning('Ignoring oversized NL Design profile history.');
            return [];
        }

        try {
            $decoded = json_decode(
                json: $rawHistory,
                associative: true,
                flags: JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            $this->logger->warning(
                'Ignoring malformed NL Design profile history.',
                ['exception' => $exception]
            );
            return [];
        }

        if (is_array($decoded) === false || array_is_list($decoded) === false) {
            return [];
        }

        return $this->normalizer->normalize(decoded: $decoded);
    }//end getHistory()

    /**
     * Append one activation operation to bounded history.
     *
     * @param array<string, mixed> $previousState Prior state.
     * @param array<string, mixed> $nextState     New state.
     * @param string               $actor         Actor identifier.
     * @param string               $timestamp     UTC timestamp.
     *
     * @return void
     *
     * @throws JsonException When history cannot be encoded.
     */
    public function record(
        array $previousState,
        array $nextState,
        string $actor,
        string $timestamp
    ): void {
        $history = $this->getHistory();
        array_unshift(
            $history,
            [
                'actor'                 => $actor,
                'timestamp'             => $timestamp,
                'from_profile_id'       => $previousState['active_profile_id'] ?? null,
                'from_profile_version'  => $previousState['active_profile_version'] ?? null,
                'from_profile_revision' => $previousState['active_profile_revision'] ?? null,
                'to_profile_id'         => $nextState['active_profile_id'] ?? null,
                'to_profile_version'    => $nextState['active_profile_version'] ?? null,
                'to_profile_revision'   => $nextState['active_profile_revision'] ?? null,
            ]
        );

        $history = array_slice($history, 0, self::MAX_HISTORY_ENTRIES);
        $this->config->setAppValueString(
            key: self::PROFILE_STATE_HISTORY_KEY,
            value: json_encode(value: $history, flags: JSON_THROW_ON_ERROR)
        );
    }//end record()
}//end class
