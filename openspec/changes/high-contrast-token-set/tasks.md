# Tasks: high-contrast-token-set

## 1. Design system definition
- [ ] 1.1 Add a `high-contrast` entry to `design-systems.json` with an ordered `stylesheets` list (`systems/high-contrast/fonts`, `systems/high-contrast/theme`, `systems/high-contrast/element-overrides`), mirroring the `summer-breeze` shape
- [ ] 1.2 Create `css/systems/high-contrast/theme.css`: map `--nldesign-*` → Nextcloud `--color-*` selectors using maximum-contrast values (near-pure foreground on background, strong borders)
- [ ] 1.3 Create `css/systems/high-contrast/element-overrides.css`: thick focus rings, visible borders on inputs/buttons/table rows, no low-contrast placeholder text
- [ ] 1.4 Reuse the bundled Fira Sans via `systems/high-contrast/fonts` (or alias the nldesign fonts stylesheet) — no new font files

## 2. Token set
- [ ] 2.1 Add a `hoog-contrast` entry to `token-sets.json` bound to `design_system: high-contrast`, display name "Hoog contrast (WCAG AAA)", with `theming.primary_color` / `theming.background_color`
- [ ] 2.2 Create `css/tokens/hoog-contrast.css` defining the `--nldesign-*` values so the fixed pairs meet WCAG 2.2 AAA (primary vs primary-text ≥ 7:1; primary vs background ≥ 4.5:1)

## 3. OS high-contrast cooperation (EN 301 549)
- [ ] 3.1 In the high-contrast system stylesheet, add `@media (prefers-contrast: more)` refinements
- [ ] 3.2 Add `@media (forced-colors: active)` handling using `system-color` keywords so Windows High Contrast Mode keeps text, borders, and focus indicators visible; do not hardcode colors the OS will discard

## 4. Verification (depends on shipped-token-set-contrast-audit)
- [ ] 4.1 The `hoog-contrast` set MUST pass the contrast audit at the AAA threshold (7:1 text); add/extend the audit to assert AAA for sets tagged high-contrast
- [ ] 4.2 Manual check under Windows High Contrast / `forced-colors` emulation: focus ring and text remain visible on the main NC surfaces

## 5. E2E (gate-19)
- [ ] 5.1 Playwright: select "Hoog contrast (WCAG AAA)" in the token-set dropdown → apply → the themed page's computed `--nldesign-color-primary`/text pair meets AAA; the app remains operable (`tests/e2e/spec-coverage/high-contrast-token-set.spec.ts`)
- [ ] 5.2 @e2e exclude for the AAA ratio computation branch (covered by the PHPUnit contrast audit)

## 6. Documentation and status (ADR-010)
- [ ] 6.1 Move `docs/GOVERNMENT-FEATURES.md` A-08 from "Gepland" to "Beschikbaar" ONLY after 4.1 is green; link the set and its AAA verdict in the contrast report
- [ ] 6.2 Add a `docs/features/` page describing the high-contrast theme, its AAA target, and the OS high-contrast cooperation
- [ ] 6.3 Any new user-visible string via `$l->t()` with an English source key; Dutch in `l10n/nl.json` (+ de/fr/es/it parity)
