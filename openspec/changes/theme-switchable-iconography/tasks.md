# Tasks — theme-switchable iconography

## 1. Icon-pack field in the design-system manifest

- [ ] 1.1 In `design-systems.json`, add `"icon_pack": ["rvo", "open-gemeenten", "den-haag"]` to the
  `nldesign` entry (its default pack list — the existing Dutch packs).
- [ ] 1.2 In `design-systems.json`, add `"icon_pack": "dsfr"` to the `lasuite` entry (French pack).
- [ ] 1.3 Leave `none`, `summer-breeze`, and `high-contrast` WITHOUT an `icon_pack` field (→ no
  pack served → Nextcloud stock icons; the switch is a no-op for them).
- [ ] 1.4 Validate the file parses (`php -r 'json_decode(file_get_contents("design-systems.json"), true) ?: exit(1);'`).

## 2. DSFR pack materialization in the build script

- [ ] 2.1 Add `@gouvfr/dsfr@1.15.1` as a **devDependency** in `package.json` (build-only; note in
  the dependency comment that it is consumed ONLY by `scripts/build-icons.js`, no runtime dep).
- [ ] 2.2 In `scripts/build-icons.js`, give each pack a `kind`: the three existing nc-vue packs are
  `kind: 'dataUri'`; add a new `dsfr` pack with `kind: 'glob'`, source
  `@gouvfr/dsfr/dist/icons`, set `dsfr`, upstream `Système de Design de l'État (DSFR) — @gouvfr/dsfr`,
  licence `Etalab-2.0`.
- [ ] 2.3 Implement a `glob` branch: recursively read every `*.svg` under the DSFR `dist/icons`
  tree, write each to `img/icons/dsfr/<basename>.svg` (basename without extension, no category
  prefix). Record `dsfr/<basename> → svg` in `svgByPath` so nothing else changes.
- [ ] 2.4 Fail (non-zero exit) if the DSFR glob yields zero SVGs, and fail on a duplicate basename
  (guards the DSFR "names unique across the set" invariant).
- [ ] 2.5 Ensure `img/icons/dsfr/` participates in the existing self-cleaning `resetDir` semantics
  (stale files from earlier builds MUST NOT survive), exactly like `rvo`/`open-gemeenten`/`den-haag`.
- [ ] 2.6 In `writeIconsMd()`, emit a `dsfr` per-set section (count, upstream, `Etalab-2.0`,
  `img/icons/dsfr/`) and a `dsfr` row in the licence-attribution table.
- [ ] 2.7 Run `npm run build:icons`; confirm `img/icons/dsfr/` is populated (~1038 SVGs) and
  `img/ICONS.md` regenerated with the DSFR section + Etalab-2.0 attribution.

## 3. Resolver in DesignSystemService

- [ ] 3.1 Add `getIconPacks(string $designSystemId): array` to `lib/Service/DesignSystemService.php`
  — returns the design system's `icon_pack` normalized to an ordered `string[]` (a scalar becomes a
  one-element list; absent → `[]`). Reuse the existing per-request cache.
- [ ] 3.2 Add `resolveActiveIconPacks(string $tokenSetId): array` implementing the precedence:
  appconfig `icon_pack` override (if set AND a valid pack directory) → token-set `icon_pack` (future
  field, honored if present) → design system `icon_pack` → `[]`. Inject `IConfig` (constructor) to
  read the override; keep the fallback safe (unknown design system → `[]`, never throws).
- [ ] 3.3 Add `resolveIconPath(string $name, string $tokenSetId): ?string` — returns
  `icons/<pack>/<name>.svg` for the first pack in `resolveActiveIconPacks()` whose
  `img/icons/<pack>/<name>.svg` exists on disk (via `getAppPath()`), else `null`. No path traversal:
  reject a `$name` containing `/`, `\`, or `..`.
- [ ] 3.4 Add `@spec openspec/specs/icon-packs/spec.md` tags to the new methods (spec-coverage gate).

## 4. Public-capability exposure

- [ ] 4.1 In `lib/Capabilities.php`, add `'iconPacks' => $this->designSystemService->resolveActiveIconPacks(tokenSetId: $tokenSetId)`
  to `buildPayload()` (using the already-read appconfig `token_set`, consistent with `designSystem`).
- [ ] 4.2 Add `'iconPacks' => []` to `minimalPayload()` (degrade path stays non-throwing).
- [ ] 4.3 Update the class docblock's key list / `theming-capability` note to mention the new key.

## 5. Read-only admin indicator

- [ ] 5.1 In the admin settings provider (`lib/Settings/Admin.php` or the initial-state producer for
  the panel), provide `activeIconPacks` (the resolved ordered list) and `iconPackSource` (`design-system`
  or `override`) via `IInitialState`.
- [ ] 5.2 In `templates/settings/admin.php` + `js/admin.js`, render a read-only indicator: "Active
  icon pack: <packs> (from <design system name> | admin override)". Include the honest limitation
  help text: this switches nldesign's bundled icon assets served via imagePath; it does NOT replace
  Nextcloud's core built-in icons beyond the theme's CSS restyling.
- [ ] 5.3 No write control for the override in this change (occ/appconfig only); the indicator shows
  the override when present.

## 6. Docs / backwards compatibility

- [ ] 6.1 Extend `img/ICONS.md` (generated header text in `writeIconsMd()`): document the icon-pack
  resolution model (active token set → design_system → icon_pack; appconfig override), that the
  `nldesign` design system's default pack is the existing Dutch packs (existing consumers unchanged),
  and the core-icons limitation.
- [ ] 6.2 Add a `CHANGELOG.md` entry: DSFR pack added (Etalab-2.0, ~1038 icons), listing it as an
  ADDED pack (no removals; existing Dutch packs and aliases untouched — non-breaking).
- [ ] 6.3 Confirm `docs/reference/icons.md` and `README.md` icon counts/sources stay consistent with
  the regenerated `img/ICONS.md` (the `icon-assets` consistency requirement).

## 7. Tests

- [ ] 7.1 `tests/Unit/DesignSystemServiceTest.php` — `getIconPacks` normalization (scalar→list,
  array, absent→`[]`); `resolveActiveIconPacks` precedence (override wins, design-system default,
  `none`→`[]`, unknown design system→`[]`); `resolveIconPath` finds a real file, returns `null` for
  a missing name, rejects traversal input.
- [ ] 7.2 `tests/Unit/IconAssetsTest.php` — the `dsfr` inventory: documented DSFR count in
  `img/ICONS.md` equals files on disk; every `img/icons/dsfr/*.svg` is well-formed SVG with no
  `<script>`/event-handler attributes; `img/ICONS.md` attributes `dsfr` as `Etalab-2.0`.
- [ ] 7.3 `tests/Unit/CapabilitiesTest.php` — the payload includes `iconPacks` as an array; for a
  `lasuite`-active instance it is `["dsfr"]`; for an `nldesign`-active instance it is the Dutch pack
  list; the minimal payload sets `iconPacks: []`.
- [ ] 7.4 Build-script check (existing icon build assertion): `img/icons/dsfr/` is non-empty and the
  build fails on a zero-icon / duplicate-basename pack.

## 8. Verify (dev instance, 8080)

- [ ] 8.1 `docker run --rm -v $PWD:/app -w /app <nc34-image> php vendor/bin/phpunit tests/Unit/DesignSystemServiceTest.php tests/Unit/IconAssetsTest.php tests/Unit/CapabilitiesTest.php` — all green.
- [ ] 8.2 `composer check:strict` passes (PHPCS, PHPMD, Psalm, PHPStan) — including SPDX docblocks
  and `@spec` tags on the new methods.
- [ ] 8.3 With the `lasuite` token set active, `curl -s http://localhost:8080/ocs/v2.php/cloud/capabilities?format=json -u admin:admin | jq '.ocs.data.capabilities.nldesign.iconPacks'`
  returns `["dsfr"]`; with an `nldesign` NL set active it returns `["rvo","open-gemeenten","den-haag"]`.
- [ ] 8.4 `curl -s -o /dev/null -w '%{http_code}' http://localhost:8080/apps/nldesign/img/icons/dsfr/<a-real-dsfr-name>.svg -u admin:admin`
  returns `200`; an existing `icons/rvo/rvo-home.svg` still returns `200` (backwards compat).
- [ ] 8.5 Set `occ config:app:set nldesign icon_pack --value=dsfr`; confirm `iconPacks` becomes
  `["dsfr"]` regardless of the active design system, and the admin indicator shows "admin override";
  unset it and confirm it reverts to the design-system default.
- [ ] 8.6 Open the nldesign admin settings page; confirm the read-only "Active icon pack" indicator
  renders with the correct pack(s), source, and the core-icons limitation help text.
