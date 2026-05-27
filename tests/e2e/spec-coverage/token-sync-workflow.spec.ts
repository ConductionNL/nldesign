/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @e2e openspec/specs/token-sync-workflow/spec.md
 *
 * @e2e exclude openspec/specs/token-sync-workflow/spec.md
 * GitHub Actions / CI workflow spec — all scenarios describe scheduled
 * workflow execution, PR creation, and token generation scripts; no
 * Nextcloud admin UI surface.
 *
 * All scenarios are excluded at the spec level.
 */

// @e2e exclude openspec/specs/token-sync-workflow/spec.md#scheduled-execution
// GitHub Actions cron — not DOM-testable.

// @e2e exclude openspec/specs/token-sync-workflow/spec.md#manual-trigger
// GitHub Actions workflow_dispatch — not DOM-testable.

// @e2e exclude openspec/specs/token-sync-workflow/spec.md#no-upstream-changes
// GitHub Actions change detection — not DOM-testable.

// @e2e exclude openspec/specs/token-sync-workflow/spec.md#upstream-changes-detected
// GitHub Actions PR creation — not DOM-testable.

// @e2e exclude openspec/specs/token-sync-workflow/spec.md#new-organization-added-upstream
// GitHub Actions + git — not DOM-testable.

// @e2e exclude openspec/specs/token-sync-workflow/spec.md#pr-creation
// GitHub Actions PR metadata — not DOM-testable.

// @e2e exclude openspec/specs/token-sync-workflow/spec.md#existing-open-pr
// GitHub Actions PR update — not DOM-testable.

// @e2e exclude openspec/specs/token-sync-workflow/spec.md#script-reads-upstream-tokens
// Node.js script execution — not DOM-testable.

// @e2e exclude openspec/specs/token-sync-workflow/spec.md#script-handles-malformed-input
// Script error handling — not DOM-testable.

// @e2e exclude openspec/specs/token-sync-workflow/spec.md#script-updates-manifest
// Script output — not DOM-testable.

// @e2e exclude openspec/specs/token-sync-workflow/spec.md#developer-reads-readme
// README documentation — not DOM-testable.

// No runnable tests in this spec — all scenarios are CI workflow / script assertions.
export {}
