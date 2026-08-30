<?php

/**
 * NL Design Font Upload Validator.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category  Service
 * @package   OCA\Thematiq
 * @author    Conduction <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://github.com/ConductionNL/thematiq
 *
 * @spec openspec/specs/custom-fonts/spec.md
 */

declare(strict_types=1);

namespace OCA\Thematiq\Service;

use RuntimeException;

/**
 * Validates admin-uploaded font binaries and their display names.
 *
 * WOFF2 is the only accepted container format, verified by content: the
 * first four bytes MUST be the `wOF2` magic number (W3C WOFF File Format
 * 2.0), never the file extension or the client-supplied MIME type — both are
 * trivially spoofable by renaming a file. Display-name validation is kept
 * independent of slug derivation so a raw path-traversal attempt (`../`, a
 * bare `/`, a NUL byte) is rejected outright with a 422 rather than silently
 * neutered into hyphens by the slugifier.
 *
 * Every check is a separate method (rather than one monolithic `validate()`)
 * so the hardening test corpus can assert precisely which check failed and
 * the failure message names it, mirroring {@see CustomTokenSetValidator}.
 *
 * @spec openspec/specs/custom-fonts/spec.md
 */
class FontValidator {

	/**
	 * Maximum accepted upload size in bytes (2 MB).
	 *
	 * @var int
	 */
	public const MAX_SIZE = (2 * 1024 * 1024);

	/**
	 * Maximum number of fonts stored per instance.
	 *
	 * @var int
	 */
	public const MAX_FONTS = 20;

	/**
	 * The WOFF2 magic number expected at offset 0.
	 *
	 * @var string
	 */
	public const MAGIC_BYTES = 'wOF2';

	/**
	 * The accepted font roles.
	 *
	 * @var string[]
	 */
	public const ROLES = ['body', 'heading'];

	/**
	 * Validate a size (either the upload's reported size, or the actual byte
	 * length) against the 2 MB cap.
	 *
	 * Callers MUST invoke this twice: once against the upload's reported
	 * size (before the bytes are even read into memory, so an oversized
	 * upload is rejected cheaply) and again against `strlen()` of the actual
	 * bytes, so a client that lies about its reported size cannot bypass the
	 * cap.
	 *
	 * @param int $size The size in bytes to check.
	 *
	 * @return void
	 *
	 * @throws RuntimeException Code 413 when the size exceeds the cap.
	 *
	 * @spec openspec/specs/custom-fonts/spec.md
	 */
	public function validateSize(int $size): void {
		if ($size > self::MAX_SIZE) {
			throw new RuntimeException('The font file exceeds the 2 MB size limit.', 413);
		}
	}//end validateSize()

	/**
	 * Validate the WOFF2 magic bytes at offset 0.
	 *
	 * @param string $bytes The raw file bytes.
	 *
	 * @return void
	 *
	 * @throws RuntimeException Code 422 when the first four bytes are not
	 *                          exactly `wOF2`. The extension and client MIME type are never
	 *                          consulted for this check.
	 *
	 * @spec openspec/specs/custom-fonts/spec.md
	 */
	public function validateMagicBytes(string $bytes): void {
		if (strncmp($bytes, self::MAGIC_BYTES, 4) !== 0) {
			throw new RuntimeException(
				'The uploaded file is not a valid WOFF2 font (magic byte check failed: expected "wOF2" at offset 0).',
				422
			);
		}
	}//end validateMagicBytes()

	/**
	 * Validate a raw admin-supplied display name.
	 *
	 * Rejects an empty name and any raw path-traversal or NUL-byte content
	 * outright. The derived slug would neutralise `/`, `.` and control
	 * characters into hyphens, but a name that deliberately smuggles `../`
	 * or a NUL byte is rejected here rather than silently accepted and
	 * merely defanged downstream.
	 *
	 * @param string $name The raw display name.
	 *
	 * @return void
	 *
	 * @throws RuntimeException Code 422 when the name is empty or contains
	 *                          `/`, `..`, or a NUL byte.
	 *
	 * @spec openspec/specs/custom-fonts/spec.md
	 */
	public function validateDisplayName(string $name): void {
		if (trim($name) === '') {
			throw new RuntimeException('A font display name is required.', 422);
		}

		if (str_contains($name, '/') === true
			|| str_contains($name, '..') === true
			|| str_contains($name, "\0") === true
		) {
			throw new RuntimeException('The font display name may not contain "/", ".." or a NUL byte.', 422);
		}
	}//end validateDisplayName()

	/**
	 * Validate a derived slug against the storage-safe charset and length.
	 *
	 * @param string $slug The slug derived from the display name.
	 *
	 * @return void
	 *
	 * @throws RuntimeException Code 422 when the slug is empty, exceeds
	 *                          64 characters, or contains anything outside `[a-z0-9-]`.
	 *
	 * @spec openspec/specs/custom-fonts/spec.md
	 */
	public function validateSlug(string $slug): void {
		if ($slug === '') {
			throw new RuntimeException('A font name must contain at least one letter or digit.', 422);
		}

		if (strlen($slug) > 64 || preg_match('/^[a-z0-9-]+$/', $slug) !== 1) {
			throw new RuntimeException('The derived font identifier is invalid.', 422);
		}
	}//end validateSlug()

	/**
	 * Validate the assigned font role.
	 *
	 * @param string $role The requested role.
	 *
	 * @return void
	 *
	 * @throws RuntimeException Code 422 when the role is neither `body` nor
	 *                          `heading`.
	 *
	 * @spec openspec/specs/custom-fonts/spec.md
	 */
	public function validateRole(string $role): void {
		if (in_array($role, self::ROLES, true) === false) {
			throw new RuntimeException('Font role must be "body" or "heading".', 422);
		}
	}//end validateRole()

	/**
	 * Validate the per-instance font cap.
	 *
	 * @param int $currentCount The number of fonts currently stored.
	 *
	 * @return void
	 *
	 * @throws RuntimeException Code 409 when the cap is already reached.
	 *
	 * @spec openspec/specs/custom-fonts/spec.md
	 */
	public function validateCap(int $currentCount): void {
		if ($currentCount >= self::MAX_FONTS) {
			throw new RuntimeException('The maximum of 20 custom fonts has been reached. Delete one first.', 409);
		}
	}//end validateCap()
}//end class
