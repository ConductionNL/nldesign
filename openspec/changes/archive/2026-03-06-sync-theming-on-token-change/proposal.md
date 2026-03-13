# Proposal: sync-theming-on-token-change

## Summary
When an admin selects a different token set in nldesign, Nextcloud's built-in theming values (primary color, background color, logo, background image) remain unchanged — forcing the admin to manually update them at `/settings/admin/theming`. This change adds a confirmation dialog after token set selection that shows current vs. new values and offers to update them automatically. Additionally, the token set selector is converted from radio buttons to a searchable dropdown, since the list is expected to grow to 400+ entries.

## Motivation
Currently, switching a token set only changes NL Design System CSS variables. But Nextcloud has its own theming layer (primary color, background color, logo, background image) that is used throughout the UI for elements that don't read CSS variables directly (e.g., email templates, mobile app theming, favicon generation). Without syncing these values, the Nextcloud instance looks partially themed — the CSS-driven parts use the new token set colors, but Nextcloud-native elements still show the old colors. This creates a confusing split-brain theming state that requires manual intervention.

The radio button UI also doesn't scale — with 39 token sets today and an expected 400+, scrolling through radio buttons is impractical.

## Affected Projects
- [x] Project: `nldesign` — Add theming sync dialog, convert radio to dropdown, add theming metadata to token sets

## Scope
### In Scope
- Confirmation dialog after token set selection showing current vs. proposed Nextcloud theming values
- Automatic update of Nextcloud primary color and background color via the theming API
- Searchable dropdown replacing radio button list for token set selection
- Adding theming metadata (primary_color, background_color) to token-sets.json entries
- PHP endpoint to proxy theming updates through nldesign

### Out of Scope
- Logo and background image auto-upload (these are file-based and organization-specific — too complex for automatic sync)
- Modifying Nextcloud's core theming app
- Per-user theming sync (this is admin-level only)
- Changing the 7-layer CSS cascade or token file format

## Approach
1. **Extend token-sets.json** with optional `theming` metadata per token set containing `primary_color` and `background_color` hex values
2. **Create a PHP endpoint** that reads current Nextcloud theming values and accepts updates (proxying to Nextcloud's theming API)
3. **Replace radio buttons with a searchable `<select>` dropdown** in the admin template
4. **Add JavaScript dialog logic** — after selecting a new token set, if it has theming metadata, show a confirmation dialog comparing current vs. new values before applying
5. **Wire up the theming update** — on user confirmation, call the nldesign endpoint which updates Nextcloud theming values

## Cross-Project Dependencies
- **Nextcloud core theming** — Uses `OCA\Theming\ThemingDefaults::set()` and `POST /apps/theming/ajax/updateStylesheet` API. Read-only dependency, no changes to core.

## Rollback Strategy
Revert the admin template, JS, and PHP changes. Remove theming metadata from token-sets.json entries. The Nextcloud theming values are independent and won't be affected by rolling back nldesign code.

## Open Questions
None — the Nextcloud theming API is well-documented and stable.
