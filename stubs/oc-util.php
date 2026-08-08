<?php

declare(strict_types=1);

/**
 * Minimal stylesheet collector for OCP Util listener tests.
 */
class OC_Util
{
    /** @var array<int, array{string, string|null, bool}> */
    public static array $styles = [];

    public static function addStyle(
        string $application,
        ?string $file=null,
        bool $prepend=false
    ): void {
        self::$styles[] = [$application, $file, $prepend];
    }
}
