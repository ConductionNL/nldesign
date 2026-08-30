# Proposal: per-app-theming-toggle

## Why

`docs/GOVERNMENT-FEATURES.md` F-15 claims "Toggle per app (aan/uit) — per-app theming activeren" with status **Beschikbaar**. No per-app enable/disable exists anywhere in `lib/`, `js/`, or `templates/` — the app's CSS is injected globally for every request via `Application::boot()`, and `docs/features/toggles.md` only documents hide-slogan and menu-labels. This is an over-claim in a compliance checklist used by procuring municipalities.

The feature is also genuinely needed: NL Design token sets restyle every Nextcloud app, and some apps (complex third-party UIs, apps with their own hardcoded styling assumptions, or apps mid-migration) can break or look wrong under a municipal huisstijl. Today an admin's only options are all-or-nothing. A per-app exclusion list lets a municipality roll the huisstijl out incrementally and quarantine a misbehaving app without losing theming everywhere else.

## What Changes

- **NEW** — Admin-managed per-app theming exclusion list: a checkbox list of installed, enabled apps in the NL Design settings panel; unchecking an app disables ALL nldesign CSS injection (design-system stylesheets, token set, custom overrides, hide-slogan, menu-labels) for requests rendering that app's pages
- **NEW** — Boot-time guard in `Application::injectThemeCSS()`: resolves the app id being rendered from the request path (`/apps/{appid}/…`, with and without `index.php` prefix) and skips all `Util::addStyle()` calls when the app is excluded
- **NEW** — `GET`/`POST /settings/app-theming` endpoints to read and persist the exclusion list (appconfig key `disabled_apps`, JSON array of app ids)
- **NEW** — Documentation: `docs/features/toggles.md` gains the per-app toggle; F-15 becomes genuinely true

## Capabilities

### New Capabilities
- `per-app-theming` — Per-app exclusion of nldesign theming: admin UI, persistence, request-scoped boot guard, and the precise scope rules (which pages are app pages, what always stays themed)

## Decisions

1. **Exclusion list, not inclusion list**: theming stays ON for every app by default; admins opt specific apps OUT. This preserves current behavior on upgrade (empty list = today's global theming) and matches the F-15 wording ("per-app theming activeren" with everything active initially).
2. **Request-scoped, not DOM-scoped**: the guard prevents the CSS from being added to the page at all (`Util::addStyle()` skipped during boot) rather than scoping selectors with per-app class prefixes. CSS custom properties cascade from `:root`; reliable DOM scoping would require rewriting every token file. Trade-off: on an excluded app's pages the global header/navigation also renders unthemed. This is accepted and documented — "toggle per app" means that app's pages are stock Nextcloud.
3. **App id resolution from the request path**: `IRequest::getPathInfo()` starting with `/apps/{appid}` identifies the rendered app. Non-app pages (login, dashboard route `/apps/dashboard` IS an app page; settings `/settings/…`, files sharing links `/s/…`, login `/login`) are not in scope of the exclusion list and remain themed always. Only enabled apps are offered in the UI.
4. **All nldesign layers toggle together**: excluding an app suppresses design-system stylesheets, the token set, `custom-overrides.css`, `hide-slogan.css`, and `show-menu-labels.css` for that request. A partial per-layer matrix is over-engineering; the use case is "this app breaks under theming".
5. **Settings UI stays vanilla**: checkbox list rendered by the PHP template + vanilla JS persistence, consistent with the admin-settings spec (no Vue, no webpack). App display names and icons come from `IAppManager`, with NC's standard form styles and CSS variables only.
6. **The nldesign settings panel itself is always reachable and functional**: the exclusion list applies to app pages, and the settings area (`/settings/…`) is not an app page — an admin can never lock themselves out of the toggle UI.

## Impact

- **nldesign app only** — `Application::injectThemeCSS()` gains a guard; new `AppThemingService`; `SettingsController` gains two methods; admin template/JS additions
- **Performance** — one appconfig read (cached by NC) + one string prefix check per request; negligible
- **No database migration** — appconfig JSON array
- **docs** — `docs/features/toggles.md` and `docs/GOVERNMENT-FEATURES.md` F-15

## Rollback Strategy

- Remove the `disabled_apps` appconfig key — boot guard becomes a no-op and behavior is exactly today's global injection
- The guard is purely subtractive: rolling back the code without clearing config also restores global theming because the guard is simply gone
- If rolled back before release, downgrade F-15 in `docs/GOVERNMENT-FEATURES.md` to "Gepland"
