<?php

/**
 * NL Design Foreign Mail Template Class Exception.
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
 * Thrown when enabling the branded template would clobber a `mail_template_class`
 * system value that names some OTHER class (e.g. an enterprise template).
 * nldesign must never overwrite it — the admin panel surfaces the configured
 * class name instead and leaves the toggle disabled.
 *
 * @spec openspec/specs/email-theming/spec.md
 */
class ForeignMailTemplateClassException extends \RuntimeException {

	/**
	 * The foreign class currently configured.
	 *
	 * @var string
	 */
	private string $foreignClass;

	/**
	 * Constructor.
	 *
	 * @param string $foreignClass The currently configured foreign class.
	 *
	 * @spec openspec/specs/email-theming/spec.md
	 */
	public function __construct(string $foreignClass) {
		parent::__construct(message: 'mail_template_class is configured to a foreign class: ' . $foreignClass);
		$this->foreignClass = $foreignClass;
	}//end __construct()

	/**
	 * Get the foreign class currently configured.
	 *
	 * @return string The foreign class name.
	 *
	 * @spec openspec/specs/email-theming/spec.md
	 */
	public function getForeignClass(): string {
		return $this->foreignClass;
	}//end getForeignClass()
}//end class
