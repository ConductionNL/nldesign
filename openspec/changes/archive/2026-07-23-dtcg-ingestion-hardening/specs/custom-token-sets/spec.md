# Custom Token Sets — DTCG v2025.10 Ingestion Delta

**Spec refs**: `custom-token-sets`, `token-set-contrast-audit` (contrast pipeline unchanged),
change `upstream-token-freshness` (consumes recorded versions), change
`compliance-evidence-report` (reports the recorded version)
**Standards**: W3C DTCG Design Tokens Format Module v2025.10 (stable 2025-10-28) — `$value`,
`$type` (incl. group inheritance), `$description`, `$extensions`, `$deprecated`, alias syntax
`{token.path}`; NL Design System theme-package semver conventions

## MODIFIED Requirements

### Requirement: W3C Design Tokens JSON Import

The upload control MUST also accept a JSON file in the W3C DTCG Design Tokens Format Module
v2025.10. The importer MUST implement the format's core semantics, not a lookalike subset:

- **`$type` resolution with group inheritance**: a token's type is its own `$type`, else the
  `$type` of the nearest ancestor group declaring one. Tokens whose resolved type is absent MUST
  be skipped with reason `missing-type` (never guessed from the value shape).
- **Typed `$value` handling** for at least: `color` (legacy string literals `#rrggbb`/`rgb()`
  AND the v2025.10 object form — sRGB-family color spaces serialized to hex; other color spaces
  skipped with reason `unsupported-color-space`), `dimension` (`{value, unit}` object serialized
  as `<value><unit>`; legacy string form accepted), `fontFamily` (string or array serialized as
  a quoted CSS font stack), `fontWeight` (number, or v2025.10 weight keyword normalized to its
  numeric value), and **composite `typography`** (each sub-property — `fontFamily`, `fontSize`,
  `fontWeight`, `lineHeight` — mapped individually where a corresponding `--nldesign-*` target
  exists, unmapped sub-properties counted as skipped).
- **`$extensions`** MUST be ignored without error and without affecting mapping
  (passthrough-ignore). **`$deprecated`** (boolean `true` or string form) on an imported token
  MUST surface a warning naming the token path (and the string message when given) while still
  importing the value.
- Recognized tokens MUST be mapped to `--nldesign-*` variables via the published mapping table;
  unmapped tokens MUST be skipped and counted with reason `unmapped-path`. The mapped result
  MUST pass through the same whitelist, serialization, and storage pipeline as CSS uploads
  (`CustomTokenSetValidator`; atomic write to `css/tokens/custom-*.css`) — DTCG hardening
  changes what the mapper understands, never where or how sets are stored.

#### Scenario: DTCG color tokens map onto the nldesign vocabulary

@e2e exclude mapping logic — PHPUnit on the mapper with DTCG fixtures
- GIVEN a `huisstijl.tokens.json` containing `{ "color": { "primary": { "$type": "color", "$value": "#154273" }, "on-primary": { "$type": "color", "$value": "#ffffff" } } }`
- WHEN the admin uploads it with display name "Eigen huisstijl"
- THEN `css/tokens/custom-eigen-huisstijl.css` MUST contain `--nldesign-color-primary: #154273` and `--nldesign-color-primary-text: #ffffff`
- AND the response MUST report `imported: 2, skipped: 0`

#### Scenario: Group-level $type is inherited by descendant tokens

@e2e exclude mapping logic — PHPUnit on the mapper
- GIVEN a document `{ "color": { "$type": "color", "primary": { "$value": "#154273" } } }`
  where the token itself declares no `$type`
- WHEN the document is imported
- THEN `color.primary` MUST resolve type `color` from its group
- AND MUST be imported as `--nldesign-color-primary: #154273`

#### Scenario: v2025.10 object color and dimension values serialize to CSS

@e2e exclude mapping logic — PHPUnit on the mapper
- GIVEN a `color.primary` token whose `$value` is the object form with an sRGB color space and
  hex fallback for `#154273`, and a `dimension.border-radius` token with
  `$value: { "value": 8, "unit": "px" }`
- WHEN the document is imported
- THEN the emitted declarations MUST be `--nldesign-color-primary: #154273` and
  `--nldesign-border-radius: 8px`
- AND a color token in an unsupported color space MUST be skipped with reason
  `unsupported-color-space` and its path listed

#### Scenario: Composite typography token maps sub-values individually

@e2e exclude mapping logic — PHPUnit on the mapper
- GIVEN a `typography.font-family`-adjacent composite token of `$type: "typography"` whose
  `$value.fontFamily` is `["Fira Sans", "sans-serif"]`
- WHEN the document is imported
- THEN `--nldesign-font-family` MUST be emitted as the serialized font stack
- AND composite sub-properties without an `--nldesign-*` target MUST be counted as skipped, each
  with its sub-path

#### Scenario: Unmapped DTCG tokens degrade to skipped counts

@e2e exclude mapping tolerance — PHPUnit on the mapper
- GIVEN a DTCG file containing a recognized `color.primary` token and an unrecognized
  `shadow.elevation-1` token
- WHEN the admin uploads it
- THEN the upload MUST succeed with `imported: 1, skipped: 1`
- AND the skipped token path MUST be listed in the response with reason `unmapped-path`

#### Scenario: Deprecated token imports with a surfaced warning

@e2e exclude mapping logic — PHPUnit on the mapper
- GIVEN a mapped token carrying `"$deprecated": "Use color.brand.primary instead"`
- WHEN the document is imported
- THEN the token MUST still be imported
- AND the response `warnings` MUST contain the token path and the deprecation message
- AND the admin panel MUST display the warning after upload

#### Scenario: Malformed JSON is rejected

@e2e exclude parse guard — PHPUnit on the controller
- GIVEN a `.json` upload that is not valid JSON
- WHEN the admin uploads it
- THEN the upload MUST be rejected with HTTP 422 and a localized parse error
- AND no file MUST be written

## ADDED Requirements

### Requirement: DTCG Alias Resolution

The importer MUST resolve DTCG alias values — a `$value` of the form `{token.path}` referencing
another token in the same document — before mapping and serialization. Resolution MUST follow
transitive chains (an alias pointing at an alias) to the terminal concrete value, applying the
terminal token's resolved `$type`. Resolution MUST detect cycles: a chain that revisits a path
MUST fail that token (never the whole import) with reason `alias-cycle` and the full cycle path
in document order (e.g. `a.b -> c.d -> a.b`). An alias whose target path does not exist MUST
fail that token with reason `alias-target-missing` naming the missing path. Alias chains longer
than a documented bound (at least 10 hops) MUST fail with reason `alias-depth-exceeded`.

#### Scenario: Transitive alias chain resolves to the concrete value

@e2e exclude resolution logic — PHPUnit on the mapper
- GIVEN a document where `color.primary.$value` is `{brand.blue}` and `brand.blue.$value` is
  `{palette.blue-500}` and `palette.blue-500` is a concrete `color` token `#154273`
- WHEN the document is imported
- THEN `--nldesign-color-primary: #154273` MUST be emitted
- AND `imported` MUST count the aliased token as one imported token

#### Scenario: Alias cycle produces an actionable per-token error

@e2e exclude resolution logic — PHPUnit on the mapper
- GIVEN a document where `a.x.$value` is `{b.y}` and `b.y.$value` is `{a.x}` alongside an
  unrelated valid `color.primary` token
- WHEN the document is imported
- THEN the upload MUST still succeed for the valid token
- AND the response `errors` MUST contain an entry with reason `alias-cycle` and the cycle path
  `a.x -> b.y -> a.x`

#### Scenario: Dangling alias is reported with its missing target

@e2e exclude resolution logic — PHPUnit on the mapper
- GIVEN a token whose `$value` is `{does.not.exist}`
- WHEN the document is imported
- THEN that token MUST appear in `errors` with reason `alias-target-missing` and the path
  `does.not.exist`
- AND no declaration MUST be emitted for it

### Requirement: DTCG Import Diagnostics

Every token that is not imported MUST be accounted for in the upload response as a structured
entry `{path, reason, detail?}` — reasons at minimum: `unmapped-path`, `missing-type`,
`unsupported-color-space`, `unsupported-value-shape`, `alias-cycle`, `alias-target-missing`,
`alias-depth-exceeded`. Diagnostics MUST be aggregated (one response listing all issues, not
fail-on-first). The existing numeric `imported`/`skipped` counts MUST remain and MUST be
consistent with the structured lists. The admin panel MUST render the diagnostics grouped by
reason after an upload. Import diagnostics are informational for a partially-mappable document;
the upload only fails outright (HTTP 422) when the document is malformed JSON or yields zero
imported declarations.

#### Scenario: Mixed-quality package yields one aggregated diagnosis

@e2e exclude aggregation logic — PHPUnit on the mapper/controller
- GIVEN a real municipal DTCG package snippet containing mappable colors, unmapped component
  tokens, one dangling alias, and one typeless token
- WHEN the admin uploads it
- THEN the response MUST list every non-imported token path exactly once with its reason
- AND `imported + |structured skip/error entries|` MUST equal the number of token leaves
  processed
- AND the upload MUST succeed because at least one declaration was imported

#### Scenario: Zero-yield import fails actionably

@e2e exclude guard branch — PHPUnit on the controller
- GIVEN a syntactically valid DTCG document none of whose tokens map to an `--nldesign-*` target
- WHEN the admin uploads it
- THEN the upload MUST be rejected with HTTP 422
- AND the body MUST carry the full structured diagnostics so the admin can see why nothing
  mapped

### Requirement: DTCG Package Version Metadata

The importer MUST record a declared package version when an uploaded DTCG document carries
one — checked in order: top-level
`$version` member; `$extensions` version conventions; a top-level non-`$` `version` string —
the importer MUST record it verbatim in the custom set's entry in the `custom_token_sets`
appconfig manifest as `version`, alongside the existing metadata (display name, description,
theming bridge values). Absent a declared version, `version` MUST be omitted (not invented).
The custom-set list endpoint and admin panel MUST expose the recorded version. Recorded
versions are the substrate for upstream-freshness comparison (change `upstream-token-freshness`)
and for compliance-report metadata (change `compliance-evidence-report`); this requirement only
covers recording and display.

#### Scenario: Declared package version is recorded and listed

@e2e exclude metadata persistence — PHPUnit on the service
- GIVEN a DTCG upload whose document carries `"$version": "2.3.1"`
- WHEN the import succeeds as set `custom-eigen-huisstijl`
- THEN the manifest entry for `custom-eigen-huisstijl` MUST contain `version: "2.3.1"`
- AND `GET /settings/tokensets/custom` MUST include `version: "2.3.1"` for that set
- AND the admin panel custom-set list MUST display it

#### Scenario: Version-less package stores no fabricated version

@e2e exclude metadata persistence — PHPUnit on the service
- GIVEN a DTCG upload declaring no version in any recognized location
- WHEN the import succeeds
- THEN the manifest entry MUST NOT contain a `version` key
- AND the list response MUST return `version: null` (or omit the field) for that set
