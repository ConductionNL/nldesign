<?php

/**
 * NL Design Group Theming Validation Exception.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category  Exception
 * @package   OCA\NLDesign
 * @author    Conduction <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://codeberg.org/Conduction/nldesign
 *
 * @spec openspec/specs/per-group-theming/spec.md
 */

declare(strict_types=1);

namespace OCA\NLDesign\Service\Exception;

/**
 * Thrown when a group-theming mapping entry fails save-time validation: an
 * unknown group id, an unavailable token-set id, or a duplicate group across
 * entries. Carries the offending entry so the controller can surface it in a
 * structured HTTP 422 response naming the entry and the reason, per the
 * "Unknown group or set is rejected without partial writes" and "Duplicate
 * group entries are rejected" scenarios.
 *
 * @spec openspec/specs/per-group-theming/spec.md
 */
class GroupThemingValidationException extends \InvalidArgumentException {

	/**
	 * The offending mapping entry, as submitted (may be malformed).
	 *
	 * @var mixed
	 */
	private mixed $entry;

	/**
	 * Constructor.
	 *
	 * @param mixed $entry The offending mapping entry.
	 * @param string $reason The human-readable validation failure reason.
	 *
	 * @spec openspec/specs/per-group-theming/spec.md
	 */
	public function __construct(mixed $entry, string $reason) {
		parent::__construct(message: $reason);
		$this->entry = $entry;
	}//end __construct()

	/**
	 * Get the offending mapping entry.
	 *
	 * @return mixed The entry, exactly as submitted.
	 *
	 * @spec openspec/specs/per-group-theming/spec.md
	 */
	public function getEntry(): mixed {
		return $this->entry;
	}//end getEntry()

	/**
	 * Get the validation failure reason.
	 *
	 * @return string The reason.
	 *
	 * @spec openspec/specs/per-group-theming/spec.md
	 */
	public function getReason(): string {
		return $this->getMessage();
	}//end getReason()
}//end class
