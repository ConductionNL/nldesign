<?php

/**
 * NL Design runtime stylesheet plan.
 *
 * @category Application
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Application\Presentation;

/**
 * Own the explicit, capability-resolved stylesheet precedence contract.
 */
final class RuntimeStylesheetPlan
{
    /**
     * Constructor.
     *
     * @param CoreRuntimeCompatibility $compatibility Runtime compatibility.
     */
    public function __construct(private CoreRuntimeCompatibility $compatibility)
    {
    }//end __construct()

    /**
     * Resolve the core profile stack around its packaged or generated profile.
     *
     * @return array{
     *     supported: bool,
     *     runtime_version: string,
     *     runtime_major: int|null,
     *     adapter_id: string|null,
     *     before_profile: array<int, string>,
     *     after_profile: array<int, string>,
     *     reason: string|null
     * }
     */
    public function build(): array
    {
        $compatibility = $this->compatibility->inspect();
        if ($compatibility['supported'] === false
            || $compatibility['adapter_id'] === null
            || $compatibility['stylesheet'] === null
        ) {
            return [
                'supported'       => false,
                'runtime_version' => $compatibility['runtime_version'],
                'runtime_major'   => $compatibility['runtime_major'],
                'adapter_id'      => null,
                'before_profile'  => [],
                'after_profile'   => [],
                'reason'          => $compatibility['reason'],
            ];
        }

        return [
            'supported'       => true,
            'runtime_version' => $compatibility['runtime_version'],
            'runtime_major'   => $compatibility['runtime_major'],
            'adapter_id'      => $compatibility['adapter_id'],
            'before_profile'  => ['fonts'],
            'after_profile'   => [$compatibility['stylesheet']],
            'reason'          => null,
        ];
    }//end build()
}//end class
