<?php

declare(strict_types=1);

/**
 * Minimal stylesheet collector for OCP Util listener tests.
 */
class OC_Util
{
    /** @var array<int, array{string, string|null, bool}> */
    public static array $styles = [];

    /** @var array<int, array{string, array<string, string>, string|null}> */
    public static array $headers = [];

    public static function addStyle(
        string $application,
        ?string $file=null,
        bool $prepend=false
    ): void {
        self::$styles[] = [$application, $file, $prepend];
    }

    /**
     * @param array<string, string> $attributes
     */
    public static function addHeader(
        string $tag,
        array $attributes,
        ?string $text=null
    ): void {
        self::$headers[] = [$tag, $attributes, $text];
    }
}
