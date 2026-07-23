## 1. Bundle service

- [x] 1.1 Create `lib/Service/ConfigBundleService.php` (SPDX docblock; promoted
      `private readonly` deps: `IConfig`, `TokenSetService`, `CustomOverridesService`,
      `CustomTokenSetService`, `CustomTokenSetValidator`, `AppThemingService`, `CssParserService`,
      `LoggerInterface`). `export(): array` returns the v1 bundle:
      `format: "nldesign-config-bundle"`, `bundleVersion: 1`, `exportedAt` (ISO 8601),
      `app: {id, version}` (informational), `config: {tokenSet, hideSlogan, showMenuLabels,
      disabledApps}`, `customOverridesCss` (raw `CustomOverridesService::getRawContent()`),
      `customTokenSets: [{id, name, description, theming, css}]` (css via
      `CustomTokenSetService::getRawContent()`).
      **Scope note (post-authoring app growth):** three more configuration surfaces shipped in
      this app after this task list was written and are included in bundle v1 per the spec's own
      ratchet rule ("every future instance-wide nldesign configuration value MUST be added to the
      bundle in the same change that introduces the value") — `config.upstreamFreshnessEnabled`
      (`UpstreamFreshnessService`), `emailFooter` (org name / accessibility URL / privacy URL,
      `EmailThemingService`), and `customFonts` (manifest metadata ONLY — binaries are NOT
      embedded; see the service's class docblock for the explicit rationale and the "export
      metadata only" fallback this exercises). Also added extra constructor deps:
      `IAppManager`, `EmailThemingService`, `FontService`, `UpstreamFreshnessService`.
- [x] 1.2 Implement `import(array $bundle, bool $dryRun = false): array` as two phases.
      Phase 1 — validate ALL sections, collect per-section results/errors:
      envelope (`format`/`bundleVersion` recognised), toggles are booleans, `disabledApps` is a
      list of app-id strings, each custom set passes
      `CustomTokenSetValidator::validateDeclarations()` (+ slug/`isCustomId()` check),
      `customOverridesCss` parses as CSS (`CssParserService`) with unknown editable tokens
      counted as skips (NOT errors, matching `token-import-export` semantics), and `tokenSet`
      is either a shipped/valid id or the id of a custom set contained in this bundle. ANY hard
      error → return `{applied: false, errors: [...per section...]}` and write NOTHING.
      Phase 2 (only when valid and not dry-run) — apply all sections: write custom set CSS files
      + `custom_token_sets` manifest (replace-by-slug, delete nothing not in conflict — new
      `CustomTokenSetService::replace()` method added for this), write overrides via
      `CustomOverridesService::write()`, set the app values (now including
      `upstream_freshness_enabled` and the three `email_footer_*` keys via a new
      `EmailThemingService::validateFooterConfig()` extracted from `setFooterConfig()` so import
      reuses the EXACT SAME validation rule set — no reimplementation). `customFonts` is
      validated (shape only) but deliberately NEVER applied. Return `{applied: true,
      sections: {…counts…}}`.
- [x] 1.3 Idempotency: applying the same bundle twice MUST produce byte-identical
      `custom-overrides.css`, identical custom-set files/manifest, identical app values. Cover
      with `@spec openspec/specs/config-portability/spec.md` tags on all public methods.

## 2. occ commands

- [x] 2.1 Create `lib/Command/ConfigExport.php` (`nldesign:config:export [file]`): writes
      pretty-printed bundle JSON to the file argument or stdout; exit 0. Symfony Console command
      extending `OCP`-sanctioned base (`Symfony\Component\Console\Command\Command`), service
      wiring via constructor injection of `ConfigBundleService`.
- [x] 2.2 Create `lib/Command/ConfigImport.php` (`nldesign:config:import <file> [--dry-run]`):
      reads + JSON-decodes the file (decode failure → error, exit 1), calls
      `import($bundle, $dryRun)`, prints per-section results table; exit 0 on success/valid
      dry-run, exit 1 on validation failure with the full error listing.
- [x] 2.3 Register both in `appinfo/info.xml` under `<commands>` (first commands in this app —
      add the block after `<settings>`), and confirm Nextcloud picks them up without any
      `Application::register()` code. (Note: `lib/Command/ComplianceReport.php` already existed
      from the `compliance-evidence` change, so this is actually the 2nd/3rd commands — followed
      its exact registration pattern.)

## 3. Controller + routes + UI

- [x] 3.1 Create `lib/Controller/ConfigBundleController.php` with
      `#[AuthorizedAdminSetting(Admin::class)]` on both methods (route-auth gate):
      `export(): DataDownloadResponse` (JSON body, Content-Type `application/json`,
      Content-Disposition `attachment; filename="nldesign-config.json"`) and
      `import(): JSONResponse` (multipart upload; 256 KB per-file cap consistent with
      `token-import-export`; 400 with the error listing on validation failure, 413 oversize;
      response mirrors the service's per-section result). Logs one `config_bundle_imported`
      audit entry (`ThemingAuditService`) on successful apply only.
- [x] 3.2 Add routes to `appinfo/routes.php`:
      `['name' => 'configBundle#export', 'url' => '/settings/config/export', 'verb' => 'GET']`,
      `['name' => 'configBundle#import', 'url' => '/settings/config/import', 'verb' => 'POST']`
      (route-reachability gate).
- [x] 3.3 `templates/settings/admin.php` + `js/admin.js`: new "Configuration bundle (OTAP
      promotion)" block with a Download button (navigates to the export URL) and an Upload
      control (multipart POST); render per-section success counts, or the all-or-nothing error
      listing, dismissibly; on applied import, reload the panel so dropdown/toggles/token editor
      reflect imported state. Localized strings, English keys.

## 4. Unit tests

- [x] 4.1 `tests/Unit/Service/ConfigBundleServiceTest.php`:
      export contains all six state parts (+ the three post-authoring additions); import of a
      bundle with one invalid custom-set declaration writes NOTHING (config, overrides file, and
      custom sets all untouched) and lists the section error; unknown override variables are
      skipped-and-counted, not fatal; `tokenSet` referencing a custom set inside the same bundle
      validates; `tokenSet` referencing a nonexistent set is a hard error; dry-run of a valid
      bundle writes nothing and reports would-be sections; import is idempotent (double apply →
      identical state); wrong envelope `format` is a hard error; an invalid `emailFooter` URL is
      a hard error (proves the reused `validateFooterConfig()` path); `customFonts` is reported
      but never applied. 11 tests, real `CustomOverridesService`/`CustomTokenSetService`/
      `TokenSetService`/`AppThemingService`/`EmailThemingService`/`UpstreamFreshnessService`
      instances against a temp app dir (mirrors `CustomTokenSetServiceTest`'s pattern) —
      `FontService` is the one collaborator mocked (its manifest is export-only, see 1.1).
- [x] 4.2 **Round-trip test**: seed config (token set, both toggles on, two disabled apps, two
      overrides, one custom set) → `export()` → wipe all parts → `import()` → assert state
      identical to seeded state (app values, `custom-overrides.css` bytes, custom set file bytes
      + manifest).
- [x] 4.3 `tests/Unit/Command/ConfigImportTest.php` (6 tests): `--dry-run` calls the service with
      `dryRun: true` and exits 0 + no writes attempted; invalid bundle exit 1 + error listing;
      missing/unreadable file exit 1 without calling the service; undecodable JSON exit 1 without
      calling the service; successful import exit 0; service exception exit 1.
      `tests/Unit/Controller/ConfigBundleControllerTest.php` (8 tests): export headers/body;
      import 400 (no file / invalid JSON / validation failure)/413 (oversize)/415 (wrong
      extension) paths; success logs exactly one audit entry with the section results; validation
      failure and a service exception log NOTHING.
- [x] 4.4 Ran phpunit in the nextcloud:34 container (25/25 new tests pass; full suite — 337
      tests, minus the pre-existing, documented `OC\Mail\EMailTemplate` container limitation —
      passes with zero regressions) and `composer check:strict` (lint/phpcs/phpmd/psalm/phpstan
      all green; `test:all` hits the same documented container limitation, which the script
      itself treats as non-fatal). SPDX docblocks on all new `lib/` files.
      **Pre-existing issue fixed while here:** `tests/vitest/admin-dtcg-diagnostics.spec.js` (2
      of its 3 tests) was already failing on this change's base commit, unrelated to this task —
      the DTCG import-diagnostics UI (`.nldesign-diagnostics-list`/`.nldesign-deprecation-list`
      grouped-by-reason rendering, the recorded package `version` in the upload result and the
      custom-set list) was speced/tested but never actually wired into `js/admin.js`. Implemented
      it (`groupDiagnosticsByReason()`/`appendDiagnosticsList()`/`appendDeprecationList()` +
      the `.nldesign-custom-set-version` row span) so the pre-existing test suite passes; all 24
      vitest tests now green (was 22/24).

## 5. Verify (live, 8080 dev instance)

- [ ] 5.1 In the container:
      `docker exec -u www-data nextcloud php occ nldesign:config:export /tmp/bundle.json` —
      inspect the JSON contains the live token set, toggles, exclusions, overrides CSS, and any
      custom sets. (deferred to post-merge live verification — requires the live 8080 instance)
- [ ] 5.2 Change the live config by hand (different token set, flip a toggle), then
      `occ nldesign:config:import /tmp/bundle.json --dry-run` — confirm it reports the sections
      it WOULD change and `occ config:app:get nldesign token_set` proves nothing changed.
      (deferred to post-merge live verification)
- [ ] 5.3 Run the import without `--dry-run` — confirm token set, toggles, exclusions, overrides
      and custom sets are all restored; reload the browser and see the restored theme.
      (deferred to post-merge live verification)
- [ ] 5.4 Corrupt a copy of the bundle (invalid custom-set declaration value) and import —
      confirm exit 1, full error listing, and NO partial application (all values still the
      restored ones). (deferred to post-merge live verification)
- [ ] 5.5 Admin UI: Download the bundle from the settings panel; re-upload it; confirm the
      per-section result renders and the panel state is unchanged (idempotent). `curl` both
      `/settings/config/*` endpoints unauthenticated — rejected, no writes.
      (deferred to post-merge live verification)
