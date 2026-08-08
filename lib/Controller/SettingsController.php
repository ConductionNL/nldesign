<?php

/**
 * NL Design Settings Controller.
 *
 * @category Controller
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Controller;

use OCA\NLDesign\Application\Branding\ManualThemingPlanBuilder;
use OCA\NLDesign\Service\ProfileStateService;
use OCA\NLDesign\Service\TokenSetService;
use OCA\NLDesign\Settings\Admin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Admin-only API for profile activation and manual Theming guidance.
 */
final class SettingsController extends Controller
{
    private const REVISION_PATTERN = '/^[a-f0-9]{20}$/';
    private const SETTINGS_ACTOR   = 'admin:settings';

    /**
     * Constructor.
     *
     * @param string                   $appName             App id.
     * @param IRequest                 $request             Request object.
     * @param TokenSetService          $tokenSetService     Profile catalogue.
     * @param ProfileStateService      $profileStateService Activation state.
     * @param ManualThemingPlanBuilder $planBuilder         Manual-plan builder.
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private TokenSetService $tokenSetService,
        private ProfileStateService $profileStateService,
        private ManualThemingPlanBuilder $planBuilder
    ) {
        parent::__construct(appName: $appName, request: $request);
    }//end __construct()

    /**
     * Activate a ready profile.
     *
     * @param string $tokenSet         Profile identifier.
     * @param string $expectedRevision Revision observed by the browser.
     *
     * @return         JSONResponse Operation response.
     * @phpstan-return JSONResponse<Http::STATUS_*, array<string, mixed>, array{}>
     */
    #[AuthorizedAdminSetting(settings: Admin::class)]
    public function setTokenSet(string $tokenSet, string $expectedRevision=''): JSONResponse
    {
        if ($this->tokenSetService->isValidTokenSet(tokenSetId: $tokenSet) === false) {
            return $this->respond(
                data: ['status' => 'invalid_profile', 'error' => 'Invalid token set'],
                statusCode: Http::STATUS_BAD_REQUEST
            );
        }

        if ($this->isValidExpectedRevision(revision: $expectedRevision) === false) {
            return $this->respond(
                data: ['status' => 'invalid_revision', 'error' => 'Invalid expected revision'],
                statusCode: Http::STATUS_BAD_REQUEST
            );
        }

        $result       = $this->profileStateService->publishProfile(
            tokenSetId: $tokenSet,
            actor: self::SETTINGS_ACTOR,
            expectedRevision: $expectedRevision
        );
        $resultStatus = (string) ($result['status'] ?? '');
        if ($resultStatus === 'revision_mismatch') {
            return $this->respond(
                data: [
                    'status'          => 'revision_mismatch',
                    'error'           => 'Profile changed while editing',
                    'currentRevision' => $result['current_revision'] ?? null,
                ],
                statusCode: Http::STATUS_CONFLICT
            );
        }

        if ($resultStatus !== 'ok' && $resultStatus !== 'noop') {
            $responseStatus = $resultStatus;
            if ($responseStatus === '') {
                $responseStatus = 'error';
            }

            return $this->respond(
                data: [
                    'status' => $responseStatus,
                    'error'  => 'Failed to update token set',
                ],
                statusCode: $this->getMutationFailureStatusCode(status: $resultStatus)
            );
        }

        $state = $result['next'] ?? $result['current'] ?? $this->getActiveState();

        return $this->respond(
            data: [
                'status'          => 'ok',
                'tokenSet'        => $state['active_profile_id'] ?? $tokenSet,
                'revision'        => $state['active_profile_revision'] ?? null,
                'previousProfile' => $state['previous_profile_snapshot']['profile_id'] ?? null,
                'canRollback'     => $this->hasPreviousSnapshot(state: $state),
            ]
        );
    }//end setTokenSet()

    /**
     * Return to native Nextcloud presentation.
     *
     * @param string $expectedRevision Revision observed by the browser.
     *
     * @return         JSONResponse Operation response.
     * @phpstan-return JSONResponse<Http::STATUS_*, array<string, mixed>, array{}>
     */
    #[AuthorizedAdminSetting(settings: Admin::class)]
    public function deactivateTokenSet(string $expectedRevision=''): JSONResponse
    {
        if ($this->isValidExpectedRevision(revision: $expectedRevision) === false) {
            return $this->respond(
                data: ['status' => 'invalid_revision', 'error' => 'Invalid expected revision'],
                statusCode: Http::STATUS_BAD_REQUEST
            );
        }

        $result       = $this->profileStateService->deactivateProfile(
            actor: self::SETTINGS_ACTOR,
            expectedRevision: $expectedRevision
        );
        $resultStatus = (string) ($result['status'] ?? '');
        if ($resultStatus === 'revision_mismatch') {
            return $this->respond(
                data: [
                    'status'          => 'revision_mismatch',
                    'error'           => 'Profile changed while editing',
                    'currentRevision' => $result['current_revision'] ?? null,
                ],
                statusCode: Http::STATUS_CONFLICT
            );
        }

        if ($resultStatus !== 'ok' && $resultStatus !== 'noop') {
            $responseStatus = $resultStatus;
            if ($responseStatus === '') {
                $responseStatus = 'error';
            }

            return $this->respond(
                data: [
                    'status' => $responseStatus,
                    'error'  => 'Failed to deactivate profile',
                ],
                statusCode: $this->getMutationFailureStatusCode(status: $resultStatus)
            );
        }

        $state = $result['next'] ?? $result['current'] ?? $this->getActiveState();

        return $this->respond(
            data: [
                'status'          => 'ok',
                'tokenSet'        => null,
                'revision'        => $state['active_profile_revision'] ?? null,
                'previousProfile' => $state['previous_profile_snapshot']['profile_id'] ?? null,
                'canRollback'     => $this->hasPreviousSnapshot(state: $state),
            ]
        );
    }//end deactivateTokenSet()

    /**
     * Get canonical active-profile state.
     *
     * @return         JSONResponse Current profile response.
     * @phpstan-return JSONResponse<Http::STATUS_*, array<string, mixed>, array{}>
     */
    #[AuthorizedAdminSetting(settings: Admin::class)]
    public function getTokenSet(): JSONResponse
    {
        $state     = $this->getActiveState();
        $tokenSet  = $state['active_profile_id'] ?? null;
        $available = false;
        $metadata  = null;
        if (is_string($tokenSet) === true) {
            $available = $this->tokenSetService->isValidTokenSet(tokenSetId: $tokenSet);
            if ($available === true) {
                $metadata = $this->tokenSetService->getTokenSetMetadata(tokenSetId: $tokenSet);
            }
        }

        return $this->respond(
            data: [
                'status'           => 'ok',
                'tokenSet'         => $tokenSet,
                'available'        => $available,
                'revision'         => $state['active_profile_revision'] ?? null,
                'previousProfile'  => $state['previous_profile_snapshot']['profile_id'] ?? null,
                'canRollback'      => $this->hasPreviousSnapshot(state: $state),
                'tokenSetMetadata' => $metadata,
            ]
        );
    }//end getTokenSet()

    /**
     * Build a non-executing Nextcloud Theming synchronization plan.
     *
     * @param string $tokenSet Profile id, or active profile when omitted.
     *
     * @return         JSONResponse Manual plan response.
     * @phpstan-return JSONResponse<Http::STATUS_*, array<string, mixed>, array{}>
     */
    #[AuthorizedAdminSetting(settings: Admin::class)]
    public function getThemingPlan(string $tokenSet=''): JSONResponse
    {
        if ($tokenSet === '') {
            $tokenSet = (string) $this->getActiveState()['active_profile_id'];
        }

        $metadata = $this->tokenSetService->getTokenSetMetadata(tokenSetId: $tokenSet);
        if ($metadata === null) {
            return $this->respond(
                data: ['status' => 'invalid_profile', 'error' => 'Invalid token set'],
                statusCode: Http::STATUS_BAD_REQUEST
            );
        }

        return $this->respond(
            data: [
                'status'           => 'ok',
                'tokenSet'         => $tokenSet,
                'tokenSetMetadata' => $metadata,
                'plan'             => $this->planBuilder->build(
                    theming: $metadata['theming'] ?? null
                ),
            ]
        );
    }//end getThemingPlan()

    /**
     * Roll back to the immediate previous profile.
     *
     * @param string $expectedRevision Revision observed by the browser.
     *
     * @return         JSONResponse Rollback response.
     * @phpstan-return JSONResponse<Http::STATUS_*, array<string, mixed>, array{}>
     */
    #[AuthorizedAdminSetting(settings: Admin::class)]
    public function rollbackTokenSet(string $expectedRevision=''): JSONResponse
    {
        if ($this->isValidExpectedRevision(revision: $expectedRevision) === false) {
            return $this->respond(
                data: ['status' => 'invalid_revision', 'error' => 'Invalid expected revision'],
                statusCode: Http::STATUS_BAD_REQUEST
            );
        }

        $result       = $this->profileStateService->rollbackProfile(
            actor: self::SETTINGS_ACTOR.':rollback',
            expectedRevision: $expectedRevision
        );
        $resultStatus = (string) ($result['status'] ?? '');
        if ($resultStatus !== 'ok' && $resultStatus !== 'noop') {
            return $this->respond(
                data: [
                    'status'          => $resultStatus,
                    'error'           => 'Unable to roll back profile',
                    'currentRevision' => $result['current_revision'] ?? null,
                ],
                statusCode: $this->getMutationFailureStatusCode(status: $resultStatus)
            );
        }

        $state = $result['next'] ?? $result['current'] ?? $this->getActiveState();

        return $this->respond(
            data: [
                'status'          => 'ok',
                'tokenSet'        => $state['active_profile_id'] ?? null,
                'revision'        => $state['active_profile_revision'] ?? null,
                'previousProfile' => $state['previous_profile_snapshot']['profile_id'] ?? null,
                'canRollback'     => $this->hasPreviousSnapshot(state: $state),
            ]
        );
    }//end rollbackTokenSet()

    /**
     * Read bounded activation history.
     *
     * @return         JSONResponse History response.
     * @phpstan-return JSONResponse<Http::STATUS_*, array<string, mixed>, array{}>
     */
    #[AuthorizedAdminSetting(settings: Admin::class)]
    public function getProfileHistory(): JSONResponse
    {
        return $this->respond(
            data: [
                'status'  => 'ok',
                'history' => $this->profileStateService->getHistory(),
            ]
        );
    }//end getProfileHistory()

    /**
     * Create a consistently typed JSON response.
     *
     * @param array<string, mixed> $data       Response body.
     * @param int                  $statusCode HTTP status.
     *
     * @return         JSONResponse Response object.
     * @phpstan-param  Http::STATUS_* $statusCode
     * @phpstan-return JSONResponse<Http::STATUS_*, array<string, mixed>, array{}>
     */
    private function respond(array $data, int $statusCode=Http::STATUS_OK): JSONResponse
    {
        return new JSONResponse(data: $data, statusCode: $statusCode);
    }//end respond()

    /**
     * Get active state, including the native-Nextcloud state.
     *
     * @return array<string, mixed> Active state.
     */
    private function getActiveState(): array
    {
        return $this->profileStateService->getActiveProfileState();
    }//end getActiveState()

    /**
     * Validate the required optimistic-concurrency revision.
     *
     * @param string $revision Revision value.
     *
     * @return bool Whether the value is valid.
     */
    private function isValidExpectedRevision(string $revision): bool
    {
        return preg_match(self::REVISION_PATTERN, $revision) === 1;
    }//end isValidExpectedRevision()

    /**
     * Map domain write failures to retry-aware HTTP status codes.
     *
     * @param string $status Domain result status.
     *
     * @return         int HTTP status code.
     * @phpstan-return Http::STATUS_*
     */
    private function getMutationFailureStatusCode(string $status): int
    {
        if ($status === 'lock_unavailable'
            || $status === 'persistence_failed'
            || $status === 'state_unavailable'
        ) {
            return Http::STATUS_SERVICE_UNAVAILABLE;
        }

        return Http::STATUS_CONFLICT;
    }//end getMutationFailureStatusCode()

    /**
     * Check whether state carries a rollback snapshot, including native state.
     *
     * @param array<string, mixed> $state Profile state.
     *
     * @return bool Whether rollback has a target.
     */
    private function hasPreviousSnapshot(array $state): bool
    {
        $previous = $state['previous_profile_snapshot'] ?? null;
        return is_array($previous) === true
            && array_key_exists('profile_id', $previous) === true;
    }//end hasPreviousSnapshot()
}//end class
