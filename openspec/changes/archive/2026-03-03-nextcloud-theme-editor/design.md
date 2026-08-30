# Design: nextcloud-theme-editor

## Context

The nldesign app currently injects seven CSS files on every Nextcloud page load via `Application::injectThemeCSS()`. All token values come from pre-authored CSS files — nothing is user-editable at runtime without file system access. The app has one PHP controller (`SettingsController`), two services (`TokenSetService`, `ThemingService`), and a single admin template rendered via `Admin::getForm()`.

**Constraints:**
- Nextcloud apps cannot write to `appdata` from a simple file-based approach — we write to the app's own `css/` directory (same location as other CSS files)
- The admin settings panel is a Nextcloud PHP template with a Vue component mounted inside it (`js/admin.js`)
- Routing uses Nextcloud's Symfony-based router with the pattern `/apps/nldesign/settings/*`
- All admin endpoints must be gated with `@AuthorizedAdminSetting`
- Files in `css/tokens/` are read-only (generated from upstream); `custom-overrides.css` is the only file we write

## Goals / Non-Goals

**Goals:**
- Add `custom-overrides.css` as a writable CSS file at the end of the load stack
- New PHP service (`CustomOverridesService`) for read/write of that file
- New PHP endpoint for computing resolved `--color-*` values for any token set (used by dialog)
- New Vue component: tabbed token editor with live preview + save
- New Vue component: token-set apply dialog with checkbox selection
- Import/export endpoints

**Non-Goals:**
- Real-time multi-user sync (last save wins)
- Per-user (non-admin) token overrides
- Editing `--nldesign-*` tokens in the UI
- Dark mode / high-contrast variant management
- Undoing individual previous saves (no history)

## Decisions

### 1. CSS Load Order Extension
`Application::injectThemeCSS()` MUST add `custom-overrides` as the final `\OCP\Util::addStyle()` call, after `element-overrides`. The file is optional — if absent, Nextcloud's `addStyle` will emit a missing-file warning in debug mode but silently skip in production. To avoid this, the service MUST always ensure the file exists (write an empty `:root {}` on first access if missing).

**Alternative considered**: Load `custom-overrides.css` via a dynamic URL (served by a controller). Rejected — Nextcloud's `addStyle` is simpler and consistent with the existing approach.

### 2. Token Registry as PHP Array
The canonical list of editable tokens (with tab assignments) MUST live in a PHP class `TokenRegistry` as a static array. This is the single source of truth used by:
- The editor endpoint (to enumerate tokens for the UI)
- The save endpoint (to validate incoming token names)
- The import endpoint (to filter uploaded tokens)

Format:
```php
[
    '--color-primary'       => ['tab' => 'login',   'type' => 'color', 'label' => 'Primary color'],
    '--color-primary-hover' => ['tab' => 'login',   'type' => 'color', 'label' => 'Primary hover'],
    '--color-main-text'     => ['tab' => 'typography', 'type' => 'color', 'label' => 'Main text'],
    // ...
]
```

Tab values: `login`, `navigation`, `content`, `buttons`, `typography`.

**Alternative considered**: Derive token list from `overrides.css` by parsing comments. Rejected — fragile and couples PHP to CSS file format.

### 3. CustomOverridesService
New service responsible for reading and writing `custom-overrides.css`. Operations:
- `read(): array` — parses the CSS file and returns `['--color-primary' => '#c00000', ...]`
- `write(array $tokens): void` — validates names against registry, writes the file atomically (temp file + rename)
- `ensureExists(): void` — creates an empty file if absent (called from `Application::injectThemeCSS()`)

CSS parsing uses a simple regex: extract `--[a-z-]+:\s*([^;]+)` from inside `:root { }`. No full CSS parser needed since the file format is tightly controlled (we write it).

### 4. Token Set Preview Endpoint
To populate the apply dialog with "what would --color-* look like with token set X", the server computes resolved values server-side:

1. Parse `defaults.css` → all `--nldesign-*` defaults
2. Parse `tokens/{id}.css` → overrides to `--nldesign-*` values
3. Merge: final `--nldesign-*` map
4. Parse `overrides.css` → extract mapping `--color-X: var(--nldesign-Y)`
5. For each `--color-X`, look up `--nldesign-Y` in the merged map → resolved color value

This is pure string manipulation (regex-based CSS value extraction), no DOM needed.

**New endpoint**: `GET /settings/tokenset-preview/{id}` → returns `{ "--color-primary": "#CC0000", ... }` for all editable tokens.

**Alternative considered**: Client-side resolution by injecting the token set CSS into a shadow root. Rejected — requires the full CSS to be injected client-side, risks visual flicker, and complicates the iframe/shadow root lifecycle.

### 5. Vanilla JS Implementation (no framework)

The admin settings panel uses vanilla JS (no Vue), consistent with the existing `js/admin.js` approach. No build step is required — the JS is served directly.

**Token editor** (`initTokenEditor` / `renderTokenEditor` in `js/admin.js`):
- Renders 4 tabs (login, content, status, typography) with HTML string injection into `#nldesign-token-editor`
- Fetches current overrides from `GET /settings/overrides` on init
- Reads resolved current values via `getComputedStyle(document.documentElement)`
- Each row: label, color picker + hex input (type=color tokens) or text input (other types), "customized" badge, reset button
- Live preview: `document.documentElement.style.setProperty('--color-X', value)` on input events
- Save: POSTs all non-default entries to `POST /settings/overrides`
- Reset per token: `style.removeProperty()` + clears badge + marks token dirty for next save

**Token-set apply dialog** (`openTokenSetApplyDialog` / `showApplyDialog` in `js/admin.js`):
- Triggered by the token-set `<select>` change event instead of direct save
- Fetches `GET /settings/tokenset-preview/{id}` for new-set resolved values
- Reads current resolved values via `getComputedStyle`
- Filters to only changed tokens (current ≠ new)
- Renders a dialog overlay with checkbox table; all checked by default; Select all / Deselect all toggles
- Live preview per checked/unchecked row via `style.setProperty()` / `style.setProperty(current)`
- Apply: fetches existing overrides, merges checked values, POSTs merged result; also POSTs new token set ID to update `token_set` config
- Cancel: `style.removeProperty()` for all previewed tokens, dropdown reverts to previous value

**Alternative considered**: Vue components with NcTabs / NcDialog. Rejected — the existing admin template is plain JS with no build pipeline; adding Vue would require a webpack config change and a build step that the app does not currently have.

### 6. Import/Export
- **Export**: `GET /settings/overrides/export` — reads `custom-overrides.css`, sets `Content-Type: text/css` and `Content-Disposition: attachment; filename="custom-overrides.css"`, returns file content
- **Import**: `POST /settings/overrides/import` — accepts `multipart/form-data` with a `file` field; PHP reads the file content (max 256 KB), extracts `--color-*` declarations via regex, filters against `TokenRegistry`, writes to `CustomOverridesService`, returns `{ imported: N, skipped: M }`

### 7. API Endpoints (full list)

All routes under `/apps/nldesign/settings/*`, all `@AuthorizedAdminSetting`:

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/settings/overrides` | Returns current custom-overrides.css entries as JSON |
| `POST` | `/settings/overrides` | Writes new entries (full replace of overrides) |
| `GET` | `/settings/overrides/export` | Downloads custom-overrides.css as file |
| `POST` | `/settings/overrides/import` | Accepts CSS file upload, imports recognized tokens |
| `GET` | `/settings/tokenset-preview/{id}` | Returns resolved `--color-*` values for a token set |

Existing endpoints are unchanged.

## Risks / Trade-offs

- **File write permissions**: `css/custom-overrides.css` must be writable by the web server user. On Docker-based dev environments this is fine, but some production setups have read-only app directories. Mitigation: `CustomOverridesService::write()` catches `IOException` and returns a clear HTTP 500 with an actionable message.

- **CSS parsing brittleness**: The server-side token-set preview computation depends on regex parsing of `defaults.css`, `tokens/{id}.css`, and `overrides.css`. If the CSS format changes (e.g. multi-line values, `var()` chains), the parser may produce incorrect resolved values. Mitigation: preview values are non-destructive (they only affect the dialog's comparison display, not any saved state), and the CSS files are format-controlled by the app authors.

- **Live preview state leak**: If the admin navigates away without saving or cancelling, injected `style.setProperty` overrides remain on `document.documentElement` until reload. Mitigation: document this in the component; the page reload on navigation will clear the state.

- **Large custom-overrides.css on import**: A malicious or oversized file upload could consume memory. Mitigation: 256 KB hard limit enforced in the import endpoint before parsing.

## Migration Plan

1. Add `custom-overrides` to `Application::injectThemeCSS()` — load order change, no visual impact if file is empty
2. Create `CustomOverridesService` + `TokenRegistry` — no UI change
3. Add new routes + controller methods — additive, no breaking changes
4. Add Vue components to `js/admin.js` — additive; existing token-set dropdown behavior changes (now triggers dialog)
5. No database migration, no file migrations needed

## Open Questions

- Should the token-set config value (`token_set`) update when the admin applies a token set via the dialog? Current design says "not necessarily" — the dialog promotes values to custom-overrides but the base layer stays. Consider adding a "also set as active base" checkbox in the dialog if this creates confusion.
- Should `custom-overrides.css` survive app updates (i.e., stored outside the app directory)? Currently it lives in `css/` which is overwritten on app update via the Nextcloud app store. This is a known limitation — document it clearly.
