<?php

/**
 * Installed profile app-data record codec.
 *
 * @category Infrastructure
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Infrastructure\Profile;

use JsonException;
use OCA\NLDesign\Application\Profile\ProfileCssCompiler;
use OCA\NLDesign\Domain\Profile\ProfilePackValidator;

/**
 * Encode and integrity-check immutable installed-profile records.
 */
final class InstalledProfileRecordCodec
{
    public const MAX_RECORD_BYTES = 65536;

    private const RECORD_SCHEMA     = 'nldesign-installed-profile/v1';
    private const HASH_PATTERN      = '/^[a-f0-9]{64}$/';
    private const TIMESTAMP_PATTERN = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/';
    private const RECORD_FIELDS     = [
        'schema'       => true,
        'profile'      => true,
        'css'          => true,
        'content_hash' => true,
        'installed_at' => true,
        'installed_by' => true,
    ];

    /**
     * Constructor.
     *
     * @param ProfilePackValidator $validator Installed descriptor validator.
     * @param ProfileCssCompiler   $compiler  Deterministic CSS compiler.
     */
    public function __construct(
        private ProfilePackValidator $validator,
        private ProfileCssCompiler $compiler
    ) {
    }//end __construct()

    /**
     * Encode one validated profile and generated stylesheet.
     *
     * @param array<string, mixed> $profile Candidate profile descriptor.
     * @param string               $css     Candidate generated stylesheet.
     * @param string               $actor   Installing actor.
     *
     * @return array<string, mixed>|null Encoded record details.
     */
    public function encode(array $profile, string $css, string $actor): ?array
    {
        $normalized = $this->validator->normalizeProfile(value: $profile);
        if ($normalized === null || $this->compiler->compile(profile: $normalized) !== $css) {
            return null;
        }

        $contentHash = $this->buildContentHash(profile: $normalized, css: $css);
        $record      = [
            'schema'       => self::RECORD_SCHEMA,
            'profile'      => $normalized,
            'css'          => $css,
            'content_hash' => $contentHash,
            'installed_at' => gmdate('c'),
            'installed_by' => $this->normalizeActor(actor: $actor),
        ];

        try {
            $content = json_encode(
                value: $record,
                flags: JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES
            );
        } catch (JsonException) {
            return null;
        }

        if (strlen($content) > self::MAX_RECORD_BYTES) {
            return null;
        }

        return [
            'content'      => $content,
            'content_hash' => $contentHash,
            'profile'      => $normalized,
        ];
    }//end encode()

    /**
     * Decode, validate and project one app-data record.
     *
     * @param string $content Raw app-data JSON.
     *
     * @return array<string, mixed>|null Verified runtime record.
     */
    public function decode(string $content): ?array
    {
        $record = $this->decodeEnvelope(content: $content);
        if ($record === null) {
            return null;
        }

        $profile     = $this->validator->normalizeProfile(value: $record['profile'] ?? null);
        $css         = $record['css'] ?? null;
        $contentHash = $record['content_hash'] ?? null;
        $installedAt = $record['installed_at'] ?? null;
        $installedBy = $record['installed_by'] ?? null;
        if ($profile === null
            || $this->isConsistent(
                profile: $profile,
                css: $css,
                contentHash: $contentHash,
                installedAt: $installedAt,
                installedBy: $installedBy
            ) === false
        ) {
            return null;
        }

        return $this->buildRuntimeRecord(
            profile: $profile,
            css: $css,
            contentHash: $contentHash,
            installedAt: $installedAt,
            installedBy: $installedBy
        );
    }//end decode()

    /**
     * Decode and validate the closed persistence envelope.
     *
     * @param string $content Raw JSON.
     *
     * @return array<string, mixed>|null Decoded envelope.
     */
    private function decodeEnvelope(string $content): ?array
    {
        if ($content === '' || strlen($content) > self::MAX_RECORD_BYTES) {
            return null;
        }

        try {
            $record = json_decode(
                json: $content,
                associative: true,
                flags: JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            return null;
        }

        if (is_array($record) === false
            || array_is_list($record) === true
            || $this->hasOnlyRecordFields(record: $record) === false
            || ($record['schema'] ?? null) !== self::RECORD_SCHEMA
        ) {
            return null;
        }

        return $record;
    }//end decodeEnvelope()

    /**
     * Verify generated output, digest and bounded installation metadata.
     *
     * @param array<string, mixed> $profile     Canonical profile.
     * @param mixed                $css         Stored CSS.
     * @param mixed                $contentHash Stored digest.
     * @param mixed                $installedAt Stored timestamp.
     * @param mixed                $installedBy Stored actor.
     *
     * @return bool Whether every stored field is internally consistent and bounded.
     */
    private function isConsistent(
        array $profile,
        mixed $css,
        mixed $contentHash,
        mixed $installedAt,
        mixed $installedBy
    ): bool {
        return is_string($css) === true
            && $css === $this->compiler->compile(profile: $profile)
            && is_string($contentHash) === true
            && preg_match(self::HASH_PATTERN, $contentHash) === 1
            && hash_equals($contentHash, $this->buildContentHash(profile: $profile, css: $css)) === true
            && is_string($installedAt) === true
            && preg_match(self::TIMESTAMP_PATTERN, $installedAt) === 1
            && $this->isValidActor(actor: $installedBy) === true;
    }//end isConsistent()

    /**
     * Project a verified persistence record into runtime metadata.
     *
     * @param array<string, mixed> $profile     Canonical profile.
     * @param string               $css         Generated CSS.
     * @param string               $contentHash Verified digest.
     * @param string               $installedAt Installation timestamp.
     * @param string               $installedBy Installing actor.
     *
     * @return array<string, mixed>|null Runtime record.
     */
    private function buildRuntimeRecord(
        array $profile,
        string $css,
        string $contentHash,
        string $installedAt,
        string $installedBy
    ): ?array {
        $tokens = $profile['tokens'] ?? null;
        if (is_array($tokens) === false || is_array($tokens['light'] ?? null) === false) {
            return null;
        }

        $primary = $tokens['light']['primary'] ?? null;
        if (is_string($primary) === false) {
            return null;
        }

        $metadata = $profile;
        unset($metadata['tokens']);
        $metadata['status']       = 'ready';
        $metadata['origin']       = 'installed';
        $metadata['installed']    = true;
        $metadata['content_hash'] = $contentHash;
        $metadata['installed_at'] = $installedAt;
        $metadata['installed_by'] = $installedBy;
        $metadata['theming']      = ['primary_color' => $primary];
        $metadata['preview']      = $this->compiler->buildPreview(profile: $profile);

        return [
            'metadata' => $metadata,
            'profile'  => $profile,
            'css'      => $css,
        ];
    }//end buildRuntimeRecord()

    /**
     * Build a content identity over metadata and generated output.
     *
     * @param array<string, mixed> $profile Canonical profile descriptor.
     * @param string               $css     Compiled CSS.
     *
     * @return string SHA-256 digest.
     */
    private function buildContentHash(array $profile, string $css): string
    {
        try {
            $canonical = json_encode(
                value: $profile,
                flags: JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES
            );
        } catch (JsonException) {
            return '';
        }

        return hash(algo: 'sha256', data: $canonical."\n".$css);
    }//end buildContentHash()

    /**
     * Keep record envelopes closed to prevent field smuggling.
     *
     * @param array<string, mixed> $record Candidate record.
     *
     * @return bool Whether every field is supported.
     */
    private function hasOnlyRecordFields(array $record): bool
    {
        foreach (array_keys($record) as $field) {
            if (isset(self::RECORD_FIELDS[$field]) === false) {
                return false;
            }
        }

        return true;
    }//end hasOnlyRecordFields()

    /**
     * Normalize the stored installation actor.
     *
     * @param string $actor Raw actor.
     *
     * @return string Bounded actor.
     */
    private function normalizeActor(string $actor): string
    {
        if ($this->isValidActor(actor: $actor) === false) {
            return 'system';
        }

        return trim($actor);
    }//end normalizeActor()

    /**
     * Validate one stored installation actor.
     *
     * @param mixed $actor Candidate actor.
     *
     * @return bool Whether it is safe metadata.
     */
    private function isValidActor(mixed $actor): bool
    {
        return is_string($actor) === true
            && trim($actor) !== ''
            && strlen($actor) <= 256
            && preg_match('/[\x00-\x1f\x7f]/u', $actor) === 0;
    }//end isValidActor()
}//end class
