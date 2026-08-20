## 1. Mapper rework (lib/Service/DesignTokensMapper.php)

- [ ] 1.1 Rework `flatten()` into a typed walk: collect token leaves as
      `{path, value(raw mixed), resolvedType, deprecated?}` with `$type` inheritance from the
      nearest ancestor group (token's own `$type` wins). Keys starting with `$` remain excluded
      from paths; `$extensions` subtrees are never descended into.
- [ ] 1.2 Add an alias resolver: detect `{token.path}` string `$value`s, resolve transitively
      against the leaf table with a visited-set for cycle detection (error `alias-cycle` with
      the full chain), `alias-target-missing` for dangling refs, `alias-depth-exceeded` beyond
      10 hops. Per-token failures never abort the import.
- [ ] 1.3 Add per-type serializers: `color` (string literal passthrough for hex/rgb(); v2025.10
      object form — sRGB-family → hex, else skip `unsupported-color-space`), `dimension`
      (`{value, unit}` → concatenated CSS; legacy string passthrough), `fontFamily` (string or
      array → quoted CSS stack), `fontWeight` (number or keyword table → numeric), `typography`
      composite (map `fontFamily`/`fontSize`/`fontWeight`/`lineHeight` sub-values to their
      `--nldesign-*` targets where present, count the rest as skipped with sub-paths). Any other
      resolved shape ⇒ `unsupported-value-shape`; missing resolved type ⇒ `missing-type`.
- [ ] 1.4 Restructure `map()` return to
      `{declarations, imported, skipped: [{path, reason, detail?}], warnings: [{path, message}]}`;
      keep the legacy numeric counts derivable and consistent. `$deprecated` tokens import AND
      emit a warning (string message included when given).
- [ ] 1.5 Extract the package version: `$version` top-level member, then recognized
      `$extensions` version conventions, then a plain top-level `version` string; expose it on
      the map result as `packageVersion` (nullable).
- [ ] 1.6 Keep `getMappingTable()` published; extend the mapping table only where the new types
      need targets that already exist in the `--nldesign-*` vocabulary (no new CSS tokens in
      this change). SPDX + `@spec` tags on all touched methods.

## 2. Pipeline integration

- [ ] 2.1 `lib/Service/CustomTokenSetService.php`: persist `version` (when non-null) and import
      `warnings` into the set's `custom_token_sets` manifest entry; include both in the list
      payload. Manifest entries without them stay valid (backward compatible).
- [ ] 2.2 `lib/Controller/CustomTokenSetController.php`: pass through the structured
      `skipped`/`errors`/`warnings` arrays in the upload response (additive to the existing
      shape); reject with HTTP 422 when the mapped result yields zero declarations, including
      the full diagnostics in the error body.
- [ ] 2.3 `js/admin.js`: after a DTCG upload, render diagnostics grouped by reason (path +
      localized reason label) and deprecation warnings; show the recorded `version` in the
      custom-set list rows. Vanilla JS, i18n keys in English.

## 3. Unit tests (tests/unit/Service/DesignTokensMapperTest.php + fixtures)

- [ ] 3.1 Fixture corpus under `tests/unit/fixtures/dtcg/`: minimal legacy-draft doc (regression
      — old behavior preserved), group-`$type` inheritance doc, object-form color + dimension
      doc, composite typography doc, alias chain doc (3 hops), alias cycle doc, dangling alias
      doc, depth-bomb doc (>10 hops), `$deprecated` doc, `$extensions`-heavy doc, version-carrying
      doc, zero-yield doc, malformed JSON file.
- [ ] 3.2 Fixture snippets from REAL municipal DTCG packages: trimmed excerpts of the
      `@nl-design-system` utrecht and amsterdam npm token JSON (checked-in static fixtures, with
      source package + version noted in a fixture README comment) — assert plausible
      imported/skipped split and zero unexplained drops (every leaf accounted for).
- [ ] 3.3 Assert accounting invariant on every fixture: token leaves processed =
      imported + structured skipped/error entries.
- [ ] 3.4 Controller tests: response shape with structured diagnostics; zero-yield 422 with
      diagnostics body; version persisted to the manifest and returned by `list`
      (`tests/unit/Controller/CustomTokenSetControllerTest.php` — extend existing).
- [ ] 3.5 vitest (`js/`): diagnostics rendering groups by reason; version renders in the list;
      no rendering when arrays are absent (backward compat).

## 4. Verify

- [ ] 4.1 PHPUnit green in the nextcloud:34 container; vitest green; `composer check:strict`
      passes.
- [ ] 4.2 Live on 8080: upload the utrecht fixture package through the admin panel upload
      control; confirm the set is created, diagnostics render grouped by reason, version shows
      in the custom-set list, and `css/tokens/custom-*.css` contains resolved literal values
      (no `{...}` alias strings, no serialized objects).
- [ ] 4.3 Live on 8080: upload the alias-cycle fixture; confirm the valid tokens import, the
      cycle is reported with its chain, and the stored CSS omits the cycled token.
- [ ] 4.4 Live on 8080: apply the uploaded set and confirm the contrast-warning pipeline still
      evaluates it (resolved literals are now evaluable instead of `unevaluated`); then delete
      the test sets to leave the shared instance clean.
