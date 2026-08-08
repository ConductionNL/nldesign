<?php

/**
 * Installed profile CSS compiler.
 *
 * @category Application
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Application\Profile;

/**
 * Compile the closed semantic profile shape to selector-bounded CSS.
 */
final class ProfileCssCompiler
{
    private const FONT_STACKS = [
        'fira-sans' => [
            '"Fira Sans"',
            '-apple-system',
            'BlinkMacSystemFont',
            '"Segoe UI"',
            'Roboto',
            'Oxygen-Sans',
            'Cantarell',
            'Ubuntu',
            '"Helvetica Neue"',
            'Arial',
            'sans-serif',
        ],
        'system'    => [
            '-apple-system',
            'BlinkMacSystemFont',
            '"Segoe UI"',
            'Roboto',
            'Oxygen-Sans',
            'Cantarell',
            'Ubuntu',
            '"Helvetica Neue"',
            'Arial',
            '"Fira Sans"',
            'sans-serif',
        ],
    ];

    /**
     * Compile one previously validated descriptor.
     *
     * @param array<string, mixed> $profile Canonical profile descriptor.
     *
     * @return string Deterministic CSS projection.
     */
    public function compile(array $profile): string
    {
        $tokens = $profile['tokens'] ?? null;
        if (is_array($tokens) === false || is_array($tokens['light'] ?? null) === false) {
            return '';
        }

        $light     = $tokens['light'];
        $fontStack = (string) ($tokens['font_stack'] ?? '');
        if (isset(self::FONT_STACKS[$fontStack]) === false) {
            return '';
        }

        $lines = [
            '/**',
            ' * Generated NL Design for Nextcloud profile projection.',
            ' * Profile: '.(string) ($profile['id'] ?? '').' '.(string) ($profile['version'] ?? ''),
            ' */',
            '',
            ':root {',
            "\t--nldesign-color-primary: ".(string) ($light['primary'] ?? '').';',
            "\t--nldesign-color-primary-text: ".(string) ($light['primary_text'] ?? '').';',
            "\t--nldesign-color-primary-hover: ".(string) ($light['primary_hover'] ?? '').';',
            "\t--nldesign-font-family: ".implode(separator: ', ', array: self::FONT_STACKS[$fontStack]).';',
            '}',
        ];

        if (is_array($tokens['dark'] ?? null) === true) {
            $dark    = $tokens['dark'];
            $lines[] = '';
            $lines[] = '[data-theme-dark] {';
            $lines   = $this->appendMode(lines: $lines, mode: $dark, indentation: "\t");
            $lines[] = '}';
            $lines[] = '';
            $lines[] = '@media (prefers-color-scheme: dark) {';
            $lines[] = "\t[data-theme-default] {";
            $lines   = $this->appendMode(lines: $lines, mode: $dark, indentation: "\t\t");
            $lines[] = "\t}";
            $lines[] = '}';
        }

        return implode(separator: "\n", array: $lines)."\n";
    }//end compile()

    /**
     * Build browser-safe metadata for previews and manual Theming guidance.
     *
     * @param array<string, mixed> $profile Canonical profile descriptor.
     *
     * @return array<string, mixed> Projection preview metadata.
     */
    public function buildPreview(array $profile): array
    {
        $tokens = $profile['tokens'] ?? null;
        if (is_array($tokens) === false) {
            return [];
        }

        $preview = [
            'font_stack' => $tokens['font_stack'],
            'light'      => $tokens['light'],
        ];
        if (isset($tokens['dark']) === true) {
            $preview['dark'] = $tokens['dark'];
        }

        return $preview;
    }//end buildPreview()

    /**
     * Append one complete mode to generated CSS lines.
     *
     * @param array<int, string>   $lines       Existing CSS lines.
     * @param array<string, mixed> $mode        Validated mode colours.
     * @param string               $indentation CSS indentation.
     *
     * @return array<int, string> Extended CSS lines.
     */
    private function appendMode(array $lines, array $mode, string $indentation): array
    {
        $lines[] = $indentation.'--nldesign-color-primary: '.(string) ($mode['primary'] ?? '').';';
        $lines[] = $indentation.'--nldesign-color-primary-text: '.(string) ($mode['primary_text'] ?? '').';';
        $lines[] = $indentation.'--nldesign-color-primary-hover: '.(string) ($mode['primary_hover'] ?? '').';';

        return $lines;
    }//end appendMode()
}//end class
