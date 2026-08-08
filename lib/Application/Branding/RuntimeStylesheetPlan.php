<?php

/**
 * NL Design Runtime Stylesheet Plan.
 *
 * @category Application
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/ConductionNL/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Application\Branding;

/**
 * Own the explicit stylesheet precedence contract.
 */
final class RuntimeStylesheetPlan
{
    /**
     * Build the core profile stack in load order.
     *
     * @param string $profileId Valid compiled profile id.
     *
     * @return array<int, string> App-relative stylesheet names.
     */
    public function build(string $profileId): array
    {
        return [
            'fonts',
            'tokens/'.$profileId,
            'theme',
        ];
    }//end build()
}//end class
