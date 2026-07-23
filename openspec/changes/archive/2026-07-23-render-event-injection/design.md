# Design — render-event-injection

## 1. Event timing: why the events are safe (boot vs event)

`OCP\Util::addStyle()` does not write to the response; it appends to a static style registry that
the page template reads when the header is generated. The only timing requirement is "before the
template's `<head>` is emitted". Both events satisfy it by contract:

- `BeforeTemplateRenderedEvent` is dispatched by the app framework when a `TemplateResponse` is
  about to render — after the controller has produced the response, before header generation.
- `BeforeLoginTemplateRenderedEvent` is dispatched by the login flow before the login/guest
  template renders.

This is not a bet: Nextcloud core's own `ThemeInjectionService` injects the per-theme stylesheets
on exactly these two events (platform research `02-nc-theming-platform.md`). If the events are
sufficient for core theming — which themes every surface nldesign themes today — they are
sufficient for nldesign. Anything rendered outside these events (maintenance mode, install pages,
fatal-error pages emitted before app load) never executed `Application::boot()` styling either,
so no surface is lost relative to today.

What is gained at boot-removal: WebDAV, OCS, `/api/*`, cron and preview requests stop paying for
config reads, service resolution, and `CustomOverridesService::ensureExists()`'s filesystem
check on every request. `ensureExists()` now runs only when a themed template actually renders —
same guarantee (the file exists before its stylesheet URL is emitted), strictly fewer executions.

Listener laziness: `registerEventListener()` from `Application::register()` registers a lazy
service — the listener class (and its service graph) is instantiated only when one of the two
events actually fires. Requests that render no template never construct it.

## 2. renderAs mapping and the context model

`BeforeTemplateRenderedEvent::getResponse()->getRenderAs()` yields the `TemplateResponse`
constants: `user`, `guest`, `public`, `error`, `blank`. The login event carries no renderAs — it
IS the context. The model:

| Event | renderAs | context |
|---|---|---|
| BeforeTemplateRendered | `user` | `user` |
| BeforeTemplateRendered | `guest` | `guest` |
| BeforeTemplateRendered | `public` | `public` (public share links, etc.) |
| BeforeTemplateRendered | `error` | `error` |
| BeforeTemplateRendered | anything else (`blank`, future values) | treated as themed (fail open) |
| BeforeLoginTemplateRendered | — | `login` |

`themed_contexts` (appconfig, JSON array of the five names) selects which contexts get CSS.
Absent, empty-invalid, or unparseable ⇒ all five — byte-identical page output to today. Unknown
renderAs values fail open to themed rather than consulting the list, so a future NC renderAs
constant cannot silently strip theming. This mirrors the app's standing rule: theming is
presentation, not security — every ambiguity resolves to "themed".

Why config-without-UI: the discrimination point is the architectural deliverable here; the first
real consumer (public-share audience branding, deferred) will define what admins actually need to
toggle. Shipping a speculative admin UI now would violate scope discipline.

## 3. Login-page parity

Today the login page is themed because `boot()` runs on the login request and registered styles
land in the guest template. After this change the login page is themed because
`BeforeLoginTemplateRenderedEvent` fires. Parity requirements preserved:

- same stylesheet set and order (the listener calls the same `CssInjectionService::inject()`);
- `hide-slogan.css` keeps working (its whole purpose is the login/guest slogan);
- the per-app guard is never applied on the login path — matching the canonical per-app-theming
  requirement "Login and settings pages are always themed";
- `LoginTokens` / login-specific CSS behavior is untouched (the service body is moved, not
  edited).

The regression tasks pin this with curls asserting the `nldesign` stylesheet `<link>`s on
`/login`, a user page, and a public share page before and after the change.

## 4. Double-injection risk

Could both events fire in one request, or one event twice? Two guards make it a non-issue:

1. `Util::addStyle` is idempotent per (app, file): the underlying registry rejects duplicates, so
   even a pathological double dispatch yields one `<link>` per stylesheet.
2. The listener itself is stateless and cheap; running twice costs two config reads, not broken
   pages.

The unit test still asserts single-injection under a simulated double dispatch (cheap to test,
catches a future refactor that replaces `Util::addStyle` with raw header emission where
idempotency would matter).

A related hazard is the opposite: **zero** injection because a response is not a
`TemplateResponse` (e.g. `DataResponse` on API routes). That is correct behavior — those
responses have no `<head>` — and is exactly the waste the change eliminates.

## 5. Per-app guard: response app id over path sniffing

`TemplateResponse::getApp()` names the app that constructed the response — the authoritative
"which app's page is this" signal, immune to URL rewrites, `index.php` prefixes, and future route
shapes. Resolution order in the listener:

1. `getResponse()->getApp()` — used when non-empty and not `core`;
2. fallback: `AppThemingService::resolveAppIdFromPath($request->getPathInfo())` (the existing
   regex, kept for `core`-attributed responses serving app content and for belt-and-braces);
3. any throwable or unresolved id ⇒ fail open to themed (unchanged rule).

`AppThemingService`'s protected-id semantics (`nldesign`, `settings`, `theming` never excluded)
and its storage/API/UI are untouched — only the call site and primary resolver change, which is
why the per-app-theming delta modifies a single requirement.

## 6. Error pages: a deliberately new capability, default-on

Error templates rendered through the app framework (`renderAs: error`) go through
`BeforeTemplateRenderedEvent`, so they now hit nldesign's injection path explicitly. Under
boot-time injection they were themed whenever the erroring request had booted apps — effectively
the same visual result, so default-on `error` context keeps parity while making the surface
addressable (research: unthemed SSO-error/maintenance surfaces break gemeente trust,
`03-user-wishes-flows.md` #10). Maintenance-mode pages stay out of reach for any app — documented
limitation, unchanged.
