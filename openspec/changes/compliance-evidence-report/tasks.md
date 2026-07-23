## 1. ComplianceReportService

- [ ] 1.1 Create `lib/Service/ComplianceReportService.php` with the 18-pair matrix as a single
      class constant (foreground token, background token, threshold, basis `normal-text` /
      `ui-component`), exactly as enumerated in
      `openspec/changes/compliance-evidence-report/specs/compliance-evidence/spec.md`.
- [ ] 1.2 Implement effective-value resolution: layer `css/systems/{ds}/defaults.css` →
      `css/tokens/{activeTokenSet}.css` → `theming.background_color` fallback → `#ffffff` last
      resort → `CustomOverridesService::read()` last-wins. Reuse `CssParserService` for all
      parsing. Map `--color-*` pair tokens to their `--nldesign-*` sources per
      `css/systems/nldesign/overrides.css`; resolve `var()` chains transitively (depth cap ≥ 4);
      non-literal ⇒ `unevaluated`.
- [ ] 1.3 Implement pair evaluation through the existing `ContrastService::parseColor()` +
      `ratio()` (inject the service; do NOT reimplement luminance math) and the summary
      classification (`pass` / `fail` / `incomplete`-on-any-unevaluated).
- [ ] 1.4 Implement metadata assembly: `instanceid` + base URL (`IConfig` system values /
      `IURLGenerator`), app version (`IAppManager::getAppVersion('nldesign')`), Nextcloud
      version, token set id/name/version (`token-sets.json` `version` field or custom-set
      manifest entry; `unversioned` fallback), design system id, ISO 8601 UTC timestamp via an
      injectable clock, and SHA-256 `overridesHash` over the sorted canonical
      `name: value` lines of the custom overrides.
- [ ] 1.5 Implement the two renderers (`renderJson()`, `renderMarkdown()`), both embedding the
      honest-scope statement verbatim (color-contrast of theme tokens only; NOT a WCAG-EM audit;
      NOT a full WCAG evaluation; expert evaluation remains required). Deterministic ordering
      everywhere (matrix order; sorted keys in canonical structures).
- [ ] 1.6 SPDX `@license`/`@copyright` docblocks and `@spec` tags on every added method
      (hydra gates).

## 2. Endpoint

- [ ] 2.1 Add `SettingsController::complianceReport(string $format = 'json')` with
      `@AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)` and NO
      `#[PublicPage]`/`#[NoAdminRequired]`/`#[NoCSRFRequired]`, returning a
      `DataDownloadResponse` (`application/json` or `text/markdown`) with filename
      `nldesign-compliance-{instanceid}-{tokenSet}-{YYYYMMDD}.{json|md}`; HTTP 400 on unknown
      format.
- [ ] 2.2 Register `['name' => 'settings#complianceReport', 'url' => '/settings/compliance-report',
      'verb' => 'GET']` in `appinfo/routes.php`.

## 3. occ command

- [ ] 3.1 Create `lib/Command/ComplianceReport.php` (Symfony Console via OCP) named
      `nldesign:compliance-report` with `--format=json|markdown` (default json) and
      `--output=<path>` (default stdout); exit 0 on successful generation regardless of verdict,
      non-zero only on generation failure.
- [ ] 3.2 Register the command in `appinfo/info.xml` under `<commands>` (first command in this
      app — add the block).

## 4. Unit tests

- [ ] 4.1 `tests/unit/Service/ComplianceReportServiceTest.php` — fixtures-based: known color
      pairs → known ratios (`#000000`/`#ffffff` → 21.00:1 pass; `#767676`/`#ffffff` → 4.54:1
      pass at 4.5 boundary; `#cccccc`/`#ffffff` → ≈1.61:1 fail; `var(--x)` → unevaluated).
      Fixture CSS files under `tests/unit/fixtures/compliance/`.
- [ ] 4.2 Test override precedence: custom override beats token set value in the resolved pair;
      `overridesHash` changes when an override changes.
- [ ] 4.3 Test summary classification: all-pass ⇒ `pass`; one fail ⇒ `fail`; zero fails + one
      unevaluated ⇒ `incomplete`.
- [ ] 4.4 Test determinism with a frozen clock: two runs byte-identical per format; test both
      renderers always contain the scope statement and never the phrase "WCAG compliant".
- [ ] 4.5 Test the shared-contract scenario of the modified `token-set-contrast-audit` spec: the
      same pair computed through `ShippedTokenSetAuditService` and `ComplianceReportService`
      yields an identical ratio.
- [ ] 4.6 `tests/unit/Command/ComplianceReportTest.php` — format/output options, exit codes.
- [ ] 4.7 Controller test: unknown format ⇒ 400; response headers carry attachment disposition.

## 5. Verify

- [ ] 5.1 Run PHPUnit in the nextcloud:34 container
      (`docker run --rm -v $PWD:/app -w /app <nc-image> php vendor/bin/phpunit`) — all new and
      existing tests green.
- [ ] 5.2 `composer check:strict` passes (PHPCS, PHPMD, Psalm, PHPStan).
- [ ] 5.3 Live on the 8080 dev instance: as admin, curl
      `http://localhost:8080/index.php/apps/nldesign/settings/compliance-report?format=json`
      (session + CSRF headers) — assert 18 pairs, metadata block, scope statement,
      `Content-Disposition: attachment`. Repeat with `format=markdown`.
- [ ] 5.4 Live: unauthenticated curl to the same URL returns 401/redirect (admin posture).
- [ ] 5.5 Live: `docker exec -u www-data nextcloud php occ nldesign:compliance-report
      --format=markdown` prints the report, exit 0; `--output=/tmp/r.md` writes identical bytes.
- [ ] 5.6 Live cross-check: set one custom override in the token editor (e.g. `--color-primary`
      to a low-contrast value), regenerate, and confirm the affected pair flips to `fail` and
      `overridesHash` changed; then reset the override.
