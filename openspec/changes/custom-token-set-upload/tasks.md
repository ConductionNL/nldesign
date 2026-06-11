# Tasks: custom-token-set-upload

## 1. Validation and Mapping Services
- [ ] 1.1 Create `lib/Service/CustomTokenSetValidator.php`: size cap (512 KB), single-`:root` parse via `CssParserService`, property whitelist (`--nldesign-*`, `--{slug}-*`), value blacklist (`@import`, `expression(`, `javascript:`, `<`, external `url()`), re-serialization from parsed declarations
- [ ] 1.2 Create `lib/Service/DesignTokensMapper.php`: W3C DTCG JSON (`$value`/`$type`, nested groups) → `--nldesign-*` declaration list via published mapping table; `{ imported, skipped[] }` accounting
- [ ] 1.3 Create `lib/Service/ContrastService.php`: WCAG 2.1 relative-luminance ratio for hex/`rgb()` literals; fixed pair checks (primary/primary-text @ 4.5:1, primary/background @ 3:1); `unevaluated` for non-literal values

## 2. Storage and Lifecycle
- [ ] 2.1 Create `lib/Service/CustomTokenSetService.php`: slugify display name → `custom-{slug}` id; collision check (HTTP 409); atomic write to `css/tokens/custom-{slug}.css` (temp + rename, same pattern as `CustomOverridesService`); delete with active-set fallback to `nextcloud`
- [ ] 2.2 Appconfig manifest `custom_token_sets` (JSON object indexed by id): name, description, derived `theming.primary_color`/`background_color`, persisted contrast warnings
- [ ] 2.3 Extend `TokenSetService::getAvailableTokenSets()`: merge appconfig manifest for `custom-*` files, `custom: true` flag, shipped-manifest precedence on collision (logged), alphabetical sort across both groups

## 3. Controller and Routes
- [ ] 3.1 Create `lib/Controller/CustomTokenSetController.php` with `upload()`, `list()`, `export()`, `delete()` — all admin-only (no `NoAdminRequired`), CSRF-checked (no `NoCSRFRequired`)
- [ ] 3.2 Register routes in `appinfo/routes.php`: `POST /settings/tokensets/upload`, `GET /settings/tokensets/custom`, `GET /settings/tokensets/custom/{id}/export`, `DELETE /settings/tokensets/custom/{id}`
- [ ] 3.3 Upload response shape: `{ id, imported, skipped: [...], warnings: [{ pair, ratio, threshold, level }] }`; export with `text/css` + `Content-Disposition: attachment`

## 4. Admin UI (vanilla JS, per admin-settings spec — no Vue)
- [ ] 4.1 Upload section in `templates/settings/admin.php`: file input (`.css`, `.tokens.json`), display-name field, upload button, result/warning message area — standard NC form markup, CSS variables only, no hardcoded colors
- [ ] 4.2 Custom-set list: name, contrast status badge, Download and Delete actions; delete confirmation; active-set fallback feedback
- [ ] 4.3 Surface persisted contrast warnings in the token-set apply dialog above the change list (non-blocking)
- [ ] 4.4 Dropdown integration: "(custom)" labeling for `custom: true` entries; list refresh after upload/delete

## 5. Unit Tests (ADR-009)
- [ ] 5.1 Validator: payload corpus (selector smuggling, external url(), oversized, comments, org-palette extras accepted)
- [ ] 5.2 Mapper: DTCG fixtures (mapped, unmapped→skipped, malformed JSON → 422)
- [ ] 5.3 ContrastService: known-ratio fixtures incl. boundary 4.5:1 and `unevaluated` path
- [ ] 5.4 CustomTokenSetService: slug derivation, collision, delete-active fallback, manifest round-trip
- [ ] 5.5 TokenSetService merge: custom metadata, file-without-manifest, manifest-without-file, precedence on collision

## 6. E2E Tests (gate-19)
- [ ] 6.1 Playwright: upload valid CSS set → appears in dropdown → apply dialog shows → activate → token visible on page
- [ ] 6.2 Playwright: upload low-contrast set → warning shown on upload and again in apply dialog → apply still possible
- [ ] 6.3 Playwright: download round-trip and delete (incl. delete-active fallback to nextcloud)
- [ ] 6.4 Newman: endpoint contract for upload/list/export/delete incl. 409/413/422 error shapes and non-admin rejection

## 7. Documentation (ADR-010) and Internationalization (ADR-005)
- [ ] 7.1 `docs/features/custom-token-sets.md`: formats, mapping table, validation rules, upgrade caveat (files in app dir — export before upgrading), contrast warnings
- [ ] 7.2 Verify `docs/GOVERNMENT-FEATURES.md` F-04 "Beschikbaar" is now accurate; link it to the feature doc
- [ ] 7.3 All user-visible strings via `$l->t()` / `t('nldesign', …)` with English source keys; Dutch translations in `l10n/nl.json`
