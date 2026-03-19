# Proposal: nextcloud-theme-editor

## Why

The nldesign app currently only loads organization-specific CSS token sets — administrators cannot customize individual Nextcloud CSS tokens themselves. Any customization requires editing files directly or using Nextcloud's limited built-in theming panel. The app should evolve from a passive NL Design theme loader into an interactive **Nextcloud Theme Editor** that also supports NL Design System themes.

## What Changes

- **NEW** — Tabbed token editor in admin settings: groups all Nextcloud CSS variables into logical categories (colors, typography, spacing, borders, shadows, etc.) with editable inputs and per-token reset controls
- **NEW** — `custom-overrides.css` persistence layer: user edits are written exclusively to this file, loaded last in the CSS stack — no database changes needed, NL Design token set CSS files are never overwritten
- **NEW** — NL Design token-set apply flow: selecting a token set opens a modal showing every value that would change (old → new). The user checks/unchecks individual changes; only checked values are written into `custom-overrides.css`. The token set CSS file is NOT applied directly — selected values are promoted to explicit custom overrides so they can be edited freely afterwards in the token editor forms.
- **NEW** — Per-token reset-to-default control: each token input in the editor has an inline button to clear its entry from `custom-overrides.css` (falls back to NL Design token set value or Nextcloud default)
- **NEW** — Token import/export: download or upload `custom-overrides.css` in Nextcloud CSS variable format (`--color-*`) and NL Design token format (`--nldesign-*` / JSON)
- **MODIFIED** — App is repositioned and re-scoped as a Nextcloud Theme Editor with NL Design System support, not solely an NL Design theme loader

## Capabilities

### New Capabilities
- `token-editor-ui` — Tabbed admin UI for browsing and editing all Nextcloud CSS token categories with per-token reset controls; reads current resolved values, writes to `custom-overrides.css`
- `custom-css-overrides` — CSS file persistence layer: `custom-overrides.css` is the single write target for all user customizations, loaded last in the stack; NL Design token set files are read-only presets
- `token-set-apply-dialog` — Modal shown when selecting an NL Design token set: lists each value that would change with checkboxes; only checked values are written to `custom-overrides.css` as explicit overrides
- `token-import-export` — Upload and download the current `custom-overrides.css` in Nextcloud CSS variable format and NL Design token format (CSS / JSON)

### Modified Capabilities
- `nl-design` — App identity and scope: re-positions the app from "NL Design theme loader" to "Nextcloud Theme Editor with NL Design support"; existing token-set functionality is preserved

## Decisions

1. **Token layer**: The editor exposes `--color-*` Nextcloud CSS variables only. The `--nldesign-*` abstraction layer is not surfaced in edit forms — it remains an invisible CSS mapping layer.

2. **Excluded tokens**: Several Nextcloud core variables are intentionally **not editable** because overriding them breaks dark mode, accessibility themes, or auto-calculated values. These are documented in `overrides.css` with "intentionally not overridden" comments. The editor MUST NOT offer fields for these tokens (e.g. `--color-main-background`, `--color-main-background-rgb`, `--color-background-plain`, `--color-primary-element-text-dark`). This exclusion list is canonical and must be maintained in both the CSS and the editor's token registry.

3. **Save behavior**: Changes are previewed live in the browser via inline `:root` style injection. A single **Save** button writes the final result to `custom-overrides.css`. Unsaved changes are lost on page reload.

4. **Tab grouping**: Tabs are organized by UI area (functional), not by CSS variable name prefix. Proposed tabs: **Login page**, **Navigation bar**, **Content area**, **Buttons & inputs**, **Typography**.

5. **Import validation**: On upload, only tokens from the known editable Nextcloud token list are accepted. Unknown variables are silently rejected. Import counts are reported (X imported, Y skipped).

6. **Token-set apply dialog**: The "current" column shows the resolved browser value — what is actually rendering now from the full CSS stack — not just the `custom-overrides.css` entries. All tokens that would change are listed; unchanged tokens are hidden.

## Impact

- **nldesign app** — Primary target: new Vue admin component, PHP controller endpoints for read/write of `custom-overrides.css`, CSS load-order extension
- **CSS architecture** — `custom-overrides.css` is added as the final layer after `element-overrides.css` (user intent always wins)
- **No other apps affected** — Changes are contained within the nldesign app
- **No database migration** — File-based approach avoids schema changes

## Rollback Strategy

- Delete `custom-overrides.css` to restore state as-if no custom tokens were set
- The existing token-set selection and CSS stack are unchanged — removing the new feature has zero impact on existing NL Design theming
- Git revert on admin Vue component if the UI causes regressions
