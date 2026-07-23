# Prometheus Metrics — Audit Entries Counter Delta

**Spec refs**: `prometheus-metrics` (canonical), `theming-audit` (new, this change), hydra
ADR-006 (metrics — admin auth)
**Standards**: Prometheus text exposition format (counter semantics: monotonic)

## ADDED Requirements

### Requirement: Audit Entries Counter Metric

The metrics endpoint MUST expose `nldesign_audit_entries_total` as a Prometheus counter of all
theming audit entries ever written. The value MUST be sourced from the monotonic IConfig app
value `audit_entries_total`, which `ThemingAuditService::log()` increments on every successful
append (same storage pattern as `theming_syncs_total`) — NOT from counting lines in the audit
file, so log rotation can never make the counter decrease. The metric MUST be emitted with HELP
and TYPE lines and default to `0` when the app value is unset, and it inherits the endpoint's
existing admin-auth posture and error-resilience requirements unchanged.

#### Scenario: Counter format

- GIVEN 12 audit entries have been written since installation
- WHEN an authenticated admin scrapes the metrics endpoint
- THEN the output MUST include:
  - `# HELP nldesign_audit_entries_total Total theming audit entries written`
  - `# TYPE nldesign_audit_entries_total counter`
  - `nldesign_audit_entries_total 12`

#### Scenario: Counter survives log rotation

- GIVEN the audit file has rotated and the current `audit.jsonl` holds fewer lines than the
  lifetime total
- WHEN the metric is collected
- THEN the value MUST equal the lifetime total from the `audit_entries_total` app value and MUST
  NOT decrease

#### Scenario: Counter defaults to zero

- GIVEN a fresh installation where no audit entry has been written
- WHEN the metric is collected from `IConfig::getAppValue('nldesign', 'audit_entries_total', '0')`
- THEN `nldesign_audit_entries_total 0` MUST be emitted (cast to int from string storage)
