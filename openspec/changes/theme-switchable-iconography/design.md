# Design — theme-switchable iconography

## Problem

The design-system switch (`active token set → design_system → CSS bundle`) already exists and is
resolved by `DesignSystemService` + `CssInjectionService`. Iconography is not part of that switch:
apps resolve a **fixed** pack (`imagePath('nldesign','icons/rvo/...')`) regardless of the active
theme, so a French-government (`lasuite`) instance still serves Dutch RVO glyphs. We want the icon
pack to travel with the design system, reusing the existing resolution chain and the existing
build/attribution discipline (`icon-assets`).

## Key decision 1 — `icon_pack` lives on the DESIGN SYSTEM, not the token set

The icon pack is bound to the **design system**, not the individual token set. Rationale:

- A design system *is* the visual identity family (Dutch NL Design vs French DSFR/La Suite). The
  iconography is a property of that family, exactly like the stylesheet bundle already declared on
  the design system.
- ~39 Dutch municipality token sets (`amsterdam`, `utrecht`, `denhaag`, …) all share
  `design_system: "nldesign"` (or inherit it). They should all get the Dutch packs from **one**
  place; putting `icon_pack` on each token set would duplicate it 39× and invite drift.
- It mirrors the shipped `stylesheets` model precisely, so the resolver is a one-line analogue of
  `getDesignSystem(id)['stylesheets']`.

**Optional token-set override (deferred, not built here):** a token set *could* carry its own
`icon_pack` to override its design system's default (e.g. a municipality that wants a bespoke pack).
The resolver's precedence leaves room for it (`token set icon_pack → design system icon_pack`), but
this change ships only the design-system field to avoid 39-way surface. The spec documents the
precedence so a later change can add the token-set field without a breaking reinterpretation.

**Admin override (built, cheap):** an appconfig key `icon_pack` (occ/config-settable) overrides the
resolved pack instance-wide when set to a valid pack directory. This satisfies the "allow an admin
override if cheap" directive without new write UI — it is one appconfig read in the resolver plus
the read-only indicator showing whether the override is active. Recommendation surfaced in docs:
**keep the pack tied to the design system for coherence**; the override exists for edge cases.

## Key decision 2 — `icon_pack` value is an ORDERED LIST of pack directories

The `nldesign` design system has **three** Dutch packs (`rvo`, `open-gemeenten`, `den-haag`), so a
single-string field cannot express its default. The field therefore accepts **either** a string
(shorthand for a one-element list) **or** an array of pack directory names in preference order:

```json
{ "id": "nldesign", "icon_pack": ["rvo", "open-gemeenten", "den-haag"], … }
{ "id": "lasuite",  "icon_pack": "dsfr", … }
{ "id": "none" }                                    // no icon_pack → NC stock icons
```

`resolveIconPath(name)` searches the ordered list and returns the first `<pack>/<name>.svg` that
exists on disk. This preserves the existing multi-pack Dutch model while making the single French
pack trivial. Each element MUST be a directory that exists under `img/icons/` — the resolver skips
(and the build/test flags) any unknown directory.

## Key decision 3 — resolver in `DesignSystemService`, exposed via `Capabilities`

The resolver extends the existing `DesignSystemService` (it already owns `getAppPath()`,
`getDesignSystem()`, `getTokenSetMeta()`, and per-request caching) rather than a new
`IconPackService`, because every input it needs is already there and a new service would duplicate
the manifest reads. New methods:

- `getIconPacks(string $designSystemId): array` — the normalized ordered pack list for a design
  system (`[]` when the field is absent → stock icons). Pure manifest read, cached.
- `resolveActiveIconPacks(string $tokenSetId): array` — the full resolution:
  1. If appconfig `icon_pack` is set and names a valid pack directory → `[that pack]` (admin
     override wins).
  2. Else if the token set carries its own `icon_pack` (future field) → that list.
  3. Else the token set's `design_system`'s `icon_pack` list.
  4. Else `[]` (no pack).
- `resolveIconPath(string $name, string $tokenSetId): ?string` — returns the `imagePath`-relative
  path `icons/<pack>/<name>.svg` for the first pack in `resolveActiveIconPacks()` that contains the
  file, else `null`. Filesystem existence check via `getAppPath()`.

**Active token set source.** The resolver takes the token set id as a parameter so callers choose
the precedence appropriate to their context: `CssInjectionService`-style callers pass
`GroupThemingService::resolveTokenSetForRequest()` (preview → group → instance default);
`Capabilities` passes the appconfig `token_set` exactly as it already does for `designSystem` (the
public capability is instance-global, not per-user). This mirrors the existing split in the
codebase rather than inventing a new one, and keeps `DesignSystemService` free of a
`GroupThemingService` dependency (avoiding a DI cycle — `GroupThemingService`/`CssInjectionService`
already depend on `DesignSystemService`).

**Exposure.** `Capabilities::buildPayload()` adds `iconPacks` = `resolveActiveIconPacks(token_set)`
(ordered array; `[]` when no pack). Other apps and the theme read it from
`/ocs/v2.php/cloud/capabilities` → `capabilities.nldesign.iconPacks`, then request
`imagePath('nldesign', 'icons/<pack>/<name>.svg')` for the first pack. PHP/template consumers can
call `resolveIconPath()` directly. The minimal degrade payload sets `iconPacks: []`.

## Key decision 4 — DSFR materialization is a filesystem-glob pack, not a data-URI pack

The Dutch packs come from nc-vue **data-URI catalogue modules** (`src/icons/rvo.js` etc.), decoded
by `decodeDataUri()`. DSFR is different: `@gouvfr/dsfr` ships **real SVG files** under
`dist/icons/<category>/<name>.svg`. `build-icons.js` gains a second pack kind:

- **`kind: 'dataUri'`** (existing) — `rvo`, `open-gemeenten`, `den-haag`.
- **`kind: 'glob'`** (new) — `dsfr`: recursively read `node_modules/@gouvfr/dsfr/dist/icons/**/*.svg`,
  write each to `img/icons/dsfr/<basename>.svg`. DSFR icon basenames are **unique across the whole
  set** (they are globally referenced as `.fr-icon-<name>` in DSFR CSS), so the flat `dsfr/<name>`
  layout is collision-free; the build MUST fail if a duplicate basename is encountered (guards a
  future upstream that breaks that invariant) and MUST fail if zero SVGs are found.

`img/icons/dsfr/` is **build output** (regenerated, self-cleaning via `resetDir` semantics), like
the Dutch pack dirs and unlike `img/logos/`. `@gouvfr/dsfr` is a **devDependency** only. The
Etalab-2.0 licence + upstream attribution are written into the regenerated `img/ICONS.md` alongside
the CC0/EUPL rows, and referenced from the DSFR section — attribution travels with the assets, per
`icon-assets`.

## Key decision 5 — honest core-icons limitation

nldesign owns only the assets it serves through `imagePath` and its capability. Nextcloud's own
built-in icons (navigation, files, the Material-style core set) are provided by core and by each
app; they are restyled only insofar as the active theme's CSS already recolours them. Switching the
`icon_pack` does **not** substitute core icons. This is stated in the `icon-packs` spec, in
`img/ICONS.md`, and in the admin indicator's help text so no operator expects a full core-icon swap.

## Data flow

```
request
  └─ active token set id  (Capabilities: appconfig token_set;
     │                      CSS path: GroupThemingService::resolveTokenSetForRequest())
     ▼
  DesignSystemService::resolveActiveIconPacks(tokenSetId)
     ├─ appconfig 'icon_pack' override?  ── yes ─▶ [override]
     ├─ token set 'icon_pack' (future)?  ── yes ─▶ [that list]
     ├─ design_system 'icon_pack'?       ── yes ─▶ ["rvo","open-gemeenten","den-haag"] | ["dsfr"]
     └─ none                                     ─▶ []   (NC stock icons)
        ▼
  Capabilities.nldesign.iconPacks  +  resolveIconPath(name) → icons/<pack>/<name>.svg
        ▼
  consumer app:  imagePath('nldesign', 'icons/<pack>/<name>.svg')
```

## Scope / non-goals

- No Vue, no new DB table — appconfig + manifest files + a build-script branch + a read-only
  admin indicator, consistent with the app's no-Vue / IConfig architecture.
- No Marianne font work (FR-state-restricted; out of scope — icons only; tracked separately).
- No core-icon replacement (limitation above).
- The token-set-level `icon_pack` override field is documented in the precedence but not shipped.
- No per-consumer icon-name mapping table (a French icon named `arrow-right-line` vs a Dutch
  `rvo-pijl-rechts` are different names): consumers that want theme-agnostic names resolve by their
  own semantic key against the active pack, or ship a fallback — documented, not solved here.
