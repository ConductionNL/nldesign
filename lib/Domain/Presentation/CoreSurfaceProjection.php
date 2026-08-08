<?php

/**
 * Nextcloud core surface projection descriptor.
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
 * Immutable identity and app-relative stylesheet for one verified adapter.
 */
final class CoreSurfaceProjection
{
    private const ID_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    private const STYLESHEET_PATTERN = '/^[a-z0-9]+(?:[\/-][a-z0-9]+)*$/';

    /**
     * Constructor.
     *
     * @param string $adapterId  Adapter contract identity.
     * @param string $stylesheet App-relative stylesheet without extension.
     */
    public function __construct(
        private string $adapterId,
        private string $stylesheet
    ) {
        if (preg_match(self::ID_PATTERN, $adapterId) !== 1
            || preg_match(self::STYLESHEET_PATTERN, $stylesheet) !== 1
        ) {
            throw new InvalidArgumentException('Invalid core surface projection.');
        }
    }//end __construct()

    /**
     * Get the immutable adapter identity.
     *
     * @return string Adapter identity.
     */
    public function getAdapterId(): string
    {
        return $this->adapterId;
    }//end getAdapterId()

    /**
     * Get the app-relative stylesheet.
     *
     * @return string Stylesheet without extension.
     */
    public function getStylesheet(): string
    {
        return $this->stylesheet;
    }//end getStylesheet()
}//end class
