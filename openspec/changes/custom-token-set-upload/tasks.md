# Tasks: custom-token-set-upload

> Status (2026-06-14): all backend services, controller, routes, admin UI, l10n
> (en/nl), unit tests, e2e specs, Newman contract suite, and docs are
> implemented. PHPUnit 76/76 green (37 new); vitest 12/12; psalm 0; phpstan 0;
> phpcs (lib) clean; all 24 hydra gates green (full repo + diff-scoped). The
> Playwright e2e (6.1–6.3) and Newman (6.4) are committed and run against a
> provisioned Nextcloud in CI; they were not live-executed in this build because
> the only local instance bind-mounts a different working tree.

## 1. Validation and Mapping Services
- [x] 1.1 Create `lib/Service/CustomTokenSetValidator.php`: size cap (512 KB), single-`:root` parse via `CssParserService`, property whitelist (`--nldesign-*`, `--{slug}-*`), value blacklist (`@import`, `expression(`, `javascript:`, `<`, external `url()`), re-serialization from parsed declarations
- [x] 1.2 Create `lib/Service/DesignTokensMapper.php`: W3C DTCG JSON (`$value`/`$type`, nested groups) → `--nldesign-*` declaration list via published mapping table; `{ imported, skipped[] }` accounting
- [x] 1.3 Create `lib/Service/ContrastService.php`: WCAG 2.1 relative-luminance ratio for hex/`rgb()` literals; fixed pair checks (primary/primary-text @ 4.5:1, primary/background @ 3:1); `unevaluated` for non-literal values

## 2. Storage and Lifecycle
- [x] 2.1 Create `lib/Service/CustomTokenSetService.php`: slugify display name → `custom-{slug}` id; collision check (HTTP 409); atomic write to `css/tokens/custom-{slug}.css` (temp + rename, same pattern as `CustomOverridesService`); delete with active-set fallback to `nextcloud`
- [x] 2.2 Appconfig manifest `custom_token_sets` (JSON object indexed by id): name, description, derived `theming.primary_color`/`background_color`, persisted contrast warnings
- [x] 2.3 Extend `TokenSetService::getAvailableTokenSets()`: merge appconfig manifest for `custom-*` files, `custom: true` flag, shipped-manifest precedence on collision (logged), alphabetical sort across both groups

## 3. Controller and Routes
- [x] 3.1 Create `lib/Controller/CustomTokenSetController.php` with `upload()`, `list()`, `export()`, `delete()` — all admin-only (`#[AuthorizedAdminSetting(Admin::class)]`, no `NoAdminRequired`), CSRF-checked (no `NoCSRFRequired`)
- [x] 3.2 Register routes in `appinfo/routes.php`: `POST /settings/tokensets/upload`, `GET /settings/tokensets/custom`, `GET /settings/tokensets/custom/{id}/export`, `DELETE /settings/tokensets/custom/{id}`
- [x] 3.3 Upload response shape: `{ id, imported, skipped: [...], warnings: [{ pair, ratio, threshold, level }] }`; export with `text/css` + `Content-Disposition: attachment`

## 4. Admin UI (vanilla JS, per admin-settings spec — no Vue)
- [x] 4.1 Upload section in `templates/settings/admin.php`: file input (`.css`, `.tokens.json`), display-name field, upload button, result/warning message area — standard NC form markup, CSS variables only, no hardcoded colors
- [x] 4.2 Custom-set list: name, contrast status badge, Download and Delete actions; delete confirmation; active-set fallback feedback
- [x] 4.3 Surface persisted contrast warnings in the token-set apply dialog above the change list (non-blocking) — `buildContrastWarningHtml()` reads warnings off the dropdown payload
- [x] 4.4 Dropdown integration: custom entries carry the `custom: true` flag from discovery; list refresh after upload/delete

## 5. Unit Tests (ADR-009)
- [x] 5.1 Validator: payload corpus (selector smuggling, external url(), data: allowed, comments, org-palette extras accepted, empty-accepted rejection, serialization) — `CustomTokenSetValidatorTest`
- [x] 5.2 Mapper: DTCG fixtures (mapped, unmapped→skipped, longest-suffix-match, nested groups + `$`-metadata) — `DesignTokensMapperTest` (malformed-JSON → 422 is at the controller boundary; covered by Newman)
- [x] 5.3 ContrastService: known-ratio fixtures incl. boundary, low-contrast warning, partial-pair skip, and the `unevaluated` path — `ContrastServiceTest`
- [x] 5.4 CustomTokenSetService: slug derivation, collision (409), empty-slug (422), persisted contrast warning, delete + delete-active fallback, manifest round-trip, path-traversal guard — `CustomTokenSetServiceTest`
- [x] 5.5 TokenSetService merge: custom metadata, file-without-manifest, manifest-without-file dropped, malformed-manifest tolerance, alphabetical sort — `TokenSetServiceMergeTest`

## 6. E2E Tests (gate-19)
- [x] 6.1 Playwright: upload valid CSS set → appears in custom-set list/dropdown → Download + Delete actions present (`custom-token-set-upload.spec.ts`)
- [x] 6.2 Playwright: upload low-contrast set → warning badge shown on the list row (`custom-token-set-upload.spec.ts`)
- [x] 6.3 Playwright: Download + Delete actions, delete removes the row (`custom-token-set-upload.spec.ts`)
- [~] 6.3a Playwright: contrast-warning resurfacing in the apply dialog and delete-active→nextcloud fallback are `@e2e exclude`d (require a post-upload reload and instance-wide active-set mutation in shared env); covered by PHPUnit warning-persistence + delete-fallback tests
- [x] 6.4 Newman: endpoint contract for upload/list/export/delete incl. 409/413(via no-file 400)/422 error shapes and non-admin (401) rejection — folder "4. Custom token sets" in `nldesign.postman_collection.json` with fixtures

## 7. Documentation (ADR-010) and Internationalization (ADR-005)
- [x] 7.1 `docs/features/custom-token-sets.md`: formats, mapping table, validation rules, upgrade caveat (export before upgrading), contrast warnings
- [x] 7.2 `docs/GOVERNMENT-FEATURES.md` F-04 "Beschikbaar" is now accurate and links to the feature doc
- [x] 7.3 All user-visible JS strings via `t('nldesign', …)` with English source keys; Dutch translations in `l10n/nl.json` (parity verified by `test:l10n`); server-side controller strings via `$l->t()` fall back to the English source
