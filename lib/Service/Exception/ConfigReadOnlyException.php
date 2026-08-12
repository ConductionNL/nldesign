<?php

/**
 * NL Design Config Read-Only Exception.
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
 * Thrown when the `mail_template_class` system config cannot be written
 * because config.php is read-only — either the `config_is_read_only` flag
 * is set, or the write failed at the filesystem level (surfaced by the
 * server as an `\OCP\HintException`). Carries the exact `occ` commands so
 * the caller (the settings controller) can hand them to the admin verbatim.
 *
 * @spec openspec/specs/email-theming/spec.md
 */
class ConfigReadOnlyException extends \RuntimeException {

	/**
	 * The occ command to enable the branded template manually.
	 *
	 * @var string
	 */
	private string $occEnableCommand;

	/**
	 * The occ command to disable the branded template manually.
	 *
	 * @var string
	 */
	private string $occDisableCommand;

	/**
	 * Constructor.
	 *
	 * @param string $occEnableCommand The occ command to enable manually.
	 * @param string $occDisableCommand The occ command to disable manually.
	 * @param \Throwable|null $previous The previous exception, if any.
	 *
	 * @spec openspec/specs/email-theming/spec.md
	 */
	public function __construct(string $occEnableCommand, string $occDisableCommand, ?\Throwable $previous = null) {
		parent::__construct(message: 'config.php is read-only; mail_template_class cannot be written.', code: 0, previous: $previous);
		$this->occEnableCommand = $occEnableCommand;
		$this->occDisableCommand = $occDisableCommand;
	}//end __construct()

	/**
	 * Get the occ command to enable the branded template manually.
	 *
	 * @return string The occ command.
	 *
	 * @spec openspec/specs/email-theming/spec.md
	 */
	public function getOccEnableCommand(): string {
		return $this->occEnableCommand;
	}//end getOccEnableCommand()

	/**
	 * Get the occ command to disable the branded template manually.
	 *
	 * @return string The occ command.
	 *
	 * @spec openspec/specs/email-theming/spec.md
	 */
	public function getOccDisableCommand(): string {
		return $this->occDisableCommand;
	}//end getOccDisableCommand()
}//end class
