---
sidebar_position: 4
---

# Admin Settings

NL Design provides an admin settings panel for configuring the active theme and optional display settings.

## Accessing Settings

1. Log in as a Nextcloud administrator
2. Go to **Administration Settings** (click your avatar, then "Administration settings")
3. Find **NL Design** in the left sidebar under "Additional settings"

## Token Set Selector

The main control is a dropdown listing all 39 available token sets. When you select a token set:

- The CSS theme updates immediately on the current page
- A color preview swatch shows the selected theme's primary color
- Nextcloud's theming system is synced with the new primary color, background, and logo (see [Theming Sync](theming-sync))

## Display Options

Below the token set selector, two toggle checkboxes provide optional adjustments:

### Hide Login Slogan

Controls whether the tagline/slogan text is visible on the Nextcloud login page. Enable this for a cleaner login experience that focuses on the organization's branding.

### Show Menu Labels

Controls whether text labels appear next to the icons in Nextcloud's left sidebar navigation. By default, Nextcloud shows only icons — enabling labels improves accessibility and usability, especially for new users.

## Technical Details

The admin settings panel is built with vanilla JavaScript and PHP templates (no Vue.js or webpack build step). This keeps the admin UI lightweight and avoids frontend build dependencies.

Settings are stored in Nextcloud's `IConfig` system and take effect immediately without requiring a page reload for the CSS changes.
