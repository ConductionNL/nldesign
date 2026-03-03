---
sidebar_position: 2
---

# Configuration

## Admin Settings

After installing NL Design, configure it through the Nextcloud admin panel:

1. Go to **Administration Settings** (top-right menu > Administration settings)
2. Scroll down to **NL Design** in the left sidebar
3. You'll see the NL Design settings panel

## Selecting a Token Set

The main configuration is choosing which organization's theme to apply:

1. Open the **Token Set** dropdown in the NL Design settings
2. Browse the list of 39 available organizations
3. Select your organization (e.g., "Gemeente Amsterdam", "Gemeente Utrecht")
4. The theme is applied immediately — a color preview shows the selected theme's primary color

The token set controls all visual styling: primary colors, header colors, button styles, link colors, border radius, and more.

## Optional Toggles

NL Design provides two optional settings:

### Hide Login Slogan

When enabled, hides the tagline/slogan text on the Nextcloud login page. This is useful for organizations that want a cleaner login experience or whose slogan doesn't match the government branding.

### Show Menu Labels

When enabled, displays text labels next to the icons in the Nextcloud app sidebar (left navigation). By default, Nextcloud only shows icons — enabling this makes the sidebar more accessible and easier to navigate.

## Theming Sync

When you select a token set, NL Design automatically syncs Nextcloud's built-in theming settings:

- **Primary color** — set to the organization's brand color
- **Background color** — set to the organization's background
- **Logo** — set to the organization's logo (if available in the token set)

This ensures consistency between NL Design's CSS theming and Nextcloud's built-in theming system (which controls elements like email templates and mobile app branding).

## Configuration Storage

NL Design stores three configuration values using Nextcloud's `IConfig`:

| Key | Values | Default |
|-----|--------|---------|
| `nldesign:token_set` | Any token set ID (e.g., `rijkshuisstijl`, `amsterdam`) | `rijkshuisstijl` |
| `nldesign:hide_slogan` | `0` or `1` | `0` |
| `nldesign:show_menu_labels` | `0` or `1` | `0` |

These can also be set via the command line:

```bash
php occ config:app:set nldesign token_set --value=amsterdam
php occ config:app:set nldesign hide_slogan --value=1
php occ config:app:set nldesign show_menu_labels --value=1
```
