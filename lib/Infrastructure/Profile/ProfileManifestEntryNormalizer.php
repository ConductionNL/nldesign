<?php

/**
 * NL Design profile-manifest entry normalization.
 *
 * @category Infrastructure
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Infrastructure\Profile;

/**
 * Reduce one untrusted package record to supported runtime metadata.
 */
final class ProfileManifestEntryNormalizer
{
    private const ID_PATTERN         = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
    private const COLOR_PATTERN      = '/^#[0-9a-fA-F]{6}$/';
    private const VERSION_CORE       = '(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)';
    private const VERSION_PRERELEASE = '(?:-(?:(?!0\d+(?:\.|$))[0-9A-Za-z-]+)(?:\.(?:(?!0\d+(?:\.|$))[0-9A-Za-z-]+))*)?';
    private const VERSION_PATTERN    = '/^'.self::VERSION_CORE.self::VERSION_PRERELEASE.'$/';
    private const ASSET_PATTERN      = '#^img/(?:logos|backgrounds)/[a-zA-Z0-9._-]+\.(?:svg|png|jpe?g|webp)$#i';
    private const READY_STATUS       = 'ready';
    private const PROJECTION_ID      = 'nextcloud-core-v1';
    private const MAX_ID_LENGTH      = 64;
    private const MAX_VERSION_LENGTH = 64;
    private const MAX_NAME_LENGTH    = 160;
    private const MAX_DESCRIPTION_LENGTH = 500;
    private const ALLOWED_FIELDS         = [
        'id'          => true,
        'version'     => true,
        'name'        => true,
        'description' => true,
        'status'      => true,
        'projection'  => true,
        'theming'     => true,
    ];

    /**
     * Constructor.
     *
     * @param PackagedProfileFiles $profileFiles Immutable package boundary.
     */
    public function __construct(private PackagedProfileFiles $profileFiles)
    {
    }//end __construct()

    /**
     * Normalize one manifest entry and discard malformed optional hints.
     *
     * @param array<string, mixed> $entry Raw manifest entry.
     *
     * @return array<string, mixed>|null Normalized metadata.
     */
    public function normalize(array $entry): ?array
    {
        foreach (array_keys($entry) as $field) {
            if (isset(self::ALLOWED_FIELDS[$field]) === false) {
                return null;
            }
        }

        $id = $entry['id'] ?? null;
        if (is_string($id) === false || $this->isValidId(id: $id) === false) {
            return null;
        }

        $availability = $this->normalizeAvailability(entry: $entry);
        if ($availability === null) {
            return null;
        }

        $name        = $this->normalizeRequiredText(
            value: $entry['name'] ?? null,
            maxLength: self::MAX_NAME_LENGTH
        );
        $description = $this->normalizeRequiredText(
            value: $entry['description'] ?? null,
            maxLength: self::MAX_DESCRIPTION_LENGTH
        );
        if ($name === null || $description === null) {
            return null;
        }

        $metadata = [
            'id'          => $id,
            'name'        => $name,
            'description' => $description,
            'source'      => 'token-sets.json',
            'origin'      => 'built-in',
            'installed'   => false,
            ...$availability,
        ];

        if ($metadata['status'] === self::READY_STATUS) {
            $version = $entry['version'] ?? null;
            if (is_string($version) === false || $this->isValidVersion(version: $version) === false) {
                return null;
            }

            $metadata['version']      = $version;
            $metadata['content_hash'] = $this->profileFiles->getStylesheetHash(profileId: $id);
            if ($metadata['content_hash'] === null) {
                return null;
            }
        }

        $theming = $this->normalizeTheming(value: $entry['theming'] ?? null);
        if ($theming !== []) {
            $metadata['theming'] = $theming;
        }

        return $metadata;
    }//end normalize()

    /**
     * Validate a profile identifier.
     *
     * @param string $id Profile identifier.
     *
     * @return bool Whether the identifier is safe.
     */
    public function isValidId(string $id): bool
    {
        return strlen($id) <= self::MAX_ID_LENGTH
            && preg_match(self::ID_PATTERN, $id) === 1;
    }//end isValidId()

    /**
     * Validate an immutable compiled-profile version.
     *
     * @param string $version Semantic version.
     *
     * @return bool Whether the version is supported.
     */
    public function isValidVersion(string $version): bool
    {
        return strlen($version) <= self::MAX_VERSION_LENGTH
            && preg_match(self::VERSION_PATTERN, $version) === 1;
    }//end isValidVersion()

    /**
     * Normalize the package availability contract.
     *
     * @param array<string, mixed> $entry Raw manifest entry.
     *
     * @return array<string, string>|null Safe status metadata.
     */
    private function normalizeAvailability(array $entry): ?array
    {
        $status = $entry['status'] ?? null;
        if ($status === self::READY_STATUS
            && ($entry['projection'] ?? null) === self::PROJECTION_ID
        ) {
            return [
                'status'     => self::READY_STATUS,
                'projection' => self::PROJECTION_ID,
            ];
        }

        if ($status === 'source-only'
            && array_key_exists('projection', $entry) === false
            && array_key_exists('theming', $entry) === false
            && array_key_exists('version', $entry) === false
        ) {
            return ['status' => 'source-only'];
        }

        return null;
    }//end normalizeAvailability()

    /**
     * Normalize required bounded human-readable manifest text.
     *
     * @param mixed $value     Candidate value.
     * @param int   $maxLength Maximum byte length.
     *
     * @return string|null Normalized text, or null for an invalid field.
     */
    private function normalizeRequiredText(mixed $value, int $maxLength): ?string
    {
        if (is_string($value) === false
            || trim($value) === ''
            || strlen($value) > $maxLength
            || preg_match('/[\x00-\x1f\x7f]/u', $value) !== 0
        ) {
            return null;
        }

        return trim($value);
    }//end normalizeRequiredText()

    /**
     * Retain only valid, explicitly supported manual theming hints.
     *
     * @param mixed $value Raw theming value.
     *
     * @return array<string, string> Safe hints.
     */
    private function normalizeTheming(mixed $value): array
    {
        if (is_array($value) === false) {
            return [];
        }

        $theming = [];
        foreach (['primary_color', 'background_color'] as $colorKey) {
            $color = $value[$colorKey] ?? null;
            if (is_string($color) === true && preg_match(self::COLOR_PATTERN, $color) === 1) {
                $theming[$colorKey] = strtolower($color);
            }
        }

        foreach (['logo', 'background'] as $assetKey) {
            $asset = $value[$assetKey] ?? null;
            if (is_string($asset) === true
                && preg_match(self::ASSET_PATTERN, $asset) === 1
                && $this->profileFiles->hasSafeAsset(asset: $asset) === true
            ) {
                $theming[$assetKey] = $asset;
            }
        }

        return $theming;
    }//end normalizeTheming()
}//end class
