<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Application\Branding;

use OCA\NLDesign\Application\Branding\ManualThemingPlanBuilder;
use PHPUnit\Framework\TestCase;

class ManualThemingPlanBuilderTest extends TestCase
{
    public function testBuildsOnlyAllowlistedValidSteps(): void
    {
        $plan = (new ManualThemingPlanBuilder())->build(
            theming: [
                'primary_color' => '#154273',
                'logo'          => 'img/logos/rijkshuisstijl.svg',
                'unknown'       => 'value',
                'background'    => '../../secret',
            ]
        );

        self::assertFalse($plan['appliesAutomatically']);
        self::assertSame(['primary_color', 'logo'], array_column($plan['steps'], 'field'));
    }

    public function testMalformedInputProducesEmptyManualPlan(): void
    {
        $plan = (new ManualThemingPlanBuilder())->build(theming: 'not-an-array');

        self::assertSame('manual', $plan['mode']);
        self::assertSame([], $plan['steps']);
    }
}
