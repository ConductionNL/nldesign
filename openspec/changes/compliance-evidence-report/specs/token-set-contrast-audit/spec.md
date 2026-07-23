# Token-Set Contrast Audit — Shared-Contract Delta

**Spec refs**: `token-set-contrast-audit`, `compliance-evidence` (new, this change)
**Standards**: WCAG 2.2 AA SC 1.4.3 / SC 1.4.11

## MODIFIED Requirements

### Requirement: Reproducible Contrast Report

The audit MUST emit a generated report at `docs/reference/contrast-report.md` listing, for every
audited set, its id, the computed primary/text ratio, the computed primary/background ratio, the
applicable thresholds, and the verdict. The report MUST be deterministic: regenerating it from
unchanged token files MUST produce byte-identical output. The report is the machine-checked
source of truth that replaces the shipped-set verdict of the hand-written
`docs/reference/token-audit.md`.

The shipped-set report and the active-configuration compliance evidence report (spec
`compliance-evidence`) MUST share one contrast contract: both MUST compute ratios through
`ContrastService::ratio()`/`parseColor()` (no second contrast implementation anywhere in the
app), and both MUST apply the `unevaluated`-never-passes rule. The shipped-set report audits
each shipped set in isolation over its fixed pairs; the per-instance ACTIVE configuration
(active set + custom overrides, full pair matrix) is the `compliance-evidence` report's scope —
this requirement does not duplicate it.

#### Scenario: Report covers every audited set deterministically

@e2e exclude generated-artifact invariant — PHPUnit compares regenerated output
- GIVEN unchanged token files
- WHEN the report is generated twice
- THEN both outputs MUST be byte-identical
- AND the report MUST contain one row for every token set with a non-`none` design system

#### Scenario: Shipped audit and compliance report agree on shared math

@e2e exclude cross-service invariant — PHPUnit computes one pair through both paths
- GIVEN a token set whose primary/primary-text pair is evaluated by `ShippedTokenSetAuditService`
- AND the same effective values evaluated by `ComplianceReportService` (no overrides configured)
- WHEN both ratios are computed
- THEN the two ratios MUST be identical
- AND both services MUST classify a non-literal value as `unevaluated`
