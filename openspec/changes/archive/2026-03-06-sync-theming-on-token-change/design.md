# Design: sync-theming-on-token-change

## Architecture Overview

This change modifies three layers of the nldesign admin settings:

1. **Data layer** — Extend `token-sets.json` with optional `theming` metadata per token set (primary_color, background_color, logo, background)
2. **Backend layer** — New endpoints to read current Nextcloud theming values, proxy color updates, and proxy image uploads via Nextcloud's theming API
3. **Frontend layer** — Replace radio buttons with a searchable `<select>` dropdown, add a confirmation dialog with a Nextcloud-style theming preview after token set change

```
User selects token set in dropdown
  → JS saves token set via existing POST /settings/tokenset
  → JS checks if new token set has theming metadata
  → If yes: JS fetches current NC theming values via GET /settings/theming
  → Dialog shows Nextcloud-style preview (background + logo overlay) for both current and proposed
  → Dialog shows color comparison table with swatches
  → On confirm: JS calls POST /settings/theming to update colors + images
  → On cancel: token set is applied but NC theming values stay unchanged
```

## Dialog Preview Design

The confirmation dialog reuses Nextcloud's theming preview style — a small box showing background-color/image with a logo overlay:

```
┌──────────────────────────────────────────────────────┐
│  Update Nextcloud theming to match VNG?              │
│                                                      │
│  ┌─── Current ───┐    ┌─── Proposed ──┐             │
│  │ ████████████  │    │ ████████████  │             │
│  │ ██ [logo] ██  │    │ ██ [logo] ██  │             │
│  │ ████████████  │    │ ████████████  │             │
│  └───────────────┘    └───────────────┘             │
│                                                      │
│  Primary color:  #0082c9  →  #003865                │
│  Background:     #00679e  →  #003865                │
│  Logo:           (current) →  vng.svg               │
│  Background:     (current) →  vng-background.jpg    │
│                                                      │
│  Only values that differ are shown.                  │
│  Items without a proposed value are left unchanged.  │
│                                                      │
│              [ Cancel ]  [ Update theming ]           │
└──────────────────────────────────────────────────────┘
```

Each preview box is styled like Nextcloud's admin theming preview:
- 230×140px box with `background-color` set to the token set's background color
- `background-image` from the token set's background image (if defined)
- Logo overlaid in center using `background-image` CSS with `contain` sizing

## Token Set Theming Metadata

### `token-sets.json` structure

Each token set entry can optionally include a `theming` object:

```json
{
  "id": "vng",
  "name": "VNG Vereniging Nederlandse Gemeenten",
  "description": "...",
  "theming": {
    "primary_color": "#003865",
    "background_color": "#003865",
    "logo": "img/logos/vng.svg",
    "background": "img/backgrounds/vng.jpg"
  }
}
```

- `primary_color` / `background_color` — hex color strings
- `logo` / `background` — paths relative to the nldesign app directory, pointing to bundled image files
- All fields are optional — only present fields are shown in the dialog and updated on confirm
- Token sets without a `theming` object skip the dialog entirely

### Image storage

Organization logos and background images are bundled in the nldesign app:

```
nldesign/
  img/
    logos/          ← SVG/PNG logos per organization
      vng.svg
      amsterdam.svg
      ...
    backgrounds/    ← Background images per organization
      vng.jpg
      amsterdam.jpg
      ...
```

These are static assets shipped with the app. The generate-tokens.mjs script can be extended later to pull logos from the upstream themes repo, but for now they are added manually per organization.

## API Design

### `GET /settings/theming`
Returns current Nextcloud theming values and image URLs for comparison in the dialog.

**Response:**
```json
{
  "primary_color": "#0082c9",
  "background_color": "#00679e",
  "logo_url": "/index.php/apps/theming/image/logo?v=123",
  "background_url": "/index.php/apps/theming/image/background?v=123",
  "has_custom_logo": true,
  "has_custom_background": false
}
```

### `POST /settings/theming`
Updates Nextcloud theming values. Proxies colors to `IConfig::setAppValue()` and images to Nextcloud's `POST /apps/theming/ajax/uploadImage`.

**Request:**
```json
{
  "primary_color": "#003865",
  "background_color": "#003865",
  "logo": "img/logos/vng.svg",
  "background": "img/backgrounds/vng.jpg"
}
```

All fields are optional — only included fields are updated.

**Response:**
```json
{
  "status": "ok",
  "updated": ["primary_color", "background_color", "logo", "background"]
}
```

**Image upload flow (server-side):**
1. Read the image file from nldesign's app directory (e.g., `img/logos/vng.svg`)
2. Use Nextcloud's `ImageManager::updateImage($key, $tmpFile)` to set it as the active logo/background
3. Increment the theming cachebuster so browsers pick up the change

### Modified: `GET /settings/tokensets`
Already exists. Response now includes theming metadata from token-sets.json:

```json
{
  "tokenSets": [
    {
      "id": "vng",
      "name": "VNG Vereniging Nederlandse Gemeenten",
      "description": "...",
      "theming": {
        "primary_color": "#003865",
        "background_color": "#003865",
        "logo": "img/logos/vng.svg",
        "background": "img/backgrounds/vng.jpg"
      }
    }
  ]
}
```

Token sets without theming metadata simply omit the `theming` field — the dialog won't appear for those.

## Database Changes

None. Theming values are stored via Nextcloud's existing `IConfig` mechanism. Images are stored via Nextcloud's existing `ImageManager` (in AppData). The token-sets.json file is the only nldesign data source that changes.

## Nextcloud Integration

- **Controllers:** `SettingsController` — add `getThemingValues()` and `updateThemingValues()` methods
- **Services:** `TokenSetService` — already reads token-sets.json, will now return theming metadata
- **OCP interfaces:**
  - `IConfig::getAppValue('theming', 'primary_color')` — read current color values
  - `IConfig::setAppValue('theming', 'primary_color', $value)` — write color values
  - `IConfig::setAppValue('theming', 'cachebuster', ...)` — increment cache buster
- **Internal theming classes (used carefully):**
  - `OCA\Theming\ImageManager::updateImage($key, $tmpFile)` — upload logo/background
  - `OCA\Theming\ImageManager::getImageUrl($key)` — get current image URLs
- **Annotations:** `#[AuthorizedAdminSetting]` on both new endpoints (admin-only)

### Decision: Use ImageManager for image uploads

While `IConfig` suffices for colors, images require Nextcloud's `ImageManager` since it handles image optimization (background resizing to 4096px), MIME type storage, cache invalidation, and AppData storage. Using `ImageManager::updateImage()` directly is the cleanest approach — it's the same method the theming controller uses internally.

### Decision: Use IConfig for colors

`IConfig::setAppValue('theming', ...)` is the public config API for writing theming values. We increment the cachebuster manually after setting colors so the browser stylesheet refresh picks up changes immediately.

### Decision: Validate hex colors server-side

The endpoint MUST validate that submitted colors are valid hex format (`#XXXXXX` or `#XXX`) to prevent injection. Reject any non-hex values with a 400 response.

### Decision: Validate image paths server-side

The endpoint MUST validate that image paths point to files within the nldesign app directory (`img/logos/` or `img/backgrounds/`). Reject path traversal attempts (e.g., `../../etc/passwd`). Only allow files that actually exist on disk.

## File Structure

```
nldesign/
  token-sets.json                      ← MODIFIED: add theming metadata per entry
  appinfo/routes.php                   ← MODIFIED: add 2 new routes
  lib/Controller/SettingsController.php ← MODIFIED: add getThemingValues, updateThemingValues
  lib/Service/TokenSetService.php       ← MODIFIED: include theming field in output
  templates/settings/admin.php          ← MODIFIED: radio → select dropdown
  js/admin.js                           ← MODIFIED: dropdown handler, dialog logic, theming sync
  css/admin.css                         ← MODIFIED: dropdown styling, dialog styling
  img/
    logos/                              ← NEW: organization logo files
    backgrounds/                        ← NEW: organization background images
```

## Security Considerations

- **Admin-only access** — Both new endpoints require `#[AuthorizedAdminSetting]`
- **CSRF protection** — All POST requests require `OC.requestToken` header (already in place for existing endpoints)
- **Color validation** — Hex color regex: `/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/`
- **Path validation** — Image paths must be within `img/logos/` or `img/backgrounds/`, exist on disk, and have allowed extensions (svg, png, jpg, jpeg, webp)
- **No direct SQL** — All config access via `IConfig` and `ImageManager` interfaces

## NL Design System

The dialog follows Nextcloud's standard admin UI patterns:
- Standard `<select>` element styled by Nextcloud's CSS
- Custom HTML modal for the comparison dialog (OC.dialogs is text-only)
- Theming preview boxes match Nextcloud's own preview style (230×140px, background + logo overlay)
- Nextcloud color variables for dialog styling

## Trade-offs

| Decision | Alternative | Rationale |
|----------|-------------|-----------|
| Searchable `<select>` dropdown | Vue/React autocomplete component | Vanilla `<select>` is simple, accessible, and Nextcloud styles it. Browser-native type-to-filter works at 400+ entries. No build toolchain needed. |
| Theming metadata in token-sets.json | Separate theming-map.json | Single manifest avoids file proliferation. generate-tokens.mjs preserves manual metadata on re-runs. |
| Bundled images in `img/` | External URL references | Local files are reliable, don't depend on external services, and can be uploaded to NC theming via ImageManager. |
| Proxy through nldesign endpoint | Direct JS call to NC theming API | Server-side validation, encapsulated theming logic, avoids cross-app CSRF issues. |
| Simple HTML dialog | Nextcloud OC.dialogs | Need rich content (preview boxes, color swatches, image previews) that OC.dialogs.confirm() can't render. |
| ImageManager for uploads | IConfig for image paths | ImageManager handles optimization, MIME types, AppData storage, and cache invalidation — the same path NC's own theming uses. |
