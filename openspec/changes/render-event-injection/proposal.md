---
kind: code
---

## Why

nldesign injects all theming in `Application::boot()` via `OCP\Util::addStyle`
(`lib/AppInfo/Application.php:90-163`). That was the simplest thing that worked, but it is the
wrong injection point on four counts:

1. **It runs on every request, not every page render.** `boot()` fires for WebDAV syncs, OCS/API
   calls, and cron web requests — none of which render HTML. Each such request still resolves
   `DesignSystemService`, reads three appconfig values, and calls
   `CustomOverridesService::ensureExists()` (a filesystem check) for nothing. Nextcloud core's own
   theming does it correctly: core `ThemeInjectionService` injects on
   `BeforeTemplateRenderedEvent` AND `BeforeLoginTemplateRenderedEvent`
   (platform research `02-nc-theming-platform.md`, --color-primary flow) — the events exist
   precisely so styling code runs only when a template is actually rendered.
2. **No render-context discrimination.** `BeforeTemplateRenderedEvent` carries the response's
   `renderAs` (`USER`/`GUEST`/`PUBLIC`/`ERROR`), which is the sanctioned way to distinguish a
   logged-in page from a public share page from an error page. Boot-time injection sees none of
   this, so nldesign cannot ever theme public-share or guest pages *deliberately* — a real demand
   (public counter / audience branding, research `02` ranked opportunity #4; edge-surface wishes
   `03-user-wishes-flows.md` #10). The platform map lists event-based injection as unused
   extension point #2 and ranked opportunity #3; the codebase inventory lists "no event
   listeners" as a structural gap.
3. **The per-app guard is path sniffing.** `Application::isThemingDisabled()` regexes
   `IRequest::getPathInfo()` (`^/apps/([a-zA-Z0-9_]+)`) to guess which app renders. At event time
   the authoritative answer is available: `BeforeTemplateRenderedEvent::getResponse()->getApp()`
   names the app that produced the `TemplateResponse`. The regex stays only as a fallback.
4. **Dead code.** `lib/AppInfo/Application.php:27` imports `OCA\NLDesign\Themes\NLDesignTheme` —
   verified dead: no `lib/Themes/` directory exists and the class is defined nowhere in the repo.

The change is behavior-preserving by default: all surfaces that receive nldesign CSS today (user
pages, login, guest, public share, rendered error pages) keep receiving exactly the same
stylesheets in the same cascade order; only the injection point and the guard mechanism change,
and per-render-context control becomes possible (default: every context themed).

## What Changes

- **New `lib/Service/CssInjectionService.php`** — the existing `injectThemeCSS()` body
  (design-system stylesheet order, token set CSS, `icon-contrast`/`error-contrast`,
  custom-overrides, conditional `hide-slogan`/`show-menu-labels`) extracted verbatim into an
  injectable service. Cascade order is unchanged (css-architecture layers 1-8 + conditionals).
- **New `lib/Listener/ThemeInjectionListener.php`** — one `IEventListener` handling both
  `OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent` and
  `OCP\AppFramework\Http\Events\BeforeLoginTemplateRenderedEvent`, registered in
  `Application::register()` via `IRegistrationContext::registerEventListener()` (both events →
  same class). For `BeforeTemplateRenderedEvent` it maps `getResponse()->getRenderAs()` to a
  render context (`user`/`guest`/`public`/`error`; anything unknown, e.g. `blank`, fails open to
  themed); `BeforeLoginTemplateRenderedEvent` maps to context `login`.
- **Per-render-context switch:** new appconfig key `themed_contexts` (JSON array; recognized
  values `user`, `login`, `guest`, `public`, `error`). Absent/invalid key ⇒ ALL contexts themed
  — identical to today. No admin UI in this change (occ `config:app:set` only; a settings toggle
  is deliberately deferred until a concrete audience-branding change needs it).
- **Per-app guard rework:** the guard moves into the listener and resolves the app id primarily
  from `TemplateResponse::getApp()`, falling back to `AppThemingService::resolveAppIdFromPath()`
  on empty/`core` app ids; still consults `AppThemingService::isThemingDisabledFor()`; still
  fails open to themed on any resolution failure. The login event path never applies the per-app
  guard (login is not an app page — parity with the existing "login and settings always themed"
  requirement).
- **`Application` slims down:** `boot()` becomes a no-op (kept only because `IBootstrap`
  requires it), `injectThemeCSS()`/`isThemingDisabled()` are removed, and the dead
  `use OCA\NLDesign\Themes\NLDesignTheme;` import is deleted.
- **Specs:** MODIFIED requirements on `css-architecture` (loading mechanism: REQ-CSS-001
  scenarios and the Layer-8 custom-overrides scenario are re-anchored from
  `Application::boot()`/`injectThemeCSS()` to the render-event listener) and `per-app-theming`
  (guard mechanism), plus an ADDED render-context requirement on `css-architecture`. See
  `design.md` for event timing, login parity, and double-injection analysis.
- **No breaking changes** for admins or consumers: same stylesheets, same order, same config
  keys, same excluded-app behavior. Maintenance-mode pages remain unthemed (they render before
  apps load — true today as well).

## Impact

- `lib/AppInfo/Application.php` — remove dead import, remove `injectThemeCSS()`/
  `isThemingDisabled()`, empty `boot()`, register the listener in `register()`.
- `lib/Listener/ThemeInjectionListener.php` — new.
- `lib/Service/CssInjectionService.php` — new (logic moved, not rewritten).
- `lib/Service/AppThemingService.php` — unchanged API; `resolveAppIdFromPath()` becomes the
  fallback resolver (docblock update only).
- `tests/Unit/` — new listener + injection-service tests; existing boot-guard tests migrate.
- `openspec/specs/css-architecture/spec.md`, `openspec/specs/per-app-theming/spec.md` — via this
  change's deltas.
- `appinfo/info.xml` — version bump (CSS delivery path changes ⇒ bust the `?v=` cache).
