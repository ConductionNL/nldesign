# Tasks: per-app-theming-toggle

## 1. Service Layer
- [x] 1.1 Create `lib/Service/AppThemingService.php`: `getDisabledApps()`, `setDisabledApps()` (validate via `IAppManager::isInstalled()`, drop unknown ids, never accept `nldesign`/`settings`/`theming`), `isThemingDisabledFor(?string $appId)`
- [x] 1.2 App id resolver: `IRequest::getPathInfo()` → `^/apps/([a-zA-Z0-9_]+)` (handles `index.php` prefix); try/catch fail-open to null (occ/cron/error contexts stay themed)

## 2. Boot Guard
- [x] 2.1 Guard `Application::injectThemeCSS()`: resolve app id, early-return before ANY `Util::addStyle()` call when excluded (design-system stylesheets, token set, custom-overrides, hide-slogan, show-menu-labels)
- [x] 2.2 Verify zero behavior change when `disabled_apps` is absent/empty (default `[]`)

## 3. Controller and Routes
- [x] 3.1 Add `SettingsController::getAppTheming()` and `setAppTheming()` — admin-only (no `NoAdminRequired`), CSRF-checked; GET returns `{ id, name, themed }` per enabled user-facing app; POST accepts `{ disabledApps: [...] }`
- [x] 3.2 Register `GET`/`POST /settings/app-theming` in `appinfo/routes.php`

## 4. Admin UI (vanilla JS, per admin-settings spec — no Vue)
- [x] 4.1 "Theming per app" section in `templates/settings/admin.php`: labelled checkbox per app (checked = themed), sorted by display name, save button, feedback area, localized stock-styling hint — standard NC form markup, CSS variables only
- [x] 4.2 JS: fetch `GET /settings/app-theming` on init, POST on save, success/error feedback via the existing message pattern
- [x] 4.3 Accessibility: label-for wiring on every checkbox, keyboard operability, token-driven focus states (WCAG 2.1 AA)

## 5. Unit Tests (ADR-009)
- [x] 5.1 AppThemingService: default empty, unknown-id self-heal, protected-id drop, round-trip
- [x] 5.2 Resolver: `/apps/x`, `/index.php/apps/x`, `/settings/...`, `/login`, no-path/throwing request → null
- [ ] 5.3 Boot guard: excluded app skips all addStyle calls (assert via Util wrapper/spy) — DEFERRED: no static-Util spy harness exists in this app; the boot guard is covered end-to-end by the gate-19 e2e (6.1)

## 6. E2E Tests (gate-19) and API Tests
- [x] 6.1 Playwright: exclude an app via panel → its pages have no nldesign stylesheets, /apps/files still themed → re-enable → themed again (`tests/e2e/spec-coverage/app-theming.spec.ts`)
- [x] 6.2 Playwright: settings pages remain themed with a non-empty exclusion list
- [ ] 6.3 Newman: GET/POST /settings/app-theming contract, protected-id drop, non-admin 403 — DEFERRED follow-up

## 7. Documentation (ADR-010) and Internationalization (ADR-005)
- [x] 7.1 Extend `docs/features/toggles.md` with the per-app toggle, incl. the documented trade-off (excluded app pages render fully stock, header included)
- [x] 7.2 Verify `docs/GOVERNMENT-FEATURES.md` F-15 "Beschikbaar" is now accurate; link to the feature doc
- [x] 7.3 All user-visible strings via `$l->t()` with English source keys; Dutch translations in `l10n/nl.json` (+ de/fr/es/it parity)
