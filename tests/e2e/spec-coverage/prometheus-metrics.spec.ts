/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @e2e openspec/specs/prometheus-metrics/spec.md
 *
 * @e2e exclude openspec/specs/prometheus-metrics/spec.md
 * API/backend metrics spec — all scenarios describe HTTP response format,
 * metric values, and controller dependency injection; no admin UI surface.
 *
 * All scenarios are excluded at the spec level.
 */

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#metrics-endpoint-returns-correct-content-type
// API response header assertion — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#metrics-endpoint-requires-an-authenticated-admin-session
// SecurityMiddleware admin-auth default + @NoCSRFRequired annotation — not DOM-testable
// (and must NOT add @NoCSRFRequired to test); covered by
// tests/Unit/Controller/MetricsControllerTest.php (attribute-absence assertions) and the
// deferred manual curl check in openspec/changes/metrics-endpoint-admin-auth/tasks.md#task-4.2.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#metrics-endpoint-returns-all-metric-families
// API response body format — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#route-registration
// appinfo/routes.php — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#info-gauge-with-version-labels
// Prometheus text format — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#info-gauge-format
// Prometheus text format — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#versions-read-from-correct-sources
// PHP service dependency — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#app-is-healthy
// Prometheus text format — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#up-gauge-always-1-when-endpoint-responds
// Prometheus metric value — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#up-gauge-format
// Prometheus text format — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#token-sets-counted-from-filesystem
// Prometheus metric value — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#token-set-metric-with-help-and-type
// Prometheus text format — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#token-set-count-error-handled-gracefully
// Error recovery path — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#active-token-set-reported
// Prometheus metric value — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#active-token-set-with-help-and-type
// Prometheus text format — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#default-token-set-reported-when-not-configured
// IConfig default value — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#active-token-set-in-error-recovery
// Error recovery path — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#custom-overrides-counted
// Prometheus metric value — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#no-custom-overrides
// Prometheus metric value — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#custom-overrides-with-help-and-type
// Prometheus text format — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#override-count-error-handled-gracefully
// Error recovery path — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#theming-syncs-counter-reported
// Prometheus metric value — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#no-theming-syncs-performed
// Prometheus metric value — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#theming-syncs-with-help-and-type
// Prometheus text format — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#syncs-counter-is-read-from-iconfig
// IConfig read logic — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#token-set-metrics-fail-other-metrics-succeed
// Error resilience — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#custom-overrides-fail-other-metrics-succeed
// Error resilience — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#multiple-failures-handled-independently
// Error resilience — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#health-check-returns-ok
// API JSON response — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#health-check-returns-degraded
// API JSON response — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#health-check-returns-error-on-failure
// Error path — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#health-endpoint-is-publicly-accessible-without-csrf
// @NoCSRFRequired annotation — API-layer, not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#route-registration-1
// appinfo/routes.php — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#help-line-format
// Prometheus text format — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#type-line-format
// Prometheus text format — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#label-values-properly-escaped
// Prometheus text format — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#dependencies-injected
// PHP constructor injection — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#no-direct-service-instantiation
// PHP DI pattern — not DOM-testable.

// @e2e exclude openspec/specs/prometheus-metrics/spec.md#health-controller-dependencies
// PHP DI — not DOM-testable.

// No runnable tests in this spec — all scenarios are API/backend assertions.
export {}
