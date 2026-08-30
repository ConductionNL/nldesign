# Token Sets — Technical Design

## Overview

The token set subsystem enables Dutch government organisations to apply their own visual identity to a Nextcloud installation. The design is intentionally filesystem-first: the authoritative list of available token sets is derived by scanning a directory, with a JSON manifest providing supplementary metadata. A single `IConfig` key persists the administrator's active selection. At boot time the app resolves that key to a concrete CSS file and injects it into every page response.

---

## Component Map

```
bootstrap (Application::boot)
  └─► injectThemeCSS()
        ├─ IConfig::getAppValue('nldesign', 'token_set', 'rijkshuisstijl')
        └─ OCP\Util::addStyle() × 7–9 calls (ordered CSS stack)

admin HTTP layer
  SettingsController  ──── TokenSetService
  Admin (ISettings)   ──┘

persistence
  IConfig  (key: nldesign/token_set)
```

### Files

| File | Role |
|------|------|
| `lib/AppInfo/Application.php` | IBootstrap; reads IConfig, enqueues CSS stack via `\OCP\Util::addStyle()` |
| `lib/Service/TokenSetService.php` | Filesystem discovery, manifest merge, validation |
| `lib/Controller/SettingsController.php` | REST endpoints for get/set token set |
| `lib/Settings/Admin.php` | ISettings panel; passes token sets + current selection to template |
| `appinfo/routes.php` | Route registrations for all `/settings/*` endpoints |
| `token-sets.json` | JSON manifest — metadata (name, description, theming) per token set id |
| `css/tokens/{id}.css` | Organisation-specific CSS variable overrides, one file per token set |
| `css/defaults.css` | Rijkshuisstijl-based fallback values for every `--nldesign-*` token |
| `css/utrecht-bridge.css` | Maps `--utrecht-*` component tokens to `--nldesign-component-*` |
| `css/theme.css` | Maps `--nldesign-*` tokens onto Nextcloud's `--color-*` variables |
| `css/overrides.css` | `:root` level Nextcloud CSS variable reassignments |
| `css/element-overrides.css` | High-specificity element-level NL Design styling |

---

## Filesystem Discovery

`TokenSetService::getAvailableTokenSets()` follows this algorithm:

```
appPath  = IAppManager::getAppPath('nldesign')
tokensDir = appPath + '/css/tokens'
manifest  = readManifest(appPath + '/token-sets.json')

for each file in scandir(tokensDir):
    if file ends with '.css':
        id = basename(file, '.css')
        meta = manifest[id] ?? null
        entry = {
            id:          id,
            name:        meta.name        ?? ucwords(str_replace('-', ' ', id)),
            description: meta.description ?? 'Design tokens for ' + formatName(id),
        }
        if meta.theming is array:
            entry.theming = meta.theming
        append entry

sort entries case-insensitively by name
return entries
```

Key properties of this design:

- **CSS file is the source of truth.** A manifest-only entry with no matching `.css` file is silently excluded.
- **Manifest is additive.** Missing manifest entries do not block discovery; names and descriptions are auto-generated.
- **Sort is applied after merge**, so the final list is always alphabetical by display name regardless of filesystem or manifest ordering.

### IAppManager Path Resolution

The service depends on `IAppManager::getAppPath('nldesign')` rather than `__DIR__` or a hardcoded path. This ensures correctness whether the app is installed as a system app, user app, or custom app, and allows unit testing by substituting a mock `IAppManager`.

---

## Manifest Parsing

`TokenSetService::readManifest(string $manifestPath): array`

```
if file does not exist → return []
content = file_get_contents(manifestPath)
if content === false → return []
data = json_decode(content, assoc=true)
if data is not array → return []

indexed = {}
for each entry in data:
    if entry has 'id':
        indexed[entry.id] = entry
return indexed
```

The result is a map keyed by `id` so that lookup during filesystem iteration is O(1). Three failure modes all return an empty map: missing file, unreadable file, and malformed JSON. In all cases filesystem discovery continues with auto-generated names.

### Manifest Entry Schema

```json
{
  "id":          "amsterdam",            // kebab-case, must match CSS filename
  "name":        "Gemeente Amsterdam",   // human-readable display name
  "description": "Design tokens for…",  // one-sentence description
  "theming": {                           // optional; drives Nextcloud Theming app
    "primary_color":    "#004699",       // hex #RGB or #RRGGBB
    "background_color": "#FFFFFF",       // hex #RGB or #RRGGBB
    "logo":             "img/logos/amsterdam.svg"  // relative app path, optional
  }
}
```

The `theming` object is passed through verbatim to API responses and to the `Admin` settings panel, which uses it to pre-populate the "Apply theming values" action (delegated to `ThemingService`).

---

## Validation

`TokenSetService::isValidTokenSet(string $tokenSetId): bool`

```
if tokenSetId contains '/' or '..' → return false
cssFile = getAppPath() + '/css/tokens/' + tokenSetId + '.css'
return file_exists(cssFile)
```

Path traversal is checked first, before any filesystem access. The check is explicit against `/` and `..` rather than a regex allowlist so the rejection logic is immediately legible. Validation intentionally re-checks the filesystem at call time (not against the in-memory discovery cache) to guard against concurrent filesystem changes.

`SettingsController::setTokenSet()` calls `isValidTokenSet()` before touching `IConfig`. A failed validation returns HTTP 400 with `{"error": "Invalid token set"}` and makes no state change.

---

## IConfig Persistence

| Key | Namespace | Default | Type |
|-----|-----------|---------|------|
| `token_set` | `nldesign` | `rijkshuisstijl` | string |
| `hide_slogan` | `nldesign` | `'0'` | `'0'`/`'1'` |
| `show_menu_labels` | `nldesign` | `'0'` | `'0'`/`'1'` |

All three keys are app-level config stored via `IConfig::setAppValue()` / `IConfig::getAppValue()`. They are read once per request in `Application::injectThemeCSS()` during the bootstrap `boot()` phase.

`token_set` is the only key relevant to this spec. The default `'rijkshuisstijl'` is applied at both read points: `getAppValue()` call in `Application::boot()`, `SettingsController::getTokenSet()`, and `Admin::getForm()`.

---

## CSS Loading

`Application::injectThemeCSS()` runs during `boot()` and enqueues the full CSS stack via `\OCP\Util::addStyle()`. Nextcloud renders all registered stylesheets as `<link>` tags in page `<head>`, maintaining the registration order.

### Load Order

```
1. fonts               — Fira Sans from @fontsource
2. defaults            — All --nldesign-* token defaults (Rijkshuisstijl values)
3. tokens/{tokenSet}   — Organisation overrides (e.g. tokens/amsterdam)
4. utrecht-bridge      — --utrecht-* → --nldesign-component-* mapping
5. theme               — --nldesign-* → Nextcloud --color-* mapping (body selector)
6. overrides           — :root Nextcloud --color-* reassignments
7. element-overrides   — High-specificity element styling
8. hide-slogan         — (conditional) Hides login page slogan
9. show-menu-labels    — (conditional) Shows app menu text labels
```

The cascade is structured so that `defaults.css` provides a complete set of `--nldesign-*` values, and the token set file only needs to override the values that differ. An organisation with only a distinctive primary colour can ship a token set with two lines; everything else inherits from `defaults.css`.

### Utrecht Bridge

`utrecht-bridge.css` maps `--utrecht-*` component tokens (the current prefix used by the NL Design System reference implementation) to `--nldesign-component-*` equivalents. Fallback values in this file reference the already-resolved `--nldesign-*` primitives from step 2/3, not other component tokens, to avoid CSS custom property cycles.

### theme.css vs overrides.css

`theme.css` uses a `body[data-themes], body` selector to override Nextcloud's `--color-*` variables with `!important`. `overrides.css` does the same at `:root` level. Both are needed because Nextcloud applies its own `!important` overrides at varying specificity levels depending on the version and theming app state.

---

## API Endpoints

All endpoints are declared in `appinfo/routes.php` and handled by `SettingsController`. Every action carries `@AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)`, which causes Nextcloud's middleware to reject non-admin requests.

| Method | URL | Handler | Description |
|--------|-----|---------|-------------|
| `GET` | `/apps/nldesign/settings/tokensets` | `getAvailableTokenSets` | Returns `{"tokenSets": [...]}` |
| `GET` | `/apps/nldesign/settings/tokenset` | `getTokenSet` | Returns `{"tokenSet": "<id>"}` |
| `POST` | `/apps/nldesign/settings/tokenset` | `setTokenSet` | Accepts `tokenSet` param; validates + persists |

`setTokenSet` response on success: `{"status": "ok", "tokenSet": "<id>"}`.
`setTokenSet` response on failure: HTTP 400, `{"error": "Invalid token set"}`.

---

## Admin Settings Panel

`Admin` (implementing `OCP\Settings\ISettings`) renders `templates/settings/admin.php`. It is registered in the `theming` section with priority 50. The `getForm()` method passes:

- `tokenSets` — full array from `TokenSetService::getAvailableTokenSets()`
- `currentTokenSet` — current `IConfig` value
- `hideSlogan` — boolean from `IConfig`
- `showMenuLabels` — boolean from `IConfig`

---

## Token Set CSS Structure

Each token set CSS file:

- Targets `:root` exclusively (no element-level rules)
- Declares `--nldesign-color-primary` and `--nldesign-color-primary-text` at minimum
- May declare any subset of the `--nldesign-*` tokens defined in `defaults.css`
- May declare organisation-specific palette variables (e.g. `--rh-color-lintblauw`, `--amsterdam-color-red`) for self-documentation
- May set `--nldesign-logo-url: url('../img/logos/{org}.svg')` for header/login logo
- May set lint/ribbon variables (`--nldesign-color-logo-background`, `--nldesign-size-lint`, etc.) for header ribbon styling

Tokens NOT declared in the token set file fall back to `defaults.css` values automatically via the CSS cascade. No PHP-level merge or inheritance is required.

---

## Coverage

As of the current implementation, 39 token sets are shipped, exactly matching the 39 entries in `token-sets.json`. The filesystem and manifest are in 1:1 correspondence (verified: `diff` produces no output). Required organisations per REQ-TSET-007 — rijkshuisstijl, amsterdam, utrecht, rotterdam, denhaag — are all present.
