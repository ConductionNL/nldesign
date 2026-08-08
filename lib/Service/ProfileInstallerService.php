<?php

/**
 * NL Design profile installation service.
 *
 * @category Service
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Service;

use OCA\NLDesign\Application\Profile\ProfileCssCompiler;
use OCA\NLDesign\Domain\Profile\ProfilePackValidator;
use OCA\NLDesign\Infrastructure\Nextcloud\ProfileStateMutationGuard;
use OCA\NLDesign\Port\Profile\InstalledProfileRepository;

/**
 * Install and remove immutable profile versions under the shared profile lock.
 */
final class ProfileInstallerService
{
    /**
     * Constructor.
     *
     * @param ProfilePackValidator       $validator     Pack validator.
     * @param ProfileCssCompiler         $compiler      Bounded CSS compiler.
     * @param InstalledProfileRepository $installed     App-data repository.
     * @param TokenSetService            $profiles      Composite catalogue.
     * @param ProfileStateService        $profileState  Active and rollback state.
     * @param ProfileStateMutationGuard  $mutationGuard Shared mutation lock.
     */
    public function __construct(
        private ProfilePackValidator $validator,
        private ProfileCssCompiler $compiler,
        private InstalledProfileRepository $installed,
        private TokenSetService $profiles,
        private ProfileStateService $profileState,
        private ProfileStateMutationGuard $mutationGuard
    ) {
    }//end __construct()

    /**
     * Validate, compile and install one immutable profile version.
     *
     * @param string $profilePack Raw profile-pack JSON.
     * @param string $actor       Installing actor.
     *
     * @return array<string, mixed> Installation result.
     */
    public function install(string $profilePack, string $actor): array
    {
        $decoded      = $this->validator->decode(content: $profilePack);
        $profileValue = $decoded['profile'] ?? null;
        if (($decoded['status'] ?? null) !== 'ok' || is_array($profileValue) === false) {
            return $decoded;
        }

        $profile = $profileValue;
        $css     = $this->compiler->compile(profile: $profile);

        return $this->mutationGuard->run(
            operation: function () use ($profile, $css, $actor): array {
                $existing = $this->profiles->getTokenSetMetadata(
                    tokenSetId: (string) $profile['id'],
                    profileVersion: (string) $profile['version']
                );
                if (is_array($existing) === true && ($existing['origin'] ?? null) === 'built-in') {
                    return ['status' => 'version_conflict'];
                }

                return $this->installed->install(
                    profile: $profile,
                    css: $css,
                    actor: $actor
                );
            }
        );
    }//end install()

    /**
     * Remove an inactive installed version that is not the rollback target.
     *
     * @param string $profileId      Stable profile identifier.
     * @param string $profileVersion Exact installed version.
     *
     * @return array<string, mixed> Removal result.
     */
    public function uninstall(string $profileId, string $profileVersion): array
    {
        if ($this->validator->isValidId(profileId: $profileId) === false
            || $this->validator->isValidVersion(profileVersion: $profileVersion) === false
        ) {
            return ['status' => 'invalid_profile'];
        }

        return $this->mutationGuard->run(
            operation: function () use ($profileId, $profileVersion): array {
                $state = $this->profileState->getActiveProfileState();
                if (($state['active_profile_id'] ?? null) === $profileId
                    && ($state['active_profile_version'] ?? null) === $profileVersion
                ) {
                    return ['status' => 'profile_active'];
                }

                $previous        = $state['previous_profile_snapshot'] ?? null;
                $retainedVersion = null;
                if (is_array($previous) === true) {
                    $retainedVersion = $previous['profile_version'] ?? null;
                }

                if (is_array($previous) === true
                    && ($previous['profile_id'] ?? null) === $profileId
                    && ($retainedVersion === null
                    || $retainedVersion === $profileVersion)
                ) {
                    return ['status' => 'profile_retained_for_rollback'];
                }

                return $this->installed->remove(
                    profileId: $profileId,
                    profileVersion: $profileVersion
                );
            }
        );
    }//end uninstall()
}//end class
