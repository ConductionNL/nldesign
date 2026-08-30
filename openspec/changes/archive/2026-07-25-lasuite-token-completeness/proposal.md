---
kind: code
---

## Why

The shipped `lasuite` design system claims a "pixel-adjacent match to La Suite chrome"
(`lasuite-stack` spec) but its Cunningham foundation is **hand-transcribed and 4.6% complete**.
`css/systems/lasuite/defaults.css` defines only ~37 `--lasuite-*` tokens; the real Cunningham
token build ships **1167**. Two independent problems follow from that gap:

1. **Hand-transcription does not scale and cannot be proven correct.** A 2026-07-24 audit fetched
   the two stable, MIT-licensed Cunningham token packages — `@openfun/cunningham-tokens@3.0.0`
   and `@gouvfr-lasuite/ui-kit@0.27.0`, both shipping `dist/cunningham-tokens.css` with **1167
   tokens** — and diffed them against the shipped file. Of the 37 transcribed tokens, 34 match
   exactly and 3 differ only in notation (`#ffffff` vs `#fff`); the transcription is *correct* but
   covers a tiny slice, so any component reading a Cunningham token outside that slice falls back
   to Nextcloud defaults and breaks the "no visual seam" promise. Every future upstream bump has
   to be re-transcribed by hand — the exact drift trap `scripts/generate-tokens.mjs` and the
   `upstream-freshness` spec exist to eliminate for the nldesign token sets.

2. **The npm packages ship only the BLUE base; La Suite's deployed theme is VIOLET.** The audit of
   the live La Suite Docs bundle (`docs.numerique.gouv.fr`) found 829 distinct `--c--*` properties
   across 7 `:root` blocks: block 0 is the blue base (`brand-600 #0659C5`, `logo #377FDB`), and
   **block 5 is a VIOLET override** (`brand-600 #534fc2`, `logo #4844ad`) that wins the cascade —
   so **the theme users actually see is violet** (~799 effective tokens). Neither MIT npm package
   contains the violet; it exists *only* as an app-level override in the deployed bundle. The
   current shipped `defaults.css` silently mislabels the violet values (`brand-600 #534fc2`,
   `brand-650 #4844ad`) as the "Cunningham light root block" — mixing a generated-base concern with
   a deployment-override concern in one hand-edited file.

The `lasuite` bridge is also incomplete: `css/systems/lasuite/bridge.css` maps ~30 Nextcloud
`--color-*` variables, but the audited Nextcloud variable surface is **68** (the count
`css/systems/nldesign/overrides.css` covers, per the `nextcloud-variable-mapping` spec). Uncovered
`--color-*` variables keep their stock Nextcloud value under the lasuite theme — a silent seam.

Finally, nothing *proves* parity: there is no test that asserts a rendered Nextcloud button/input/
modal actually computes to the Cunningham reference values, and no guard that the committed
generated file still matches npm.

This change replaces transcription with **generation**, separates the **sourced violet override**
from the generated base, **completes and proves the bridge**, adds a **component-parity e2e**, and
adds a **drift guard**. It also (secondarily) ships the published blue base as its own selectable
`cunningham` set, since that is the artifact the npm packages actually distribute.

## What Changes

- **Generate, don't transcribe.** Add `@openfun/cunningham-tokens` as a **devDependency** and a
  re-runnable `scripts/generate-lasuite-tokens.mjs` that reads its `dist/cunningham-tokens.css` and
  emits `css/systems/lasuite/defaults.css` with **all 1167** tokens renamed `--c--*` → `--lasuite--*`
  via a **documented, reversible** prefix-swap (double-dash hierarchy separators preserved), plus a
  provenance header (package name, version, token count, generation date) and a small
  **compatibility-alias block** so the ~37 short names the existing bridge/element-overrides layers
  read (`--lasuite-color-brand-650`, …) keep resolving. The generated file is committed.
  **BREAKING (internal):** `defaults.css` becomes generated output — no longer hand-edited; the base
  is now the true **blue** Cunningham base (`--lasuite--globals--colors--brand-600 #0659C5`), not the
  violet values previously mislabelled in it.
- **Ship the sourced violet override separately.** Add `css/systems/lasuite/brand-override.css` — a
  hand-authored, **provenance-commented** file (observed in the `docs.numerique.gouv.fr` bundle,
  `:root` block 5, 2026-07-24) reproducing the ~799-effective violet theme (`brand-600 #534fc2`,
  `brand-650/logo #4844ad`, and the dependent brand-scale + logo tokens). Layer it **after**
  `defaults` so the cascade yields the *deployed* values. `token-sets.json` `lasuite.primary_color`
  stays **`#4844AD`**. The `lasuite` bundle becomes five stylesheets: `fonts → defaults →
  brand-override → bridge → element-overrides`.
- **Ship the blue base as an optional sibling set (secondary).** Add a `cunningham` design system
  (bundle `fonts → defaults → bridge → element-overrides`, **no** brand-override) and a `cunningham`
  token set (`primary_color #1A509F` — brand-650 of the blue base, the step the shared bridge/
  element-overrides actually derive `--color-primary` from) that **reuses the same generated
  `defaults.css`**. This is the
  artifact the npm packages actually publish; it is optional/secondary to `lasuite` and adds no new
  CSS beyond one token file + two manifest entries.
- **Complete the bridge with provable coverage.** Audit every Nextcloud `--color-*` variable in the
  `nextcloud-variable-mapping` canonical audit (the 68-variable surface covered by
  `css/systems/nldesign/overrides.css`) and map each to its Cunningham `--lasuite--*` counterpart in
  `css/systems/lasuite/bridge.css`, or leave it commented with a reason (respecting the
  dark-mode-compat exclusions in `REQ-CSS-007`). Add a coverage assertion.
- **Prove component parity (e2e).** Add `tests/e2e/spec-coverage/lasuite-parity.spec.ts` rendering a
  Nextcloud button, input, modal, header and table under the active `lasuite` set and asserting the
  **computed** `background-color`, `color`, `border-radius`, `font-family`/`-size`/`-weight`,
  `padding` and `box-shadow` equal the Cunningham reference values. Failures MUST name the exact
  property + delta. The e2e instance is load-fragile (issue #181), so the spec MUST be small and
  batchable (one page, one element per batch, serial).
- **Guard against drift.** Add a `test:lasuite-tokens` check that re-runs the generator against the
  installed npm package into a temp file and fails if the committed `defaults.css` differs (mirrors
  the l10n-completeness check pattern), wired into `package.json` scripts.

## Impact

- **New canonical spec:** `openspec/specs/lasuite-parity/spec.md` (created on archive) — token
  generation, sourced violet override, blue-base sibling, bridge coverage, component-parity e2e,
  drift guard.
- **Modified canonical specs:** `lasuite-stack` (generated defaults layer; new brand-override layer;
  five-file bundle; asset-license note for the Cunningham devDependency), `css-architecture` (La Suite
  system-files list → 5 files; design-system resolution → 5-stylesheet lasuite bundle + optional
  `cunningham` bundle), `token-sets` (lasuite entry unchanged at `#4844AD`; add optional `cunningham`
  entry).
- **Code (build target of this change):** `scripts/generate-lasuite-tokens.mjs` (new);
  `css/systems/lasuite/defaults.css` (regenerated, 1167 tokens); `css/systems/lasuite/brand-override.css`
  (new); `css/systems/lasuite/bridge.css` (68-variable coverage); `css/tokens/cunningham.css` (new,
  optional); `design-systems.json` (lasuite bundle +brand-override; add `cunningham`);
  `token-sets.json` (add optional `cunningham`; `lasuite` unchanged); `package.json`
  (`@openfun/cunningham-tokens` devDependency + `test:lasuite-tokens` script);
  `tests/e2e/spec-coverage/lasuite-parity.spec.ts` (new).
- **No PHP, no DB, no Vue.** Purely CSS/manifest/tooling/test — the design-system loader
  (`DesignSystemService` + `Application::injectThemeCSS()`) already loads any declared stylesheet
  array in order, so the extra `brand-override` layer and the `cunningham` bundle need no PHP change.
