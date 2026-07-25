---
kind: code
---

## Why

nldesign already bundles the Dutch-government icon sets (`img/icons/{rvo,open-gemeenten,den-haag}/`,
~1488 SVGs materialized from `@conduction/nextcloud-vue` packs) and exposes them to every app
through the `IURLGenerator::imagePath('nldesign', 'icons/{set}/{key}.svg')` contract
(`openspec/specs/icon-assets/spec.md`). It also already models a **design system per theme**:
`design-systems.json` keys each design system by `id` and declares an ordered CSS bundle, and
`token-sets.json` points each token set at its `design_system`. `DesignSystemService` resolves
`active token set → design_system → stylesheet bundle`, `CssInjectionService::inject()` loads that
bundle, and `Capabilities` advertises the resolved design system on the public capabilities
document.

What is missing is the **iconography half of that same switch**. When the `lasuite` design system
is active (a French-government / La Suite numérique context — see
`openspec/specs/lasuite-stack/spec.md`), the theme restyles colours and typography but the bundled
icon assets an app resolves through `imagePath` are still the Dutch RVO/OpenGemeenten/Den Haag
glyphs. A served French government must get **French-government icons (DSFR)**; a served Dutch
government must get **Dutch-government icons** — driven by the active design system / token set, not
hardcoded per consumer.

The French state ships an official, redistributable icon set: `@gouvfr/dsfr@1.15.1`
(`dist/icons/**/*.svg`, ~1038 categorized SVGs), licensed under the **Etalab Open Licence 2.0**
(`etalab-2.0`) — freely redistributable, unlike the Marianne typeface in the same package which is
FR-state-restricted (see `10-lasuite-parity-facts.md`; the Marianne restriction is out of scope
here — this change ships **icons only**). This lets the existing design-system switch also switch
the icon pack, with the same build-time materialization + attribution discipline that
`icon-assets` already mandates for the Dutch packs.

### Evidence
- `design-systems.json` — design systems keyed by `id`, each with an ordered `stylesheets` array;
  `nldesign`, `none`, `summer-breeze`, `high-contrast`, `lasuite`.
- `token-sets.json` — each set carries `design_system` (e.g. `lasuite` → `design_system: "lasuite"`).
- `lib/Service/DesignSystemService.php` — `getDesignSystem(id)`, `getTokenSetMeta(tokenSetId)`
  already implement the `active set → design_system → bundle` resolution this change extends.
- `lib/Service/CssInjectionService.php` — resolves the active set via
  `GroupThemingService::resolveTokenSetForRequest()` (preview → group → instance default).
- `lib/Capabilities.php` — public capability already emits `designSystem`; the natural place to
  emit the resolved icon pack for other apps.
- `scripts/build-icons.js` — materializes nc-vue data-URI packs into `img/icons/{set}/`; the
  natural place to also materialize the DSFR filesystem pack into `img/icons/dsfr/`.
- `openspec/specs/icon-assets/spec.md` — the naming-stability + attribution-travels contract the
  DSFR pack must adopt.
- `@gouvfr/dsfr@1.15.1` `dist/icons/**/*.svg` — 1038 icons, Etalab-2.0, redistributable.

## What Changes

- **NEW `icon-packs` capability spec** — a canonical model for theme-switchable iconography:
  - An optional `icon_pack` field on each design system in `design-systems.json` naming the icon
    directory (or ordered directories) under `img/icons/<pack>/` that the design system serves.
    `nldesign` → the existing Dutch packs `["rvo", "open-gemeenten", "den-haag"]`; `lasuite` →
    `["dsfr"]` (French); `none` and any design system without the field → **no pack** (Nextcloud
    stock icons — the icon switch is a no-op).
  - A documented **resolution path**: active token set → its `design_system` → its `icon_pack`,
    with an optional appconfig `icon_pack` admin override that wins when set to a valid pack.
  - A resolver (`DesignSystemService`) exposing (a) the active design system's ordered pack list
    and (b) `resolveIconPath(name)` returning the `imagePath`-relative path for the first pack that
    contains `<pack>/<name>.svg` — so the theme and other apps can request "the active pack's icon
    by name".
  - Public-capability exposure: `Capabilities` gains an `iconPacks` key (the resolved ordered pack
    list) so unauthenticated clients / other apps can read the active pack.
  - A **read-only admin indicator** surfacing which icon pack the active theme uses (and whether it
    comes from the design system or an admin override).
- **NEW build materialization of the DSFR pack** — `scripts/build-icons.js` additionally
  materializes `@gouvfr/dsfr@1.15.1` `dist/icons/**/*.svg` into `img/icons/dsfr/{name}.svg`
  (basename key; DSFR names are unique across the set). `@gouvfr/dsfr` becomes a **devDependency**
  consumed only by the build script — no runtime PHP/JS depends on it. The Etalab-2.0 attribution
  travels into `img/ICONS.md` exactly as the CC0/EUPL attribution does for the Dutch packs. The
  build fails (non-zero exit) if the DSFR pack yields zero icons.
- **MODIFIED `icon-assets`** — the materialization requirement, the naming-stability contract, and
  the licence-attribution requirement are extended to include the `dsfr` pack (Etalab-2.0). The
  imagePath consumption contract is unchanged; a note documents that consumers who want the
  *active-theme* pack (rather than a fixed pack) should resolve it via the `icon-packs` resolver /
  capability instead of hardcoding `icons/rvo/...`.
- **Honest limitation (documented, not a defect)** — this switches nldesign's **own bundled icon
  assets** (imagePath / capability consumers). It does **not** force-replace Nextcloud core's
  built-in icon set (Material-style core icons) beyond what the active theme's CSS already
  restyles. Stated in `icon-packs` spec, `img/ICONS.md`, and the admin indicator help text.
- **Backwards compatibility (non-BREAKING)** — existing `img/icons/{rvo,open-gemeenten,den-haag}/`
  files, the legacy Amsterdam aliases, and every `imagePath('nldesign', 'icons/rvo/...')` consumer
  keep working byte-for-byte. The `nldesign` design system's default `icon_pack` maps to those
  Dutch packs, so the resolver returns them for every existing NL theme. A design system with no
  `icon_pack` behaves exactly as today (stock NC icons; no pack served).

## Impact

- `design-systems.json` — add optional `icon_pack` to the `nldesign` (Dutch packs) and `lasuite`
  (`dsfr`) entries; omit for `none`/`summer-breeze`/`high-contrast` (no pack → NC stock).
- `scripts/build-icons.js` — add a filesystem-glob pack branch materializing `@gouvfr/dsfr`
  `dist/icons/**/*.svg` → `img/icons/dsfr/`; regenerate the DSFR section + Etalab-2.0 attribution
  in `img/ICONS.md`.
- `package.json` — add `@gouvfr/dsfr` as a devDependency (build-only).
- `img/icons/dsfr/` — new materialized pack (~1038 SVGs, build output, not hand-checked-in).
- `img/ICONS.md` — DSFR section, Etalab-2.0 attribution, the icon-pack resolution model, and the
  core-icons limitation note.
- `lib/Service/DesignSystemService.php` — add `getIconPacks(designSystemId)`,
  `resolveActiveIconPacks(tokenSetId)`, `resolveIconPath(name, tokenSetId)`, honoring the appconfig
  `icon_pack` override.
- `lib/Capabilities.php` — add the `iconPacks` key to the public payload (and to the minimal
  degrade payload).
- `templates/settings/admin.php` + `js/admin.js` (or an `IInitialState` value) — read-only "active
  icon pack" indicator.
- `lib/Settings/Admin.php` (initial-state) — provide the resolved pack list to the admin panel.
- `openspec/specs/icon-packs/spec.md` — NEW canonical spec.
- `openspec/specs/icon-assets/spec.md` — MODIFIED (dsfr in materialization / naming / attribution).
- Tests: `tests/Unit/DesignSystemServiceTest.php` (resolver + override), `tests/Unit/IconAssetsTest.php`
  (dsfr inventory), `tests/Unit/CapabilitiesTest.php` (`iconPacks` key), build-script check.
