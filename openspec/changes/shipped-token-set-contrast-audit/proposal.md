# Proposal: shipped-token-set-contrast-audit

## Why

`docs/GOVERNMENT-FEATURES.md` markets WCAG conformance as the app's flagship differentiator: F-09 "WCAG 2.1 AA-conforme kleuren — Contrastverhouding gegarandeerd", A-03/A-04 "Contrastverhouding minimaal 4.5:1 / 3:1 — Via design tokens **afgedwongen**" (enforced), and the "Onderscheidende kenmerken" table closes with "WCAG AA gegarandeerd". This is the single most demanded requirement in the market: the Specter intelligence corpus has a **WCAG accessibility** cluster of 168 requirements across **93 tenders**, plus a broader **Accessibility** cluster (266 requirements / 122 tenders), with tender language such as "De externe gepubliceerde onderdelen van de Oplossing voldoen aan de minimale eisen van WCAG 2.1/2.2 niveau AA (EN 301 549, Digitoegankelijk)". Competitor government design systems (GOV.UK, US Web Design System) ship documented, automatically-tested contrast conformance; nldesign claims it but does not test it.

At HEAD the claim is **not enforced for shipped token sets**:

- The app has a real `lib/Service/ContrastService.php` that computes WCAG 2.1 relative-luminance ratios, but it is only invoked for *custom uploads* (the `custom-token-sets` spec, "WCAG AA Contrast Warnings on Upload"). The **41 shipped token sets** in `token-sets.json` are never run through it.
- The only "audit" of shipped sets is `docs/reference/token-audit.md` — a hand-written, AI-authored document dated 2026-02-03 that covers **5** of the 41 sets and asserts "100/100 / APPROVED FOR PRODUCTION". 36 community-derived sets (bodegraven-reeuwijk, borne, buren, …) have never had their contrast computed by anything.
- So "afgedwongen" / "gegarandeerd" is aspirational: nothing fails if a shipped set's primary/text pair is below 4.5:1.

This change makes the claim **true by construction**: it reuses the existing `ContrastService` to evaluate every shipped token set as a PHPUnit inventory gate (mirroring the existing `tests/Unit/IconAssetsTest.php` filesystem-inventory pattern), emits a reproducible per-set report, and surfaces any sub-AA set instead of silently shipping it. The companion `fix-readiness-claims` change downgrades the manual audit doc's over-claim; this change supplies the machine-checked replacement.

## What Changes

- **NEW** — A PHPUnit audit (`tests/Unit/TokenSetContrastAuditTest.php`) that, for every token set in `token-sets.json` bound to a design system that reads `--nldesign-*`, resolves the fixed WCAG pairs from the set's `css/tokens/{id}.css` (falling back to `defaults.css`) via `ContrastService` and asserts an AA verdict per set.
- **NEW** — A generated report (`docs/reference/contrast-report.md`) listing every set with its computed ratio, the applicable threshold (4.5:1 text, 3:1 UI), and an `AA` / `fail` / `unevaluated` verdict — regenerated deterministically from the token files.
- **NEW** — Sub-AA and unevaluated shipped sets are surfaced in the token-set apply dialog, reusing the existing custom-set contrast-warning surface, so an admin selecting a non-compliant set sees the same non-blocking warning a custom upload would raise.
- **NO** new runtime service — `ContrastService` already exists and is reused; this change is a test/gate plus a small read of the audit result into the existing warning surface.

## Capabilities

### New Capabilities
- `token-set-contrast-audit` — Automated WCAG contrast verification of all shipped token sets, a reproducible report, and surfacing of non-compliant sets in the apply dialog.

## Decisions

1. **Reuse `ContrastService`, do not reimplement.** The WCAG relative-luminance math already exists and is unit-tested (`tests/Unit/Service/ContrastServiceTest.php`); the audit is a new caller, not new math.
2. **Fixed pairs, same as custom uploads.** `--nldesign-color-primary` vs `--nldesign-color-primary-text` at 4.5:1, and `--nldesign-color-primary` vs the set's background (`--nldesign-color-background` or `theming.background_color`) at 3:1. This keeps shipped-set auditing identical to the already-specified custom-set contrast rule, so there is one contrast contract, not two.
3. **Inventory gate, mirroring `IconAssetsTest`.** The app already enforces a static-inventory invariant (icons on disk == documented). Contrast is the same shape: derive the truth from files, fail the build on drift. This is how the "afgedwossen" claim becomes real.
4. **Non-blocking at runtime, blocking in CI.** A sub-AA municipal set is a fact about that municipality's brand, not a bug to hard-fail an admin's selection — so at runtime it is a warning (reusing the custom-set surface). In CI the audit reports the verdict; whether a specific failing set blocks release is a review decision recorded in the report, not a silent pass.
5. **`unevaluated` never counts as passing.** Sets whose pair values are non-literal (`var()`, unresolved) are reported `unevaluated`, matching the custom-token-sets rule, so the report never launders an unknown into a green tick.
6. **The report supersedes the manual doc.** `docs/reference/token-audit.md`'s shipped-set verdict is replaced by this generated report; the manual doc (after `fix-readiness-claims`) retains only the human notes on the five originally-reviewed brands.

## Impact

- **nldesign app only** — new `tests/Unit/TokenSetContrastAuditTest.php`, generated `docs/reference/contrast-report.md`, a small read of the audit result into the existing apply-dialog warning path (`TokenSetPreviewService` / `ContrastService`). No new controller or route.
- **CI** — one additional PHPUnit test; deterministic, no network.
- **Truthfulness** — GOVERNMENT-FEATURES A-01..A-05 and F-09 become backed by a reproducible computation over all sets rather than a prose assertion.
- **No database migration, no config keys.**

## Rollback Strategy

- Remove the audit test and the generated report; runtime warning surface reverts to custom-sets-only.
- No theming or persisted state changes, so rollback is inert for running instances.
