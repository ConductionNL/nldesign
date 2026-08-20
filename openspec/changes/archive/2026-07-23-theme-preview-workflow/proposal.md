---
kind: code
---

## Why

Today, selecting a token set in the nldesign admin panel applies it **instantly,
instance-wide**: `SettingsController::setTokenSet()` writes the `token_set` app value
(`lib/Controller/SettingsController.php:147`) and the very next page render of *every* user on
the instance picks it up in `Application::injectThemeCSS()`
(`lib/AppInfo/Application.php:120`). There is no way to see a token set live, on real pages, in
one's own session, before committing the whole organisation to it. The apply dialog's per-token
diff (`token-set-apply-dialog` spec, `TokenSetPreviewService`) and the settings panel's static
preview box (`admin-settings` REQ-ASET-004) both preview *inside the settings page only* — a
mocked header bar and two buttons, not the actual Files/Calendar/login surfaces where theming
regressions actually show up (nldesign #123 documented preview drift between the preview box and
the real render; #128 documented silently-swallowed typo'd tokens that only surface on real
pages).

This mismatch maps directly onto how gemeenten roll out huisstijl (research
`03-user-wishes-flows.md`, flow 1 "Admin rollout"): the communications department owns the
huisstijl (via brand portals such as Huisstijlmanager/Prindustry) and must approve what goes
live; IT operates the Nextcloud instance and executes. The pain recorded there is verbatim "no
preview-and-approve loop (theming applies instantly instance-wide)". The Dutch working term is
*proefdraaien*: an admin should be able to trial-run a token set across the real product in
their own session, walk through the pages with communications looking over their shoulder (or
screen-share), and only then publish. M365 SharePoint Brand Center — the incumbent nldesign
displaces (`04-nlds-ecosystem-competitors.md`) — has exactly this preview-before-enforce loop;
its absence here is a competitive and an operational gap.

Because nldesign has no DB tables and no Vue (`01-codebase-inventory.md`), the mechanism must be
IConfig **user values** plus vanilla JS — both of which the app already uses everywhere.

## What Changes

- **New service `lib/Service/ThemePreviewService.php`** managing per-user session preview state
  in IConfig *user* values (app `nldesign`): `preview_token_set` (a token set id) and
  `preview_expires_at` (unix timestamp, now + 24h). Not app values — so no other user can ever
  be affected. Exposes `startPreview(uid, tokenSetId)`, `getActivePreview(uid): ?array`
  (returns null when unset, expired, or the id no longer validates), `clearPreview(uid)`, and
  `publishPreview(uid)` (promotes the previewed id to the instance-wide `token_set` app value,
  then clears the user values). Expiry is lazy — read-time — because the app has no background
  jobs and must not grow one for this.
- **New controller `lib/Controller/PreviewController.php`** with three routes, all
  `#[AuthorizedAdminSetting(Admin::class)]` (route-auth gate): `POST /settings/preview` (start),
  `DELETE /settings/preview` (discard), `POST /settings/preview/publish` (publish). Invalid
  token set ids are rejected with 400 via `TokenSetService::isValidTokenSet()`.
- **CSS injection layer honours preview**: `Application::injectThemeCSS()`
  (`lib/AppInfo/Application.php`) resolves the *effective* token set: if the requesting user has
  a non-expired preview **and is (still) an admin**, the previewed set replaces the active
  `token_set` for that render only — the whole existing cascade (design-system resolution, token
  stylesheet, contrast fixes) follows the substituted id unchanged; custom overrides and the
  hide-slogan/menu-labels toggles keep their active values. All resolution failures (no session,
  CLI/occ, cron) fall through to the active set. This check is specified against "the CSS
  injection layer" as a contract, not against `boot()` as a location: change
  `render-event-injection` (this wave) moves injection to a `BeforeTemplateRenderedEvent`
  listener, and the same effective-token-set resolution MUST move with it. Cross-reference only
  — this change does **not** depend on `render-event-injection` and lands against today's
  boot-time injection.
- **Persistent preview banner** on every themed page for the previewing user only: new
  `js/preview-banner.js` + `css/preview-banner.css` (vanilla JS, no build step, per
  REQ-ASET-008's architecture), loaded from the injection layer only when a preview is active,
  with state passed via `IInitialState::provideInitialState()` (initial-state gate: no DOM
  data-attribute reads). Banner text "Preview: {name} — this is only visible to you", with
  **Publish** and **Discard** buttons. Discard calls `DELETE /settings/preview` and reloads.
  Publish deep-links to the nldesign admin settings panel, where the existing apply dialog and
  theming-sync dialog run exactly as they do today for an instance-wide change (those specs are
  untouched); completing that flow calls `POST /settings/preview/publish`.
- **Admin settings panel gains a "Preview in my session" button** next to the token-set
  dropdown, and, while a preview is active, shows which set is being previewed with
  Publish/Discard — MODIFIED `admin-settings` canonical spec (new controls added as a new
  requirement).
- **New canonical spec slug `theme-preview`** for the preview lifecycle, isolation, expiry, and
  banner requirements. (Distinct from the existing `TokenSetPreviewService`/apply-dialog
  per-token diff preview, which stays as-is.)
- Non-breaking: with no preview user values set, injection behaviour is byte-identical to today.

## Impact

- `lib/Service/ThemePreviewService.php` — new.
- `lib/Controller/PreviewController.php` — new.
- `lib/AppInfo/Application.php` — effective-token-set resolution + conditional banner
  script/style/initial-state injection in `injectThemeCSS()`.
- `appinfo/routes.php` — three new `/settings/preview*` routes.
- `js/preview-banner.js`, `css/preview-banner.css` — new (vanilla, no build step).
- `js/admin.js`, `templates/settings/admin.php` — "Preview in my session" button + active-preview
  controls in the settings panel.
- `openspec/specs/theme-preview/spec.md` — new canonical spec (via this change's delta).
- `openspec/specs/admin-settings/spec.md` — new requirement (settings-panel preview controls).
- `tests/Unit/Service/ThemePreviewServiceTest.php`, `tests/Unit/Controller/PreviewControllerTest.php`
  — new.
- Cross-referenced, not depended on: change `render-event-injection` (injection-layer contract
  carries over).
