# Tasks — lasuite-token-completeness

## 1. Cunningham token generation

- [ ] 1.1 Add `@openfun/cunningham-tokens` (`^3.0.0`, MIT) to `devDependencies` in `package.json`;
      run `npm install` and confirm `node_modules/@openfun/cunningham-tokens/dist/cunningham-tokens.css`
      exists with 1167 `--c--*` tokens.
- [ ] 1.2 Write `scripts/generate-lasuite-tokens.mjs`: read the package's `dist/cunningham-tokens.css`,
      parse every `--c--<path>: <value>;` on `:root`, rename `--c--` → `--lasuite--` (double-dash
      hierarchy separators preserved — the reversible prefix-swap documented in the file header), and
      emit `css/systems/lasuite/defaults.css` with all 1167 tokens on `:root`, sorted stably.
- [ ] 1.3 Emit a provenance header on the generated file: source package name + resolved version (read
      from the package's `package.json`), token count, the mapping rule, the generation date, and the
      MIT attribution.
- [ ] 1.4 Append a closed, explicitly-listed **compatibility-alias** `:root` block mapping the ~37
      short names the current `bridge.css` / `element-overrides.css` read
      (`--lasuite-color-*`, `--lasuite-border-radius`, `--lasuite-spacing-*`, `--lasuite-font-family`
      if applicable) to their canonical `--lasuite--*` tokens via `var()`.
- [ ] 1.5 Accept an output-path arg/env (default the committed `defaults.css`) so the drift check can
      generate to a temp file. Make output deterministic (stable order, fixed formatting).
- [ ] 1.6 Run the generator; commit the regenerated `css/systems/lasuite/defaults.css`. Confirm the
      base is **blue** (`--lasuite--globals--colors--brand-600: #0659C5`).

## 2. Sourced violet brand override

- [ ] 2.1 Create `css/systems/lasuite/brand-override.css` with a provenance comment: "Observed in the
      docs.numerique.gouv.fr La Suite Docs bundle, :root block 5 (violet override), 2026-07-24. Not
      present in any published Cunningham npm package."
- [ ] 2.2 Redeclare on `:root` the violet tokens block 5 overrides: `brand-600 #534fc2`,
      `brand-650 #4844ad` (== logo), the dependent `brand-050…brand-950` scale, and the `logo-*`
      tokens — plus their short-alias counterparts so the bridge resolves to violet. Use canonical
      `--lasuite--*` names matching §1.2 so the override lands on the generated tokens.
- [ ] 2.3 Add `systems/lasuite/brand-override` to the `lasuite` bundle in `design-systems.json`,
      ordered **after** `systems/lasuite/defaults` and before `systems/lasuite/bridge`. Final order:
      `fonts, defaults, brand-override, bridge, element-overrides`.
- [ ] 2.4 Leave `token-sets.json` `lasuite.theming.primary_color` as `#4844AD` and
      `background_color` as `#FFFFFF`; do not add a `logo` key.

## 3. Optional blue-base `cunningham` sibling (SHOULD)

- [ ] 3.1 Add a `cunningham` entry to `design-systems.json` with bundle
      `systems/lasuite/fonts, systems/lasuite/defaults, systems/lasuite/bridge,
      systems/lasuite/element-overrides` (no brand-override — resolves blue from the shared defaults).
- [ ] 3.2 Create `css/tokens/cunningham.css`: a standard Layer-3 `--nldesign-*` set pinning the blue
      identity (`--nldesign-color-primary: #0659C5`, matching text/hover/light from the generated
      blue scale), falling through to the bridge for the rest.
- [ ] 3.3 Add a `cunningham` entry to `token-sets.json`: `design_system: "cunningham"`,
      `theming.primary_color: "#0659C5"`, `background_color: "#FFFFFF"`, no `logo` key; name/description
      make clear this is the published Cunningham blue base (MIT) vs the deployed violet `lasuite`.

## 4. Complete the Nextcloud `--color-*` bridge

- [ ] 4.1 Enumerate the audited Nextcloud `--color-*` surface from the `nextcloud-variable-mapping`
      canonical audit — the concrete list is the 68 `--color-*` variables covered by
      `css/systems/nldesign/overrides.css` (cross-check `docs/reference/mappings.md`).
- [ ] 4.2 In `css/systems/lasuite/bridge.css`, ensure **every** audited variable is present as either a
      mapping to a `--lasuite--*`/short-alias token (with `!important` at `body[data-themes]`) or a
      commented line with a reason.
- [ ] 4.3 Keep the dark-mode-compat exclusions commented with their reason (REQ-CSS-007):
      `--color-main-background`, `--color-main-background-rgb`, `--color-main-background-translucent`,
      `--color-background-plain`, `--background-invert-if-dark`, `--background-invert-if-bright` MUST NOT
      be overridden.
- [ ] 4.4 Verify no circular `var()` references and that every fallback resolves to a concrete value or
      a token defined in `defaults.css`/`brand-override.css` (REQ-CSS-005).

## 5. Component-parity e2e

- [ ] 5.1 Create `tests/e2e/spec-coverage/lasuite-parity.spec.ts` with the SPDX header and an
      `@e2e openspec/specs/lasuite-parity/spec.md` tag.
- [ ] 5.2 Configure `test.describe.configure({ mode: 'serial' })` and one `test()` per element
      (button, input, modal, header, table) — small and batchable for the load-fragile instance
      (issue #181).
- [ ] 5.3 For each element, read **computed** styles via `page.evaluate(getComputedStyle)` and compare
      `background-color`, `color`, `border-radius`, `font-family`, `font-size`, `font-weight`,
      `padding`, `box-shadow` against the Cunningham reference table for the active set (violet for
      `lasuite`, blue for `cunningham`).
- [ ] 5.4 Normalise before compare (rgb()↔hex, whitespace in font-family/box-shadow) so notation
      differences are not false deltas; on mismatch, throw an assertion message naming the exact
      property and the expected-vs-actual delta.
- [ ] 5.5 Ensure the spec activates the `lasuite` token set first (via the admin theming settings or a
      test fixture) and restores the prior set on teardown.

## 6. Drift guard

- [ ] 6.1 Add a `test:lasuite-tokens` script to `package.json` that runs
      `scripts/generate-lasuite-tokens.mjs` into a temp file and `diff`s it against the committed
      `css/systems/lasuite/defaults.css`, exiting non-zero (printing the first differing tokens) on any
      difference.
- [ ] 6.2 Follow the `tests/l10n/check-l10n-completeness.js` / `test:l10n:completeness` pattern so it
      is wired into CI the same way; document it as the guard that a `package.json` upstream bump must
      be accompanied by a regenerate + commit.

## 7. Verify

- [ ] 7.1 `npm run test:lasuite-tokens` passes on the committed file, and fails when a token in
      `defaults.css` is manually edited (revert after proving).
- [ ] 7.2 `npm run stylelint` passes on the new/changed CSS files.
- [ ] 7.3 `node tests/validate-manifest.js` (`check:manifest`) passes with the new `cunningham` entries
      and the 5-file `lasuite` bundle.
- [ ] 7.4 On the 8080 dev instance, activate the `lasuite` set and confirm live in the browser:
      `getComputedStyle(document.documentElement).getPropertyValue('--color-primary')` resolves to the
      **violet** `#4844ad`; a primary button computes `border-radius: 4px` and the violet fill; the
      header is white with a hairline border. Capture a screenshot.
- [ ] 7.5 Activate the `cunningham` set and confirm `--color-primary` resolves to the **blue**
      `#0659C5` from the same `defaults.css` with no violet override applied.
- [ ] 7.6 Run `tests/e2e/spec-coverage/lasuite-parity.spec.ts` (serial, small batch) against the dev
      instance and confirm all five element checks pass; confirm a deliberately wrong reference value
      produces a failure naming the property + delta (revert after proving).
- [ ] 7.7 Bridge coverage: assert the set of audited Nextcloud `--color-*` variables is fully present
      in `bridge.css` (mapping or reasoned comment) — e.g. a small node/grep check diffing the variable
      name sets against `css/systems/nldesign/overrides.css`.
