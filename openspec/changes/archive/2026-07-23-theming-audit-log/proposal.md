---
kind: code
---

## Why

The compliance-audit flow (research `03-user-wishes-flows.md`, flow 3) is blunt about the gap:
WCAG 2.1/2.2 AA is legally required for gemeente workplaces (Besluit digitale toegankelijkheid /
Wdo — intranets and extranets are in scope per EN 301 549), auditors preparing a
toegankelijkheidsverklaring or WCAG-EM report ask *which huisstijl version was live when*, and
"audit trail (who activated which token-set version when — doesn't exist)". Nextcloud core keeps
no trail of theming changes either — the `admin_audit` app does not cover app-config writes like
nldesign's, and nldesign's own state changes (token set switches, token-editor override writes,
custom set uploads/deletes, per-app exclusions) currently vanish without a trace the moment the
next change overwrites them. Only 4% of Dutch government accessibility declarations are at
status A (`04-nlds-ecosystem-competitors.md`); procurement increasingly demands evidence, and
"who changed the theme, from what, to what, when" is the cheapest evidence there is. M365 Brand
Center — the incumbent being displaced — logs brand changes centrally; nldesign should not lose
that comparison.

Storage decision (justified against IConfig, the app's only other store): the trail must be
**append-only** and **unbounded-ish**. IConfig app values are single mutable rows — every write
replaces the previous value, which is the *opposite* of append-only; a JSON-array-in-one-value
grows unboundedly in a row loaded on every config read, and any writer can silently truncate it.
The app's charter forbids DB tables. Appdata files (via `IAppDataFactory`) are the remaining NC
primitive: append semantics, outside the webroot, not user-writable, survive app-config wipes,
and stream naturally as a download. Hence: **JSONL in appdata** (one JSON object per line),
size-capped rotation at 1 MB with exactly one rotated generation kept (bounded worst-case ~2 MB;
years of theming changes at ~250 bytes/entry — theming changes are rare events). A separate
monotonic IConfig counter feeds the Prometheus metric so rotation never makes the counter go
backwards.

## What Changes

- **New service `lib/Service/ThemingAuditService.php`** with a single write API —
  `log(string $action, array $context = []): void` — called from the app's own service/controller
  layer only. Each entry (one JSONL line) records: `ts` (ISO 8601 UTC), `actor` (uid from
  `IUserSession`, or `cli` for occ, `system` when neither resolves), `action` (fixed vocabulary
  below), `old`/`new` value summaries (strings/small maps — never full CSS bodies; for CSS
  payloads a `sha256:<12 hex>` content hash plus byte size), and `tokenSetVersion` (the
  `token-sets.json` manifest `version` field when one exists, else the nldesign
  `installed_version` app value, which pins shipped set content; custom sets additionally get a
  content hash). Write failures are logged as warnings and NEVER break the calling operation —
  the trail is evidence, not an enforcement gate.
- **Action vocabulary** (spec'd, closed, extensible only by spec change): `token_set_changed`,
  `toggle_changed` (hide-slogan / menu-labels, key in context), `overrides_written`,
  `overrides_imported`, `app_exclusions_changed`, `custom_set_uploaded`, `custom_set_deleted`,
  `theming_sync_applied`, `config_imported` (change `theme-config-portability`),
  `preview_published` (change `theme-preview-workflow`).
- **Storage**: appdata folder `audit`, file `audit.jsonl`; when the file exceeds 1 MB after a
  write, rotate to `audit.jsonl.1` (replacing any previous generation) and start fresh.
  Append-only contract: the service exposes `log()`, `getRecent(int $limit): array`, and
  `exportAll()` (current + rotated, oldest first) — no update or delete API exists.
- **Call-site wiring** (enumerated, the complete set as of this change):
  `SettingsController::setTokenSet()` (token_set_changed), `setSloganSetting()` +
  `setMenuLabelsSetting()` (toggle_changed), `updateThemingValues()` (theming_sync_applied),
  `setAppTheming()` (app_exclusions_changed); `OverridesController::setOverrides()`
  (overrides_written) + `importOverrides()` (overrides_imported);
  `CustomTokenSetController::upload()` (custom_set_uploaded) + `delete()` (custom_set_deleted).
  For `config_imported` and `preview_published`: the vocabulary and requirements land here; the
  wiring lands in whichever of the sibling changes merges second (each change stays
  independently buildable — a guarded task covers the case where they are already merged when
  this change is applied).
- **Admin settings panel**: a "Theming audit log" block showing the most recent 20 entries in a
  table (timestamp, actor, action, summary) via `GET /settings/audit?limit=N`, plus a
  **Download full log** button hitting `GET /settings/audit/export` (streams JSONL, attachment
  `nldesign-audit.jsonl`, rotated generation included). Both on a new
  `lib/Controller/AuditController.php`, `#[AuthorizedAdminSetting(Admin::class)]` — MODIFIED
  `admin-settings` spec (new requirement).
- **Prometheus**: new counter `nldesign_audit_entries_total`, sourced from a monotonic IConfig
  app value `audit_entries_total` incremented inside `log()` (same pattern as
  `theming_syncs_total`; immune to file rotation) — MODIFIED `prometheus-metrics` spec (added
  requirement, exposed by the existing admin-auth `MetricsController`).
- **New canonical spec slug `theming-audit`**.
- Non-breaking: pure addition; failure mode of the audit path is warn-and-continue.

## Impact

- `lib/Service/ThemingAuditService.php` — new.
- `lib/Controller/AuditController.php` — new; `appinfo/routes.php` — two new `/settings/audit*`
  routes.
- `lib/Controller/SettingsController.php`, `lib/Controller/OverridesController.php`,
  `lib/Controller/CustomTokenSetController.php` — inject the service + one `log()` call per
  enumerated site.
- `lib/Controller/MetricsController.php` — emit `nldesign_audit_entries_total`.
- `templates/settings/admin.php`, `js/admin.js` — audit table + download button.
- `openspec/specs/theming-audit/spec.md` — new canonical spec (via this change's delta).
- `openspec/specs/admin-settings/spec.md` — new requirement (audit panel).
- `openspec/specs/prometheus-metrics/spec.md` — added counter requirement.
- `tests/Unit/Service/ThemingAuditServiceTest.php`,
  `tests/Unit/Controller/AuditControllerTest.php` — new; MetricsController test extended.
- Cross-referenced, not depended on: changes `theme-config-portability`,
  `theme-preview-workflow` (their actions are in the vocabulary; wiring in whichever lands
  second).
