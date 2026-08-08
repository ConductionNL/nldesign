<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Application\Branding;

use OCA\NLDesign\Application\Branding\RuntimeStylesheetPlan;
use PHPUnit\Framework\TestCase;

class RuntimeStylesheetPlanTest extends TestCase
{
    public function testDefinesCompletePrecedenceOrder(): void
    {
        self::assertSame(
            [
                'fonts',
                'tokens/utrecht',
                'theme',
            ],
            (new RuntimeStylesheetPlan())->build(profileId: 'utrecht')
        );
    }
}
