---
sidebar_position: 14
---

# Shipped Token-Set Contrast Audit

Every shipped token set is verified for WCAG contrast by the same `ContrastService`
the app uses for custom uploads. This makes the WCAG-AA claims in
[GOVERNMENT-FEATURES](../GOVERNMENT-FEATURES.md) (F-09, A-01..A-05) true by
construction rather than by prose.

## What is checked

For every token set in `token-sets.json` whose design system reads `--nldesign-*`
tokens (`design_system` is not `none`), two fixed pairs are computed:

- **primary/text** — `--nldesign-color-primary` vs `--nldesign-color-primary-text`,
  against the **4.5:1** AA text threshold (7:1 for high-contrast sets).
- **primary/bg** — `--nldesign-color-primary` vs the set background
  (`--nldesign-color-background`, or `theming.background_color` when the token is
  absent), against the **3:1** AA UI threshold (4.5:1 for high-contrast sets).

Values are resolved from `css/tokens/{id}.css` layered over
`css/systems/nldesign/defaults.css` — the same order the runtime uses.

## Verdicts

| Verdict | Meaning |
|---------|---------|
| `pass` | Both pairs meet the applicable threshold. |
| `fail` | At least one pair is below the threshold. The set still ships and can be applied; the fact is recorded, not hidden. |
| `unevaluated` | A pair's colours are not literal (e.g. a `var()` reference) and could not be computed. **Never** treated as passing. |

## The report

The audit emits [`../reference/contrast-report.md`](../reference/contrast-report.md),
one row per audited set with both ratios, the thresholds, and the verdict. It is
deterministic — regenerating from unchanged token files produces byte-identical
output — and is checked in CI by `tests/Unit/TokenSetContrastAuditTest.php`. This
report supersedes the shipped-set verdict of the older hand-written
[`token-audit.md`](../reference/token-audit.md).

## Runtime surfacing

When an administrator opens the apply dialog for a shipped set whose verdict is
`fail` or `unevaluated`, the dialog shows the same non-blocking contrast warning a
custom upload would raise (via `TokenSetService` → `ShippedTokenSetAuditService`).
The warning is informational: the administrator can still apply the set, because a
sub-AA municipal set is a fact about that municipality's brand, not a bug.
