/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @e2e openspec/specs/nl-design/spec.md
 *
 * @e2e exclude openspec/specs/nl-design/spec.md
 * CSS-variable / delta spec — scenarios describe CSS variable usage rules and
 * component-token prefix conventions; no distinct testable UI surface beyond
 * what admin-settings tests cover.
 *
 * All scenarios are excluded at the spec level.
 */

// @e2e exclude openspec/specs/nl-design/spec.md#custom-municipality-theme
// Requires selecting a specific token set — mutates IConfig.

// @e2e exclude openspec/specs/nl-design/spec.md#incomplete-token-set-renders-correctly
// Requires selecting a specific token set — mutates IConfig.

// @e2e exclude openspec/specs/nl-design/spec.md#component-uses-color
// CSS variable usage rule — not DOM-testable.

// @e2e exclude openspec/specs/nl-design/spec.md#component-uses-component-level-token
// CSS variable usage rule — not DOM-testable.

// No runnable tests in this spec — all scenarios are CSS convention assertions.
export {}
