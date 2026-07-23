# Per-App Theming — Render-Event Guard Delta

**Spec refs**: `per-app-theming` (Request-Scoped Boot Guard), `css-architecture` (companion
delta in this change)
**Standards**: Nextcloud app framework events; `TemplateResponse::getApp()` as the authoritative
rendering-app signal

## MODIFIED Requirements

### Requirement: Request-Scoped Boot Guard

The per-app exclusion guard MUST run inside the `ThemeInjectionListener` when handling
`BeforeTemplateRenderedEvent`, before any style injection (historical requirement name retained
for continuity — the guard now runs at render-event time, not boot time). The app id being rendered MUST be
resolved primarily from the event's `TemplateResponse::getApp()`; when that value is empty or
`core`, the listener MUST fall back to
`AppThemingService::resolveAppIdFromPath(IRequest::getPathInfo())` (pattern
`^/apps/([a-zA-Z0-9_]+)`, working with and without the `index.php` prefix). When the resolved app
id is in the exclusion list, ALL nldesign style injection MUST be skipped for that render
(design-system stylesheets, token set CSS, `icon-contrast`/`error-contrast`,
`custom-overrides.css`, `hide-slogan.css`, `show-menu-labels.css`). The guard MUST NOT be applied
when handling `BeforeLoginTemplateRenderedEvent` (login is never an app page). Resolution
failures MUST fail open to themed (theming is presentation, not security).

#### Scenario: Excluded app renders without any nldesign CSS
- GIVEN the active token set is `rijkshuisstijl`
- AND `calendar` is in the exclusion list
- WHEN a user opens `/apps/calendar/` and its `TemplateResponse` (app id `calendar`) dispatches
  `BeforeTemplateRenderedEvent`
- THEN no stylesheet from the `nldesign` app MUST be present in the page head
- AND the page MUST render with stock Nextcloud styling (including the header on that page)

#### Scenario: Non-excluded app stays fully themed
- GIVEN the active token set is `rijkshuisstijl`
- AND `calendar` is in the exclusion list
- WHEN a user opens `/apps/files/`
- THEN all nldesign stylesheets MUST be injected exactly as without any exclusion
- AND the resolved `--nldesign-color-primary` MUST equal the rijkshuisstijl value

#### Scenario: Response app id takes precedence over the URL
@e2e exclude resolver-precedence branch — PHPUnit on the listener with a mocked TemplateResponse
- GIVEN `calendar` is in the exclusion list
- AND a `BeforeTemplateRenderedEvent` whose response reports app id `calendar`
- WHEN the guard resolves the app id
- THEN it MUST use the response's app id without consulting the request path
- AND injection MUST be skipped

#### Scenario: Empty or core response app falls back to path resolution
@e2e exclude fallback branch — PHPUnit on the listener with both URL forms
- GIVEN `calendar` is in the exclusion list
- AND a `BeforeTemplateRenderedEvent` whose response reports an empty app id or `core`
- WHEN the request path is `/apps/calendar/` or `/index.php/apps/calendar/`
- THEN the fallback resolver MUST yield `calendar` for both URL forms
- AND injection MUST be skipped identically for both

#### Scenario: Login and settings pages are always themed
- GIVEN any non-empty exclusion list
- WHEN the login page (`/login`, via `BeforeLoginTemplateRenderedEvent`) or an admin settings
  page (`/settings/admin/theming`) is rendered
- THEN nldesign CSS MUST be injected normally
- AND the guard MUST NOT run on the login event at all
- AND the exclusion list MUST have no effect on pages that resolve to no `/apps/{appid}` app

#### Scenario: Resolution failure fails open to themed
@e2e exclude error-path branch — PHPUnit with a throwing request/response mock
- GIVEN app id resolution throws, or both the response app id and the request path yield no app
  id
- WHEN the listener handles the render event
- THEN the guard MUST treat the app id as unresolved (null)
- AND theming MUST be injected normally without raising an error
