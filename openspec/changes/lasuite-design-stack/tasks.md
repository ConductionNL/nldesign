## 1. Manifests

- [ ] 1.1 Add the `lasuite` entry to `design-systems.json`:
      id `lasuite`, name `La Suite numérique`, description naming Cunningham (MIT) and the
      pixel-adjacent-match goal, `stylesheets` in exact order:
      `systems/lasuite/fonts`, `systems/lasuite/defaults`, `systems/lasuite/bridge`,
      `systems/lasuite/element-overrides`.
- [ ] 1.2 Add the `lasuite` token set entry to `token-sets.json`:
      id `lasuite`, name `La Suite numérique`, description, `design_system: "lasuite"`,
      `theming: { "primary_color": "#4844AD", "background_color": "#FFFFFF" }` — NO `logo` key
      (trademark: no La Suite/state logos are bundled).

## 2. Fonts layer (open fallback, no Marianne files)

- [ ] 2.1 Vendor Inter (SIL OFL 1.1) woff2 into `css/systems/lasuite/fonts/`: weights
      400/500/600/700 normal + 400/700 italic, Latin subset; include the OFL.txt license file
      alongside the font files.
- [ ] 2.2 Write `css/systems/lasuite/fonts.css`: one @font-face per weight/style with
      `src: local('Inter'), url(...woff2) format('woff2')` and `font-display: swap`; then define
      `--lasuite-font-family: Marianne, Inter, sans-serif;` on `:root` — Marianne resolves ONLY
      when locally installed (French administration machines); we ship zero Marianne files.
- [ ] 2.3 Verify by grep + directory listing that no file named `Marianne*` and no La Suite logo
      asset exists anywhere in the change (license-compliance gate; mirrors the Amsterdam-icons
      NOTICE lesson from icons-from-ncvue).

## 3. Defaults layer (Cunningham token transcription)

- [ ] 3.1 Write `css/systems/lasuite/defaults.css` defining the curated `--lasuite-*` set on
      `:root`, values transcribed from `@gouvfr-lasuite/cunningham-react` /
      `suitenumerique/ui-kit` `cunningham.ts` (MIT). Required groups:
      brand scale (`--lasuite-color-brand-050: #EEF1FA` … `--lasuite-color-brand-650: #4844AD` …
      `--lasuite-color-brand-950: #11131F`), greyscale (`gray-000 #FFFFFF` → `gray-1000
      #000000`, the 50/100/…/900 steps used by the bridge), semantic colors
      (`success #1E884A`, `warning #CB5000`, `error #E82322`, `info #0077DE` + text-on-color
      counterparts), `--lasuite-border-radius: 4px`, base spacing (`1rem` base scale).
      Each group carries a source comment (file + token name upstream).
- [ ] 3.2 Header comment: SPDX-style attribution block naming Cunningham, MIT license, upstream
      commit/version transcribed from.

## 4. Bridge layer

- [ ] 4.1 Write `css/systems/lasuite/bridge.css` mapping `--lasuite-*` → `--nldesign-*` on
      `:root`: primary/primary-text/primary-hover/primary-light from the brand scale, status
      colors (+ `-rgb` variants), text/text-muted from the greyscale, border, focus
      (info-based, per ADR-CSS-003 pattern), font-family from `--lasuite-font-family`,
      border-radius tokens from `--lasuite-border-radius`, and the `--nldesign-component-*`
      button/textbox/heading tokens the theme layer consumes.
- [ ] 4.2 Map Nextcloud `--color-*` variables in the same file following the css-architecture
      invariants: `!important` only where NC's own `body[data-themes]` assignments must be beaten
      (ADR-CSS-002); MUST NOT touch `--color-main-background*`, `--color-background-plain`,
      `--background-invert-if-dark`, `--background-invert-if-bright` (REQ-CSS-007 dark-mode
      compatibility); comment every intentionally-unset variable.
- [ ] 4.3 No circular `var()` references; every fallback resolves to a concrete value or a
      `--lasuite-*` token defined in the defaults layer (REQ-CSS-005 discipline).

## 5. Element overrides layer + token file

- [ ] 5.1 Write `css/systems/lasuite/element-overrides.css` for the pixel-adjacent chrome match:
      header bar (white surface, `gray-200` hairline border, brand-650 accents), app navigation
      (flat white card, 4px radii), primary buttons (brand-650 bg, white text, 4px radius,
      brand-750 hover), inputs (1px `gray-300` border, 4px radius), font application via body
      inheritance per ADR-CSS-001 (no universal-selector `!important`).
- [ ] 5.2 Write `css/tokens/lasuite.css` (Layer 3): `:root`-only `--nldesign-*` declarations for
      the set-level values (primary `#4844AD`, primary-text `#FFFFFF`, background tones from the
      greyscale) so the set behaves like every other token set (REQ-TSET-005; partial set —
      defaults fall through).
- [ ] 5.3 Bump `appinfo/info.xml` version (NC `?v=` cache-bust gotcha).

## 6. Tests

- [ ] 6.1 PHPUnit: `DesignSystemService` resolves `lasuite` with the 4 stylesheets in declared
      order; `TokenSetService::getAvailableTokenSets()` returns the `lasuite` entry with
      `design_system: "lasuite"` and its theming metadata; manifest/CSS-file pairing intact.
- [ ] 6.2 PHPUnit: `ShippedTokenSetAuditService::auditSet()` over `lasuite` reports zero WCAG AA
      failures (brand-650 `#4844AD` vs white measures ≈ 7.7:1 — assert ≥ 4.5:1 via
      `ContrastService::ratio()` fixture test).
- [ ] 6.3 License-compliance test: assert `css/systems/lasuite/fonts/` contains an `OFL.txt`
      and no file matching `/marianne/i`; assert `token-sets.json` `lasuite` entry has no `logo`
      key.
- [ ] 6.4 `composer check:strict` green (SPDX docblocks N/A for CSS, but any touched PHP test
      files carry them); vitest untouched-green.

## 7. Verify (live, 8080)

- [ ] 7.1 Activate the `lasuite` token set via the admin panel on the 8080 dev instance; confirm
      via curl that the page head loads the 4 lasuite stylesheets in declared order followed by
      `tokens/lasuite.css` and `custom-overrides`.
- [ ] 7.2 **Visual comparison (the acceptance gate)**: with a Playwright browser, capture
      (a) a real La Suite app page — https://docs.numerique.gouv.fr (or a local
      `suitenumerique/docs` dev instance if the public page is gated) — and (b) the themed
      Nextcloud Files view + login page on 8080. Produce a side-by-side composite and check the
      parity list: font renders as Inter (not NC system stack), primary interactive color
      `#4844AD`, 4px control radii, white header with hairline border, greyscale surface tones.
      Store the composite with the change artifacts.
- [ ] 7.3 Switch NC user theme to dark on 8080 with `lasuite` active: confirm no broken surfaces
      (the bridge leaves `--color-main-background*` and invert variables alone). Screenshot.
- [ ] 7.4 Switch back to the `rijkshuisstijl` set and confirm zero lasuite bleed-through (systems
      isolation, REQ-CSS-012). Screenshot.
