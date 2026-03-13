# Design: full-nextcloud-token-support-from-nldesign

## Architecture Overview

The current nldesign app uses a 4-layer CSS approach: fonts → tokens → theme → overrides. This design expands it to a 7-layer system with auto-generated tokens, a defaults layer, and a temporary Utrecht bridge.

```
┌─────────────────────────────────────────────────┐
│  1. fonts.css         (font declarations)       │
│  2. defaults.css      (all --nldesign-* defaults)│  ← NEW
│  3. tokens/{org}.css  (org-specific overrides)   │  ← AUTO-GENERATED
│  4. utrecht-bridge.css (--utrecht-* → --nldesign)│  ← NEW, TEMPORARY
│  5. theme.css         (--nldesign-* → NC vars)   │
│  6. overrides.css     (ALL NC vars, comprehensive)│  ← REWRITTEN
│  7. Optional extras   (hide-slogan, menu-labels) │
└─────────────────────────────────────────────────┘
```

### Key design principle: cascading defaults

`defaults.css` sets ALL `--nldesign-*` tokens to sensible values (based on Rijkshuisstijl). Organization token files only need to define the tokens they want to override. This means:
- Incomplete token sets work out of the box
- New `--nldesign-*` tokens can be added without updating all 48 token files
- The defaults file serves as the single source of truth for available tokens

## API Design

### `POST /apps/nldesign/settings/tokenset`

**Change**: Remove hardcoded `$validSets` array. Instead, dynamically discover available token sets by scanning the `css/tokens/` directory.

**Request:**
```json
{ "tokenSet": "groningen" }
```
**Response (200):**
```json
{ "status": "ok", "tokenSet": "groningen" }
```
**Response (400):**
```json
{ "error": "Invalid token set: no CSS file found for 'nonexistent'" }
```

### `GET /apps/nldesign/settings/tokenset`
No changes needed.

### `GET /apps/nldesign/settings/available-tokensets` (NEW)
Returns all available token sets with metadata.

**Response:**
```json
{
  "tokenSets": [
    { "id": "rijkshuisstijl", "name": "Rijkshuisstijl", "description": "Dutch national government" },
    { "id": "amsterdam", "name": "Amsterdam", "description": "Gemeente Amsterdam" },
    { "id": "groningen", "name": "Groningen", "description": "Gemeente Groningen" }
  ]
}
```

## Database Changes

None. All configuration stays in `IConfig` app values (`token_set`, `hide_slogan`, `show_menu_labels`).

## Nextcloud Integration

- **Controllers**: `SettingsController` — remove hardcoded `$validSets`, add filesystem-based token discovery, add `getAvailableTokenSets()` method
- **Services**: `TokenSetService` (NEW) — handles token discovery by scanning `css/tokens/` directory, provides metadata from a `token-sets.json` manifest
- **Settings/Admin**: Update `getForm()` to use `TokenSetService` instead of hardcoded array
- **AppInfo/Application**: Update `injectThemeCSS()` to add `defaults.css` and `utrecht-bridge.css` in correct order
- **Events/Hooks**: None needed

## File Structure

### New files
```
nldesign/
├── css/
│   ├── defaults.css                    # All --nldesign-* tokens with default values
│   ├── utrecht-bridge.css              # --utrecht-* → --nldesign-* (temporary)
│   ├── tokens/
│   │   ├── rijkshuisstijl.css          # Existing (will be regenerated)
│   │   ├── amsterdam.css               # Existing (will be regenerated)
│   │   ├── utrecht.css                 # Existing (will be regenerated)
│   │   ├── denhaag.css                 # Existing (will be regenerated)
│   │   ├── rotterdam.css               # Existing (will be regenerated)
│   │   ├── groningen.css               # NEW (auto-generated)
│   │   ├── tilburg.css                 # NEW (auto-generated)
│   │   ├── ... (43 more)
│   │   └── zwolle.css                  # NEW (auto-generated)
├── lib/
│   └── Service/
│       └── TokenSetService.php         # NEW: token discovery & metadata
├── scripts/
│   └── generate-tokens.mjs            # Token generation script (Node.js ESM)
├── token-sets.json                     # Manifest: id → name/description per org
├── mappings.md                         # Complete NC ↔ NL Design mapping table
└── .github/
    └── workflows/
        └── sync-tokens.yml            # Nightly token sync workflow
```

### Modified files
```
nldesign/
├── css/
│   ├── theme.css                       # Add mappings for new --nldesign-* tokens
│   └── overrides.css                   # REWRITTEN: all NC variables, comprehensive
├── lib/
│   ├── AppInfo/Application.php         # Updated CSS load order
│   ├── Controller/SettingsController.php  # Dynamic token validation
│   └── Settings/Admin.php              # Dynamic token set list
├── appinfo/
│   └── routes.php                      # Add available-tokensets route
├── templates/
│   └── settings/admin.php              # Dynamic dropdown
├── README.md                           # Add sources section
└── package.json                        # Add style-dictionary or token build deps
```

## Token Generation Pipeline

### Script: `scripts/generate-tokens.mjs`

**Input**: Cloned `nl-design-system/themes` repository (or fetched via GitHub API)

**Process**:
1. For each directory in `proprietary/` and relevant `packages/`:
   - Read `src/config.json` for metadata (name, prefix)
   - Recursively read all `*.tokens.json` files under `src/`
   - Flatten token hierarchy into CSS custom properties
   - Map token names to `--nldesign-*` prefix using a mapping table
   - For component tokens (e.g., `utrecht.button.background-color`), generate `--nldesign-component-button-background-color`
2. Write one CSS file per organization to `css/tokens/{org-id}.css`
3. Update `token-sets.json` manifest with discovered organizations

**Token name mapping strategy**:
```
Source JSON path                          → CSS variable
────────────────────────────────────────────────────────────
{org}.color.primary                       → --nldesign-color-primary
{org}.color.grey.40                       → (org-specific palette, kept as --{org}-color-grey-40)
utrecht.button.background-color           → --nldesign-component-button-background-color
utrecht.button.primary-action.color       → --nldesign-component-button-primary-action-color
utrecht.heading.1.font-size               → --nldesign-component-heading-1-font-size
```

**Fallback handling**: If a token set doesn't define a value, it's simply omitted from the generated CSS. The `defaults.css` provides the fallback.

### Manifest: `token-sets.json`

```json
{
  "rijkshuisstijl": { "name": "Rijkshuisstijl", "description": "Dutch national government (Rijksoverheid)" },
  "amsterdam": { "name": "Amsterdam", "description": "Gemeente Amsterdam" },
  "groningen": { "name": "Groningen", "description": "Gemeente Groningen" },
  ...
}
```

This file is read by `TokenSetService.php` to populate the admin dropdown. It's also updated by the generation script when new organizations appear upstream.

## GitHub Actions Workflow: `sync-tokens.yml`

```yaml
name: Sync NL Design System Tokens
on:
  schedule:
    - cron: '0 3 * * *'  # Every night at 3 AM UTC
  workflow_dispatch: {}   # Manual trigger

jobs:
  sync-tokens:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'

      - name: Clone NL Design System themes
        run: git clone --depth 1 https://github.com/nl-design-system/themes.git /tmp/themes

      - name: Generate token CSS files
        run: node scripts/generate-tokens.mjs /tmp/themes

      - name: Check for changes
        id: changes
        run: |
          git diff --quiet css/tokens/ token-sets.json || echo "changed=true" >> $GITHUB_OUTPUT

      - name: Create PR if changed
        if: steps.changes.outputs.changed == 'true'
        uses: peter-evans/create-pull-request@v6
        with:
          title: 'chore: sync NL Design System tokens'
          body: 'Automated token sync from nl-design-system/themes'
          branch: chore/sync-nldesign-tokens
          labels: automated, nldesign
```

## CSS Architecture Details

### defaults.css

Defines ALL `--nldesign-*` tokens with Rijkshuisstijl-based defaults. Organized by category:

```css
:root {
  /* === Brand Colors === */
  --nldesign-color-primary: #154273;
  --nldesign-color-primary-text: #ffffff;
  --nldesign-color-primary-hover: #1d5499;
  --nldesign-color-primary-light: #e8f0f8;
  --nldesign-color-primary-light-hover: #d4e4f2;

  /* === Background === */
  --nldesign-color-background-hover: #e8e9ea;
  --nldesign-color-background-dark: #e0e1e2;
  --nldesign-color-background-darker: #d0d1d2;

  /* === Text === */
  --nldesign-color-text: #333333;
  --nldesign-color-text-muted: #696969;
  --nldesign-color-text-light: #ffffff;

  /* === Status === */
  --nldesign-color-error: #d52b1e;
  /* ... all status colors with RGB variants ... */

  /* === NEW: Tokens for previously unmapped NC variables === */
  --nldesign-color-favorite: #ffcc00;
  --nldesign-color-placeholder-light: #e6e6e6;
  --nldesign-color-placeholder-dark: #b3b3b3;
  --nldesign-color-scrollbar: #cccccc;
  --nldesign-color-loading-light: #cccccc;
  --nldesign-color-loading-dark: #444444;
  --nldesign-animation-quick: 100ms;
  --nldesign-animation-slow: 300ms;
  --nldesign-spacing-baseline: 4px;

  /* === Component tokens (defaults) === */
  --nldesign-component-button-background-color: transparent;
  --nldesign-component-button-border-radius: var(--nldesign-border-radius);
  --nldesign-component-button-primary-background-color: var(--nldesign-color-primary);
  --nldesign-component-button-primary-color: var(--nldesign-color-primary-text);
  --nldesign-component-heading-1-font-size: 2rem;
  /* ... etc ... */
}
```

### utrecht-bridge.css

**Temporary file** — maps the NL Design System's current `--utrecht-*` component tokens to our `--nldesign-component-*` tokens. This exists because the NL Design System currently uses `--utrecht-*` as the de facto standard for component tokens, but this prefix is expected to become vendor-neutral in the future.

```css
/**
 * Utrecht Bridge — TEMPORARY
 *
 * Maps --utrecht-* component tokens to --nldesign-component-* tokens.
 * The NL Design System currently uses --utrecht-* as its component token
 * standard. When the NL Design System adopts a vendor-neutral prefix,
 * this file can be removed and the token generation script updated.
 *
 * See: https://github.com/nl-design-system/themes
 */

:root {
  --nldesign-component-button-background-color: var(--utrecht-button-background-color, var(--nldesign-component-button-background-color));
  --nldesign-component-button-border-radius: var(--utrecht-button-border-radius, var(--nldesign-component-button-border-radius));
  --nldesign-component-button-primary-background-color: var(--utrecht-button-primary-action-background-color, var(--nldesign-component-button-primary-background-color));
  /* ... etc ... */
}
```

### overrides.css (rewritten)

Contains ALL Nextcloud CSS variables, organized by category. Each line either maps to an `--nldesign-*` variable or is commented out with a reason.

```css
/**
 * NL Design System — Complete Nextcloud Variable Overrides
 *
 * This file maps ALL Nextcloud CSS custom properties to --nldesign-* tokens.
 * Variables without an appropriate NL Design equivalent are commented out
 * with an explanation.
 *
 * Source: Nextcloud apps/theming/lib/Themes/DefaultTheme.php
 * Generated from mappings.md
 */

body[data-themes],
body {
  /* === Primary Colors === */
  --color-primary: var(--nldesign-color-primary) !important;
  --color-primary-text: var(--nldesign-color-primary-text) !important;
  --color-primary-hover: var(--nldesign-color-primary-hover) !important;
  --color-primary-element: var(--nldesign-color-primary) !important;
  --color-primary-element-hover: var(--nldesign-color-primary-hover) !important;
  --color-primary-element-text: var(--nldesign-color-primary-text) !important;
  /* --color-primary-element-text-dark: no NL Design equivalent — NC internal: dark variant of element text */
  --color-primary-light: var(--nldesign-color-primary-light) !important;
  --color-primary-light-hover: var(--nldesign-color-primary-light-hover) !important;
  /* --color-primary-light-text: no NL Design equivalent — NC internal: text on primary-light background */
  --color-primary-element-light: var(--nldesign-color-primary-light) !important;
  --color-primary-element-light-hover: var(--nldesign-color-primary-light-hover) !important;
  /* --color-primary-element-light-text: no NL Design equivalent — NC internal: text on primary-element-light */

  /* === Background === */
  /* --color-main-background: intentionally not overridden — managed by Nextcloud theming */
  /* --color-main-background-rgb: intentionally not overridden — derived from main-background */
  /* --color-main-background-translucent: intentionally not overridden — derived from main-background */
  /* --color-main-background-blur: intentionally not overridden — NC blur filter */
  --color-background-hover: var(--nldesign-color-background-hover) !important;
  --color-background-dark: var(--nldesign-color-background-dark) !important;
  --color-background-darker: var(--nldesign-color-background-darker) !important;
  /* --color-background-plain: no NL Design equivalent — NC internal: non-blurred background */
  /* --color-background-plain-text: no NL Design equivalent — NC internal */

  /* ... continues for ALL ~97 variables ... */
}
```

### theme.css

Stays mostly the same but gains mappings for new `--nldesign-*` tokens (animations, spacing, etc.) and component tokens mapped to Nextcloud elements.

## Decisions

### 1. Node.js for token generation (not Python)

**Why**: The `nl-design-system/themes` repo uses `style-dictionary` (JavaScript) for its own builds. Using Node.js allows us to potentially reuse their config/transforms. The nldesign app already has `package.json` with npm dependencies.

**Alternative considered**: Python script — simpler but adds a second language requirement and can't reuse style-dictionary transforms.

### 2. Filesystem-based token discovery (not config array)

**Why**: Scanning `css/tokens/*.css` to find available token sets means adding a new organization only requires adding a CSS file — no PHP code changes. The `token-sets.json` manifest provides display names and descriptions.

**Alternative considered**: Hardcoded array in PHP — current approach, doesn't scale to 48+ sets, requires code changes for each new org.

### 3. PR-based token sync (not auto-commit)

**Why**: Opening a PR allows review before token changes go live. Token upstream changes could contain breaking changes, new naming conventions, or errors. PRs also create an audit trail.

**Alternative considered**: Auto-commit to main — faster but risky, no review step.

### 4. `--nldesign-component-*` prefix for component tokens

**Why**: Using a consistent `--nldesign-*` namespace means consumers only need to know one prefix. The `utrecht-bridge.css` handles the translation from `--utrecht-*` internally.

**Alternative considered**: Passing `--utrecht-*` tokens through directly — simpler but creates a dependency on a prefix that the NL Design System plans to change.

### 5. Separate `defaults.css` instead of inline fallbacks

**Why**: `var(--nldesign-color-primary, #154273)` (inline fallback) would need to be repeated in every file that uses the token. A dedicated `defaults.css` loaded first means all tokens always have a value, and the fallback values are maintained in one place.

**Alternative considered**: CSS `var()` fallback values — works but duplicates default values everywhere, harder to maintain.

## Risks / Trade-offs

- **[Upstream format changes]** → The `nl-design-system/themes` repo could change its JSON token format. Mitigation: the generation script validates expected structure, the nightly PR allows review before merging.
- **[Large number of CSS files]** → 48 token CSS files adds to app package size. Mitigation: only one is loaded at runtime via `\OCP\Util::addStyle()`, and each file is small (~2-5 KB).
- **[Utrecht prefix deprecation]** → When the NL Design System drops `--utrecht-*`, we need to update the generation script and remove `utrecht-bridge.css`. Mitigation: the bridge file is clearly marked as temporary with removal instructions.
- **[Incomplete component tokens]** → Some orgs only define brand tokens, not component tokens. Mitigation: `defaults.css` provides sensible component defaults.
- **[Breaking the current 5 themes]** → Regenerating existing token files could change values. Mitigation: compare generated output against current files during implementation, validate visually before merging.

## Migration Plan

1. All changes are additive — existing deployments with `token_set=rijkshuisstijl` continue to work
2. The `defaults.css` layer ensures backwards compatibility even if a token set file changes
3. New CSS files are only loaded if selected in admin settings
4. Rollback: revert the commit and the previous `overrides.css` / token files are restored

## Security Considerations

- Token CSS files are static assets, no user input involved
- The `setTokenSet` endpoint validates against available files on disk (no path traversal — only checks if `css/tokens/{input}.css` exists as a filename, not a path)
- GitHub Actions workflow uses read-only clone of public repo, PR creation uses standard `GITHUB_TOKEN`
- No CORS changes needed — all CSS is served by Nextcloud's static file handler
