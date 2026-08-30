## 1. Validation + storage services

- [x] 1.1 Create `lib/Service/FontValidator.php`: `validate(string $bytes, string $displayName):
      void` throwing typed exceptions — magic-byte check (first 4 bytes exactly `wOF2`,
      checked via `substr`/`strncmp`, NEVER extension or client MIME), size cap 2 MB
      (reject BEFORE buffering where possible via the upload's reported size, and again on
      actual bytes), display-name validation (non-empty, max 64 chars after slugging,
      slug charset `[a-z0-9-]`). SPDX docblocks per hydra gate.
- [x] 1.2 Create `lib/Service/FontService.php` using `IAppData` (`fonts/` folder):
      `slugify()` reusing the exact contract of `CustomTokenSetService::slugify()`
      (`lib/Service/CustomTokenSetService.php:118`); `store(displayName, role, bytes)`
      writing `custom-{slug}.woff2` atomically (SimpleFS `putContent` on a new file after
      validation) and adding a manifest entry to appconfig `custom_fonts` (JSON indexed by
      id: `name`, `role` (`body`|`heading`), `size`, `uploadedAt`, `rev`); reject id
      collisions with a typed 409 exception; `list()`, `delete(id)` (file + manifest, and
      bump the global font revision), `getFont(id): ?ISimpleFile` resolving ONLY via the
      manifest (no user-supplied path segments touch the filesystem), per-instance cap
      20 fonts enforced in `store()`.
- [x] 1.3 Generated stylesheet: `FontService::buildCss(): string` emitting one `@font-face`
      per font (`font-family: "<Display Name>"; src: url('<absolute serve URL>')
      format('woff2'); font-display: swap;`) plus `:root` overrides —
      `--nldesign-font-family: "<body font>", "Fira Sans", -apple-system, ...` preserving
      the existing fallback chain from `css/` defaults verbatim after the uploaded family,
      and the heading token override when a `heading`-role font exists. Display names are
      CSS-string-escaped (quotes/backslashes) before interpolation.

## 2. Controller + routes

- [x] 2.1 Create `lib/Controller/FontController.php`:
      `upload()` (POST, multipart `font` + `name` + `role`), `list()`, `delete(string $id)`
      — all `#[AuthorizedAdminSetting(Admin::class)]`, CSRF-protected (no `NoCSRFRequired`),
      mirroring `CustomTokenSetController::upload()`
      (`lib/Controller/CustomTokenSetController.php:125`). Map validator exceptions:
      non-woff2 → 422 naming the check that failed, oversize → 413, collision → 409,
      cap reached → 409, bad name → 422.
- [x] 2.2 `serve(string $id)` (GET): resolve via manifest, return the woff2 bytes with
      `Content-Type: font/woff2`, `Cache-Control: public, max-age=31536000, immutable`,
      ETag from the manifest `rev`; 404 (no body detail) for unknown/invalid ids.
      Annotate `#[PublicPage]` + `#[NoCSRFRequired]` with a docblock rationale: CSS `url()`
      font loads carry neither session guarantees nor CSRF tokens and MUST work on the
      pre-login page; the route serves admin-curated static binaries addressed by opaque
      id — deliberate public surface (route-auth + semantic-auth gates).
- [x] 2.3 `css()` (GET): serve `FontService::buildCss()` as `text/css` with the same public
      posture and caching (ETag = font revision); empty 200 stylesheet when no fonts are
      configured.
- [x] 2.4 Register routes in `appinfo/routes.php`:
      `['name' => 'font#upload', 'url' => '/settings/fonts/upload', 'verb' => 'POST']`,
      `['name' => 'font#list', 'url' => '/settings/fonts', 'verb' => 'GET']`,
      `['name' => 'font#delete', 'url' => '/settings/fonts/{id}', 'verb' => 'DELETE']`,
      `['name' => 'font#serve', 'url' => '/fonts/{id}.woff2', 'verb' => 'GET']`,
      `['name' => 'font#css', 'url' => '/fonts/css', 'verb' => 'GET']`.

## 3. Injection

- [x] 3.1 In `lib/AppInfo/Application.php` `injectThemeCSS()` path: when the active design
      system is themed (same guard as existing style injection) AND at least one custom
      font is configured, inject `<link rel="stylesheet" href="<fonts/css route>?v=<rev>">`
      via `\OCP\Util::addHeader()` AFTER the token-set styles so the font tokens win the
      cascade. Zero fonts ⇒ zero injection (no empty stylesheet request on every page).

## 4. Admin UI (vanilla JS — no Vue)

- [x] 4.1 `templates/settings/admin.php`: new "Custom fonts" section — display-name input,
      role select (body / heading), file input (`accept=".woff2"` as UX hint only —
      server-side validation is authoritative), upload button, uploaded-font list (name,
      role, size, delete). Include the license notice paragraph rendered ABOVE the upload
      control: only upload fonts your organization is licensed to self-host; license
      responsibility rests with the uploader (i18n key in ENGLISH, e.g.
      `t('nldesign', 'Only upload fonts your organization holds a license to self-host. Licensing responsibility rests with the uploader.')`).
- [x] 4.2 `js/admin.js`: wire upload (FormData POST), list refresh, delete with confirm;
      surface 413/422/409 error bodies as localized inline messages; after
      upload/delete prompt for reload so the injected stylesheet revision refreshes.
- [x] 4.3 `l10n/` — English source keys + nl translations for all new strings.
- [x] 4.4 Bump `appinfo/info.xml` `<version>` in the implementation PR so the `?v=` cache
      buster invalidates stale CSS (known stale-bundle gotcha).

## 5. Tests

- [x] 5.1 `tests/unit/Service/FontValidatorTest.php` — hardening corpus:
      (a) valid woff2 header accepted; (b) TTF bytes (`\x00\x01\x00\x00`), OTF (`OTTO`),
      WOFF1 (`wOFF`), zip (`PK`), and renamed `.woff2` text file all rejected as non-woff2;
      (c) 2 MB + 1 byte rejected as oversize; (d) display names yielding empty slugs,
      >64-char slugs, and names containing `../`, `/`, NUL rejected; (e) error messages
      name the failed check.
- [x] 5.2 `tests/unit/Service/FontServiceTest.php` — store writes `custom-{slug}.woff2` and
      manifest entry; collision rejected without touching the existing file; delete removes
      file + manifest + bumps rev; `getFont()` returns null for ids not in the manifest even
      if a matching file exists (manifest is the gate); path-traversal ids
      (`../../config`, `custom-a/../b`) never reach appdata lookups; 21st font rejected;
      `buildCss()` output contains `@font-face`, `format('woff2')`, `font-display: swap`,
      the preserved Fira Sans fallback chain after the custom family, and CSS-escaped
      display names (`Test"Font` case).
- [x] 5.3 `tests/unit/Controller/FontControllerTest.php` — upload maps validator errors to
      413/422/409; serve returns immutable cache headers + ETag and 404 for unknown id;
      css endpoint returns `text/css` and empty stylesheet with no fonts; auth posture
      asserted: upload/list/delete carry `AuthorizedAdminSetting`, serve/css carry
      `PublicPage` (reflection on attributes, mirroring existing controller tests).

## 6. Verify

- [x] 6.1 Run PHPUnit in the nextcloud:34 container and `composer check:strict` — green.
- [ ] 6.2 (deferred to post-merge live verification — requires the running 8080 instance,
      out of scope for a worktree build) Live on 8080: upload a real woff2 (e.g. Fira Sans
      woff2 renamed display-name "Test Sans", role body) via the admin panel; confirm
      `curl -I http://localhost:8080/index.php/apps/nldesign/fonts/custom-test-sans.woff2`
      returns 200 + `font/woff2` + immutable cache headers WITHOUT any session cookie;
      confirm `/fonts/css` shows the `@font-face` and the `--nldesign-font-family` override
      with the Fira Sans fallback chain intact.
- [ ] 6.3 (deferred to post-merge live verification — requires a browser against the
      running 8080 instance) Live in the browser: hard-reload the themed instance, verify
      via devtools computed styles that body text resolves to "Test Sans" and that the
      login page (logged-out window) loads the font without auth errors in the network tab.
- [ ] 6.4 (deferred to post-merge live verification — requires the running 8080 instance)
      Live negative: `curl -F 'font=@somefile.ttf'` (admin session + CSRF token) → 422;
      oversize file → 413; upload as non-admin → rejected by middleware. Delete the test
      font and confirm rendering degrades cleanly back to Fira Sans.
