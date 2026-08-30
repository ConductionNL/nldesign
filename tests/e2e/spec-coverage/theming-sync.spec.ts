/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @e2e openspec/specs/theming-sync/spec.md
 *
 * @e2e exclude openspec/specs/theming-sync/spec.md
 * Backend/API theming-sync spec — scenarios cover PHP service logic,
 * validation methods, IConfig/ImageManager API calls, and route config;
 * the frontend dialog surface is covered by theming-sync-dialog tests.
 *
 * All scenarios are excluded at the spec level.
 */

// @e2e exclude openspec/specs/theming-sync/spec.md#token-set-with-full-theming-metadata
// token-sets.json content — not DOM-testable.

// @e2e exclude openspec/specs/theming-sync/spec.md#token-set-with-logo-and-background-theming
// token-sets.json content + filesystem — not DOM-testable.

// @e2e exclude openspec/specs/theming-sync/spec.md#token-set-without-theming-metadata
// JSON manifest assertion — not DOM-testable.

// @e2e exclude openspec/specs/theming-sync/spec.md#valid-hex-color-accepted
// API validation — not DOM-testable.

// @e2e exclude openspec/specs/theming-sync/spec.md#invalid-hex-color-rejected
// API validation — not DOM-testable.

// @e2e exclude openspec/specs/theming-sync/spec.md#path-traversal-rejected
// Security validation — not DOM-testable.

// @e2e exclude openspec/specs/theming-sync/spec.md#colors-updated-via-theming-app
// NC ThemingDefaults mutation — not DOM-testable without mutating shared env theming.

// @e2e exclude openspec/specs/theming-sync/spec.md#logo-uploaded-via-image-manager
// ImageManager mutation — not DOM-testable.

// @e2e exclude openspec/specs/theming-sync/spec.md#background-image-uploaded-via-image-manager
// ImageManager mutation — not DOM-testable.

// @e2e exclude openspec/specs/theming-sync/spec.md#sync-count-incremented
// IConfig counter — not DOM-testable.

// @e2e exclude openspec/specs/theming-sync/spec.md#invalid-token-set-rejected
// API validation — not DOM-testable.

// @e2e exclude openspec/specs/theming-sync/spec.md#valid-hex-color-pattern
// Regex validation — not DOM-testable.

// @e2e exclude openspec/specs/theming-sync/spec.md#invalid-hex-color-pattern
// Regex validation — not DOM-testable.

// @e2e exclude openspec/specs/theming-sync/spec.md#path-traversal-detection
// Security validation — not DOM-testable.

// @e2e exclude openspec/specs/theming-sync/spec.md#file-not-found-detection
// Filesystem validation — not DOM-testable.

// @e2e exclude openspec/specs/theming-sync/spec.md#theming-app-not-available
// Service availability check — not DOM-testable.

// @e2e exclude openspec/specs/theming-sync/spec.md#endpoint-authorization
// Admin-only annotation — not DOM-testable.

// @e2e exclude openspec/specs/theming-sync/spec.md#route-registration
// appinfo/routes.php — not DOM-testable.

// @e2e exclude openspec/specs/theming-sync/spec.md#sync-applied-via-settings-endpoint
// End-to-end sync — mutates NC core theming; covered by theming-sync-dialog tests.

// No runnable tests in this spec — all scenarios are PHP service / API assertions.
// Frontend dialog UI is covered by theming-sync-dialog spec-coverage tests.
export {}
