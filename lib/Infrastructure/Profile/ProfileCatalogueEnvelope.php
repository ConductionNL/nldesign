<?php

/**
 * NL Design profile-catalogue envelope decoder.
 *
 * @category Infrastructure
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/ConductionNL/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Infrastructure\Profile;

use JsonException;

/**
 * Decode and validate the versioned top-level catalogue contract.
 */
final class ProfileCatalogueEnvelope
{
    private const MANIFEST_SCHEMA = 'nldesign-profile-catalogue/v1';
    private const ALLOWED_FIELDS  = [
        'schema'          => true,
        'default_profile' => true,
        'profiles'        => true,
    ];

    /**
     * Decode a catalogue without allowing legacy or ambiguous shapes.
     *
     * @param string $content Raw catalogue JSON.
     *
     * @return array{default_profile: null, profiles: array<int, mixed>}|null Valid envelope.
     */
    public function decode(string $content): ?array
    {
        try {
            $decoded = json_decode(
                json: $content,
                associative: true,
                flags: JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            return null;
        }

        if (is_array($decoded) === false
            || array_is_list($decoded) === true
            || ($decoded['schema'] ?? null) !== self::MANIFEST_SCHEMA
        ) {
            return null;
        }

        foreach (array_keys($decoded) as $field) {
            if (isset(self::ALLOWED_FIELDS[$field]) === false) {
                return null;
            }
        }

        if (array_key_exists('default_profile', $decoded) === false) {
            return null;
        }

        $defaultProfile = $decoded['default_profile'];
        $profiles       = $decoded['profiles'] ?? null;
        if ($defaultProfile !== null
            || is_array($profiles) === false
            || array_is_list($profiles) === false
        ) {
            return null;
        }

        return [
            'default_profile' => $defaultProfile,
            'profiles'        => $profiles,
        ];
    }//end decode()
}//end class
