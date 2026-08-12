<?php

/**
 * NL Design Footer Validation Exception.
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
 * @spec openspec/specs/email-theming/spec.md
 */

declare(strict_types=1);

namespace OCA\NLDesign\Service\Exception;

/**
 * Thrown when a footer config value fails validation at write time — e.g. a
 * URL that is not `http(s)://` (rejecting `javascript:` and other schemes)
 * or exceeds the maximum stored length. The controller maps this to HTTP 422.
 *
 * @spec openspec/specs/email-theming/spec.md
 */
class FooterValidationException extends \InvalidArgumentException {

	/**
	 * The footer field that failed validation.
	 *
	 * @var string
	 */
	private string $field;

	/**
	 * Constructor.
	 *
	 * @param string $field The footer field that failed validation.
	 * @param string $message The validation error message.
	 *
	 * @spec openspec/specs/email-theming/spec.md
	 */
	public function __construct(string $field, string $message) {
		parent::__construct(message: $message);
		$this->field = $field;
	}//end __construct()

	/**
	 * Get the footer field that failed validation.
	 *
	 * @return string The field name.
	 *
	 * @spec openspec/specs/email-theming/spec.md
	 */
	public function getField(): string {
		return $this->field;
	}//end getField()
}//end class
