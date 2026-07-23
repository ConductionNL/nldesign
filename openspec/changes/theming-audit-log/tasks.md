## 1. Audit service

- [ ] 1.1 Create `lib/Service/ThemingAuditService.php` (SPDX docblock; promoted
      `private readonly` deps: `IAppDataFactory` (folder `audit`, file `audit.jsonl`),
      `IConfig`, `IUserSession`, `ITimeFactory`, `LoggerInterface`). Single write API
      `log(string $action, array $context = []): void`: builds the entry
      `{ts (ISO 8601 UTC), actor (uid | "cli" | "system"), action, old, new, tokenSetVersion}`,
      appends one JSON line, increments the `audit_entries_total` app value. Reject unknown
      actions (log a warning, drop the entry) — the vocabulary is the closed set from the spec.
- [ ] 1.2 Value-summary rules inside the service: scalar old/new values verbatim; CSS payloads
      as `sha256:<12 hex>` + byte size; arrays (exclusion lists, override maps) as counts plus
      changed keys. `tokenSetVersion` = manifest `version` field when present in
      `token-sets.json`, else the `installed_version` app value; for `custom-*` sets append the
      content hash.
- [ ] 1.3 Rotation: after append, if `audit.jsonl` > 1 MB (1048576 bytes), move it to
      `audit.jsonl.1` (replacing any existing generation) and start a fresh file. No other
      deletion path exists.
- [ ] 1.4 Read APIs: `getRecent(int $limit): array` (parsed entries, newest first, from current
      + rotated file as needed) and `exportAll()` (streamable concatenation, oldest first,
      rotated generation before current). NO update/delete API (append-only contract).
- [ ] 1.5 Failure posture: every filesystem/appdata Throwable inside `log()` is caught, logged
      as a warning, and swallowed — the calling operation MUST succeed regardless. `@spec` tags
      on all public methods.

## 2. Call-site wiring (complete enumeration)

- [ ] 2.1 `lib/Controller/SettingsController.php` — inject `ThemingAuditService`; wire:
      `setTokenSet()` → `token_set_changed` (old = previous `token_set`, new = requested id);
      `setSloganSetting()` → `toggle_changed` (context key `hide_slogan`);
      `setMenuLabelsSetting()` → `toggle_changed` (context key `show_menu_labels`);
      `updateThemingValues()` → `theming_sync_applied` (old/new = the color/image params
      applied); `setAppTheming()` → `app_exclusions_changed` (old/new = list counts + diff).
      Log only AFTER the underlying write succeeded, never on the validation-error paths.
- [ ] 2.2 `lib/Controller/OverridesController.php` — `setOverrides()` → `overrides_written`
      (old/new = token count + changed keys); `importOverrides()` → `overrides_imported`
      (imported/skipped counts + content hash).
- [ ] 2.3 `lib/Controller/CustomTokenSetController.php` — `upload()` → `custom_set_uploaded`
      (id, name, declaration count, content hash); `delete()` → `custom_set_deleted` (id; note
      when the active set was reset to `nextcloud` by the delete).
- [ ] 2.4 Cross-change wiring (guarded — only if the sibling change is already merged when this
      change is applied; otherwise its own tasks carry this): `ConfigBundleService::import()`
      apply-phase → `config_imported` (change `theme-config-portability`);
      `ThemePreviewService::publishPreview()` → `preview_published` (change
      `theme-preview-workflow`). If absent, skip — the action names stay reserved in the spec.

## 3. Controller, routes, metrics

- [ ] 3.1 Create `lib/Controller/AuditController.php`, `#[AuthorizedAdminSetting(Admin::class)]`
      on both methods (route-auth gate): `list(int $limit = 20): JSONResponse` (capped at 200)
      and `export()` streaming JSONL with Content-Type `application/x-ndjson`,
      Content-Disposition `attachment; filename="nldesign-audit.jsonl"`.
- [ ] 3.2 Routes in `appinfo/routes.php`:
      `['name' => 'audit#list', 'url' => '/settings/audit', 'verb' => 'GET']`,
      `['name' => 'audit#export', 'url' => '/settings/audit/export', 'verb' => 'GET']`
      (route-reachability gate).
- [ ] 3.3 `lib/Controller/MetricsController.php`: emit
      `# HELP nldesign_audit_entries_total Total theming audit entries written` /
      `# TYPE nldesign_audit_entries_total counter` / value from the `audit_entries_total` app
      value cast to int, default `'0'` (same pattern as `theming_syncs_total`; monotonic across
      file rotation).

## 4. Admin UI

- [ ] 4.1 `templates/settings/admin.php`: add a "Theming audit log" block — table mount point
      `#nldesign-audit-table` + "Download full log" link/button to the export URL. Localized
      strings, English keys; table headers Timestamp / User / Action / Details.
- [ ] 4.2 `js/admin.js`: fetch `GET /settings/audit?limit=20` on panel load, render rows
      (escape ALL values via text nodes — audit content is attacker-influenceable via set
      names), show an empty-state message when no entries exist. Vanilla JS, no build step.

## 5. Unit tests

- [ ] 5.1 `tests/Unit/Service/ThemingAuditServiceTest.php`: entry shape (ts/actor/action/
      old/new/tokenSetVersion); actor falls back `uid → cli → system`; unknown action dropped
      with warning; counter app value increments per entry; CSS payload summarized as hash+size
      (never raw CSS in the entry); rotation at >1 MB keeps exactly one generation;
      `getRecent()` order + limit; `exportAll()` includes the rotated generation oldest-first;
      appdata Throwable is swallowed (calling code unaffected) and warned.
- [ ] 5.2 `tests/Unit/Controller/AuditControllerTest.php`: list caps the limit; export headers;
      both admin-annotated.
- [ ] 5.3 Extend the call-site controllers' tests: successful `setTokenSet()` logs exactly one
      `token_set_changed` entry with correct old/new; failed validation logs nothing; audit
      service throwing does not change the endpoint's response.
- [ ] 5.4 Extend `MetricsController` tests for `nldesign_audit_entries_total` (HELP/TYPE/value,
      present in the family list). Run phpunit in the nextcloud:34 container +
      `composer check:strict`; SPDX docblocks on all new files (hydra gates).

## 6. Verify (live, 8080 dev instance)

- [ ] 6.1 As admin on 8080: change the token set (complete the apply dialog). Reload the
      settings panel — the audit table MUST show a `token_set_changed` entry with your uid, the
      old and new set ids, and a timestamp just now.
- [ ] 6.2 Toggle hide-slogan, save an override in the token editor, upload + delete a custom
      set — four more entries with the matching actions appear in order.
- [ ] 6.3 Click "Download full log" — the JSONL file downloads; each line parses as JSON
      (`jq -c . nldesign-audit.jsonl`).
- [ ] 6.4 `curl` `/settings/audit` and `/settings/audit/export` unauthenticated and as a
      non-admin — rejected (route-auth), no content.
- [ ] 6.5 `curl -u admin:<app-password> http://localhost:8080/index.php/apps/nldesign/api/metrics | grep nldesign_audit_entries_total`
      — counter equals the number of entries written and never decreases after further changes.
- [ ] 6.6 Confirm the file lives under the instance's appdata
      (`data/appdata_*/nldesign/audit/audit.jsonl`) and is not reachable over HTTP directly.
