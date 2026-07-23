## 1. Preview service

- [x] 1.1 Create `lib/Service/ThemePreviewService.php` (SPDX docblock, EUPL-1.2, promoted
      `private readonly` constructor deps: `IConfig`, `IGroupManager`, `TokenSetService`,
      `LoggerInterface`). Methods: `startPreview(string $uid, string $tokenSetId): array`
      (validates via `TokenSetService::isValidTokenSet()`, throws/returns error for invalid ids;
      writes user values `preview_token_set` and `preview_expires_at` = `time() + 86400`),
      `getActivePreview(string $uid): ?array` (returns `['tokenSet' => …, 'expiresAt' => …]` or
      null when unset/expired/invalid; opportunistically deletes stale values when called outside
      the boot path), `clearPreview(string $uid): void` (deletes both user values),
      `publishPreview(string $uid): string` (reads the preview id, sets app value `token_set`,
      clears the preview, returns the published id; throws when no active preview). Also adds
      `resolveEffectiveTokenSet(IUserSession $userSession, string $activeTokenSet): array` — the
      single, testable home of the whole "effective token set for this render" contract (user
      resolution, the demotion-defence admin re-check, expiry, validity, fail-open on exception)
      that the CSS injection layer calls, so a future relocation of the CALL SITE (e.g. change
      `render-event-injection`) never has to re-derive the RULES.
      (Note: constructor deps use plain `private` properties + a body-assigned constructor, not
      PHP 8 promoted-`readonly` params, to match this app's existing service style — see e.g.
      `TokenSetService`, `ThemingAuditService`.)
- [x] 1.2 Add `@spec openspec/specs/theme-preview/spec.md` tags on every public method
      (spec-coverage gate).

## 2. Controller + routes

- [x] 2.1 Create `lib/Controller/PreviewController.php` with
      `#[AuthorizedAdminSetting(Admin::class)]` on every method (route-auth + semantic-auth
      gates): `start(string $tokenSet): JSONResponse` (400 for invalid id),
      `discard(): JSONResponse`, `publish(): JSONResponse` (400 when no active preview). Resolve
      the acting uid from `IUserSession` — never from a request parameter.
- [x] 2.2 Register routes in `appinfo/routes.php`:
      `['name' => 'preview#start', 'url' => '/settings/preview', 'verb' => 'POST']`,
      `['name' => 'preview#discard', 'url' => '/settings/preview', 'verb' => 'DELETE']`,
      `['name' => 'preview#publish', 'url' => '/settings/preview/publish', 'verb' => 'POST']`
      (route-reachability gate: every method routed, every route's method exists).

## 3. Injection layer

- [x] 3.1 In `lib/AppInfo/Application.php` `injectThemeCSS()`, resolve the effective token set
      per `design.md`: requesting user's `preview_token_set` user value replaces the active
      `token_set` only when the user exists, is an admin (`IGroupManager::isAdmin()`),
      `preview_expires_at` is in the future, and the id still validates. Wrap in try/catch
      falling back to the active set (CLI/occ/cron/no-session safe). Keep the per-app exclusion
      guard running first, unchanged.
      (Implemented as a thin `resolvePreview()` private helper that delegates entirely to
      `ThemePreviewService::resolveEffectiveTokenSet()` — the ONE place the contract lives, per
      the sibling `render-event-injection` change's relocation note. `injectThemeCSS()` was also
      split into `resolvePreview()` + `injectPreviewBanner()` to stay under the
      ExcessiveMethodLength phpmd threshold.)
- [x] 3.2 When the preview is active for the requesting user, additionally
      `Util::addScript('nldesign', 'preview-banner')`,
      `Util::addStyle('nldesign', 'preview-banner')`, and provide initial state
      `IInitialState::provideInitialState('preview', ['tokenSet' => …, 'name' => …, 'expiresAt' => …])`.
      No banner assets load for any user without an active preview.
      (`IInitialState` is resolved from the app-scoped container — `IBootContext::getAppContainer()`
      — since it is bound per-app; `Application::boot()` was updated to pass it through.)

## 4. Banner + admin panel UI (vanilla JS, no build step)

- [x] 4.1 Create `js/preview-banner.js`: reads state via
      `OCP.InitialState.loadState('nldesign', 'preview')` (initial-state gate — no DOM
      data-attributes), renders a fixed banner (`role="status"`, i18n via `t('nldesign', …)`,
      English keys) with text "Preview: {name} — only visible to you", a **Publish** link to the
      admin theming settings anchor, and a **Discard** button calling
      `DELETE /settings/preview` (with `requesttoken`) then `window.location.reload()`.
- [x] 4.2 Create `css/preview-banner.css` using NC CSS variables only (no hardcoded colors);
      WCAG AA contrast; banner must not overlap the NC 34 header controls.
      (Fixed to the viewport BOTTOM rather than top, specifically so it can never overlap the
      header controls, per NC 34's header occupying the top of the viewport.)
- [x] 4.3 In `templates/settings/admin.php` + `js/admin.js`: add a "Preview in my session"
      button next to the token-set dropdown (posts `POST /settings/preview` with the selected
      id, then reloads); when a preview is active, show an active-preview row (set name +
      Publish + Discard). Publish runs the EXISTING apply dialog and theming-sync dialog flows
      for the previewed set and, on confirmation, calls `POST /settings/preview/publish`
      (instead of `POST /settings/tokenset`). Cancel keeps the preview untouched.
      (The active-preview row's data — like `currentTokenSet`/`tokenSets` already do — is
      server-rendered by `lib/Settings/Admin.php` into a `data-active-preview` attribute on
      `#nldesign-settings`, mirroring this template's existing convention, rather than a second
      client-side fetch. `openTokenSetApplyDialog()`/`showApplyDialog()`/`saveTokenSet()` gained
      an optional `publishMode` parameter so the SAME dialog functions are reused verbatim for
      both the direct instance-wide path and the preview-publish path — only the terminal commit
      endpoint differs, via a new small `commitTokenSetChange()` helper.)

## 5. Unit tests

- [x] 5.1 `tests/Unit/Service/ThemePreviewServiceTest.php`: start writes both user values with
      ~24h expiry; invalid id rejected; `getActivePreview()` returns null for absent, expired
      (`expires_at` in the past), and no-longer-valid token set ids; `publishPreview()` sets the
      `token_set` app value and clears user values; `clearPreview()` deletes both values;
      publish with no active preview throws. Also covers `resolveEffectiveTokenSet()`: no user,
      no preview value (and the admin check is never even called — the zero-cost path), demoted
      user, expired, invalid token set, the happy path, and a thrown exception during user
      resolution falling back to the active set. 16 tests, 36 assertions.
- [x] 5.2 `tests/Unit/Controller/PreviewControllerTest.php`: start returns 400 on invalid id;
      discard and publish return ok envelopes; publish with no preview returns 400; acting uid
      comes from `IUserSession`; all three methods carry `#[AuthorizedAdminSetting(Admin::class)]`;
      no user session returns 400 on every endpoint without touching the service. 7 tests, 20
      assertions.
- [x] 5.3 Injection resolution test (Application or extracted helper): non-admin user with a
      (manually planted) preview value still gets the ACTIVE set; expired preview ignored;
      exception in user-session resolution falls back to active set.
      (Covered directly on `ThemePreviewService::resolveEffectiveTokenSet()` — the extracted,
      fully-testable helper `Application::injectThemeCSS()` delegates to via `resolvePreview()` —
      rather than reflecting into `Application`'s private methods, which would need a full NC
      server-container bootstrap unavailable in this standalone unit-test harness.)
- [x] 5.4 Run in the nextcloud:34 container:
      `docker run --rm -v $PWD:/app -w /app <nc34-image> php vendor/bin/phpunit -c tests/phpunit.xml`
      and `composer check:strict`.
      Results: 358 tests total pass (23 new for this change + 335 pre-existing, run via
      `phpunit-unit.xml`, the app's actual unit-test config); `tests/Unit/Mail/NLDesignEMailTemplateTest.php`
      fails to even load in this standalone container (`Class "OC\Mail\EMailTemplate" not found`)
      — the documented, pre-existing harness limitation, not a regression from this change (the
      full-suite `composer check:strict`'s `test:all` step degrades to its designed
      "Tests require Nextcloud environment, skipping..." fallback for the same reason).
      `composer check:strict` (lint/phpcs/phpmd/psalm/phpstan) is fully green with zero findings
      in the changed files.

## 6. Verify (live, 8080 dev instance)

- [ ] 6.1 As admin (browser session 1): open Settings → Administration → Theming, select
      "Gemeente Amsterdam", click "Preview in my session". Confirm: banner appears on the
      settings page AND on Files/Dashboard; the header/primary colors are Amsterdam's; occ
      `occ config:app:get nldesign token_set` still returns the previous active set.
      (deferred to post-merge live verification)
- [ ] 6.2 In a second browser session (second user, non-admin — create one if needed, password
      ≥ 10 chars): confirm pages render with the ACTIVE set, no banner, no
      `preview-banner.js` in the page source.
      (deferred to post-merge live verification)
- [ ] 6.3 Session 1: click Discard on the banner → banner gone, admin sees the active set again;
      both user values removed (`occ config:list` / user-value inspection).
      (deferred to post-merge live verification)
- [ ] 6.4 Start the preview again, click Publish → land on the settings panel, apply dialog +
      theming-sync dialog run, confirm → `token_set` app value now `amsterdam`, banner gone,
      second user's next page load shows Amsterdam.
      (deferred to post-merge live verification)
- [ ] 6.5 Expiry: plant `preview_expires_at` in the past via `occ user:setting`, reload —
      preview ignored, active set rendered.
      (deferred to post-merge live verification)
- [ ] 6.6 `curl -X POST` the three `/settings/preview*` endpoints unauthenticated and as the
      non-admin user — all rejected (401/403), no state change.
      (deferred to post-merge live verification)
