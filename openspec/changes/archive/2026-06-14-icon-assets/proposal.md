# Proposal: icon-assets

## Why

The README advertises the Amsterdam Design System icon set as a headline feature ("Includes 344 SVG icons and 6 logos from the official Amsterdam Design System for use across all Nextcloud apps"), `docs/reference/icons.md` and `img/ICONS.md` document a consumption recipe for other apps, and the assets physically exist in `img/icons/` (344 SVGs) and `img/logos/` — yet no spec covers the feature. That means there is no contract for: asset inventory and naming stability (other Conduction apps hardcode icon names like `MagnifyingGlass.svg`), the consumption API (`IURLGenerator::imagePath('nldesign', 'icons/{Name}.svg')`), or the MPL-2.0 licensing constraint that travels with the assets. Any rename or removal would silently break consumer apps with no gate noticing.

Additionally the README's icon-documentation link is broken: `[View Icon Documentation →](ICONS.md)` points at a repo-root `ICONS.md` that does not exist (the file lives at `img/ICONS.md`).

This is a retrofit/reverse-spec change: it specs the feature that already ships, adds the missing stability/licensing contract, and fixes the documentation defects. It intentionally adds no new runtime code beyond a regression test.

## What Changes

- **NEW (spec only, behavior exists)** — `icon-assets` capability spec covering: asset inventory (344 icons, 23 logos), PascalCase naming convention with `Fill` variants matching upstream Amsterdam DS names, the image-path consumption contract for other apps, and graceful-degradation expectations when nldesign is disabled
- **NEW** — Naming-stability contract: icon/logo filenames are a public API; renames/removals are breaking changes that require a changelog entry and a major-ish version signal
- **NEW** — Licensing requirement: assets originate from `@amsterdam/design-system-assets` (Mozilla Public License 2.0); the MPL-2.0 notice MUST remain co-located with the assets and named in app docs
- **NEW** — Inventory regression test (PHPUnit): every icon name documented in `img/ICONS.md` resolves to an existing file; counts match the documented totals
- **FIXED** — README icon-documentation link points to `img/ICONS.md`; README and `docs/reference/icons.md` are aligned on counts and the consumption snippet

## Capabilities

### New Capabilities
- `icon-assets` — Amsterdam Design System icon and logo assets: inventory, naming stability, consumption contract, licensing, documentation

## Decisions

1. **Retrofit, not redesign**: the spec describes the shipped behavior (static SVGs served through Nextcloud's standard app image path). No icon-listing REST endpoint, no icon-browser UI — consumers are developers reading docs, and `imagePath()` is the canonical NC mechanism. If programmatic discovery is ever needed, that is a follow-up change.
2. **Filenames are the API**: stability is contractual (spec requirement + changelog rule), enforced socially and by the inventory test, not by tooling — upstream Amsterdam DS releases occasionally rename icons, and syncing upstream must be a conscious, changelog-documented act.
3. **Licensing is a requirement, not a footnote**: icons are MPL-2.0 while the app is EUPL-1.2; the spec pins the obligation to keep the MPL notice with the assets so a future cleanup cannot accidentally strip it.

## Impact

- **openspec/specs/** — new `icon-assets` capability (via archive on completion)
- **README.md** — one link fix, counts verification
- **tests/** — one new PHPUnit inventory test
- **No runtime code changes**, no routes, no migration

## Rollback Strategy

- Spec-only retrofit: reverting removes the contract but changes no behavior
- The README link fix and inventory test are independently revertible
