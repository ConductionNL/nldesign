# Admin Settings — Technical Design

## Overview

The admin settings panel for the NL Design app is a pure server-rendered, vanilla-JS feature. It has no frontend build step, no Vue, and no webpack. The PHP class registers the panel into Nextcloud's Settings infrastructure; a plain PHP template renders the HTML; a standalone JS file handles interactions; a standalone CSS file handles presentation.

---

## 1. Settings Panel Registration

### ISettings implementation — `lib/Settings/Admin.php`

`Admin` implements `OCP\Settings\ISettings`. Nextcloud discovers it via the `@AuthorizedAdminSetting` annotation system and the `ISettings` interface contract.

```
OCP\Settings\ISettings
  getForm()     → TemplateResponse('nldesign', 'settings/admin', $params)
  getSection()  → 'theming'
  getPriority() → 50
```

**Dependencies injected by DI container:**

| Dependency | Interface | Purpose |
|---|---|---|
| `$config` | `OCP\IConfig` | Read current persisted settings |
| `$l` | `OCP\IL10N` | Localization (not actively used in PHP — strings passed via `p($l->t(...))` in template) |
| `$tokenSetService` | `TokenSetService` | Enumerate available token sets |

**`getForm()` data flow:**

1. `TokenSetService::getAvailableTokenSets()` scans `css/tokens/*.css` + merges `token-sets.json` metadata, sorts alphabetically, returns `array<{id, name, description, ?theming}>`.
2. `IConfig::getAppValue('nldesign', 'token_set', 'rijkshuisstijl')` reads current active set.
3. `IConfig::getAppValue('nldesign', 'hide_slogan', '0') === '1'` returns boolean.
4. `IConfig::getAppValue('nldesign', 'show_menu_labels', '0') === '1'` returns boolean.
5. Returns `TemplateResponse` with four template variables: `tokenSets`, `currentTokenSet`, `hideSlogan`, `showMenuLabels`.

### Token discovery — `lib/Service/TokenSetService.php`

`TokenSetService` is responsible for the authoritative list of available token sets.

- **Primary source**: filesystem scan of `css/tokens/` for `*.css` files. Each filename stem becomes the token set `id`.
- **Metadata source**: `token-sets.json` at the app root. Provides `name`, `description`, and optional `theming` block (primary_color, background_color, logo, background). Entries are indexed by `id`.
- **Fallback name**: if a CSS file has no matching JSON entry, `formatName()` converts kebab-case to Title Case via `ucwords(str_replace('-', ' ', $id))`.
- **Sort**: alphabetical by `name` (case-insensitive `strcasecmp`).
- **Path traversal guard in `isValidTokenSet()`**: rejects ids containing `/` or `..` before checking `file_exists('css/tokens/{id}.css')`.

---

## 2. PHP Template — `templates/settings/admin.php`

The template is loaded by Nextcloud's `TemplateResponse`. It has no PHP logic beyond iteration and conditionals — all business logic lives in `Admin::getForm()`.

### Asset loading

```php
script('nldesign', 'admin');   // loads js/admin.js (no build step)
style('nldesign', 'admin');    // loads css/admin.css
```

These are Nextcloud helper functions that enqueue scripts/styles through the normal asset pipeline. The files are served directly from disk — there are no webpack bundles.

### Root element and data attributes

```html
<div id="nldesign-settings" class="section"
     data-token-sets="<?php p(json_encode($_['tokenSets'])); ?>"
     data-current-token-set="<?php p($_['currentTokenSet']); ?>">
```

- `data-token-sets`: the full token set array serialised as JSON. `json_encode()` produces safe JSON, and `p()` then HTML-encodes any residual special characters in the attribute context, preventing XSS.
- `data-current-token-set`: the active token set id, output with `p()`.

JavaScript reads both attributes on `DOMContentLoaded` using `getAttribute()`.

### XSS prevention strategy

| Context | Method | Rationale |
|---|---|---|
| `data-token-sets` attribute | `p(json_encode($array))` | `json_encode` escapes all non-ASCII and special JSON chars; `p()` additionally HTML-encodes the attribute context |
| Individual `value` / text attributes | `p($value)` | Nextcloud's `p()` runs `htmlspecialchars()` internally |
| Localized strings | `p($l->t('...'))` | `p()` escapes the translated string |
| JS-generated HTML in dialog | `escapeHtml(text)` | DOM-based escaping via `div.textContent = text; return div.innerHTML` |

### Dropdown — token set selector

```php
<select id="nldesign-token-set-select">
  <?php foreach ($_['tokenSets'] as $tokenSet): ?>
    <option value="<?php p($tokenSet['id']); ?>"
            <?php if ($_['currentTokenSet'] === $tokenSet['id']): ?>selected<?php endif; ?>>
      <?php p($tokenSet['name']); ?>
    </option>
  <?php endforeach; ?>
</select>
```

Strict equality check (`===`) selects the matching option server-side. The JS preview fires immediately on `DOMContentLoaded` using the already-selected `<option>` value.

### Checkbox controls

Both checkboxes use the same pattern:

```php
<input type="checkbox"
       id="nldesign-hide-slogan"
       class="checkbox"
       <?php if ($_['hideSlogan']): ?>checked<?php endif; ?>>
```

The boolean value is evaluated in a truthy context. No `p()` is needed because no dynamic data is output into an attribute — only the literal string `checked` or nothing.

---

## 3. JavaScript — `js/admin.js`

Plain ES5-compatible vanilla JS. No modules, no imports, no transpilation.

### Initialisation

All logic is wrapped in a single `DOMContentLoaded` listener. Element references are captured once at the top and reused.

```
settingsEl          = #nldesign-settings
tokenSetSelect      = #nldesign-token-set-select
hideSloganCheckbox  = #nldesign-hide-slogan
showMenuLabelsCheckbox = #nldesign-show-menu-labels
previewBox          = .nldesign-preview-box
```

Token set metadata is parsed from `settingsEl.getAttribute('data-token-sets')` with a `try/catch` around `JSON.parse`.

### Live preview

`updatePreview(tokenSet)` is called:
- On init (using the current `tokenSetSelect.value`)
- On every `change` event of the dropdown (optimistic update before the server call returns)

`tokenSetColors` is a hard-coded JS object mapping known token set ids to their primary color values for the preview header and primary button. Token sets not in the map silently skip the preview update (`if (!colors || !previewBox) return`).

The function sets:
- `header.style.backgroundColor` to `colors.primary`
- `primaryButton.style.backgroundColor / borderColor / color`
- `primaryButton.onmouseenter / onmouseleave` for hover effect

### API calls — fetch with CSRF token

All mutating requests use the Nextcloud CSRF token:

```js
headers: {
  'Content-Type': 'application/json',
  'requesttoken': OC.requestToken
}
```

| Action | Method | URL | Body |
|---|---|---|---|
| Save token set | POST | `/apps/nldesign/settings/tokenset` | `{ tokenSet: string }` |
| Save hide slogan | POST | `/apps/nldesign/settings/slogan` | `{ hideSlogan: boolean }` |
| Save menu labels | POST | `/apps/nldesign/settings/menulabels` | `{ showMenuLabels: boolean }` |
| Get theming values | GET | `/apps/nldesign/settings/theming` | — |
| Update theming values | POST | `/apps/nldesign/settings/theming` | `application/x-www-form-urlencoded` |

URLs are generated via `OC.generateUrl('/apps/nldesign/settings/...')`.

Success/failure feedback uses `OC.Notification.showTemporary(t('nldesign', '...'))`.

### Theming sync dialog

When a token set with a `theming` metadata block is selected and saved, `checkAndShowThemingDialog()` fetches current Nextcloud theming values (GET `/settings/theming`) and compares them against the token set's proposed values.

Differences are collected into a `diffs` array (primary_color, background_color, logo, background). If any diffs exist, `showThemingDialog()` renders a modal overlay entirely via `insertAdjacentHTML('beforeend', dialogHtml)`.

The dialog includes:
- Side-by-side preview boxes (current vs proposed) with inline `background-color` and optional `background-image` styles
- A comparison table of changed values with color swatches for hex colors
- Cancel and "Update theming" action buttons

On confirm, a form-encoded POST to `/settings/theming` is sent with only the changed fields. On success the page reloads after 1500 ms.

All dynamic values in the dialog HTML go through `escapeHtml()` (DOM-based, not regex-based) before insertion.

---

## 4. CSS — `css/admin.css`

Admin-specific styles using Nextcloud CSS custom properties throughout. No hardcoded colors anywhere in the structural layout — only the preview elements receive inline color values from JavaScript.

### Layout structure

```
#nldesign-settings (max-width: 800px)
  h2
  .settings-hint
  .nldesign-token-set-selector
    label
    select (max-width: 400px)
  .nldesign-option (x2, flex row, border)
    input[type=checkbox]
    label
  .nldesign-preview (background-dark bg)
    h3
    .nldesign-preview-box (overflow:hidden, shadow)
      .nldesign-preview-header (height: 50px, color-primary default)
      .nldesign-preview-content (flex row, gap 12px)
        .nldesign-preview-button (secondary style)
        .nldesign-preview-button.primary (primary style)
  .nldesign-info
    a (external link)
```

### Theming sync dialog

```
.nldesign-dialog-overlay (fixed fullscreen, rgba backdrop, z-index: 9999, flex center)
  .nldesign-dialog (max-width: 600px, max-height: 90vh, scroll)
    h3
    .nldesign-dialog-previews (flex, gap 20px)
      .nldesign-dialog-preview-col (x2)
        .nldesign-dialog-preview-label
        .nldesign-dialog-preview-box (230x140px)
          img.nldesign-dialog-preview-logo
    table.nldesign-dialog-table
      .nldesign-dialog-swatch (inline-block 16x16px color chip)
    .nldesign-dialog-hint
    .nldesign-dialog-actions (flex end, gap)
      button.nldesign-dialog-cancel
      button.nldesign-dialog-confirm (primary style)
```

CSS custom properties used (Nextcloud token vocabulary):
`--color-text-maxcontrast`, `--color-border-dark`, `--color-border`, `--color-main-background`, `--color-background-dark`, `--color-background-hover`, `--color-primary`, `--color-primary-text`, `--color-primary-hover`, `--color-primary-element`, `--color-primary-element-text`, `--color-primary-element-hover`, `--color-primary-element-light`, `--border-radius`, `--border-radius-large`, `--border-radius-element`

---

## 5. API Controller — `lib/Controller/SettingsController.php`

All endpoints are guarded by `@AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)`. Non-admin requests are rejected by the Nextcloud framework before the controller method is called.

| Method | Route name | Validation |
|---|---|---|
| `setTokenSet(string $tokenSet)` | `settings#setTokenSet` | `TokenSetService::isValidTokenSet()` — rejects unknown ids and path traversal attempts; returns 400 on failure |
| `getTokenSet()` | `settings#getTokenSet` | None required |
| `getAvailableTokenSets()` | `settings#getAvailableTokenSets` | None required |
| `setSloganSetting(bool $hideSlogan)` | `settings#setSloganSetting` | Bool coerced to `'1'`/`'0'` string before `setAppValue` |
| `setMenuLabelsSetting(bool $showMenuLabels)` | `settings#setMenuLabelsSetting` | Bool coerced to `'1'`/`'0'` string before `setAppValue` |
| `getThemingValues()` | `settings#getThemingValues` | None required |
| `updateThemingValues()` | `settings#updateThemingValues` | `ThemingService::validateColors()` (hex regex); `ThemingService::validateImagePaths()` (path traversal + directory whitelist + `file_exists`) |

Routes are registered in `appinfo/routes.php` via the standard Nextcloud routes array.

---

## 6. CSS injection at boot — `lib/AppInfo/Application.php`

The theme CSS files are injected globally on every Nextcloud page load via `IBootstrap::boot()`. This is separate from the admin settings but is the runtime effect of the settings choices.

Load order (intentional — later files override earlier):

1. `fonts` — Fira Sans via `@fontsource`
2. `defaults` — base `--nldesign-*` token values
3. `tokens/{tokenSet}` — active organisation token overrides
4. `utrecht-bridge` — maps `--utrecht-*` to `--nldesign-*`
5. `theme` — maps `--nldesign-*` to Nextcloud element styling
6. `overrides` — maps Nextcloud `--color-*` vars to `--nldesign-*`
7. `element-overrides` — targeted Nextcloud element overrides
8. `hide-slogan` — conditionally added when `hide_slogan === '1'`
9. `show-menu-labels` — conditionally added when `show_menu_labels === '1'`

Settings are read fresh on every boot via `IConfig::getAppValue`.
