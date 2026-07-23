---
kind: code
---

## Why

Dutch government IT runs OTAP (Ontwikkel → Test → Acceptatie → Productie): every change is
rehearsed on lower environments and promoted, identically, to production. nldesign's gemeente
rollout flow (research `03-user-wishes-flows.md`, flow 1) records this as an explicit pain:
"OTAP (dev/test/acc/prod) promotion has no export/import". Today an operator who has configured
nldesign on acceptatie — active token set, hide-slogan/menu-labels toggles, per-app exclusions,
token-editor overrides, uploaded custom token sets — must re-click every one of them by hand on
productie, with nothing guaranteeing the environments match. Upstream is no help: Nextcloud's
own `occ theming:config` is partial and buggy for this purpose (nextcloud/server#60173), and it
covers only core theming values anyway, not any nldesign state.

nldesign's configuration is spread across two storage kinds, neither individually portable
today:

- **IConfig app values** (`nldesign` app id): `token_set`, `hide_slogan`, `show_menu_labels`,
  `disabled_apps` (JSON array, `AppThemingService::CONFIG_KEY`), `custom_token_sets` (custom-set
  metadata manifest, `CustomTokenSetService::MANIFEST_KEY`).
- **Files in `css/`**: `css/custom-overrides.css` (token-editor output,
  `CustomOverridesService`) and `css/tokens/custom-{slug}.css` (uploaded custom sets,
  `CustomTokenSetService`).

The existing `token-import-export` canonical spec covers ONLY `custom-overrides.css` — one file
of the six-part state above. Its endpoints stay useful for sharing token tweaks, but it must be
cross-referenced to the full bundle so nobody mistakes it for whole-config portability.

All the validation machinery a safe import needs already exists and MUST be reused, not
reimplemented: `CustomTokenSetValidator` (declaration whitelist, forbidden values, disallowed
selectors — hardened further by in-flight change
`harden-custom-token-set-value-validation`), the editable-token whitelist in
`CustomOverridesService`/`TokenRegistry`, and `TokenSetService::isValidTokenSet()`.

## What Changes

- **New JSON bundle format** (`nldesign-config-bundle`, `bundleVersion: 1`) capturing the
  COMPLETE nldesign configuration in one file: active token set id, hide-slogan toggle,
  menu-labels toggle, per-app exclusion list, full `custom-overrides.css` content, and every
  custom token set (metadata + inline CSS content). Explicitly excluded, with rationale in the
  spec: `theming_syncs_total` and the audit counter (operational telemetry, not configuration),
  `installed_version` (NC-managed), per-user preview values (session-scoped — change
  `theme-preview-workflow`), and NC core `theming` app values (owned by Nextcloud's theming app;
  re-running the theming-sync dialog after import is the supported path). Any future
  feature-toggle app value MUST be added to the bundle in the same change that introduces it,
  bumping `bundleVersion` — this ratchet is a spec requirement, so the bundle can never silently
  fall behind the app again.
- **New service `lib/Service/ConfigBundleService.php`** — single implementation shared by occ and
  HTTP: `export(): array` and `import(array $bundle, bool $dryRun): array`. Import is
  **validate-everything-first, then write**: phase 1 validates every section using the existing
  validators; ANY hard validation error aborts the whole import with a per-section error listing
  and zero writes (all-or-nothing). Phase 2 (skipped under `--dry-run`) applies all sections.
  Unknown-but-well-formed override variables follow the existing import semantics
  (skip-and-count, per `token-import-export`) and are reported per section, not treated as hard
  errors. Import is idempotent: importing the same bundle twice yields byte-identical state.
- **Two occ commands** for OTAP automation, registered via `<commands>` in `appinfo/info.xml`:
  - `occ nldesign:config:export [file]` — bundle JSON to the file, or stdout when omitted.
  - `occ nldesign:config:import <file> [--dry-run]` — validate (+apply unless dry-run), print
    per-section results, exit non-zero on validation failure.
- **Admin UI**: Download/Upload buttons for the full bundle in the settings panel (new
  "Configuration" block), backed by `GET /settings/config/export` (attachment
  `nldesign-config.json`) and `POST /settings/config/import` (multipart) on a new
  `lib/Controller/ConfigBundleController.php`, both `#[AuthorizedAdminSetting(Admin::class)]`.
  Import result (per-section counts or the all-or-nothing error listing) is shown in the panel.
- **New canonical spec slug `config-portability`**; **MODIFIED `token-import-export`** — the two
  overrides-only requirements gain explicit scope statements cross-referencing the full bundle;
  **MODIFIED `admin-settings`** — new requirement for the bundle controls.
- Non-breaking: no existing endpoint, key, or file changes shape.

## Impact

- `lib/Service/ConfigBundleService.php` — new.
- `lib/Command/ConfigExport.php`, `lib/Command/ConfigImport.php` — new (first occ commands in
  the app; `<commands>` block added to `appinfo/info.xml`).
- `lib/Controller/ConfigBundleController.php` — new.
- `appinfo/routes.php` — two new `/settings/config/*` routes.
- `appinfo/info.xml` — `<commands>` registration.
- `templates/settings/admin.php`, `js/admin.js` — Configuration block with Download/Upload +
  result reporting.
- `openspec/specs/config-portability/spec.md` — new canonical spec (via this change's delta).
- `openspec/specs/token-import-export/spec.md` — scope cross-reference (MODIFIED requirements).
- `openspec/specs/admin-settings/spec.md` — new requirement (bundle controls).
- `tests/Unit/Service/ConfigBundleServiceTest.php`, `tests/Unit/Command/ConfigImportTest.php`,
  `tests/Unit/Controller/ConfigBundleControllerTest.php` — new, including the round-trip test
  (export → wipe → import → identical state).
