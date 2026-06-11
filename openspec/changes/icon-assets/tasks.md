# Tasks: icon-assets

## 1. Spec Retrofit
- [ ] 1.1 Adopt the `icon-assets` capability spec (this change's delta) — no runtime code changes expected; verify each requirement against the shipped assets before archiving

## 2. Inventory Regression Test (ADR-009)
- [ ] 2.1 Add `tests/unit/IconAssetsTest.php`: every icon name listed in `img/ICONS.md` resolves to a file; on-disk icon count equals the documented total (344); logo inventory matches
- [ ] 2.2 Same test: `*Fill.svg` files have an existing base-variant counterpart
- [ ] 2.3 Same test: sampled assets are well-formed SVG without `<script>`/event-handler attributes
- [ ] 2.4 Same test: `img/ICONS.md` contains the `@amsterdam/design-system-assets` MPL-2.0 attribution

## 3. Documentation Fixes (ADR-010)
- [ ] 3.1 Fix README "View Icon Documentation" link: `ICONS.md` → `img/ICONS.md`
- [ ] 3.2 Align icon/logo counts and the `imagePath('nldesign', 'icons/{Name}.svg')` consumption snippet across `README.md`, `docs/reference/icons.md`, and `img/ICONS.md`
- [ ] 3.3 Add the consumer fallback note to `docs/reference/icons.md`: icon URLs only resolve while nldesign is enabled — ship a fallback or declare a dependency
- [ ] 3.4 Document the naming-stability rule (renames/removals = breaking change with changelog entry; upstream syncs are explicit reviewed changes)

## 4. Verification
- [ ] 4.1 Live-verify the consumption contract once against the dev container: `GET /custom_apps/nldesign/img/icons/MagnifyingGlass.svg` (via imagePath URL) returns the SVG with HTTP 200; a logo URL likewise
