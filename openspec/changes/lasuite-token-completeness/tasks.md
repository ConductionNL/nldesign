# Tasks — lasuite-token-completeness

## 1. Cunningham token generation

- [x] 1.1 Add `@openfun/cunningham-tokens` (`^3.0.0`, MIT) to `devDependencies` in `package.json`;
      run `npm install` and confirm `node_modules/@openfun/cunningham-tokens/dist/cunningham-tokens.css`
      exists with 1167 `--c--*` tokens.
- [x] 1.2 Write `scripts/generate-lasuite-tokens.mjs`: read the package's `dist/cunningham-tokens.css`,
      parse every `--c--<path>: <value>;` on `:root`, rename `--c--` → `--lasuite--` (double-dash
      hierarchy separators preserved — the reversible prefix-swap documented in the file header), and
      emit `css/systems/lasuite/defaults.css` with all 1167 tokens on `:root`, sorted stably.
      IMPLEMENTATION NOTE: the source ships tokens across TWO top-level blocks (`html { ... }` = 584
      light + `.cunningham-theme--dark { ... }` = 583 dark, 584+583=1167) — the generator mirrors that
      structure (`:root` for light, `.cunningham-theme--dark` for dark) rather than flattening both
      into one `:root`, which would silently let dark values clobber light ones. See the module header
      comment in generate-lasuite-tokens.mjs for the full rationale.
- [x] 1.3 Emit a provenance header on the generated file: source package name + resolved version (read
      from the package's `package.json`), token count, the mapping rule, the generation date, and the
      MIT attribution.
- [x] 1.4 Append a closed, explicitly-listed **compatibility-alias** block (merged into the same
      `:root` as the canonical tokens — a separate `:root` block would trip stylelint's
      no-duplicate-selectors rule) mapping the 20 short names `bridge.css`/`element-overrides.css`
      actually read (verified by grep, not the ~37 stale names in the old hand-curated file) to their
      canonical `--lasuite--*` tokens via `var()`. `--lasuite-font-family` is defined separately in
      fonts.css (unaffected by regeneration); `--lasuite-border-radius` is the one literal (no
      upstream border-radius token exists in the package at all).
- [x] 1.5 Accept an output-path arg/env (default the committed `defaults.css`) so the drift check can
      generate to a temp file. Make output deterministic (stable order, fixed formatting).
- [x] 1.6 Run the generator; commit the regenerated `css/systems/lasuite/defaults.css`. Confirm the
      base is **blue** (`--lasuite--globals--colors--brand-600: #0659C5`).

## 2. Sourced violet brand override

- [x] 2.1 Create `css/systems/lasuite/brand-override.css` with a provenance comment: "Observed in the
      docs.numerique.gouv.fr La Suite Docs bundle, :root block 5 (violet override), 2026-07-24. Not
      present in any published Cunningham npm package."
- [x] 2.2 Redeclare on `:root` the violet tokens block 5 overrides: `brand-600 #534fc2`,
      `brand-650 #4844ad` (== logo), the dependent `brand-050…brand-950` scale, and the `logo-*`
      tokens (including `logo-1-light`/`logo-2-light`, which do not exist at all in the base package —
      and the `--c--contextuals--content--logo1/2` redirect to them) — plus the four short-alias
      counterparts (`--lasuite-color-brand-050/100/650/750`) so the bridge resolves to violet. Uses
      canonical `--lasuite--*` names matching §1.2 so the override lands on the generated tokens.
- [x] 2.3 Add `systems/lasuite/brand-override` to the `lasuite` bundle in `design-systems.json`,
      ordered **after** `systems/lasuite/defaults` and before `systems/lasuite/bridge`. Final order:
      `fonts, defaults, brand-override, bridge, element-overrides`.
- [x] 2.4 Leave `token-sets.json` `lasuite.theming.primary_color` as `#4844AD` and
      `background_color` as `#FFFFFF`; do not add a `logo` key.

## 3. Optional blue-base `cunningham` sibling (SHOULD)

- [x] 3.1 Add a `cunningham` entry to `design-systems.json` with bundle
      `systems/lasuite/fonts, systems/lasuite/defaults, systems/lasuite/bridge,
      systems/lasuite/element-overrides` (no brand-override — resolves blue from the shared defaults).
- [x] 3.2 Create `css/tokens/cunningham.css`: a standard Layer-3 `--nldesign-*` set pinning the blue
      identity. CORRECTED during implementation: `--nldesign-color-primary` is `#1A509F` (brand-650),
      not `#0659C5` (brand-600) as originally drafted here — the shared `bridge.css`/
      `element-overrides.css` (reused unmodified from the lasuite bundle) derive `--color-primary`
      and every brand-accent rule from `--lasuite-color-brand-650` specifically, the same step that
      resolves to lasuite's deployed violet `#4844AD`; brand-600 is a different, unrendered step. See
      the same correction reflected in design.md and the three spec deltas below.
- [x] 3.3 Add a `cunningham` entry to `token-sets.json`: `design_system: "cunningham"`,
      `theming.primary_color: "#1A509F"` (brand-650, corrected per 3.2 — NOT `#0659C5`),
      `background_color: "#FFFFFF"`, no `logo` key; name/description make clear this is the published
      Cunningham blue base (MIT) vs the deployed violet `lasuite`.

## 4. Complete the Nextcloud `--color-*` bridge

- [x] 4.1 Enumerate the audited Nextcloud `--color-*` surface from the `nextcloud-variable-mapping`
      canonical audit — the concrete list is the 68 `--color-*` variables covered by
      `css/systems/nldesign/overrides.css` (cross-check `docs/reference/mappings.md`).
- [x] 4.2 In `css/systems/lasuite/bridge.css`, ensure **every** audited variable is present as either a
      mapping to a `--lasuite--*`/short-alias token (with `!important` at `body[data-themes]`) or a
      commented line with a reason. (68/68 — 44 mapped, 24 reasoned comments; also fixed the
      `--nldesign-color-{error,warning,success,info}-rgb` literal triples and `--nldesign-color-focus`
      rgba(), which were pre-existing but computed against the OLD hand-transcribed hex values and
      would otherwise have gone out of sync with the regenerated defaults.css.)
- [x] 4.3 Keep the dark-mode-compat exclusions commented with their reason (REQ-CSS-007):
      `--color-main-background`, `--color-main-background-rgb`, `--color-main-background-translucent`,
      `--color-background-plain`, `--background-invert-if-dark`, `--background-invert-if-bright` MUST NOT
      be overridden.
- [x] 4.4 Verify no circular `var()` references and that every fallback resolves to a concrete value or
      a token defined in `defaults.css`/`brand-override.css` (REQ-CSS-005).

## 5. Component-parity e2e

- [x] 5.1 Create `tests/e2e/spec-coverage/lasuite-parity.spec.ts` with the SPDX header and an
      `@e2e openspec/specs/lasuite-parity/spec.md` tag.
- [x] 5.2 Configure `test.describe.configure({ mode: 'serial' })` and one `test()` per element
      (button, input, modal, header, table) — small and batchable for the load-fragile instance
      (issue #181).
- [x] 5.3 For each element, read **computed** styles via `page.evaluate(getComputedStyle)` and compare
      against the Cunningham reference table for the active set (violet for `lasuite`, blue for
      `cunningham`). SCOPED: only the properties nldesign's own CSS actually sources for that selector
      are compared per element (e.g. the button has no nldesign-authored padding/font-weight/box-shadow
      — those are Nextcloud/Vue-component internals unrelated to this app's theming claims, so
      asserting them would be guessing, not verifying). See the spec file's header comment.
- [x] 5.4 Normalise before compare (hex→rgb() since getComputedStyle always reports rgb(), font-family
      list whitespace/quoting/case) so notation differences are not false deltas; on mismatch, throw an
      assertion message naming the exact property and the expected-vs-actual delta.
- [x] 5.5 The spec activates `lasuite`/`cunningham` per test via `setTokenSet()` (reusing
      `tests/e2e/workflows/_helpers.ts`), snapshots the prior set in `beforeAll`, and restores it in
      `afterAll`.

## 6. Drift guard

- [x] 6.1 Add a `test:lasuite-tokens` script to `package.json` that runs
      `scripts/generate-lasuite-tokens.mjs --check` (generates into a temp file internally and diffs
      against the committed `css/systems/lasuite/defaults.css`), exiting non-zero (printing the first
      20 differing lines) on any difference.
- [x] 6.2 Follows the `tests/l10n/check-l10n-completeness.js` / `test:l10n:completeness` pattern
      (single script, mode flag) so it is wired into CI the same way; documented in the generator's own
      header comment as the guard that a `package.json` upstream bump must be accompanied by a
      regenerate + commit.

## 7. Verify

- [x] 7.1 `npm run test:lasuite-tokens` passes on the committed file. Drift detection proven during
      implementation (hand-edited a token value, confirmed the check fails and names the exact line/
      values, reverted) — see the builder's session log / PR description for the exact output.
- [x] 7.2 `npx stylelint 'css/systems/lasuite/**/*.css' 'css/tokens/lasuite.css' 'css/tokens/
      cunningham.css'` passes (0 problems). NOTE: `npm run stylelint` (whole-repo `css/**/*.css`) hits
      one PRE-EXISTING, unrelated failure in `css/admin.css` (`Unclosed block`, not modified by this
      change, last touched by an unrelated merge commit) — out of scope for this change; reported, not
      fixed.
- [x] 7.3 `node tests/validate-manifest.js` (`check:manifest`) passes — unaffected by this change (it
      validates `src/manifest.json`, not `design-systems.json`/`token-sets.json`; there is no dedicated
      schema validator for those two files in this app).
- [ ] 7.4 DEFERRED — requires the live 8080 dev instance. Per the builder-brief ground rules ("SKIP
      tasks that require the live 8080 instance"), not run in this worktree session; the shared
      instance is load-fragile (#181) and other builders in this wave may be using it concurrently.
      Do on the main checkout post-merge: activate `lasuite`, confirm
      `getComputedStyle(document.documentElement).getPropertyValue('--color-primary')` = violet
      `#4844ad`; primary button `border-radius: 4px` + violet fill; header white with hairline border.
- [ ] 7.5 DEFERRED (same reason as 7.4). Activate `cunningham` and confirm `--color-primary` resolves
      to the blue brand-650 `#1A509F` (corrected per task 3.2 — not brand-600 `#0659C5`).
- [ ] 7.6 DEFERRED (same reason as 7.4). `tests/e2e/spec-coverage/lasuite-parity.spec.ts` is written,
      committed, and verified to parse/list correctly under Playwright (`npx playwright test --list`
      → 11 tests found, 0 errors) but not executed against the live instance.
- [x] 7.7 Bridge coverage implemented TWICE, in two toolchains, both passing: `tests/css/check-lasuite-
      bridge-coverage.js` (`npm run test:lasuite-bridge-coverage`) diffs the variable name sets between
      `overrides.css` and `bridge.css`; `LasuiteDesignStackTest::testBridgeAccountsForEveryAuditedColor
      Variable` (PHPUnit) asserts the same invariant so it is enforced from both the JS and PHP
      toolchains.
