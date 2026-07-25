# app-token-set-selection

**Spec refs**: openspec/specs/token-sets/spec.md, openspec/specs/custom-token-sets/spec.md, openspec/specs/theming-capability/spec.md, openspec/specs/token-set-contrast-audit/spec.md, openspec/specs/css-architecture/spec.md
**Standards**: WCAG 2.1 https://www.w3.org/TR/WCAG21/ · NL Design System https://nldesignsystem.nl/

## Purpose

Let a leaf app's own picker (e.g. a builder tool inside another Conduction app) enumerate nldesign's token-set catalogue and evaluate WCAG contrast without admin privileges and without re-implementing nldesign's discovery or contrast math. nldesign already owns token-set discovery (`token-sets`), custom-set upload (`custom-token-sets`), and WCAG contrast auditing (`token-set-contrast-audit`, `theming-capability`); this capability adds the read-only, non-admin consumption surface those existing capabilities were missing, and the scoped-application contract a shared client-side applier (a companion `nextcloud-vue` change, not part of this capability) implements against. It intentionally defines no new storage, no new admin UI, and no client-side applier — only a read contract.

## ADDED Requirements

### Requirement: Non-Admin Token-Set Catalogue Endpoint

The app MUST expose `GET /api/token-sets`, authenticated non-admin (`#[NoAdminRequired]`, not `#[PublicPage]`), returning `{ tokenSets: TokenSetSummary[] }` where each `TokenSetSummary` is exactly `{ id, name, design_system, theming: { primary_color, background_color, logo? }, wcagLevel }`. The endpoint MUST reuse `TokenSetService::getAvailableTokenSets()` for discovery without a second scan or a second manifest-merge implementation, and MUST include every entry that endpoint discovers (shipped and admin-uploaded custom sets alike — no filtering by origin). The response MUST NOT include `description`, `custom`, `warnings`, `upstreamVersion`, or `upstreamRef`. `logo` MUST be omitted (not `null`) when the set declares none, matching `TokenSetEntry`'s existing optionality. `wcagLevel` MUST be one of `AAA`, `AA`, `fail`, or `null`, computed via the same per-set audit path `Capabilities::computeWcagLevel()` already uses for the active set (`ShippedTokenSetAuditService::auditSet()`), and MUST be cached under the existing `ICache` prefix `nldesign_wcag_level` (key `level-<id>`, TTL 3600s) so this endpoint and `Capabilities` share one cache entry per set id rather than computing the audit twice.

#### Scenario: Authenticated non-admin user reads the full catalogue

- GIVEN an authenticated user who is not an instance admin
- WHEN they call `GET /api/token-sets`
- THEN the response MUST be HTTP 200 with `tokenSets` containing one entry per set
  `TokenSetService::getAvailableTokenSets()` discovers, including any admin-uploaded custom sets
- AND each entry MUST contain exactly `id`, `name`, `design_system`, `theming`, `wcagLevel` (and
  `theming.logo` only when the set declares one) — no other keys

#### Scenario: Unauthenticated request is rejected

@e2e exclude auth-posture assertion — PHPUnit/Newman verify middleware rejection, not a UI flow
- GIVEN no active Nextcloud session
- WHEN a request is made to `GET /api/token-sets`
- THEN Nextcloud's SecurityMiddleware MUST reject the request (no `#[PublicPage]` on the method)

#### Scenario: wcagLevel matches the active-set capability computation

@e2e exclude cache-sharing assertion — PHPUnit compares the catalogue entry against Capabilities for the same active set id
- GIVEN a token set that is currently the active instance theme
- WHEN its `wcagLevel` is read from both `GET /api/token-sets` and the public capabilities
  document (`capabilities.nldesign.wcagLevel`)
- THEN both values MUST be identical for that set id
- AND the underlying audit MUST be computed at most once per set id per cache TTL window

#### Scenario: Catalogue shape stays independent of the admin endpoint

@e2e exclude contract-independence assertion — PHPUnit asserts field allowlist
- GIVEN the existing admin `GET /settings/tokensets` response for a given set, which includes
  `description`, `warnings`, and (for custom sets) `custom: true`
- WHEN the same set is read via `GET /api/token-sets`
- THEN none of `description`, `warnings`, `custom`, `upstreamVersion`, `upstreamRef` MUST be
  present on the non-admin entry

### Requirement: Shared Contrast Evaluation Endpoint

The app MUST expose `POST /api/contrast/evaluate`, authenticated non-admin (`#[NoAdminRequired]`), accepting `{ candidates: [{ name: string, value: string, role: "text"|"ui" }], background: string }` and returning `{ results: [{ name, ratio: number|null, threshold: number, level: "AA", pass: boolean, unevaluated?: true }] }`. The endpoint MUST generalise `ContrastService`'s existing relative-luminance math (`relativeLuminance()`, `ratio()`, `parseColor()`) to an arbitrary candidate list rather than the two fixed pairs `check()` evaluates, using threshold 4.5:1 for `role: "text"` and 3.0:1 for `role: "ui"`. A candidate whose `value` (or the `background`) is not a parseable literal color (hex or `rgb()`/`rgba()`) MUST be reported with `unevaluated: true` and `ratio: null`, never silently reported as passing. The response MUST NOT contain a `blocked`, `allowed`, or any other verdict field — the endpoint reports facts only; the caller decides what to do with them (see the Selection Contrast Is Non-Blocking requirement below). The existing `ContrastService::check()` (fixed-pair, upload-time) MUST remain unchanged and MUST continue to serve `CustomTokenSetService::store()` exactly as before this change.

#### Scenario: Multiple candidate colors are evaluated against one background

- GIVEN a request with candidates `primary` (`#154273`, role `text`) and `accent` (`#e8f0f8`,
  role `ui`) against `background: "#F5F6F7"`
- WHEN `POST /api/contrast/evaluate` is called
- THEN the response MUST contain one result per candidate with its computed ratio, its role's
  threshold (4.5 for `primary`, 3.0 for `accent`), and `pass` reflecting whether the ratio meets
  that threshold

#### Scenario: Non-literal candidate value is reported unevaluated, never passing

- GIVEN a candidate whose `value` is `var(--some-token)`
- WHEN the endpoint evaluates it
- THEN the result for that candidate MUST have `unevaluated: true` and `ratio: null`
- AND MUST NOT have `pass: true`

#### Scenario: Response never carries a blocking verdict

@e2e exclude contract-shape assertion — PHPUnit asserts absence of verdict keys
- GIVEN any request, compliant or non-compliant
- WHEN the response is inspected
- THEN no key named `blocked`, `allowed`, or `verdict` MUST be present anywhere in the response
  body

#### Scenario: Existing upload-time check is unaffected

@e2e exclude regression assertion — PHPUnit re-runs the existing CustomTokenSetService::store() contrast fixtures
- GIVEN the existing `custom-token-sets` upload-time contrast fixtures
- WHEN a custom set is uploaded through `CustomTokenSetService::store()` after this change ships
- THEN the computed `warnings` MUST be byte-identical to before this change (evaluate() is
  additive; check() is untouched)

### Requirement: Scoped Application Contract for Base Token CSS

nldesign MUST publish (in a docs reference page cross-linked from this spec) a stable scoped-application contract for its base/light token CSS, for a shared client-side applier (built outside this capability) to implement against: (1) the token namespace is `--nldesign-*` (unchanged); (2) the scope attribute is `data-nldesign-theme-scope="<scopeId>"`, owned by nldesign rather than any individual leaf app, where `<scopeId>` is minted and validated by the consuming applier, not by nldesign; (3) the required selector-rewrite rule is `:root` → `[data-nldesign-theme-scope="<scopeId>"]`, a 1:1 selector-prefix transform with no property name, value, or order changes; (4) the contract covers `css/tokens/<id>.css` (base/light) only — the generated dark variant `css/tokens/dark/<id>.css` uses an unrelated `@media (prefers-color-scheme: dark) { body:not(...) { ... } }` shape and is explicitly out of scope; (5) a consuming applier MUST verify the fetched CSS is exactly one flat `:root { }` block with no at-rules and no other selector before rewriting, and MUST inject nothing (degrading to default styling) if it is not — partial rewriting MUST NEVER occur. Every shipped `css/tokens/*.css` file (excluding `dark/`) MUST be exactly one flat `:root { }` block with no at-rules and no selector other than `:root`, enforced by an automated structural test; every custom-uploaded set is already guaranteed this shape by `CustomTokenSetValidator`.

#### Scenario: Every shipped base token CSS file is flat `:root`-only

@e2e exclude structural invariant — PHPUnit scans css/tokens/*.css (excluding dark/)
- GIVEN the full set of shipped `css/tokens/*.css` files (excluding the `dark/` subdirectory)
- WHEN each file is parsed for at-rules and top-level selectors
- THEN every file MUST contain exactly one `:root { }` block
- AND no file MUST contain `@media`, `@supports`, `@import`, `@font-face`, or any selector other
  than `:root`

#### Scenario: Custom-uploaded sets already satisfy the contract

@e2e exclude existing-guarantee assertion — PHPUnit re-asserts CustomTokenSetValidator's existing selector/at-rule rejection
- GIVEN the existing `CustomTokenSetValidator` selector and at-rule rejection rules
- WHEN a custom token set is uploaded
- THEN the stored CSS MUST already satisfy the flat `:root`-only shape this contract requires,
  with no additional validation needed

#### Scenario: Dark variants are explicitly excluded from the contract

@e2e exclude scope-boundary documentation assertion — reviewed in docs, not independently testable at runtime
- GIVEN a generated `css/tokens/dark/<id>.css` file
- WHEN it is checked against this contract
- THEN it MUST NOT be expected to satisfy the flat `:root`-only shape
- AND the published contract page MUST state that scoped dark-mode application is out of scope

### Requirement: Selection Contrast Is Non-Blocking

Evaluating contrast for a leaf app's SELECTION of an existing catalogue entry (any set listed by `GET /api/token-sets` — shipped or already-uploaded custom) MUST always be treated as a warning, never a hard block, consistent with nldesign's own upload-time policy (`custom-token-sets` "WCAG AA Contrast Warnings on Upload": non-blocking). Neither `GET /api/token-sets` (via `wcagLevel`) nor `POST /api/contrast/evaluate` (via its response shape) exposes a mechanism for a caller to condition a hard block on nldesign's own contrast data for a selection flow. This policy governs SELECTION only; it does not constrain or dictate a leaf app's own local policy for its OWN free-hand custom-color authoring (a distinct concern, e.g. a leaf app's raw color-picker feature), which remains that app's decision.

#### Scenario: A sub-AA catalogue entry is still selectable

- GIVEN a catalogue entry whose `wcagLevel` is `fail`
- WHEN a leaf app's picker reads it via `GET /api/token-sets`
- THEN nothing in the response prevents the leaf app from allowing the user to select it
- AND no nldesign endpoint returns a value that represents "selection forbidden"

#### Scenario: Free-hand color authoring is unaffected by this policy

@e2e exclude cross-app policy boundary — documented, not independently testable from nldesign
- GIVEN a leaf app's own free-hand custom-color authoring feature (not a catalogue selection)
- WHEN that app evaluates a candidate color via `POST /api/contrast/evaluate`
- THEN this capability places no requirement on whether that app blocks or warns on the result —
  that decision remains entirely the calling app's own
