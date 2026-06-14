# Tasks: icon-assets

## 1. Spec Retrofit
- [x] 1.1 Adopt the `icon-assets` capability spec (this change's delta) — no runtime code changes; each requirement verified against the shipped assets (344 icons, 23 logos on disk; counts reconciled in docs)

## 2. Inventory Regression Test (ADR-009)
- [x] 2.1 Add `tests/Unit/IconAssetsTest.php` (placed under `tests/Unit/` to match the PHPUnit `Unit Tests` testsuite directory): every icon name listed in `img/ICONS.md` resolves to a file; on-disk icon count equals the documented total (344); logo count matches the documented total
- [x] 2.2 Same test: `*Fill.svg` files have an existing base-variant counterpart
- [x] 2.3 Same test: sampled assets are well-formed SVG without `<script>`/event-handler attributes
- [x] 2.4 Same test: `img/ICONS.md` contains the `@amsterdam/design-system-assets` MPL-2.0 attribution

## 3. Documentation Fixes (ADR-010)
- [x] 3.1 Fix README "View Icon Documentation" link: `ICONS.md` → `img/ICONS.md`
- [x] 3.2 Align icon/logo counts and the `imagePath('nldesign', 'icons/{Name}.svg')` consumption snippet across `README.md`, `docs/reference/icons.md`, and `img/ICONS.md` (logo count corrected from stale "6" to actual 23; build-icons.js template already emits the real count and now also emits the contract sections)
- [x] 3.3 Add the consumer fallback note to `docs/reference/icons.md`: icon URLs only resolve while nldesign is enabled — ship a fallback or declare a dependency
- [x] 3.4 Document the naming-stability rule (renames/removals = breaking change with changelog entry; upstream syncs are explicit reviewed changes) — in README.md, docs/reference/icons.md, img/ICONS.md and the build-icons.js template

## 4. Verification
- [x] 4.1 Live-verify the consumption contract against the dev container: `img/icons/Bell.svg` and `img/logos/amsterdam.svg` both return HTTP 200 with `image/svg+xml`. (Reconcile finding: the docs' old example `MagnifyingGlass.svg` does NOT exist on disk — upstream named it `Search.svg`/`MagnifyingGlassWithEye.svg`; all broken doc examples corrected to real filenames, exactly the drift this contract guards against.)
