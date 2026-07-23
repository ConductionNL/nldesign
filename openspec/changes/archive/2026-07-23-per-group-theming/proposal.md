---
kind: code
---

## Why

One Nextcloud instance, multiple huisstijlen: shared service centres, hosters and
samenwerkingsverbanden serve multiple gemeenten (and waterschappen/GGD'en) from a single
deployment — Centric's sovereign-workplace pact (Jun 2026), Dimpact (~40 gemeenten), and NL
hosters like ProcoliX, The Good Cloud and IONOS are exactly this shape. Upstream Nextcloud has
never delivered it: **server#23545** (theming per domain/group/user), **server#31749**
(per-group/department theming) and **server#3985** have been open for years, and the only
existing primitive is the neutral "Domain Theming" app, which keys on trusted domain only. With
42 shipped municipal token sets plus custom uploads, nldesign already has the *content* for
per-tenant theming; what is missing is *resolution*: which token set does THIS user get?

This change maps NC groups → token sets. Scope honesty up front: per-group theming applies
ONLY to the token-set CSS layer (the design-system stack of the mapped set). Nextcloud core
theming values — logo, primary color in `ThemingDefaults`, backgrounds pushed by theming-sync —
are **instance-global by Nextcloud's architecture** and remain so; theming-sync stays
default-set-only. That limitation is stated normatively in the spec so nobody sells this as full
multi-tenant white-labeling.

Architecture fit: nldesign has no DB tables and no Vue, and this change keeps it that way — the
mapping is one IConfig app value, resolution is a service consulted from
`Application::injectThemeCSS()`, and the admin UI is a vanilla-JS section following the existing
per-app-theming list pattern (`#nldesign-app-theming-list` in `js/admin.js`).

## What Changes

- New `GroupThemingService` (`lib/Service/GroupThemingService.php`):
  - reads/writes the ordered mapping (IConfig app value `group_token_sets`: JSON array of
    `{group, tokenSet}` entries, array order = priority order);
  - validates group ids against `IGroupManager` and token-set ids against the available sets on
    save;
  - resolves the effective token set for the current request: first matching entry in
    configured order wins (deterministic tie-break: a user in several mapped groups gets the
    highest-priority — earliest — entry); entries whose set no longer exists are skipped;
    no match, no session, or any resolution error ⇒ instance default `token_set`
    (fail-open-to-default: presentation, never security — mirroring `isThemingDisabled()`);
  - caches the per-user resolution (ICacheFactory distributed/local cache) keyed on user id +
    a mapping generation counter, so per-request cost is O(1)-ish; the generation counter is
    bumped on every mapping write, invalidating all cached resolutions at once.
- `Application::injectThemeCSS()` resolves the active token set through `GroupThemingService`
  instead of reading `token_set` directly; anonymous/public/login pages always resolve to the
  instance default set.
- Admin UI: new "Group theming" section in the settings panel (vanilla JS + PHP template)
  listing group→set rows with add / remove / reorder (priority), populated from a new
  `GET/POST /settings/group-theming` endpoint pair on `SettingsController` (same
  `@AuthorizedAdminSetting` posture as all `/settings/*` routes).
- Interactions (specified normatively, detailed in design.md):
  - **theme-preview-workflow** (this wave): an active admin preview wins over group mapping for
    the previewing admin;
  - **per-app exclusions**: orthogonal — exclusion still suppresses ALL injection first; group
    mapping only picks WHICH set loads when injection happens;
  - custom overrides, hide-slogan, menu labels: instance-global, apply on top of whichever set
    resolved.
- **New canonical spec** `per-group-theming` (ADDED) + **MODIFIED** `css-architecture`
  (Design System Driven Stylesheet Loading — injection resolves the set per request) +
  **ADDED** requirement in `admin-settings` (the mapping section).
- `design.md` included (multi-tenant resolution is architecturally non-trivial).

## Impact

- `lib/Service/GroupThemingService.php` — new.
- `lib/AppInfo/Application.php` — `injectThemeCSS()` resolves the set via the service.
- `lib/Controller/SettingsController.php` — `getGroupTheming()` / `setGroupTheming()`.
- `appinfo/routes.php` — two new routes.
- `templates/settings/admin.php`, `js/admin.js`, `css/admin.css` — group-theming section.
- `tests/unit/Service/GroupThemingServiceTest.php` — precedence matrix; controller tests; vitest
  for the list UI.
- `openspec/specs/per-group-theming/spec.md` — new canonical spec (via archive);
  `openspec/specs/css-architecture/spec.md`, `openspec/specs/admin-settings/spec.md` — modified
  (via archive).
- Explicit non-impact: `lib/Service/ThemingService.php` (theming-sync) unchanged —
  default-set-only; no DB tables; no Vue.
