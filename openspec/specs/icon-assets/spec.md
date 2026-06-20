---
status: done
---

# icon-assets Specification

## Purpose
Bundles the Amsterdam Design System icon set (344 SVG icons) and organization logos (23 logos) and makes them consumable by other Nextcloud apps through the standard image-path API without any nldesign-specific bootstrap. It guarantees a documented, validated inventory, treats filenames as a stable public API where renames are breaking changes, and keeps the MPL-2.0 attribution co-located with the assets.
## Requirements
### Requirement: Bundled Icon and Logo Inventory
The app MUST bundle the Amsterdam Design System icon set as individual SVG files in `img/icons/` (344 icons) and the organization logos in `img/logos/` (23 logos). Icon filenames MUST follow the upstream Amsterdam Design System PascalCase names (e.g. `Search.svg`), with filled variants carrying the `Fill` suffix (e.g. `AppleFill.svg`). The documented inventory in `img/ICONS.md` MUST match the filesystem.

#### Scenario: Documented icons exist on disk
@e2e exclude static asset inventory — PHPUnit test compares docs to filesystem
- GIVEN the icon names listed in `img/ICONS.md`
- WHEN each name is resolved to `img/icons/{Name}.svg`
- THEN every documented name MUST exist as a file
- AND the total icon count on disk MUST equal the documented total (344)

#### Scenario: Fill variants pair with their base icon
@e2e exclude naming convention check — PHPUnit on the inventory
- GIVEN an icon file ending in `Fill.svg` (e.g. `AppleFill.svg`)
- WHEN its base name is derived by stripping the `Fill` suffix
- THEN the corresponding base icon (e.g. `Apple.svg`) MUST also exist

#### Scenario: Assets are valid standalone SVG
@e2e exclude asset sanity — PHPUnit samples files for SVG root element
- GIVEN any file in `img/icons/` or `img/logos/`
- WHEN the file is parsed
- THEN it MUST be a well-formed SVG document with an `<svg>` root
- AND it MUST NOT contain `<script>` elements or event-handler attributes (assets are served from the app directory to all users)

### Requirement: Consumption Contract via Nextcloud Image Path
Other Nextcloud apps MUST be able to consume the icons through Nextcloud's standard image-path API — `IURLGenerator::imagePath('nldesign', 'icons/{Name}.svg')` (or `OC.imagePath('nldesign', 'icons/{Name}.svg')` in frontend code) — without any nldesign-specific bootstrap. This URL contract only holds while the nldesign app is enabled; consumer documentation MUST state that consumers need a fallback (or an app dependency) for instances without nldesign.

#### Scenario: Icon resolves through the image-path API
@e2e exclude server-side image-path contract — static asset served by the NC app-image route, not a UI flow; verified by curl against the dev container and by the inventory regression test (tests/Unit/IconAssetsTest.php)
- GIVEN the nldesign app is enabled
- WHEN a page references the URL produced by `imagePath('nldesign', 'icons/Search.svg')`
- THEN the server MUST return the SVG with an image SVG content type and an HTTP 200
- AND the same MUST hold for logos via `imagePath('nldesign', 'logos/amsterdam.svg')`

#### Scenario: Disabled app means no icon URLs
@e2e exclude negative-availability statement — documented contract, verified manually at consumer level
- GIVEN the nldesign app is disabled
- WHEN a consumer app resolves an nldesign icon path
- THEN the asset MUST NOT be expected to load
- AND the integration documentation MUST instruct consumers to ship a fallback icon or declare a dependency on nldesign

### Requirement: Naming Stability
Icon and logo filenames are a public API consumed by other apps. Renaming or removing a bundled icon or logo MUST be treated as a breaking change: it MUST be recorded in the changelog with the old and new names, and upstream Amsterdam DS asset syncs MUST be explicit, reviewed changes (never a silent regenerate).

#### Scenario: Rename requires a changelog entry
@e2e exclude process requirement — enforced in review, backed by the inventory test failing on undocumented drift
- GIVEN a change that renames `Map.svg` to `MapPin.svg`
- WHEN the change is reviewed
- THEN the changelog MUST contain an entry naming both `Map.svg` and `MapPin.svg`
- AND `img/ICONS.md` MUST be updated in the same change so the inventory test passes

### Requirement: MPL-2.0 License Notice Travels With the Assets
The icons originate from `@amsterdam/design-system-assets`, licensed under the Mozilla Public License 2.0, while the app itself is EUPL-1.2. The MPL-2.0 attribution MUST remain co-located with the assets (in `img/ICONS.md`) and MUST be named in the app's user-facing documentation. No change may strip or relocate the notice away from the asset directories.

#### Scenario: License notice present alongside assets
@e2e exclude licensing artifact check — PHPUnit asserts the notice string in img/ICONS.md
- GIVEN the shipped app package
- WHEN `img/ICONS.md` is inspected
- THEN it MUST attribute the icons to `@amsterdam/design-system-assets`
- AND it MUST name the Mozilla Public License 2.0

### Requirement: Icon Documentation Is Reachable and Consistent
The README's icon-documentation link MUST resolve to the actual documentation file (`img/ICONS.md`), and the icon counts and consumption snippet MUST be consistent across `README.md`, `docs/reference/icons.md`, and `img/ICONS.md`.

#### Scenario: README link resolves
@e2e exclude docs link check — verified by docs link checker / PHPUnit on relative path existence
- GIVEN the README section "Icons"
- WHEN the "View Icon Documentation" link target is resolved relative to the repository root
- THEN the target file MUST exist (currently `img/ICONS.md`; the broken root-level `ICONS.md` reference MUST be corrected)

#### Scenario: Counts agree across documents
@e2e exclude docs consistency — PHPUnit compares counts in the three documents to the filesystem
- GIVEN the icon and logo counts stated in `README.md`, `docs/reference/icons.md`, and `img/ICONS.md`
- WHEN compared with the filesystem inventory
- THEN all stated counts MUST match the actual number of files

