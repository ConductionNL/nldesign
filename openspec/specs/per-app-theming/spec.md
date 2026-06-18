---
status: done
---

# per-app-theming Specification

## Purpose
Let an administrator exclude individual Nextcloud apps from NL Design theming. Requests rendering an excluded app's pages receive no nldesign CSS at all (stock Nextcloud styling, header included), while everything else — login, settings, and every non-excluded app — stays themed. Theming stays on by default (empty exclusion list reproduces global injection); admins opt specific apps out to roll a municipal huisstijl out incrementally or quarantine an app that breaks under theming. Backs GOVERNMENT-FEATURES F-15.

## Requirements
### Requirement: Per-App Exclusion List Storage
The set of theming-excluded apps MUST be stored in the `nldesign` appconfig key `disabled_apps` as a JSON array of app id strings, defaulting to an empty array. An empty or absent list MUST reproduce today's behavior exactly (theming injected globally).

#### Scenario: Fresh install / upgrade has no exclusions
@e2e exclude default-config branch — PHPUnit on AppThemingService
- GIVEN the `disabled_apps` appconfig key is absent
- WHEN any page is rendered
- THEN all nldesign CSS MUST be injected exactly as before this change
- AND `AppThemingService::getDisabledApps()` MUST return an empty array

#### Scenario: Unknown app ids self-heal on save
@e2e exclude validation branch — PHPUnit on AppThemingService
- GIVEN a saved list containing `files` and `uninstalled-app`
- AND `uninstalled-app` is not an installed app
- WHEN the admin saves the exclusion list again
- THEN `uninstalled-app` MUST be dropped from the persisted list without an error
- AND `files` MUST be retained

### Requirement: Request-Scoped Boot Guard
`Application::injectThemeCSS()` MUST resolve the app id being rendered from `IRequest::getPathInfo()` (pattern `^/apps/([a-zA-Z0-9_]+)`, working with and without the `index.php` prefix) and MUST skip ALL nldesign style injection (design-system stylesheets, token set CSS, `custom-overrides.css`, `hide-slogan.css`, `show-menu-labels.css`) when that app id is in the exclusion list. Resolution failures MUST fail open to themed (theming is presentation, not security).

#### Scenario: Excluded app renders without any nldesign CSS
- GIVEN the active token set is `rijkshuisstijl`
- AND `calendar` is in the exclusion list
- WHEN a user opens `/apps/calendar/`
- THEN no stylesheet from the `nldesign` app MUST be present in the page head
- AND the page MUST render with stock Nextcloud styling (including the header on that page)

#### Scenario: Non-excluded app stays fully themed
- GIVEN the active token set is `rijkshuisstijl`
- AND `calendar` is in the exclusion list
- WHEN a user opens `/apps/files/`
- THEN all nldesign stylesheets MUST be injected exactly as without any exclusion
- AND the resolved `--nldesign-color-primary` MUST equal the rijkshuisstijl value

#### Scenario: Login and settings pages are always themed
- GIVEN any non-empty exclusion list
- WHEN the login page (`/login`) or an admin settings page (`/settings/admin/theming`) is rendered
- THEN nldesign CSS MUST be injected normally
- AND the exclusion list MUST have no effect on pages whose path does not match `/apps/{appid}`

#### Scenario: index.php-prefixed URLs resolve to the same app id
@e2e exclude path-parsing branch — PHPUnit on the resolver with both URL forms
- GIVEN `calendar` is in the exclusion list
- WHEN a request arrives at `/index.php/apps/calendar/` instead of `/apps/calendar/`
- THEN the resolved app id MUST be `calendar`
- AND the injection MUST be skipped identically for both URL forms

#### Scenario: Resolution failure fails open to themed
@e2e exclude error-path branch — PHPUnit with a throwing request mock
- GIVEN app id resolution throws or the request has no path info (e.g. occ/cron context)
- WHEN `injectThemeCSS()` runs
- THEN the guard MUST treat the app id as unresolved (null)
- AND theming MUST be injected normally without raising an error

### Requirement: App Theming Settings API
The app MUST expose `GET /settings/app-theming` returning the enabled user-facing apps with their themed state (`{ id, name, themed }`), and `POST /settings/app-theming` accepting `{ disabledApps: [...] }` to replace the exclusion list. Both endpoints MUST be admin-only (no `NoAdminRequired`) and CSRF-protected (no `NoCSRFRequired`). The ids `nldesign`, `settings`, and `theming` MUST never be listed nor accepted into the exclusion list.

#### Scenario: Admin reads the per-app theming state
@e2e exclude API contract — Newman collection covers request/response shape
- GIVEN `calendar` is in the exclusion list
- WHEN the admin calls `GET /settings/app-theming`
- THEN the response MUST list each enabled user-facing app with `id`, display `name` (from IAppManager), and `themed` boolean
- AND the entry for `calendar` MUST have `themed: false`
- AND no entry for `nldesign`, `settings`, or `theming` MUST be present

#### Scenario: Posting an exclusion for a protected id is ignored
@e2e exclude guard branch — PHPUnit on the controller
- GIVEN an admin POST with `disabledApps: ["calendar", "nldesign"]`
- WHEN the request is processed
- THEN `calendar` MUST be persisted as excluded
- AND `nldesign` MUST be silently dropped from the persisted list

#### Scenario: Non-admin cannot change the exclusion list
@e2e exclude auth-posture assertion — Newman verifies middleware rejection
- GIVEN a non-admin authenticated user
- WHEN they call `POST /settings/app-theming`
- THEN Nextcloud's SecurityMiddleware MUST reject the request

### Requirement: Per-App Toggle Admin UI
The NL Design settings panel MUST show a "Theming per app" section listing each enabled user-facing app as a labelled checkbox (checked = themed), sorted by display name, with a save action, saved/error feedback, and a localized hint explaining that excluded apps render with stock Nextcloud styling including the header on those pages. The UI MUST use standard Nextcloud form markup and CSS variables only (no hardcoded colors) and MUST be keyboard- and screen-reader-accessible (every checkbox has an associated label, WCAG 2.1 AA).

#### Scenario: Admin excludes an app via the panel
- GIVEN the admin opens the NL Design settings panel with an active token set
- WHEN the admin unchecks "Calendar" in the Theming per app section and saves
- THEN a success message MUST appear
- AND after reloading `/apps/calendar/` the page MUST render without nldesign CSS
- AND after reloading `/apps/files/` the page MUST remain themed

#### Scenario: Admin re-enables theming for an app
- GIVEN `calendar` is excluded
- WHEN the admin checks "Calendar" again and saves
- THEN `/apps/calendar/` MUST render with the active token set after reload

#### Scenario: Checkboxes are accessible
- GIVEN the Theming per app section is rendered
- WHEN inspected for accessibility
- THEN every checkbox MUST be associated with a visible label naming the app
- AND the section MUST be operable by keyboard alone
- AND focus states MUST be visible via the standard token-driven focus indicators

