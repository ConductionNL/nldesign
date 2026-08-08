<?php

/**
 * NL Design profile library controller.
 *
 * @category Controller
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Controller;

use OCA\NLDesign\Service\ProfileInstallerService;
use OCA\NLDesign\Service\TokenSetService;
use OCA\NLDesign\Settings\Admin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Admin-only profile discovery, installation and removal API.
 */
final class ProfileLibraryController extends Controller
{
    private const SETTINGS_ACTOR = 'admin:settings';

    /**
     * Constructor.
     *
     * @param string                  $appName   App id.
     * @param IRequest                $request   Request object.
     * @param TokenSetService         $profiles  Composite profile catalogue.
     * @param ProfileInstallerService $installer Profile installer.
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private TokenSetService $profiles,
        private ProfileInstallerService $installer
    ) {
        parent::__construct(appName: $appName, request: $request);
    }//end __construct()

    /**
     * List built-in and installed profile versions.
     *
     * @return         JSONResponse Profile catalogue response.
     * @phpstan-return JSONResponse<Http::STATUS_*, array<string, mixed>, array{}>
     */
    #[AuthorizedAdminSetting(settings: Admin::class)]
    public function getProfiles(): JSONResponse
    {
        return $this->respond(
            data: [
                'status'   => 'ok',
                'profiles' => $this->profiles->getAvailableTokenSets(),
            ]
        );
    }//end getProfiles()

    /**
     * Install one uploaded profile pack.
     *
     * @param string $profilePack Raw profile-pack JSON.
     *
     * @return         JSONResponse Installation response.
     * @phpstan-return JSONResponse<Http::STATUS_*, array<string, mixed>, array{}>
     */
    #[AuthorizedAdminSetting(settings: Admin::class)]
    public function installProfile(string $profilePack=''): JSONResponse
    {
        $result = $this->installer->install(
            profilePack: $profilePack,
            actor: self::SETTINGS_ACTOR
        );
        $status = (string) ($result['status'] ?? 'error');
        if ($status === 'ok' || $status === 'noop') {
            return $this->respond(
                data: [
                    ...$result,
                    'profiles' => $this->profiles->getAvailableTokenSets(),
                ]
            );
        }

        return $this->respond(
            data: [
                ...$result,
                'error' => $this->getErrorMessage(status: $status),
            ],
            statusCode: $this->getFailureStatusCode(status: $status)
        );
    }//end installProfile()

    /**
     * Uninstall one inactive installed profile version.
     *
     * @param string $profileId      Stable profile identifier.
     * @param string $profileVersion Exact installed version.
     *
     * @return         JSONResponse Removal response.
     * @phpstan-return JSONResponse<Http::STATUS_*, array<string, mixed>, array{}>
     */
    #[AuthorizedAdminSetting(settings: Admin::class)]
    public function uninstallProfile(string $profileId='', string $profileVersion=''): JSONResponse
    {
        $result = $this->installer->uninstall(
            profileId: $profileId,
            profileVersion: $profileVersion
        );
        $status = (string) ($result['status'] ?? 'error');
        if ($status === 'ok') {
            return $this->respond(
                data: [
                    'status'   => 'ok',
                    'profiles' => $this->profiles->getAvailableTokenSets(),
                ]
            );
        }

        return $this->respond(
            data: [
                'status' => $status,
                'error'  => $this->getErrorMessage(status: $status),
            ],
            statusCode: $this->getFailureStatusCode(status: $status)
        );
    }//end uninstallProfile()

    /**
     * Create a consistently typed JSON response.
     *
     * @param array<string, mixed> $data       Response body.
     * @param int                  $statusCode HTTP status.
     *
     * @return         JSONResponse JSON response.
     * @phpstan-param  Http::STATUS_* $statusCode
     * @phpstan-return JSONResponse<Http::STATUS_*, array<string, mixed>, array{}>
     */
    private function respond(array $data, int $statusCode=Http::STATUS_OK): JSONResponse
    {
        return new JSONResponse(data: $data, statusCode: $statusCode);
    }//end respond()

    /**
     * Map domain failures to retry-aware HTTP status codes.
     *
     * @param string $status Domain status.
     *
     * @return         int HTTP status code.
     * @phpstan-return Http::STATUS_*
     */
    private function getFailureStatusCode(string $status): int
    {
        if ($status === 'invalid_pack' || $status === 'invalid_profile') {
            return Http::STATUS_UNPROCESSABLE_ENTITY;
        }

        if ($status === 'not_found') {
            return Http::STATUS_NOT_FOUND;
        }

        if ($status === 'capacity_exceeded') {
            return Http::STATUS_INSUFFICIENT_STORAGE;
        }

        if ($status === 'lock_unavailable'
            || $status === 'state_unavailable'
            || $status === 'storage_failed'
        ) {
            return Http::STATUS_SERVICE_UNAVAILABLE;
        }

        return Http::STATUS_CONFLICT;
    }//end getFailureStatusCode()

    /**
     * Return a stable administrator-facing failure message.
     *
     * @param string $status Domain status.
     *
     * @return string Safe failure message.
     */
    private function getErrorMessage(string $status): string
    {
        return match ($status) {
            'invalid_pack', 'invalid_profile' => 'The profile pack is invalid or unsupported.',
            'version_conflict' => 'This profile version already exists with different content.',
            'profile_active' => 'Deactivate or replace this profile version before uninstalling it.',
            'profile_retained_for_rollback' => 'This version is retained as the current rollback target.',
            'not_found' => 'The installed profile version was not found.',
            'capacity_exceeded' => 'The installed profile library has reached its version limit.',
            default => 'The profile library could not be changed.',
        };
    }//end getErrorMessage()
}//end class
