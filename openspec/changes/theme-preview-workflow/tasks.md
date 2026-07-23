## 1. Preview service

- [ ] 1.1 Create `lib/Service/ThemePreviewService.php` (SPDX docblock, EUPL-1.2, promoted
      `private readonly` constructor deps: `IConfig`, `IGroupManager`, `TokenSetService`,
      `LoggerInterface`). Methods: `startPreview(string $uid, string $tokenSetId): array`
      (validates via `TokenSetService::isValidTokenSet()`, throws/returns error for invalid ids;
      writes user values `preview_token_set` and `preview_expires_at` = `time() + 86400`),
      `getActivePreview(string $uid): ?array` (returns `['tokenSet' => …, 'expiresAt' => …]` or
      null when unset/expired/invalid; opportunistically deletes stale values when called outside
      the boot path), `clearPreview(string $uid): void` (deletes both user values),
      `publishPreview(string $uid): string` (reads the preview id, sets app value `token_set`,
      clears the preview, returns the published id; throws when no active preview).
- [ ] 1.2 Add `@spec openspec/specs/theme-preview/spec.md` tags on every public method
      (spec-coverage gate).

## 2. Controller + routes

- [ ] 2.1 Create `lib/Controller/PreviewController.php` with
      `#[AuthorizedAdminSetting(Admin::class)]` on every method (route-auth + semantic-auth
      gates): `start(string $tokenSet): JSONResponse` (400 for invalid id),
      `discard(): JSONResponse`, `publish(): JSONResponse` (400 when no active preview). Resolve
      the acting uid from `IUserSession` — never from a request parameter.
- [ ] 2.2 Register routes in `appinfo/routes.php`:
      `['name' => 'preview#start', 'url' => '/settings/preview', 'verb' => 'POST']`,
      `['name' => 'preview#discard', 'url' => '/settings/preview', 'verb' => 'DELETE']`,
      `['name' => 'preview#publish', 'url' => '/settings/preview/publish', 'verb' => 'POST']`
      (route-reachability gate: every method routed, every route's method exists).

## 3. Injection layer

- [ ] 3.1 In `lib/AppInfo/Application.php` `injectThemeCSS()`, resolve the effective token set
      per `design.md`: requesting user's `preview_token_set` user value replaces the active
      `token_set` only when the user exists, is an admin (`IGroupManager::isAdmin()`),
      `preview_expires_at` is in the future, and the id still validates. Wrap in try/catch
      falling back to the active set (CLI/occ/cron/no-session safe). Keep the per-app exclusion
      guard running first, unchanged.
- [ ] 3.2 When the preview is active for the requesting user, additionally
      `Util::addScript('nldesign', 'preview-banner')`,
      `Util::addStyle('nldesign', 'preview-banner')`, and provide initial state
      `IInitialState::provideInitialState('preview', ['tokenSet' => …, 'name' => …, 'expiresAt' => …])`.
      No banner assets load for any user without an active preview.

## 4. Banner + admin panel UI (vanilla JS, no build step)

- [ ] 4.1 Create `js/preview-banner.js`: reads state via
      `OCP.InitialState.loadState('nldesign', 'preview')` (initial-state gate — no DOM
      data-attributes), renders a fixed banner (`role="status"`, i18n via `t('nldesign', …)`,
      English keys) with text "Preview: {name} — only visible to you", a **Publish** link to the
      admin theming settings anchor, and a **Discard** button calling
      `DELETE /settings/preview` (with `requesttoken`) then `window.location.reload()`.
- [ ] 4.2 Create `css/preview-banner.css` using NC CSS variables only (no hardcoded colors);
      WCAG AA contrast; banner must not overlap the NC 34 header controls.
- [ ] 4.3 In `templates/settings/admin.php` + `js/admin.js`: add a "Preview in my session"
      button next to the token-set dropdown (posts `POST /settings/preview` with the selected
      id, then reloads); when a preview is active, show an active-preview row (set name +
      Publish + Discard). Publish runs the EXISTING apply dialog and theming-sync dialog flows
      for the previewed set and, on confirmation, calls `POST /settings/preview/publish`
      (instead of `POST /settings/tokenset`). Cancel keeps the preview untouched.

## 5. Unit tests

- [ ] 5.1 `tests/Unit/Service/ThemePreviewServiceTest.php`: start writes both user values with
      ~24h expiry; invalid id rejected; `getActivePreview()` returns null for absent, expired
      (`expires_at` in the past), and no-longer-valid token set ids; `publishPreview()` sets the
      `token_set` app value and clears user values; `clearPreview()` deletes both values;
      publish with no active preview throws.
- [ ] 5.2 `tests/Unit/Controller/PreviewControllerTest.php`: start returns 400 on invalid id;
      discard and publish return ok envelopes; publish with no preview returns 400; acting uid
      comes from `IUserSession`.
- [ ] 5.3 Injection resolution test (Application or extracted helper): non-admin user with a
      (manually planted) preview value still gets the ACTIVE set; expired preview ignored;
      exception in user-session resolution falls back to active set.
- [ ] 5.4 Run in the nextcloud:34 container:
      `docker run --rm -v $PWD:/app -w /app <nc34-image> php vendor/bin/phpunit -c tests/phpunit.xml`
      and `composer check:strict`.

## 6. Verify (live, 8080 dev instance)

- [ ] 6.1 As admin (browser session 1): open Settings → Administration → Theming, select
      "Gemeente Amsterdam", click "Preview in my session". Confirm: banner appears on the
      settings page AND on Files/Dashboard; the header/primary colors are Amsterdam's; occ
      `occ config:app:get nldesign token_set` still returns the previous active set.
- [ ] 6.2 In a second browser session (second user, non-admin — create one if needed, password
      ≥ 10 chars): confirm pages render with the ACTIVE set, no banner, no
      `preview-banner.js` in the page source.
- [ ] 6.3 Session 1: click Discard on the banner → banner gone, admin sees the active set again;
      both user values removed (`occ config:list` / user-value inspection).
- [ ] 6.4 Start the preview again, click Publish → land on the settings panel, apply dialog +
      theming-sync dialog run, confirm → `token_set` app value now `amsterdam`, banner gone,
      second user's next page load shows Amsterdam.
- [ ] 6.5 Expiry: plant `preview_expires_at` in the past via `occ user:setting`, reload —
      preview ignored, active set rendered.
- [ ] 6.6 `curl -X POST` the three `/settings/preview*` endpoints unauthenticated and as the
      non-admin user — all rejected (401/403), no state change.
