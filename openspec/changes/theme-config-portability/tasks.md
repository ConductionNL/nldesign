## 1. Bundle service

- [ ] 1.1 Create `lib/Service/ConfigBundleService.php` (SPDX docblock; promoted
      `private readonly` deps: `IConfig`, `TokenSetService`, `CustomOverridesService`,
      `CustomTokenSetService`, `CustomTokenSetValidator`, `AppThemingService`, `CssParserService`,
      `LoggerInterface`). `export(): array` returns the v1 bundle:
      `format: "nldesign-config-bundle"`, `bundleVersion: 1`, `exportedAt` (ISO 8601),
      `app: {id, version}` (informational), `config: {tokenSet, hideSlogan, showMenuLabels,
      disabledApps}`, `customOverridesCss` (raw `CustomOverridesService::getRawContent()`),
      `customTokenSets: [{id, name, description, theming, css}]` (css via
      `CustomTokenSetService::getRawContent()`).
- [ ] 1.2 Implement `import(array $bundle, bool $dryRun = false): array` as two phases.
      Phase 1 — validate ALL sections, collect per-section results/errors:
      envelope (`format`/`bundleVersion` recognised), toggles are booleans, `disabledApps` is a
      list of app-id strings, each custom set passes
      `CustomTokenSetValidator::validateDeclarations()` (+ slug/`isCustomId()` check),
      `customOverridesCss` parses as CSS (`CssParserService`) with unknown editable tokens
      counted as skips (NOT errors, matching `token-import-export` semantics), and `tokenSet`
      is either a shipped/valid id or the id of a custom set contained in this bundle. ANY hard
      error → return `{applied: false, errors: [...per section...]}` and write NOTHING.
      Phase 2 (only when valid and not dry-run) — apply all sections: write custom set CSS files
      + `custom_token_sets` manifest (replace-by-slug, delete nothing not in conflict), write
      overrides via `CustomOverridesService::write()`, set the four app values. Return
      `{applied: true, sections: {…counts…}}`.
- [ ] 1.3 Idempotency: applying the same bundle twice MUST produce byte-identical
      `custom-overrides.css`, identical custom-set files/manifest, identical app values. Cover
      with `@spec openspec/specs/config-portability/spec.md` tags on all public methods.

## 2. occ commands

- [ ] 2.1 Create `lib/Command/ConfigExport.php` (`nldesign:config:export [file]`): writes
      pretty-printed bundle JSON to the file argument or stdout; exit 0. Symfony Console command
      extending `OCP`-sanctioned base (`Symfony\Component\Console\Command\Command`), service
      wiring via constructor injection of `ConfigBundleService`.
- [ ] 2.2 Create `lib/Command/ConfigImport.php` (`nldesign:config:import <file> [--dry-run]`):
      reads + JSON-decodes the file (decode failure → error, exit 1), calls
      `import($bundle, $dryRun)`, prints per-section results table; exit 0 on success/valid
      dry-run, exit 1 on validation failure with the full error listing.
- [ ] 2.3 Register both in `appinfo/info.xml` under `<commands>` (first commands in this app —
      add the block after `<settings>`), and confirm Nextcloud picks them up without any
      `Application::register()` code.

## 3. Controller + routes + UI

- [ ] 3.1 Create `lib/Controller/ConfigBundleController.php` with
      `#[AuthorizedAdminSetting(Admin::class)]` on both methods (route-auth gate):
      `export(): DataDownloadResponse` (JSON body, Content-Type `application/json`,
      Content-Disposition `attachment; filename="nldesign-config.json"`) and
      `import(): JSONResponse` (multipart upload; 256 KB per-file cap consistent with
      `token-import-export`; 400 with the error listing on validation failure, 413 oversize;
      response mirrors the service's per-section result).
- [ ] 3.2 Add routes to `appinfo/routes.php`:
      `['name' => 'configBundle#export', 'url' => '/settings/config/export', 'verb' => 'GET']`,
      `['name' => 'configBundle#import', 'url' => '/settings/config/import', 'verb' => 'POST']`
      (route-reachability gate).
- [ ] 3.3 `templates/settings/admin.php` + `js/admin.js`: new "Configuration" block with a
      Download button (navigates to the export URL) and an Upload control (multipart POST);
      render per-section success counts, or the all-or-nothing error listing, dismissibly; on
      applied import, reload the panel so dropdown/toggles/token editor reflect imported state.
      Localized strings, English keys.

## 4. Unit tests

- [ ] 4.1 `tests/Unit/Service/ConfigBundleServiceTest.php`:
      export contains all six state parts; import of a bundle with one invalid custom-set
      declaration writes NOTHING (config, overrides file, and custom sets all untouched) and
      lists the section error; unknown override variables are skipped-and-counted, not fatal;
      `tokenSet` referencing a custom set inside the same bundle validates; `tokenSet`
      referencing a nonexistent set is a hard error; dry-run of a valid bundle writes nothing
      and reports would-be sections; import is idempotent (double apply → identical state).
- [ ] 4.2 **Round-trip test**: seed config (token set, both toggles on, two disabled apps, two
      overrides, one custom set) → `export()` → wipe all six parts → `import()` → assert state
      identical to seeded state (app values, `custom-overrides.css` bytes, custom set file bytes
      + manifest).
- [ ] 4.3 `tests/Unit/Command/ConfigImportTest.php`: `--dry-run` exit 0 + no writes; invalid
      bundle exit 1 + error listing; missing/unreadable file exit 1.
      `tests/Unit/Controller/ConfigBundleControllerTest.php`: export headers; import 400/413
      paths.
- [ ] 4.4 Run phpunit in the nextcloud:34 container and `composer check:strict`; SPDX docblocks
      on all new `lib/` files (hydra gates).

## 5. Verify (live, 8080 dev instance)

- [ ] 5.1 In the container:
      `docker exec -u www-data nextcloud php occ nldesign:config:export /tmp/bundle.json` —
      inspect the JSON contains the live token set, toggles, exclusions, overrides CSS, and any
      custom sets.
- [ ] 5.2 Change the live config by hand (different token set, flip a toggle), then
      `occ nldesign:config:import /tmp/bundle.json --dry-run` — confirm it reports the sections
      it WOULD change and `occ config:app:get nldesign token_set` proves nothing changed.
- [ ] 5.3 Run the import without `--dry-run` — confirm token set, toggles, exclusions, overrides
      and custom sets are all restored; reload the browser and see the restored theme.
- [ ] 5.4 Corrupt a copy of the bundle (invalid custom-set declaration value) and import —
      confirm exit 1, full error listing, and NO partial application (all values still the
      restored ones).
- [ ] 5.5 Admin UI: Download the bundle from the settings panel; re-upload it; confirm the
      per-section result renders and the panel state is unchanged (idempotent). `curl` both
      `/settings/config/*` endpoints unauthenticated — rejected, no writes.
