# Tasks — app-specific token-set selection + shared contrast entry point

## 1. Non-admin token-set catalogue endpoint

- [x] 1.1 Add `TokenSetService::getPublicCatalogue(): array` projecting
  `getAvailableTokenSets()` entries to the closed shape `{ id, name, design_system,
  theming: {primary_color, background_color, logo}, wcagLevel }`. `wcagLevel` computed via the
  same `ShippedTokenSetAuditService::auditSet()` path `Capabilities::computeWcagLevel()` uses,
  cached under the existing `ICache` prefix `nldesign_wcag_level` (key `level-<id>`, TTL 3600s) —
  do not create a second cache namespace.
- [x] 1.2 Add a new `CatalogController::tokenSets()` (or a method on `SettingsController` if the
  apply phase prefers — see design.md Open Questions) with `#[NoAdminRequired]` (no
  `#[NoCSRFRequired]` — GET needs no CSRF exemption, matching every other GET in this app),
  returning `{ tokenSets: [...] }`.
- [x] 1.3 Register `GET /api/token-sets` → `catalog#tokenSets` in `appinfo/routes.php`, placed
  outside the `/settings/*` prefix (this app's existing convention for admin-gated routes).

## 2. Shared contrast-evaluation endpoint

- [x] 2.1 Add `ContrastService::evaluate(array $candidates, string $background): array` —
  generalises the existing fixed-pair `check()` to an arbitrary list of
  `{ name: string, value: string, role: 'text'|'ui' }` candidates against one background,
  reusing `relativeLuminance()`/`ratio()`/`parseColor()` unchanged. Threshold by role: `text` →
  4.5:1, `ui` → 3.0:1. Returns `{ name, ratio, threshold, level: 'AA', pass, unevaluated? }[]` —
  no `blocked`/`allowed` field (design.md decision 2/4: data only, never a verdict). `check()`
  itself is unchanged.
- [x] 2.2 Add `ContrastController::evaluate()` with `#[NoAdminRequired]` (CSRF default-on — same-
  origin browser callers carry the NC request token automatically), and register
  `POST /api/contrast/evaluate` → `contrast#evaluate` in `appinfo/routes.php`.

## 3. Scoped-application contract (published, not implemented)

- [x] 3.1 Publish the contract in a new docs reference page (e.g.
  `docs/reference/app-token-scope-contract.md`, linked from `README.md`'s docs index): token
  namespace `--nldesign-*`; scope attribute `data-nldesign-theme-scope="<scopeId>"`; the
  `:root` → `[data-nldesign-theme-scope="<scopeId>"]` rewrite rule; applies to base/light
  `css/tokens/<id>.css` only (the dark variant is explicitly out of scope, per design.md decision
  3); the bail-and-degrade defensive rule (inject nothing, degrade to default styling, single
  console warning, if the fetched CSS is not exactly one flat `:root {}` block).
- [x] 3.2 Add a PHPUnit structural-invariant test (e.g. `tests/Unit/TokenCssShapeTest.php`)
  asserting every `css/tokens/*.css` file (excluding `dark/`) contains exactly one `:root { }`
  block, no at-rules (`@media`, `@supports`, `@import`, `@font-face`), and no selector other than
  `:root` — turning the empirically-verified-but-undocumented invariant this change's contract
  depends on into a mechanically-enforced regression guard.

## 4. Blocking-policy documentation

- [x] 4.1 State the resolved policy in this capability's spec and cross-reference it from
  `openspec/specs/custom-token-sets/spec.md` and `openspec/specs/token-set-contrast-audit/spec.md`
  (a short "see also" note, not a requirement change to either): selecting an existing catalogue
  entry is always warn-only via this endpoint; the endpoint never returns a block/allow verdict;
  a caller's own free-hand custom-color authoring policy (if any) is out of this endpoint's
  control.

## 5. Tests

- [x] 5.1 `tests/Unit/CatalogControllerTest.php` (or equivalent) — auth posture (authenticated
  non-admin succeeds; unauthenticated rejected); response contains exactly the 5 allowlisted
  fields (no `description`/`custom`/`warnings`/`upstream*` leakage); `wcagLevel` for the active
  set matches `Capabilities`' own computed value for the same set id.
- [x] 5.2 `tests/Unit/ContrastServiceEvaluateTest.php` — known-ratio fixtures for `text` and `ui`
  roles (compliant and non-compliant), `unevaluated` for non-literal (`var(...)`) values, auth
  posture on the controller.
- [x] 5.3 Newman/API-contract collection entry pinning both new endpoints' request/response shape
  (mirrors the existing `per-app-theming` API-contract Newman scenario pattern).

## 6. Verify (dev instance, 8080)

- [x] 6.1 `docker run --rm -v $PWD:/app -w /app <nc34-image> php vendor/bin/phpunit
  tests/Unit/CatalogControllerTest.php tests/Unit/ContrastServiceEvaluateTest.php
  tests/Unit/TokenCssShapeTest.php` — all green (25/25 new tests across all five new/updated test
  files; full suite 471/482 passing, remaining 11 are a pre-existing, unrelated
  `ConfigBundleServiceTest` / `IAppManager::getEnabledApps` stub-version mismatch — confirmed via
  `git diff` that this change did not touch that failing assertion).
- [x] 6.2 `composer check:strict` (PHPCS, PHPMD, Psalm, PHPStan) passes, including SPDX docblocks
  and `@spec openspec/specs/app-token-set-selection/spec.md` tags on the new methods. Run as
  discrete steps (`lint`, `phpcs`, `phpmd`, `psalm`, `phpstan`, `test:unit`) in a PHP 8.3 container
  because the shared composer:2 image's PHP 8.5 crashes Psalm 5.26 on an unrelated `E_STRICT`
  deprecation — all steps pass.
- [ ] 6.3 `curl -u <non-admin-user>:<pw> http://localhost:8080/apps/nldesign/api/token-sets`
  returns 200 with the 5-field shape for every shipped + custom set; the same call with no
  credentials returns 401/997 (Nextcloud's unauthenticated-rejection code). **Unverified**: not
  deployed to the shared dev instance (isolation requirement — never deploy to shared dev per
  team policy); covered instead by PHPUnit auth-posture/shape tests and the new Newman scenarios.
- [ ] 6.4 `curl -u <non-admin-user>:<pw> -X POST -H "Content-Type: application/json"
  http://localhost:8080/apps/nldesign/api/contrast/evaluate -d
  '{"candidates":[{"name":"primary","value":"#154273","role":"text"}],"background":"#F5F6F7"}'`
  returns a ratio matching the hand-computed WCAG value for that pair. **Unverified**: same reason
  as 6.3; covered instead by `ContrastServiceEvaluateTest`'s known-ratio fixtures and the new
  Newman scenario.
