# Design: per-app-theming-toggle

## Context

All nldesign CSS is injected unconditionally during `Application::boot()` → `injectThemeCSS()`: design-system stylesheets, `tokens/{token_set}.css`, `custom-overrides.css`, plus the `hide-slogan` / `show-menu-labels` toggles. `boot()` runs for every authenticated page render, including the login page (which is why theming works there). There is no notion of "which app is being rendered" anywhere in the app today.

## Goals / Non-Goals

**Goals**
- Admin can exclude specific apps from nldesign theming; everything else stays themed
- Zero behavior change for upgrades with no exclusions configured
- Admin can never lock themselves out of the settings UI

**Non-Goals**
- Per-layer toggles (e.g. "tokens yes, menu-labels no" per app)
- Per-group or per-user theming scoping (separate future change; different mechanism)
- DOM-level scoping that keeps the global header themed inside an excluded app

## Decisions

### D1 — Suppression mechanism: skip `addStyle()` at boot (request-scoped)

| Option | Pros | Cons |
|---|---|---|
| **A. Skip CSS injection for the request (chosen)** | Trivially correct — excluded app renders byte-identical to nldesign-disabled; no CSS rewrite; works for all current and future token sets | Header/nav on that app's pages also unthemed (accepted, documented) |
| B. Scope selectors per app (`body#body-app-x …`) | Header could stay themed | `:root` custom properties cannot be conditionally scoped without rewriting every token file and the whole mapping architecture (css-architecture spec); fragile against NC DOM changes |
| C. Frontend JS removes stylesheets on excluded apps | No PHP changes | FOUC of themed→unthemed; CSP concerns; trivially defeated by slow JS; wrong layer |

### D2 — App id resolution

`injectThemeCSS()` receives the server container; it additionally inspects `IRequest`:

```
pathInfo = request->getPathInfo()           // e.g. "/apps/files/...", works with and without index.php prefix
if pathInfo matches "#^/apps/([a-zA-Z0-9_]+)#" → appId = $1
else → appId = null (login, /settings, /s/{token}, dav, ocs, …)
```

- `appId === null` → always themed (login page, settings, share links are the huisstijl's most important surfaces).
- `appId` in `disabled_apps` → skip every `Util::addStyle()` call for this request.
- Resolution failures (CLI/occ, cron, exceptions) → fail open to themed: the guard wraps resolution in a try/catch and treats errors as `null`. Theming is presentation, never security — fail-open is correct here.

### D3 — Persistence and API

- Appconfig key: `nldesign/disabled_apps`, JSON array of app id strings, default `[]`.
- `AppThemingService`: `getDisabledApps(): array`, `setDisabledApps(array $appIds): void` (validates each id against `IAppManager::isInstalled()`; unknown ids are dropped, not errored, so stale entries from uninstalled apps self-heal on next save), `isThemingDisabledFor(?string $appId): bool`.
- Routes: `GET /settings/app-theming` (list of enabled apps with `{ id, name, themed }`) and `POST /settings/app-theming` (`{ disabledApps: [...] }`). Admin-only (no `NoAdminRequired`), CSRF-checked.
- `nldesign` itself, `settings`, and `theming` are not offered in the UI (the panel lives in settings; excluding these is meaningless or footgun).

### D4 — Admin UI

Vanilla template + JS per the admin-settings spec. A collapsible "Theming per app" section under the existing toggles:

- One labelled checkbox per enabled user-facing app (from `IAppManager`), checked = themed, sorted by display name.
- Save button + saved/error feedback via the existing message pattern; standard NC form classes; CSS variables only.
- An explanatory hint that excluded apps render with stock Nextcloud styling including the header on those pages (D1 trade-off made visible to the admin).

## Risks / Trade-offs

- **Visual inconsistency across navigation** (themed header on app A, stock header on excluded app B) — inherent to request-scoped suppression; documented in the UI hint and feature docs.
- **Apps rendered outside `/apps/{id}` paths** (e.g. public share pages of an app) resolve to `null` and stay themed — acceptable: the toggle's contract is the app's own pages.
- **Appconfig read per request** — NC caches appconfig in memory/distributed cache; no measurable cost.

## Open Questions

- Should excluded apps also suppress the `NLDesignTheme` ITheme registration (if/when that is used for dark-variant work)? Out of scope until the theme class actually injects per-request CSS.
