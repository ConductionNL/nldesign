<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Application\Profile;

use OCA\NLDesign\Application\Profile\ProfileCssCompiler;
use PHPUnit\Framework\TestCase;

class ProfileCssCompilerTest extends TestCase
{
    public function testCompilesOnlyTheBoundedCoreProjection(): void
    {
        $compiler = new ProfileCssCompiler();
        $css      = $compiler->compile(profile: $this->profile());

        self::assertSame($css, $compiler->compile(profile: $this->profile()));
        self::assertStringContainsString(':root {', $css);
        self::assertStringContainsString('[data-theme-dark] {', $css);
        self::assertStringContainsString('@media (prefers-color-scheme: dark) {', $css);
        self::assertStringContainsString('--nldesign-color-primary: #183a37;', $css);
        self::assertStringContainsString('--nldesign-font-family: "Fira Sans",', $css);
        self::assertStringNotContainsString('@import', $css);
        self::assertStringNotContainsString('url(', $css);
        self::assertStringNotContainsString('<script', $css);
    }

    public function testOmitsDarkOverridesWhenNoDarkModeWasDeclared(): void
    {
        $profile = $this->profile();
        unset($profile['tokens']['dark']);

        $css = (new ProfileCssCompiler())->compile(profile: $profile);

        self::assertStringContainsString(':root {', $css);
        self::assertStringNotContainsString('[data-theme-dark]', $css);
        self::assertStringNotContainsString('prefers-color-scheme', $css);
    }

    public function testFailsClosedForAnUnsupportedFontProjection(): void
    {
        $profile = $this->profile();
        $profile['tokens']['font_stack'] = 'remote-font';

        self::assertSame('', (new ProfileCssCompiler())->compile(profile: $profile));
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(): array
    {
        return [
            'id'      => 'voorbeeld-profiel',
            'version' => '1.0.0',
            'tokens'  => [
                'font_stack' => 'fira-sans',
                'light'      => [
                    'primary'       => '#183a37',
                    'primary_text'  => '#faf7f0',
                    'primary_hover' => '#3f5a57',
                ],
                'dark'       => [
                    'primary'       => '#efd6ac',
                    'primary_text'  => '#04151f',
                    'primary_hover' => '#e7ece8',
                ],
            ],
        ];
    }
}
