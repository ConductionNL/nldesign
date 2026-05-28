/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @e2e openspec/specs/nextcloud-variable-mapping/spec.md
 *
 * @e2e exclude openspec/specs/nextcloud-variable-mapping/spec.md
 * CSS-variable mapping / documentation spec — all scenarios describe CSS file
 * content, variable mapping tables, and cascade ordering; no testable UI
 * surface in the admin settings page.
 *
 * All scenarios are excluded at the spec level.
 */

// @e2e exclude openspec/specs/nextcloud-variable-mapping/spec.md#all-nextcloud-variables-are-accounted-for
// CSS file content assertion — not DOM-testable.

// @e2e exclude openspec/specs/nextcloud-variable-mapping/spec.md#new-nextcloud-variable-is-added-upstream
// Documentation update process — not DOM-testable.

// @e2e exclude openspec/specs/nextcloud-variable-mapping/spec.md#mapped-variable
// CSS override file content — not DOM-testable.

// @e2e exclude openspec/specs/nextcloud-variable-mapping/spec.md#unmapped-variable
// CSS file content — not DOM-testable.

// @e2e exclude openspec/specs/nextcloud-variable-mapping/spec.md#intentionally-unoverridden-variable
// CSS file comment — not DOM-testable.

// @e2e exclude openspec/specs/nextcloud-variable-mapping/spec.md#token-has-no-organization-specific-override
// CSS cascade fallback — not DOM-testable.

// @e2e exclude openspec/specs/nextcloud-variable-mapping/spec.md#token-is-overridden-by-organization
// CSS variable resolution — requires selecting a specific token set.

// @e2e exclude openspec/specs/nextcloud-variable-mapping/spec.md#new-nldesign-token-is-added
// Development workflow — not DOM-testable.

// @e2e exclude openspec/specs/nextcloud-variable-mapping/spec.md#developer-looks-up-a-nextcloud-variable
// Documentation file content — not DOM-testable.

// @e2e exclude openspec/specs/nextcloud-variable-mapping/spec.md#unmapped-variable-in-documentation
// Documentation file content — not DOM-testable.

// @e2e exclude openspec/specs/nextcloud-variable-mapping/spec.md#css-files-load-in-correct-order
// CSS load order — not DOM-testable.

// No runnable tests in this spec — all scenarios are CSS/documentation assertions.
export {}
