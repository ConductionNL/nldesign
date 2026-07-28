<?php

/**
 * NL Design Freeform Custom CSS Validator.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category  Service
 * @package   OCA\NLDesign
 * @author    Conduction <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://codeberg.org/Conduction/nldesign
 *
 * @spec openspec/specs/custom-css-freeform/spec.md
 */

declare(strict_types=1);

namespace OCA\NLDesign\Service;

/**
 * Validates admin-authored freeform CSS before it is persisted and served.
 *
 * Unlike CustomOverridesService — which only ever emits a `:root` block of
 * registry-validated `--color-*` tokens — this layer accepts arbitrary rules,
 * so the submitted text is genuinely untrusted input even though only an
 * administrator can reach the endpoint. Nextcloud's theming delegation means
 * a DELEGATED admin (a lower trust tier than a full admin) can be granted the
 * theming section, so "an admin typed it" is not on its own a sufficient
 * control.
 *
 * The validator is FAIL-CLOSED and ALL-OR-NOTHING: any single violation
 * rejects the entire submission and returns every reason found. Silently
 * stripping the offending fragment is deliberately not done — it would mutate
 * CSS the administrator never reviewed and could change the meaning of the
 * rules that survive.
 *
 * It is a standalone service (no filesystem, no HTTP, no Nextcloud services)
 * precisely so every rule below is unit-testable in isolation.
 *
 * @spec openspec/specs/custom-css-freeform/spec.md
 */
class CustomCssValidator
{

    /**
     * Maximum accepted payload size in bytes.
     *
     * 64 KiB is far beyond any hand-authored theme tweak while keeping the
     * regex scans below bounded and audit-log entries small.
     *
     * @var integer
     */
    public const MAX_BYTES = 65536;

    /**
     * Variables whose values Nextcloud derives for dark mode.
     *
     * REQ-CSS-007: overriding any of these breaks Nextcloud's own automatic
     * dark-mode derivation. The bridge layer is already forbidden from setting
     * them; freeform CSS must not become a back door to the same breakage.
     * Checked across the WHOLE document, not just a `:root` block, since a
     * freeform author can set them under any selector.
     *
     * @var string[]
     */
    public const RESERVED_VARIABLES = [
        '--color-main-background',
        '--color-main-background-rgb',
        '--color-main-background-translucent',
        '--color-main-background-blur',
        '--color-background-plain',
        '--color-background-plain-text',
        '--background-invert-if-dark',
        '--background-invert-if-bright',
    ];

    /**
     * Validate a freeform CSS document.
     *
     * @param string $css The admin-submitted CSS.
     *
     * @return string[] Human-readable reasons the submission was rejected.
     *                  An EMPTY array means the CSS is acceptable.
     *
     * @spec openspec/specs/custom-css-freeform/spec.md
     */
    public function validate(string $css): array
    {
        $errors = [];

        if (strlen($css) > self::MAX_BYTES) {
            $errors[] = sprintf(
                'Custom CSS is %d bytes, which exceeds the %d byte limit.',
                strlen($css),
                self::MAX_BYTES
            );
        }

        // `@import` fetches a remote stylesheet on every page load — both an
        // exfiltration channel (the request itself carries the referrer) and a
        // content-injection vector. `@charset` can change how the remaining
        // bytes are decoded.
        if (preg_match('/@(?:import|charset)\b/i', $css) === 1) {
            $errors[] = '@import and @charset are not allowed.';
        }

        // The url() function is the classic CSS exfiltration channel: with
        // attribute selectors it can leak page content to a third party via
        // background-image, @font-face src, cursor or list-style-image.
        // Relative/same-origin references and data: URIs cannot reach another
        // origin, so those remain available for legitimate theming.
        if (preg_match('#url\(\s*[\'"]?\s*(?:[a-z][a-z0-9+.-]*:|//)#i', $css, $m) === 1) {
            if (preg_match('#url\(\s*[\'"]?\s*data:#i', $css) !== 1
                || preg_match('#url\(\s*[\'"]?\s*(?:https?:|//)#i', $css) === 1
            ) {
                $errors[] = 'External url() references are not allowed; use a relative path or a data: URI.';
            }
        }

        // Legacy script-execution vectors. Modern engines ignore them, but
        // they cost nothing to refuse and keep the rule set honest.
        if (preg_match('/(?:expression\s*\(|behavior\s*:|-moz-binding\s*:)/i', $css) === 1) {
            $errors[] = 'expression(), behavior: and -moz-binding: are not allowed.';
        }

        // Defence in depth. The document is served as a linked stylesheet, not
        // inlined, so a breakout is not currently reachable — but refusing the
        // sequences means a future change to inline delivery cannot silently
        // turn this into an XSS sink.
        if (preg_match('#</\s*style|<\s*script#i', $css) === 1) {
            $errors[] = 'HTML tags are not allowed in custom CSS.';
        }

        foreach (self::RESERVED_VARIABLES as $reserved) {
            if (preg_match('/'.preg_quote($reserved, '/').'\s*:/', $css) === 1) {
                $errors[] = sprintf(
                    '%s is reserved for Nextcloud dark-mode derivation and cannot be set (REQ-CSS-007).',
                    $reserved
                );
            }
        }

        // The document is emitted verbatim as the last stylesheet. An
        // unbalanced brace would swallow — or prematurely close — the rules
        // around it, so the cascade must never receive one.
        if ($this->bracesAreBalanced(css: $css) === false) {
            $errors[] = 'Unbalanced braces: every { must have a matching }.';
        }

        return $errors;

    }//end validate()

    /**
     * Report whether every `{` has a matching `}`.
     *
     * Braces appearing inside strings or comments are ignored so a legitimate
     * `content: "{"` declaration is not mistaken for a structural brace.
     *
     * @param string $css The CSS to scan.
     *
     * @return boolean True when balanced.
     */
    private function bracesAreBalanced(string $css): bool
    {
        $depth     = 0;
        $length    = strlen($css);
        $inComment = false;
        $quote     = null;

        for ($i = 0; $i < $length; $i++) {
            $char = $css[$i];

            if ($inComment === true) {
                if ($char === '*' && ($i + 1) < $length && $css[($i + 1)] === '/') {
                    $inComment = false;
                    $i++;
                }

                continue;
            }

            if ($quote !== null) {
                if ($char === '\\') {
                    $i++;
                } else if ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === '/' && ($i + 1) < $length && $css[($i + 1)] === '*') {
                $inComment = true;
                $i++;
                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
                continue;
            }

            if ($char === '{') {
                $depth++;
            } else if ($char === '}') {
                $depth--;
                if ($depth < 0) {
                    return false;
                }
            }
        }//end for

        return ($depth === 0);

    }//end bracesAreBalanced()
}//end class
