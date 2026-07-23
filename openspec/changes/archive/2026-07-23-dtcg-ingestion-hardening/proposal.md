---
kind: code
---

## Why

The W3C Design Tokens Community Group **Format Module v2025.10 has been stable since
28 October 2025**, with Style Dictionary, Tokens Studio and Penpot support and ~84% tooling-team
adoption. NL Design System has committed to the format and publishes its themes (the
`nl-design-system/themes` repo: ~23 municipality themes plus Provincie Zuid-Holland) as DTCG
token packages with **explicit semver conventions** — upstream warns that even patch releases
can break downstream consumers, which makes recording the package version at import time a
correctness requirement, not a nicety. Ingesting these packages properly is what makes **all
380+ gemeenten addressable** without nldesign hand-compiling a CSS file per municipality (today:
42 shipped sets, maintained by hand and a nightly CI sync). It is also the app's defense against
its main strategic risk: staying a static-42-set app while the ecosystem moves to live DTCG
packages.

The current `DesignTokensMapper` (`lib/Service/DesignTokensMapper.php`) is a minimal
draft-format reader and silently mishandles real NLDS packages:

- Only **scalar string `$value`** leaves survive (`flatten()` drops non-scalar values) — the
  v2025.10 **object forms** (`color` as `{colorSpace, components, alpha, hex}`, `dimension` as
  `{value, unit}`) and **composite `typography`** tokens are dropped without a trace.
- **No alias resolution**: `"$value": "{color.brand.primary}"` — pervasive in NLDS theme
  packages, which layer brand → semantic → component tokens — is passed through as a literal
  string, producing a broken CSS value that then reads as `unevaluated` in contrast checks.
- **`$type` is ignored entirely**: no type inheritance from groups (v2025.10 allows `$type` on a
  group applying to all descendants), no per-type value validation/serialization.
- Failures are invisible: everything lands in an undifferentiated `skipped` list with no reason,
  so an admin uploading a real `@nl-design-system/*` package sees "imported: 3, skipped: 214"
  and has no idea whether that is fine or broken.
- No `$deprecated` surfacing, no `$extensions` awareness, no package version capture.

The canonical `custom-token-sets` spec ("W3C Design Tokens JSON Import" requirement) currently
promises only the minimal draft behavior. This change upgrades that requirement to the full
v2025.10 contract. Decision per the wave brief: **grow `custom-token-sets` via MODIFIED + ADDED
requirements rather than mint a new `dtcg-import` slug** — the import is one pipeline with one
upload control, one validator, one storage path; a second slug would split one capability's
source of truth across two specs.

## What Changes

- Rework `DesignTokensMapper` to the DTCG Format Module v2025.10:
  - `$type` on tokens AND groups, with group-declared types inherited by descendant tokens
    (nearest ancestor wins);
  - `$value` object forms: `color` (both legacy string hex/rgb and the v2025.10 object form —
    sRGB color spaces serialized to hex; unsupported color spaces produce an actionable skip),
    `dimension` (`{value, unit}` → `16px`), `fontFamily` (string or array → CSS font stack),
    `fontWeight` (number or v2025.10 keyword → CSS value), and composite `typography`
    (sub-values mapped individually where an `--nldesign-*` target exists);
  - alias resolution for `{token.path}` references, including transitive chains, with **cycle
    detection** producing an actionable error naming the cycle path;
  - `$extensions` passthrough-ignore (never an error, never mapped); `$deprecated` (boolean or
    string form) surfaced as warnings on otherwise-successful imports;
  - structured, aggregated, actionable error/skip reporting: every skipped or failed token
    carries its dotted path and a machine-readable reason.
- Record package version metadata when present (top-level `$version` /
  `$extensions.['nl.nldesign.version']` / sidecar `version` conventions, checked in that order)
  into the custom set's stored metadata in the `custom_token_sets` appconfig manifest
  (`CustomTokenSetService::MANIFEST_KEY`), exposed in the custom-set list.
- Mapped output continues through the EXISTING whitelist/serialization/storage pipeline
  (`CustomTokenSetValidator`, atomic write to `css/tokens/custom-*.css`) — no change to the
  storage contract, no new endpoints.
- **MODIFIED** `custom-token-sets` requirement "W3C Design Tokens JSON Import" (full v2025.10
  contract) + **ADDED** requirements in the same spec: alias resolution, import diagnostics,
  package version metadata.
- Not breaking: every document the old mapper imported correctly still imports with identical
  or better results; previously-silently-dropped constructs now import or produce diagnostics.

## Impact

- `lib/Service/DesignTokensMapper.php` — rework (flatten with type inheritance, alias resolver,
  per-type serializers, diagnostics).
- `lib/Service/CustomTokenSetService.php` — store `version` + `deprecated`/import-warning
  metadata in the manifest entry; expose in `list`.
- `lib/Controller/CustomTokenSetController.php` — response shape gains structured
  `errors`/`warnings` arrays (additive).
- `js/admin.js` — render per-path import diagnostics and the stored package version in the
  custom-set list (vanilla JS, additive).
- `tests/unit/Service/DesignTokensMapperTest.php` — exhaustive new coverage;
  `tests/unit/fixtures/dtcg/` — fixture corpus including real municipal package snippets
  (utrecht / amsterdam npm token JSON).
- `openspec/specs/custom-token-sets/spec.md` — modified + added requirements (via archive).
- Cross-reference: change `compliance-evidence-report` consumes the stored set version in its
  report metadata; change `upstream-token-freshness` (this wave) builds on the same recorded
  versions.
