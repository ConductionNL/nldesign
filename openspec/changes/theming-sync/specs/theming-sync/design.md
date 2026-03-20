# Theming Sync — Technical Design

## Overview

When an admin selects a design token set in the NL Design admin settings, the app optionally synchronises matching values into Nextcloud's built-in theming system (`OCA\Theming`). This keeps the CSS-layer colours and brand assets consistent with Nextcloud core theming, which drives background images, email templates, and the server branding shown in the file-sharing UI.

The feature is purely opt-in and triggered by a confirmation dialog: if a token set carries `theming` metadata and that metadata differs from the current Nextcloud theming state, the admin is shown a "before / after" comparison and must explicitly click "Update theming" before any changes are written.

---

## Component Map

```
token-sets.json                 ← theming metadata source-of-truth
        |
        v
TokenSetService::getAvailableTokenSets()
        |  passes theming object through when present
        v
Admin::getForm()  →  templates/settings/admin.php
        |  writes tokenSets as data-token-sets JSON on #nldesign-settings
        v
js/admin.js
  saveTokenSet()               — POST /settings/tokenset (saves CSS layer selection)
  checkAndShowThemingDialog()  — GET /settings/theming   (compare current vs proposed)
  showThemingDialog()          — builds inline modal with diff table + preview boxes
  [confirm click]              — POST /settings/theming  (apply selected diffs)
        |
        v
SettingsController
  getThemingValues()           — reads IConfig theming values + ImageManager state
  updateThemingValues()        — validates then applies via ThemingService
        |
        v
ThemingService
  validateColors()             — hex regex check on primary_color, background_color
  validateImagePaths()         — path-traversal check + directory allowlist + file_exists
  applyColors()                — ThemingDefaults::set(key, value)
  applyImages()                — ImageManager::updateImage(key, absolutePath)
```

---

## Theming Metadata in token-sets.json

Each entry in `token-sets.json` may carry an optional `theming` object:

```json
{
  "id": "rijkshuisstijl",
  "name": "Rijkshuisstijl",
  "description": "Official Dutch national government design system (Rijksoverheid)",
  "theming": {
    "primary_color": "#154273",
    "background_color": "#F5F6F7",
    "logo": "img/logos/rijkshuisstijl.svg"
  }
}
```

| Field              | Type            | Required | Description                                              |
|--------------------|-----------------|----------|----------------------------------------------------------|
| `primary_color`    | hex string      | yes      | Brand accent colour (3-digit or 6-digit hex, `#` prefix) |
| `background_color` | hex string      | yes      | Login/background colour                                  |
| `logo`             | relative path   | no       | Path relative to app root, must be under `img/logos/`   |
| `background`       | relative path   | no       | Path relative to app root, must be under `img/backgrounds/` |

Token sets without a `theming` key are treated as CSS-only sets. The `TokenSetService` passes the `theming` object through unchanged when building the available-token-sets array; if the key is absent it is omitted from the output entirely. This means the admin JS can use `ts.theming` as a presence check.

---

## API Endpoints

All endpoints are registered in `appinfo/routes.php` and protected by `@AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)`.

### GET /apps/nldesign/settings/theming

Returns the current state of Nextcloud's built-in theming for comparison.

**Handler:** `SettingsController::getThemingValues()`

**Implementation detail:** Colors are read from `IConfig::getAppValue('theming', 'primary_color', '')` — this is the raw config store used by `ThemingDefaults`, not the `ThemingDefaults` object itself (which applies fallbacks). Image state is read through `ImageManager::getImageUrl()` and `ImageManager::hasImage()`.

**Response schema:**
```json
{
  "primary_color":         "string (hex or empty)",
  "background_color":      "string (hex or empty)",
  "logo_url":              "string (URL or empty)",
  "background_url":        "string (URL or empty)",
  "has_custom_logo":       "boolean",
  "has_custom_background": "boolean"
}
```

When no custom theming has been applied: both color fields are empty strings, both boolean fields are `false`.

### POST /apps/nldesign/settings/theming

Validates and applies theming values. Accepts `application/x-www-form-urlencoded` (as sent by the admin dialog confirm action).

**Handler:** `SettingsController::updateThemingValues()`

**Accepted parameters:**
| Parameter          | Type          | Description                                         |
|--------------------|---------------|-----------------------------------------------------|
| `primary_color`    | hex string    | Nextcloud primary/accent colour                     |
| `background_color` | hex string    | Nextcloud background/login colour                   |
| `logo`             | relative path | Logo image, relative to app root                    |
| `background`       | relative path | Background image, relative to app root              |

All parameters are optional; omitted or empty parameters are skipped. Processing order is: color validation → image-path validation → apply colors → apply images. Any validation failure returns HTTP 400 and nothing is applied.

**Success response:**
```json
{ "status": "ok", "updated": ["primary_color", "logo"] }
```

**Error response:**
```json
{ "error": "Invalid hex color for primary_color: not-a-color" }
```

---

## ThemingService

**File:** `lib/Service/ThemingService.php`

**Constructor injection:**
```php
public function __construct(
    ImageManager    $imageManager,
    ThemingDefaults $themingDefaults,
    IAppManager     $appManager
)
```

`ImageManager` and `ThemingDefaults` are provided by `OCA\Theming` (the built-in Nextcloud theming app). `IAppManager` is used to resolve the absolute path to the nldesign app directory when building full file paths for image uploads.

### Color Validation

`isValidHexColor(string $color): bool`
- Regex: `/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/`
- Accepts both 3-digit shorthand (`#abc`) and 6-digit full form (`#aabbcc`)
- Case-insensitive hex digits

`validateColors(array $params): ?string`
- Iterates over `['primary_color', 'background_color']`
- Skips keys that are absent or empty (allows partial updates)
- Returns error string on first failure, `null` on success

### Image Path Validation

`validateImagePaths(array $params): ?string`
- Iterates over `['logo', 'background']`
- Delegates each non-empty path to `validateSinglePath()`
- Returns error string on first failure, `null` on success

`validateSinglePath(string $imageKey, string $imagePath): ?string` (private)

Three-stage validation:
1. **Path traversal check** — rejects any path containing `..` or starting with `/`
   - Error: `"Invalid image path for {key}: path traversal not allowed"`
2. **Directory allowlist** — path must start with `img/logos/` or `img/backgrounds/`
   - Error: `"Invalid image path for {key}: must be in img/logos/ or img/backgrounds/"`
3. **File existence check** — `file_exists(appPath . '/' . imagePath)`
   - Error: `"Image file not found: {imagePath}"`

The absolute app path is obtained via `IAppManager::getAppPath('nldesign')`.

### Applying Colors

`applyColors(array $params): array`
- Iterates over `['primary_color', 'background_color']`
- For each non-empty value: calls `ThemingDefaults::set(key, value)`
- Returns array of applied keys

### Applying Images

`applyImages(array $params): array`
- Iterates over `['logo', 'background']`
- For each non-empty value: resolves absolute path as `appPath . '/' . relativePath`
- Calls `ImageManager::updateImage(key, absolutePath)`
- Returns array of applied keys

---

## TokenSetService — Theming Passthrough

**File:** `lib/Service/TokenSetService.php`

`getAvailableTokenSets()` scans `css/tokens/` for CSS files and merges metadata from `token-sets.json`. The `theming` key from the manifest is conditionally included:

```php
if (isset($meta['theming']) === true && is_array($meta['theming']) === true) {
    $tokenSet['theming'] = $meta['theming'];
}
```

This means token sets without theming metadata will not have the key in the response, and the admin JS will not offer theming sync for them.

---

## Admin Template and Data Binding

**File:** `templates/settings/admin.php`

The full token sets array (including any `theming` sub-objects) is JSON-encoded into a `data-token-sets` attribute on the root settings element:

```php
data-token-sets="<?php p(json_encode($_['tokenSets'])); ?>"
```

The admin JS reads this at `DOMContentLoaded` and builds a lookup map keyed by token set ID:

```js
var tokenSets = JSON.parse(settingsEl.getAttribute('data-token-sets') || '[]');
tokenSets.forEach(function(ts) { tokenSetsData[ts.id] = ts; });
```

---

## Admin Dialog UX (js/admin.js)

The theming-sync flow is triggered automatically when a token set selection is saved and that token set carries `theming` metadata.

### Flow

```
admin selects token set
  → saveTokenSet()  POST /settings/tokenset
    → on success: check tsData.theming
      → checkAndShowThemingDialog(tsData)
        → GET /settings/theming   (current NC theming state)
          → compute diffs between proposed and current
            → if diffs.length > 0: showThemingDialog()
```

### Diff Computation

In `checkAndShowThemingDialog()`:
- `primary_color` diff: case-insensitive hex comparison (`toLowerCase()`)
- `background_color` diff: case-insensitive hex comparison
- `logo` diff: always shown if proposed has a logo value (no URL-level comparison — the current logo URL is opaque)
- `background` diff: always shown if proposed has a background value

Only differing fields are shown in the dialog table.

### Dialog Structure

`showThemingDialog()` injects a full-screen overlay into `document.body`:

```
#nldesign-theming-dialog-overlay   (overlay, click-outside-to-close)
  .nldesign-dialog
    h3  "Update Nextcloud theming to match {name}?"
    .nldesign-dialog-previews
      .nldesign-dialog-preview-col  "Current"
        .nldesign-dialog-preview-box  (background-color from current, bg-image if custom)
          img  (current logo, if has_custom_logo)
      .nldesign-dialog-preview-col  "Proposed"
        .nldesign-dialog-preview-box  (background-color from proposed or current fallback)
          img  (proposed logo via OC.linkTo, or current logo as fallback)
    table.nldesign-dialog-table
      thead  Setting | Current | Proposed
      tbody  one row per diff
        color cells: inline swatch span + hex string
        image cells: filename only (split('/').pop())
    p.nldesign-dialog-hint  (note about unchanged values)
    .nldesign-dialog-actions
      button.nldesign-dialog-cancel
      button.nldesign-dialog-confirm  "Update theming"
```

XSS prevention: all user-visible strings pass through `escapeHtml()` (creates a DOM text node and reads back `innerHTML`). Color swatches use `escapeHtml()` on the hex string before embedding in `style=`.

### Confirm Action

On confirm, the dialog:
1. Disables the button and sets text to "Updating..."
2. Builds a payload from the diff list — only keys that appeared in `diffs` are sent
3. POSTs to `/apps/nldesign/settings/theming` as `application/x-www-form-urlencoded`
4. On success: shows temporary notification, reloads page after 1500 ms
5. On error: shows notification with `data.error` message; overlay is removed in both cases

---

## Dependencies

| Dependency          | Source              | Purpose                                              |
|---------------------|---------------------|------------------------------------------------------|
| `ThemingDefaults`   | `OCA\Theming`       | `set(key, value)` — persists color values            |
| `ImageManager`      | `OCA\Theming`       | `updateImage(key, path)`, `getImageUrl()`, `hasImage()` |
| `IAppManager`       | `OCP\App`           | `getAppPath('nldesign')` — resolves absolute FS path |
| `IConfig`           | `OCP`               | Read raw theming app config values for GET response  |

The `theming` app must be enabled in Nextcloud for `ThemingDefaults` and `ImageManager` to be available. If the app is absent, DI will fail at container resolution time. No explicit app-enabled check is implemented; this is a platform-level dependency.

---

## File Inventory

| File                                    | Role                                              |
|-----------------------------------------|---------------------------------------------------|
| `token-sets.json`                       | Theming metadata for all token sets               |
| `lib/Service/ThemingService.php`        | Validation and application of theming values      |
| `lib/Service/TokenSetService.php`       | Token set discovery; passthrough of theming key   |
| `lib/Controller/SettingsController.php` | HTTP handlers for GET/POST /settings/theming      |
| `lib/Settings/Admin.php`                | Admin settings form; passes tokenSets to template |
| `templates/settings/admin.php`          | Embeds tokenSets JSON in data-token-sets attr     |
| `js/admin.js`                           | Dialog logic: diff, render, confirm, POST         |
| `appinfo/routes.php`                    | Route registration for theming endpoints          |
| `img/logos/`                            | Allowed logo images (SVG)                         |
| `img/backgrounds/`                      | Allowed background images                         |
