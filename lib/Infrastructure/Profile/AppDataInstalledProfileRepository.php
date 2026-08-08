<?php

/**
 * Nextcloud app-data installed profile repository.
 *
 * @category Infrastructure
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Infrastructure\Profile;

use OCA\NLDesign\Domain\Profile\ProfilePackValidator;
use OCA\NLDesign\Port\Profile\InstalledProfileRepository;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Store each immutable installed version as one independently valid app-data file.
 */
final class AppDataInstalledProfileRepository implements InstalledProfileRepository
{
    private const FOLDER_NAME            = 'installed-profiles';
    private const MAX_INSTALLED_VERSIONS = 100;

    /**
     * Constructor.
     *
     * @param IAppData                    $appData   App-owned data root.
     * @param ProfilePackValidator        $validator Installed descriptor validator.
     * @param InstalledProfileRecordCodec $codec     Record encoder and verifier.
     * @param LoggerInterface             $logger    Application logger.
     */
    public function __construct(
        private IAppData $appData,
        private ProfilePackValidator $validator,
        private InstalledProfileRecordCodec $codec,
        private LoggerInterface $logger
    ) {
    }//end __construct()

    /**
     * List every valid installed profile record.
     *
     * @return array<int, array<string, mixed>> Installed records.
     */
    public function listRecords(): array
    {
        try {
            $folder = $this->getFolder(create: false);
            if ($folder === null) {
                return [];
            }

            $files = $folder->getDirectoryListing();
            if (count($files) > self::MAX_INSTALLED_VERSIONS) {
                $this->logger->warning('Ignoring installed profile library above its version limit.');
                return [];
            }

            $records = [];
            foreach ($files as $file) {
                if (str_ends_with($file->getName(), '.json') === false
                    || $file->getSize() > InstalledProfileRecordCodec::MAX_RECORD_BYTES
                ) {
                    continue;
                }

                $record = $this->codec->decode(content: $file->getContent());
                if ($record !== null
                    && $file->getName() === $this->getFileName(
                        profileId: (string) $record['metadata']['id'],
                        profileVersion: (string) $record['metadata']['version']
                    )
                ) {
                    $records[] = $record;
                }
            }//end foreach

            return $records;
        } catch (Throwable $exception) {
            $this->logger->warning(
                'Installed NL Design profiles could not be listed.',
                ['exception' => $exception]
            );
            return [];
        }//end try
    }//end listRecords()

    /**
     * Find one exact installed profile version.
     *
     * @param string $profileId      Stable profile identifier.
     * @param string $profileVersion Immutable profile version.
     *
     * @return array<string, mixed>|null Installed record.
     */
    public function find(string $profileId, string $profileVersion): ?array
    {
        if ($this->validator->isValidId(profileId: $profileId) === false
            || $this->validator->isValidVersion(profileVersion: $profileVersion) === false
        ) {
            return null;
        }

        try {
            $folder = $this->getFolder(create: false);
            $name   = $this->getFileName(profileId: $profileId, profileVersion: $profileVersion);
            if ($folder === null || $folder->fileExists(name: $name) === false) {
                return null;
            }

            $file = $folder->getFile(name: $name);
            if ($file->getSize() > InstalledProfileRecordCodec::MAX_RECORD_BYTES) {
                return null;
            }

            return $this->codec->decode(content: $file->getContent());
        } catch (Throwable $exception) {
            $this->logger->warning(
                'An installed NL Design profile could not be read.',
                [
                    'profile_id'      => $profileId,
                    'profile_version' => $profileVersion,
                    'exception'       => $exception,
                ]
            );
            return null;
        }//end try
    }//end find()

    /**
     * Store one validated, compiled version without overwriting an existing one.
     *
     * @param array<string, mixed> $profile Validated profile descriptor.
     * @param string               $css     Deterministically compiled CSS.
     * @param string               $actor   Installing actor.
     *
     * @return array<string, mixed> Installation result.
     */
    public function install(array $profile, string $css, string $actor): array
    {
        $encoded = $this->codec->encode(profile: $profile, css: $css, actor: $actor);
        if ($encoded === null) {
            return ['status' => 'invalid_profile'];
        }

        $normalized  = $encoded['profile'];
        $createdFile = null;

        try {
            $folder = $this->getFolder(create: true);
            if ($folder === null) {
                return ['status' => 'storage_failed'];
            }

            $name = $this->getFileName(
                profileId: (string) $normalized['id'],
                profileVersion: (string) $normalized['version']
            );
            $hash = $encoded['content_hash'];
            if ($folder->fileExists(name: $name) === true) {
                $existing = $this->find(
                    profileId: (string) $normalized['id'],
                    profileVersion: (string) $normalized['version']
                );
                if (is_array($existing) === true
                    && ($existing['metadata']['content_hash'] ?? null) === $hash
                ) {
                    return [
                        'status'   => 'noop',
                        'metadata' => $existing['metadata'],
                    ];
                }

                return ['status' => 'version_conflict'];
            }

            if (count($folder->getDirectoryListing()) >= self::MAX_INSTALLED_VERSIONS) {
                return ['status' => 'capacity_exceeded'];
            }

            $createdFile = $folder->newFile(name: $name, content: $encoded['content']);
            $saved       = $this->codec->decode(content: $createdFile->getContent());
            if ($saved === null || ($saved['metadata']['content_hash'] ?? null) !== $hash) {
                $createdFile->delete();
                return ['status' => 'storage_failed'];
            }

            return [
                'status'   => 'ok',
                'metadata' => $saved['metadata'],
            ];
        } catch (Throwable $exception) {
            if ($createdFile instanceof ISimpleFile) {
                $this->removeFailedFile(file: $createdFile);
            }

            $this->logger->error(
                'An NL Design profile version could not be installed.',
                [
                    'profile_id'      => $normalized['id'],
                    'profile_version' => $normalized['version'],
                    'exception'       => $exception,
                ]
            );
            return ['status' => 'storage_failed'];
        }//end try
    }//end install()

    /**
     * Remove a newly created record after verification or storage failed.
     *
     * @param ISimpleFile $file Newly created app-data file.
     *
     * @return void
     */
    private function removeFailedFile(ISimpleFile $file): void
    {
        try {
            $file->delete();
        } catch (Throwable $exception) {
            $this->logger->warning(
                'A failed NL Design profile record could not be cleaned up.',
                ['exception' => $exception]
            );
        }
    }//end removeFailedFile()

    /**
     * Remove one exact installed version.
     *
     * @param string $profileId      Stable profile identifier.
     * @param string $profileVersion Immutable profile version.
     *
     * @return array<string, mixed> Removal result.
     */
    public function remove(string $profileId, string $profileVersion): array
    {
        if ($this->validator->isValidId(profileId: $profileId) === false
            || $this->validator->isValidVersion(profileVersion: $profileVersion) === false
        ) {
            return ['status' => 'invalid_profile'];
        }

        try {
            $folder = $this->getFolder(create: false);
            $name   = $this->getFileName(profileId: $profileId, profileVersion: $profileVersion);
            if ($folder === null || $folder->fileExists(name: $name) === false) {
                return ['status' => 'not_found'];
            }

            $folder->getFile(name: $name)->delete();
            return ['status' => 'ok'];
        } catch (Throwable $exception) {
            $this->logger->error(
                'An installed NL Design profile version could not be removed.',
                [
                    'profile_id'      => $profileId,
                    'profile_version' => $profileVersion,
                    'exception'       => $exception,
                ]
            );
            return ['status' => 'storage_failed'];
        }//end try
    }//end remove()

    /**
     * Resolve the app-data folder, optionally creating it.
     *
     * @param bool $create Create the folder when absent.
     *
     * @return ISimpleFolder|null App-data folder.
     */
    private function getFolder(bool $create): ?ISimpleFolder
    {
        try {
            return $this->appData->getFolder(name: self::FOLDER_NAME);
        } catch (NotFoundException) {
            if ($create === false) {
                return null;
            }

            return $this->appData->newFolder(name: self::FOLDER_NAME);
        }
    }//end getFolder()

    /**
     * Build the deterministic app-data filename for one exact version.
     *
     * @param string $profileId      Stable profile identifier.
     * @param string $profileVersion Immutable profile version.
     *
     * @return string Safe filename.
     */
    private function getFileName(string $profileId, string $profileVersion): string
    {
        return $profileId.'--'.$profileVersion.'.json';
    }//end getFileName()
}//end class
