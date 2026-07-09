# Tasks: shipped-token-set-contrast-audit

## 1. Audit harness (reuses ContrastService)
- [x] 1.1 Add a helper that, given a token set id, loads `css/tokens/{id}.css` layered over `css/defaults.css` and resolves the literal values of `--nldesign-color-primary`, `--nldesign-color-primary-text`, and `--nldesign-color-background` (using the existing `CssParserService`)
- [x] 1.2 For each set, compute the two fixed WCAG pairs via `ContrastService`: primary vs primary-text (4.5:1) and primary vs background (3:1); classify each as `AA` / `fail` / `unevaluated` (non-literal → unevaluated, never passing)

## 2. PHPUnit inventory gate (ADR-009, mirrors IconAssetsTest)
- [x] 2.1 `tests/Unit/TokenSetContrastAuditTest.php`: iterate every token set in `token-sets.json` whose design system reads `--nldesign-*` (skip `design_system: none`)
- [x] 2.2 Assert each such set produces a computed ratio for both pairs (no crash / no missing token)
- [x] 2.3 Assert that every set whose documentation/marketing presents it as WCAG-AA compliant meets 4.5:1 (text) and 3:1 (UI); a shortfall fails the test with the set id, pair, computed ratio, and threshold
- [x] 2.4 Assert `unevaluated` pairs are reported as such and are never classified as passing

## 3. Generated report
- [x] 3.1 Emit `docs/reference/contrast-report.md`: one row per set with id, computed primary/text ratio, primary/background ratio, thresholds, and verdict; deterministic ordering
- [x] 3.2 Regeneration on unchanged token files MUST produce a byte-identical report (guards against nondeterminism)
- [x] 3.3 Cross-link the report from `docs/reference/token-audit.md` and `docs/GOVERNMENT-FEATURES.md` A-01..A-05 / F-09

## 4. Runtime surfacing (reuses custom-set warning path)
- [x] 4.1 When the apply dialog opens for a shipped set with a stored sub-AA or unevaluated verdict, display the same non-blocking contrast warning already used for custom uploads (via `TokenSetPreviewService`)
- [x] 4.2 The warning MUST be non-blocking — the admin can still apply the set

## 5. E2E / API (gate-19)
- [x] 5.1 Playwright: open the apply dialog for a set flagged sub-AA (fixture) → the contrast warning is visible above the change list; applying still succeeds (`tests/e2e/spec-coverage/token-set-contrast-audit.spec.ts`)
- [x] 5.2 @e2e exclude for the PHPUnit-only audit and report-generation scenarios (backend computation, not a UI flow)

## 6. Documentation and i18n (ADR-005, ADR-010)
- [x] 6.1 Document the audit and the report in `docs/features/` (how to read verdicts, what unevaluated means)
- [x] 6.2 Any new user-visible warning string via `$l->t()` with an English source key; Dutch in `l10n/nl.json` (+ de/fr/es/it parity) — reuse the existing custom-set warning strings where possible
