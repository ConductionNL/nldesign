---
kind: code
---

## Why

Typography is half of a Dutch government huisstijl, and it is the half nldesign cannot deliver
today. The real government typefaces are proprietary: RijksoverheidSans is paid/licensed, and
municipal corporate fonts (Amsterdam, Den Haag, ...) are likewise not redistributable — which
is exactly why nldesign ships Fira Sans (SIL OFL) as the community-consensus *proxy*, not the
real thing (ecosystem research; NLDS community consensus). Organizations that DO hold a font
license currently have no path to use it: Nextcloud upstream has no font facility
(nextcloud/server#46043, open), and the only store precedent (Inter Fonts) hardcodes a single
font. nldesign cannot ship these fonts, but it can let a licensed admin upload them — the
license question then sits where it belongs, with the license holder.

Self-hosting is mandatory, not optional: loading fonts from an external CDN would violate both
Nextcloud's CSP posture and the sovereignty positioning of the app (no third-party requests
from a gov workplace). Fonts must therefore be stored in appdata and served from the instance
itself via an app route. That route must be reachable without authentication — CSS `url()`
font loads carry no CSRF token and must also work on the login page before any session exists
— so the serving endpoint is deliberately `#[PublicPage]` + `#[NoCSRFRequired]` with the
rationale documented at the annotation (route-auth gate compliance: this is a considered
public surface serving admin-curated binary assets by opaque id, not a data leak).

The upload pipeline mirrors the proven custom-token-set architecture
(`CustomTokenSetController` / `CustomTokenSetService` / `CustomTokenSetValidator`:
admin-only CSRF-protected upload, slugified ids, appconfig JSON manifest, size cap,
validation before storage, atomic write, delete/export lifecycle) — but hardened for binary
input: woff2 only, verified by magic bytes (`wOF2`), never by extension or client MIME type.
woff2-only keeps the validation surface minimal (single well-defined container format, best
compression, universally supported by every browser Nextcloud 34 supports).

## What Changes

- New `lib/Controller/FontController.php` with four routes: `POST /settings/fonts/upload`,
  `GET /settings/fonts` (list), `DELETE /settings/fonts/{id}` — all admin-only
  (`#[AuthorizedAdminSetting(Admin::class)]`, CSRF-protected, mirroring
  `customTokenSet#*`) — and the public serving route `GET /fonts/{id}.woff2`
  (`#[PublicPage]` + `#[NoCSRFRequired]`, annotated with the rationale above; long-lived
  `Cache-Control: public, max-age=31536000, immutable` + ETag so login/page loads stay
  cheap).
- New `lib/Service/FontService.php`: storage in appdata (`IAppData` folder `fonts/`,
  filenames `custom-{slug}.woff2` where slug derives from the admin-supplied display name,
  `[a-z0-9-]`, max 64 chars — identical slugging contract to `CustomTokenSetService`),
  appconfig manifest `custom_fonts` (JSON object indexed by font id: display name, file
  size, uploaded timestamp, assigned font role), delete lifecycle, collision rejection.
- New `lib/Service/FontValidator.php`: magic-byte check (`wOF2` at offset 0), size cap 2 MB,
  per-instance cap of 20 fonts, id/filename sanitization (no `/`, no `..`, no NUL — lookup
  by manifest id only, never by user-supplied path).
- Font-token mapping: each uploaded font is assigned to a font role (`body` and/or
  `heading`). A generated `@font-face` + `:root { --nldesign-font-family: ... }` stylesheet
  is served by `GET /fonts/css` (public, same posture and caching as the binary route,
  cache-busted by a manifest revision) and injected in `Application::boot()` via
  `\OCP\Util::addHeader('link', ...)` only when at least one font is configured. The
  generated `font-family` value MUST keep the existing fallback chain intact:
  `"<Display Name>", "Fira Sans", ...` — a broken or unloadable font degrades to exactly
  today's rendering.
- Admin UI section (vanilla JS in `js/admin.js` + `templates/settings/admin.php`): upload
  control (display name + file + role), font list with delete, and mandatory license copy in
  the UI: uploading is only permitted for fonts the organization is licensed to self-host;
  responsibility rests with the uploader (ENGLISH i18n keys, nl translation).
- New canonical spec `openspec/specs/custom-fonts/spec.md` (delta in this change).
- No DB tables, no Vue, no external requests. Not BREAKING: with zero fonts uploaded,
  nothing is injected and behavior is unchanged.

## Impact

- `appinfo/routes.php` — five new routes (`font#upload`, `font#list`, `font#delete`,
  `font#serve`, `font#css`).
- `lib/Controller/FontController.php`, `lib/Service/FontService.php`,
  `lib/Service/FontValidator.php` — new.
- `lib/AppInfo/Application.php` — conditional `addHeader` link injection for `/fonts/css`.
- `templates/settings/admin.php`, `js/admin.js` — new Fonts section incl. license notice.
- `l10n/` — new strings.
- `tests/unit/Service/FontServiceTest.php`, `tests/unit/Service/FontValidatorTest.php`,
  `tests/unit/Controller/FontControllerTest.php` — new.
- `openspec/specs/custom-fonts/spec.md` — new canonical spec (via this change's delta).
- Cross-references: `email-template-theming` deliberately does NOT consume uploaded fonts
  (email clients don't reliably load webfonts); the custom-token-sets pipeline is the
  architectural template but its files are not touched.
