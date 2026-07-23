# Icon Assets — Icons From nc-vue Delta

**Spec refs**: `icon-assets`; `nextcloud-vue/src/icons/ATTRIBUTION.md` (canonical licence record);
`@amsterdam/design-system-assets` `LICENSE.md` (proprietary notice motivating removal)
**Standards**: EUPL-1.2, CC0-1.0 (RVO, OpenGemeenten), SVG 1.1/2 well-formedness, semver
breaking-change discipline for public asset filenames

## ADDED Requirements

### Requirement: Build-Time Materialization from nc-vue Icon Packs

The icon inventory MUST be generated at build time from the EUPL-compatible NL-government icon
packs bundled in `@conduction/nextcloud-vue` (`src/icons/rvo.js`, `src/icons/openGemeenten.js`,
`src/icons/denHaag.js` — data-URI catalogue modules). `scripts/build-icons.js` MUST decode each
pack entry's `data:image/svg+xml` URI into a standalone SVG file at
`img/icons/{set}/{key}.svg`, where `{set}` is `rvo`, `open-gemeenten`, or `den-haag` and `{key}`
is the pack entry key. `@conduction/nextcloud-vue` MUST be a devDependency consumed only by the
build script — no runtime PHP or JS code may depend on it. The build MUST NOT copy any asset
from `@amsterdam/design-system-assets`, MUST NOT write into `img/logos/`, and MUST fail (non-zero
exit) if any pack yields zero icons.

#### Scenario: Packs are materialized to set-prefixed SVG files
@e2e exclude build tooling — verified by running npm run build:icons and by the PHPUnit inventory test
- GIVEN `@conduction/nextcloud-vue` is installed and `npm run build:icons` runs
- WHEN the script processes the `rvo`, `openGemeenten`, and `denHaag` pack modules
- THEN every pack entry MUST exist as `img/icons/{set}/{key}.svg` with byte content equal to the
  decoded data-URI payload
- AND the per-set file counts MUST equal the pack entry counts
- AND no file in `img/icons/` MUST originate from `@amsterdam/design-system-assets`

#### Scenario: Regeneration is idempotent and self-cleaning
@e2e exclude build tooling — script-level check
- GIVEN a previous build left files in `img/icons/`
- WHEN `npm run build:icons` runs again
- THEN the resulting `img/icons/` tree MUST contain exactly the current pack entries plus the
  configured legacy aliases — stale files from earlier builds MUST NOT survive

#### Scenario: Logos are not build output
@e2e exclude build tooling — script-level check plus git status inspection
- GIVEN `img/logos/` contains the 23 checked-in organisation logos
- WHEN `npm run build:icons` runs
- THEN no file in `img/logos/` MUST be created, modified, or deleted

### Requirement: Legacy Amsterdam Filename Aliases (One-Release Deprecation)

For exactly one release, the build MUST additionally materialize compatibility aliases for
legacy Amsterdam Design System filenames from the curated map `scripts/icon-aliases.json`
(legacy PascalCase name → `{set}/{key}` replacement). Each alias MUST be written to the legacy
top-level path `img/icons/{Name}.svg` as a byte-identical copy of the mapped replacement SVG —
the shipped bytes are always the EUPL-compatible replacement artwork, never Amsterdam artwork.
Legacy names absent from the map MUST NOT resolve (HTTP 404). `img/ICONS.md` and `CHANGELOG.md`
MUST both state that the aliases are removed in the next minor release; that removal MUST empty
the alias map and stop emitting top-level files.

#### Scenario: Aliased legacy URL serves replacement artwork
@e2e exclude static asset contract — curl against the dev container + PHPUnit alias test
- GIVEN `scripts/icon-aliases.json` maps `Star` to a replacement pack icon
- WHEN a consumer requests the URL for `imagePath('nldesign', 'icons/Star.svg')`
- THEN the server MUST return HTTP 200 with SVG content byte-identical to the mapped
  `img/icons/{set}/{key}.svg` file
- AND the served bytes MUST NOT be the removed Amsterdam artwork

#### Scenario: Unmapped legacy name is gone
@e2e exclude negative contract — curl against the dev container
- GIVEN a legacy Amsterdam icon name that has no entry in `scripts/icon-aliases.json`
- WHEN its old URL `img/icons/{Name}.svg` is requested
- THEN the server MUST return HTTP 404
- AND the changelog MUST list that name as removed without equivalent

#### Scenario: Alias lifecycle is documented with a deadline
@e2e exclude docs artifact check — PHPUnit asserts the deadline strings
- GIVEN the shipped `img/ICONS.md` and `CHANGELOG.md`
- WHEN they are inspected
- THEN both MUST contain the alias table (or reference it) and state removal in the next minor
  release

### Requirement: Upstream Licence Attribution Travels With the Assets

`img/ICONS.md` MUST name each bundled icon set with its upstream project and licence and MUST
reference nc-vue's `src/icons/ATTRIBUTION.md` as the canonical licence record. The bundled icons
originate from three NL-government sets redistributed via `@conduction/nextcloud-vue`: RVO
(CC0-1.0), OpenGemeenten (CC0-1.0), and Gemeente Den Haag (EUPL-1.2), as recorded in that
ATTRIBUTION.md. The proprietary `@amsterdam/design-system-assets` package MUST NOT be
named as a current source anywhere in `img/`, `docs/`, `README.md`, or `package.json`
dependencies, and no shipped asset may derive from it. No change may strip or relocate the
licence attribution away from the asset directories.

#### Scenario: Licence attribution present and correct
@e2e exclude licensing artifact check — PHPUnit asserts the attribution strings in img/ICONS.md
- GIVEN the shipped app package
- WHEN `img/ICONS.md` is inspected
- THEN it MUST attribute the `rvo` set as CC0-1.0, the `open-gemeenten` set as CC0-1.0, and the
  `den-haag` set as EUPL-1.2
- AND it MUST reference `@conduction/nextcloud-vue` `src/icons/ATTRIBUTION.md`
- AND it MUST NOT claim MPL-2.0 for the icons nor name `@amsterdam/design-system-assets` as a
  current source

#### Scenario: No proprietary Amsterdam assets ship
@e2e exclude licensing gate — grep/PHPUnit assertions
- GIVEN the repository at release
- WHEN `package.json` dependencies and the `img/` tree are inspected
- THEN `@amsterdam/design-system-assets` and `@amsterdam/design-system-react-icons` MUST NOT be
  dependencies
- AND every top-level `img/icons/*.svg` file MUST correspond to an entry in
  `scripts/icon-aliases.json` (i.e. no orphaned Amsterdam-era file survives)

## MODIFIED Requirements

### Requirement: Bundled Icon and Logo Inventory

The app MUST bundle the NL-government icon sets materialized from the `@conduction/nextcloud-vue`
packs as individual SVG files under `img/icons/{set}/` (sets `rvo`, `open-gemeenten`,
`den-haag`; approximately 1488 icons at authoring time — the authoritative counts are the pack
entry counts of the installed nc-vue version, never a hardcoded number) plus the organisation
logos in `img/logos/` (23 logos, static checked-in huisstijl assets tied to token sets). Icon
filenames MUST be the pack entry keys (kebab-case slugs). The documented inventory in
`img/ICONS.md` MUST be generated by the same build that writes the files and MUST match the
filesystem, including the legacy alias files while the deprecation window lasts.

#### Scenario: Documented icons exist on disk
@e2e exclude static asset inventory — PHPUnit test compares docs to filesystem
- GIVEN the per-set icon inventories listed in `img/ICONS.md`
- WHEN each name is resolved to `img/icons/{set}/{key}.svg`
- THEN every documented name MUST exist as a file
- AND the per-set totals on disk MUST equal the documented totals
- AND the logo total on disk MUST equal the documented total (23)

#### Scenario: Assets are valid standalone SVG
@e2e exclude asset sanity — PHPUnit samples files for SVG root element
- GIVEN any file in `img/icons/` (including alias files) or `img/logos/`
- WHEN the file is parsed
- THEN it MUST be a well-formed SVG document with an `<svg>` root
- AND it MUST NOT contain `<script>` elements or event-handler attributes (assets are served from
  the app directory to all users)

### Requirement: Consumption Contract via Nextcloud Image Path

Other Nextcloud apps MUST be able to consume the icons through Nextcloud's standard image-path
API — `IURLGenerator::imagePath('nldesign', 'icons/{set}/{key}.svg')` (or
`OC.imagePath('nldesign', 'icons/{set}/{key}.svg')` in frontend code) — without any
nldesign-specific bootstrap, and the logos via `imagePath('nldesign', 'logos/{name}.svg')`.
Vue-based consumers SHOULD prefer importing the packs from `@conduction/nextcloud-vue` directly
(e.g. `CnIconBrowser` `url-icons`); the imagePath contract exists for PHP/template and non-Vue
consumers. This URL contract only holds while the nldesign app is enabled; consumer documentation
MUST state that consumers need a fallback (or an app dependency) for instances without nldesign.

#### Scenario: Icon resolves through the image-path API
@e2e exclude server-side image-path contract — static asset served by the NC app-image route, not a UI flow; verified by curl against the dev container and by the inventory regression test (tests/Unit/IconAssetsTest.php)
- GIVEN the nldesign app is enabled
- WHEN a page references the URL produced by `imagePath('nldesign', 'icons/rvo/home.svg')` (any
  existing pack key)
- THEN the server MUST return the SVG with an image SVG content type and an HTTP 200
- AND the same MUST hold for logos via `imagePath('nldesign', 'logos/amsterdam.svg')`

#### Scenario: Disabled app means no icon URLs
@e2e exclude negative-availability statement — documented contract, verified manually at consumer level
- GIVEN the nldesign app is disabled
- WHEN a consumer app resolves an nldesign icon path
- THEN the asset MUST NOT be expected to load
- AND the integration documentation MUST instruct consumers to ship a fallback icon or declare a
  dependency on nldesign

### Requirement: Naming Stability

Icon and logo filenames are a public API consumed by other apps and MUST be kept stable: within
an installed nc-vue pack version, renaming or removing a bundled icon or logo MUST be treated as
a breaking change — recorded in the changelog with the old and new names, with `img/ICONS.md`
regenerated in the same change so the inventory test passes. Upgrading the
`@conduction/nextcloud-vue` dependency (which can change pack contents) MUST be an explicit,
reviewed change whose diff of added/removed keys is listed in the changelog — never a silent
regenerate. Removing the legacy Amsterdam aliases at the end of the deprecation window is a
planned, pre-announced break and MUST reference the release that announced it.

#### Scenario: Pack upgrade requires an inventory diff in the changelog
@e2e exclude process requirement — enforced in review, backed by the inventory test failing on undocumented drift
- GIVEN a change that bumps the `@conduction/nextcloud-vue` version and regenerates `img/icons/`
- WHEN the change is reviewed
- THEN the changelog MUST list every icon key that was added or removed by the regeneration
- AND `img/ICONS.md` MUST be regenerated in the same change so the inventory test passes

#### Scenario: Logo rename requires a changelog entry
@e2e exclude process requirement — enforced in review
- GIVEN a change that renames `img/logos/utrecht.svg`
- WHEN the change is reviewed
- THEN the changelog MUST contain an entry naming both the old and new filenames
- AND any `token-sets.json` `theming.logo` reference to the old name MUST be updated in the same
  change

### Requirement: Icon Documentation Is Reachable and Consistent

The README's icon-documentation link MUST resolve to the actual documentation file
(`img/ICONS.md`), and the icon counts, set names, licences, and consumption snippet MUST be
consistent across `README.md`, `docs/reference/icons.md`, and `img/ICONS.md` — all reflecting
the nc-vue-sourced sets and never the removed Amsterdam set as a current source.

#### Scenario: README link resolves
@e2e exclude docs link check — verified by docs link checker / PHPUnit on relative path existence
- GIVEN the README section "Icons"
- WHEN the icon documentation link target is resolved relative to the repository root
- THEN the target file MUST exist (`img/ICONS.md`)

#### Scenario: Counts and sources agree across documents
@e2e exclude docs consistency — PHPUnit compares counts in the three documents to the filesystem
- GIVEN the icon and logo counts and set attributions stated in `README.md`,
  `docs/reference/icons.md`, and `img/ICONS.md`
- WHEN compared with the filesystem inventory
- THEN all stated counts MUST match the actual number of files
- AND no document may present the Amsterdam Design System set as currently bundled

## REMOVED Requirements

### Requirement: MPL-2.0 License Notice Travels With the Assets

**Reason**: The claim was factually wrong — `@amsterdam/design-system-assets` `LICENSE.md`
declares the assets proprietary to the City of Amsterdam ("The open-source licence does NOT
apply to files in this directory"), not MPL-2.0, and the set is removed by this change. Replaced
by "Upstream Licence Attribution Travels With the Assets" covering the CC0-1.0/EUPL-1.2 nc-vue
packs.
