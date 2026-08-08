<?php

/**
 * Installable profile-pack validation.
 *
 * @category Domain
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Domain\Profile;

use JsonException;

/**
 * Reduce an untrusted profile pack to the bounded Nextcloud projection input.
 */
final class ProfilePackValidator
{
    public const SCHEMA = 'nldesign-profile-pack/v1';

    private const PROJECTION         = 'nextcloud-core-v1';
    private const ID_PATTERN         = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
    private const VERSION_CORE       = '(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)';
    private const VERSION_PRERELEASE = '(?:-(?:(?!0\d+(?:\.|$))[0-9A-Za-z-]+)(?:\.(?:(?!0\d+(?:\.|$))[0-9A-Za-z-]+))*)?';
    private const VERSION_PATTERN    = '/^'.self::VERSION_CORE.self::VERSION_PRERELEASE.'$/';
    private const MAX_PACK_BYTES     = 65536;
    private const MAX_ID_LENGTH      = 64;
    private const MAX_VERSION_LENGTH = 64;
    private const MAX_NAME_LENGTH    = 160;
    private const MAX_DESCRIPTION_LENGTH  = 500;
    private const MAX_PROVENANCE_LENGTH   = 500;
    private const ALLOWED_ENVELOPE_FIELDS = [
        'schema'  => true,
        'profile' => true,
    ];
    private const ALLOWED_PROFILE_FIELDS  = [
        'id'              => true,
        'version'         => true,
        'name'            => true,
        'description'     => true,
        'publisher'       => true,
        'license'         => true,
        'source'          => true,
        'source_revision' => true,
        'projection'      => true,
        'tokens'          => true,
    ];
    private const ALLOWED_TOKEN_FIELDS    = [
        'font_stack' => true,
        'light'      => true,
        'dark'       => true,
    ];
    private const ALLOWED_FONT_STACKS     = [
        'fira-sans' => true,
        'system'    => true,
    ];

    /**
     * Constructor.
     *
     * @param ProfileModeValidator $modeValidator Colour-mode validator.
     */
    public function __construct(private ProfileModeValidator $modeValidator)
    {
    }//end __construct()

    /**
     * Decode and validate an uploaded profile pack.
     *
     * @param string $content Raw JSON document.
     *
     * @return array<string, mixed> Validation result.
     */
    public function decode(string $content): array
    {
        if ($content === '' || strlen($content) > self::MAX_PACK_BYTES) {
            return $this->failure(
                code: 'invalid_pack_size',
                message: 'Profile pack must be a non-empty JSON file no larger than 64 KiB.'
            );
        }

        try {
            $decoded = json_decode(
                json: $content,
                associative: true,
                flags: JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            return $this->failure(
                code: 'invalid_json',
                message: 'Profile pack is not valid JSON.'
            );
        }

        if (is_array($decoded) === false
            || array_is_list($decoded) === true
            || $this->hasOnlyFields(value: $decoded, allowed: self::ALLOWED_ENVELOPE_FIELDS) === false
            || ($decoded['schema'] ?? null) !== self::SCHEMA
        ) {
            return $this->failure(
                code: 'invalid_envelope',
                message: 'Profile pack does not use the supported nldesign-profile-pack/v1 envelope.'
            );
        }

        $profile = $this->normalizeProfile(value: $decoded['profile'] ?? null);
        if ($profile === null) {
            return $this->failure(
                code: 'invalid_profile',
                message: 'Profile metadata or projection tokens are incomplete or unsupported.'
            );
        }

        return [
            'status'  => 'ok',
            'profile' => $profile,
        ];
    }//end decode()

    /**
     * Normalize a profile descriptor read from a trusted envelope or app data.
     *
     * @param mixed $value Candidate descriptor.
     *
     * @return array<string, mixed>|null Canonical profile descriptor.
     */
    public function normalizeProfile(mixed $value): ?array
    {
        if (is_array($value) === false
            || array_is_list($value) === true
            || $this->hasOnlyFields(value: $value, allowed: self::ALLOWED_PROFILE_FIELDS) === false
        ) {
            return null;
        }

        $id      = $value['id'] ?? null;
        $version = $value['version'] ?? null;
        if (is_string($id) === false
            || is_string($version) === false
            || $this->isValidId(profileId: $id) === false
            || $this->isValidVersion(profileVersion: $version) === false
            || ($value['projection'] ?? null) !== self::PROJECTION
        ) {
            return null;
        }

        $textLimits = [
            'name'            => self::MAX_NAME_LENGTH,
            'description'     => self::MAX_DESCRIPTION_LENGTH,
            'publisher'       => self::MAX_PROVENANCE_LENGTH,
            'license'         => self::MAX_PROVENANCE_LENGTH,
            'source'          => self::MAX_PROVENANCE_LENGTH,
            'source_revision' => self::MAX_PROVENANCE_LENGTH,
        ];
        $texts      = [];
        foreach ($textLimits as $field => $limit) {
            $normalized = $this->normalizeText(value: $value[$field] ?? null, maxLength: $limit);
            if ($normalized === null) {
                return null;
            }

            $texts[$field] = $normalized;
        }

        $tokens = $this->normalizeTokens(value: $value['tokens'] ?? null);
        if ($tokens === null) {
            return null;
        }

        return [
            'id'         => $id,
            'version'    => $version,
            ...$texts,
            'projection' => self::PROJECTION,
            'tokens'     => $tokens,
        ];
    }//end normalizeProfile()

    /**
     * Validate a stable profile identifier.
     *
     * @param string $profileId Profile identifier.
     *
     * @return bool Whether the identifier is supported.
     */
    public function isValidId(string $profileId): bool
    {
        return strlen($profileId) <= self::MAX_ID_LENGTH
            && preg_match(self::ID_PATTERN, $profileId) === 1;
    }//end isValidId()

    /**
     * Validate an immutable semantic version.
     *
     * @param string $profileVersion Profile version.
     *
     * @return bool Whether the version is supported.
     */
    public function isValidVersion(string $profileVersion): bool
    {
        return strlen($profileVersion) <= self::MAX_VERSION_LENGTH
            && preg_match(self::VERSION_PATTERN, $profileVersion) === 1;
    }//end isValidVersion()

    /**
     * Normalize the bounded semantic projection.
     *
     * @param mixed $value Raw token object.
     *
     * @return array<string, mixed>|null Canonical tokens.
     */
    private function normalizeTokens(mixed $value): ?array
    {
        if (is_array($value) === false
            || array_is_list($value) === true
            || $this->hasOnlyFields(value: $value, allowed: self::ALLOWED_TOKEN_FIELDS) === false
        ) {
            return null;
        }

        $fontStack = $value['font_stack'] ?? null;
        if (is_string($fontStack) === false || isset(self::ALLOWED_FONT_STACKS[$fontStack]) === false) {
            return null;
        }

        $light = $this->modeValidator->normalize(value: $value['light'] ?? null);
        if ($light === null) {
            return null;
        }

        $tokens = [
            'font_stack' => $fontStack,
            'light'      => $light,
        ];
        if (array_key_exists('dark', $value) === true) {
            $dark = $this->modeValidator->normalize(value: $value['dark']);
            if ($dark === null) {
                return null;
            }

            $tokens['dark'] = $dark;
        }

        return $tokens;
    }//end normalizeTokens()

    /**
     * Normalize bounded display or provenance text.
     *
     * @param mixed $value     Candidate text.
     * @param int   $maxLength Maximum UTF-8 byte length.
     *
     * @return string|null Normalized text.
     */
    private function normalizeText(mixed $value, int $maxLength): ?string
    {
        if (is_string($value) === false
            || trim($value) === ''
            || strlen($value) > $maxLength
            || preg_match('/[\x00-\x1f\x7f]/u', $value) !== 0
        ) {
            return null;
        }

        return trim($value);
    }//end normalizeText()

    /**
     * Check a closed JSON-object shape.
     *
     * @param array<string, mixed> $value   Candidate object.
     * @param array<string, bool>  $allowed Allowed field map.
     *
     * @return bool Whether every field is known.
     */
    private function hasOnlyFields(array $value, array $allowed): bool
    {
        foreach (array_keys($value) as $field) {
            if (isset($allowed[$field]) === false) {
                return false;
            }
        }

        return true;
    }//end hasOnlyFields()

    /**
     * Build a stable validation failure.
     *
     * @param string $code    Machine-readable code.
     * @param string $message Administrator-safe explanation.
     *
     * @return array<string, string> Failure result.
     */
    private function failure(string $code, string $message): array
    {
        return [
            'status'  => 'invalid_pack',
            'code'    => $code,
            'message' => $message,
        ];
    }//end failure()
}//end class
