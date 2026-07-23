## 1. ComplianceReportService

- [x] 1.1 Create `lib/Service/ComplianceReportService.php` with the 18-pair matrix as a single
      class constant (foreground token, background token, threshold, basis `normal-text` /
      `ui-component`), exactly as enumerated in
      `openspec/changes/compliance-evidence-report/specs/compliance-evidence/spec.md`.
- [x] 1.2 Implement effective-value resolution: layer `css/systems/{ds}/defaults.css` →
      `css/tokens/{activeTokenSet}.css` → `theming.background_color` fallback → `#ffffff` last
      resort → `CustomOverridesService::read()` last-wins. Reuse `CssParserService` for all
      parsing. Map `--color-*` pair tokens to their `--nldesign-*` sources per
      `css/systems/nldesign/overrides.css`; resolve `var()` chains transitively (depth cap ≥ 4);
      non-literal ⇒ `unevaluated`. (The `--nldesign-*` design-system layers are only consulted
      when the active set's design system is not `none`, mirroring
      `Application::injectThemeCSS()`'s own gate exactly, so a stock-Nextcloud active set is
      never scored against values the runtime never actually loads.)
- [x] 1.3 Implement pair evaluation through the existing `ContrastService::parseColor()` +
      `ratio()` (inject the service; do NOT reimplement luminance math) and the summary
      classification (`pass` / `fail` / `incomplete`-on-any-unevaluated).
- [x] 1.4 Implement metadata assembly: `instanceid` + base URL (`IConfig` system values /
      `IURLGenerator`), app version (`IAppManager::getAppVersion('nldesign')`), Nextcloud
      version, token set id/name/version (`token-sets.json` `version` field or custom-set
      manifest entry; `unversioned` fallback), design system id, ISO 8601 UTC timestamp via an
      injectable clock, and SHA-256 `overridesHash` over the sorted canonical
      `name: value` lines of the custom overrides.
- [x] 1.5 Implement the two renderers (`renderJson()`, `renderMarkdown()`), both embedding the
      honest-scope statement verbatim (color-contrast of theme tokens only; NOT a WCAG-EM audit;
      NOT a full WCAG evaluation; expert evaluation remains required). Deterministic ordering
      everywhere (matrix order; sorted keys in canonical structures).
- [x] 1.6 SPDX `@license`/`@copyright` docblocks and `@spec` tags on every added method
      (hydra gates).

## 2. Endpoint

- [x] 2.1 Add `SettingsController::complianceReport(string $format = 'json')` with
      `@AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)` and NO
      `#[PublicPage]`/`#[NoAdminRequired]`/`#[NoCSRFRequired]`, returning a
      `DataDownloadResponse` (`application/json` or `text/markdown`) with filename
      `nldesign-compliance-{instanceid}-{tokenSet}-{YYYYMMDD}.{json|md}`; HTTP 400 on unknown
      format.
- [x] 2.2 Register `['name' => 'settings#complianceReport', 'url' => '/settings/compliance-report',
      'verb' => 'GET']` in `appinfo/routes.php`.

## 3. occ command

- [x] 3.1 Create `lib/Command/ComplianceReport.php` (Symfony Console via OCP) named
      `nldesign:compliance-report` with `--format=json|markdown` (default json) and
      `--output=<path>` (default stdout); exit 0 on successful generation regardless of verdict,
      non-zero only on generation failure.
- [x] 3.2 Register the command in `appinfo/info.xml` under `<commands>` (first command in this
      app — add the block).

## 4. Unit tests

- [x] 4.1 `tests/Unit/Service/ComplianceReportServiceTest.php` — fixtures-based: known color
      pairs → known ratios (`#000000`/`#ffffff` → 21.00:1 pass; `#767676`/`#ffffff` → 4.54:1
      pass at 4.5 boundary; `#cccccc`/`#ffffff` → ≈1.61:1 fail; `var(--x)` → unevaluated).
      (Fixtures are built as temp-directory CSS files per test, matching this app's existing
      `CustomTokenSetServiceTest` convention, rather than static files under
      `tests/unit/fixtures/compliance/` — the app's test suite root is `tests/Unit/`, not
      `tests/unit/`.)
- [x] 4.2 Test override precedence: custom override beats token set value in the resolved pair;
      `overridesHash` changes when an override changes.
- [x] 4.3 Test summary classification: all-pass ⇒ `pass`; one fail ⇒ `fail`; zero fails + one
      unevaluated ⇒ `incomplete`.
- [x] 4.4 Test determinism with a frozen clock: two runs byte-identical per format; test both
      renderers always contain the scope statement and never the phrase "WCAG compliant".
- [x] 4.5 Test the shared-contract scenario of the modified `token-set-contrast-audit` spec: the
      same pair computed through `ShippedTokenSetAuditService` and `ComplianceReportService`
      yields an identical ratio.
- [x] 4.6 `tests/Unit/Command/ComplianceReportTest.php` — format/output options, exit codes.
- [x] 4.7 Controller test: unknown format ⇒ 400; response headers carry attachment disposition.

## 5. Verify

- [x] 5.1 Run PHPUnit in the nextcloud:34 container
      (`docker run --rm -v $PWD:/app -w /app <nc-image> php vendor/bin/phpunit`) — all new and
      existing tests green. 118 tests, 1597 assertions, OK. (Required a `tests/bootstrap.php` fix:
      `vendor/` is a symlink into the shared main checkout, and PHP resolves `__DIR__` through
      symlinks, so composer's generated PSR-4 base dir AND its `optimize-autoloader` classmap both
      pointed at the shared checkout's `lib/` instead of this worktree's. Fixed by repointing the
      `OCA\NLDesign\` PSR-4 prefix and stripping `OCA\NLDesign\*` classmap entries onto this
      checkout's own `lib/` in bootstrap.php, located via `spl_autoload_functions()` since
      `require_once` returns `true` — not the loader — on PHPUnit's second inclusion of
      `vendor/autoload.php`.)
- [x] 5.2 `composer check:strict`: `lint` and `phpcs` (the two hard-gating steps per
      `pre-merge-check-strict.yaml` — `phpmd`/`psalm`/`phpstan`/`test:all` are non-blocking,
      wrapped in `|| echo skipping` by composer.json itself, per ADR-022) both pass clean.
      `phpmd` flags one new, non-blocking `ExcessiveClassComplexity` (53 vs threshold 50) on
      `ComplianceReportService` — accepted as reasonable for an 18-pair-matrix + dual-renderer
      service; no other new phpmd findings. `psalm` reports "No errors found!" (45 pre-existing-
      pattern INFO-level notices, e.g. `IConfig::getAppValue` deprecation used identically
      throughout the rest of this app). `phpstan` cannot resolve any `OCP\*` class in this sandbox
      (299 errors across the WHOLE existing codebase, not scoped to this change — the
      `nextcloud/ocp` stub package's PSR-4 root is a relative symlink that only resolves inside a
      real NC container mount, matching why the org's own `pre-merge-check-strict.yaml` only
      enforces `lint`+`phpcs`, not phpstan).
- [ ] 5.3 Live on the 8080 dev instance: as admin, curl
      `http://localhost:8080/index.php/apps/nldesign/settings/compliance-report?format=json`
      (session + CSRF headers) — assert 18 pairs, metadata block, scope statement,
      `Content-Disposition: attachment`. Repeat with `format=markdown`.
      (deferred to post-merge live verification — requires the shared 8080 dev instance)
- [ ] 5.4 Live: unauthenticated curl to the same URL returns 401/redirect (admin posture).
      (deferred to post-merge live verification — requires the shared 8080 dev instance)
- [ ] 5.5 Live: `docker exec -u www-data nextcloud php occ nldesign:compliance-report
      --format=markdown` prints the report, exit 0; `--output=/tmp/r.md` writes identical bytes.
      (deferred to post-merge live verification — requires the shared 8080 dev instance)
- [ ] 5.6 Live cross-check: set one custom override in the token editor (e.g. `--color-primary`
      to a low-contrast value), regenerate, and confirm the affected pair flips to `fail` and
      `overridesHash` changed; then reset the override.
      (deferred to post-merge live verification — requires the shared 8080 dev instance)
