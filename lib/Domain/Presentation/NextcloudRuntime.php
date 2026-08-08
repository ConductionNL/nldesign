<?php

/**
 * Nextcloud runtime identity.
 *
 * @category Domain
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Domain\Presentation;

use InvalidArgumentException;

/**
 * Immutable public Nextcloud version tuple used by presentation adapters.
 */
final class NextcloudRuntime
{
    /**
     * Constructor.
     *
     * @param int $major Major release.
     * @param int $minor Minor release.
     * @param int $patch Patch release.
     */
    public function __construct(
        private int $major,
        private int $minor,
        private int $patch
    ) {
        if ($major < 1 || $minor < 0 || $patch < 0) {
            throw new InvalidArgumentException('Invalid Nextcloud runtime version.');
        }
    }//end __construct()

    /**
     * Get the major release.
     *
     * @return int Major release.
     */
    public function getMajor(): int
    {
        return $this->major;
    }//end getMajor()

    /**
     * Format the normalized numeric release.
     *
     * @return string Version string.
     */
    public function toVersionString(): string
    {
        return $this->major.'.'.$this->minor.'.'.$this->patch;
    }//end toVersionString()
}//end class
