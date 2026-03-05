---
sidebar_position: 3
---

# Theming Sync

When you select a token set in the NL Design admin settings, the app automatically syncs key values with Nextcloud's built-in theming system.

## What Gets Synced

| Setting | Source | Purpose |
|---------|--------|---------|
| Primary color | `theming.primary_color` from `token-sets.json` | Nextcloud's accent color for emails, mobile apps, and generated assets |
| Background color | `theming.background_color` from `token-sets.json` | Login page and default background |
| Logo | `theming.logo` from `token-sets.json` | Organization logo in header and login page |

## Why Sync Matters

NL Design controls visual styling through CSS injection, but Nextcloud's built-in theming system controls elements that CSS can't reach:

- **Email templates** use the primary color from theming settings
- **Mobile apps** display the primary color and logo from theming settings
- **Generated favicons and manifest files** use theming colors
- **Login page background** is controlled by theming settings

Without syncing, you'd see the correct colors in the web interface but the wrong colors in emails and mobile apps.

## How It Works

The `ThemingService` class writes to Nextcloud's theming configuration when the admin saves a token set:

1. Reads the selected token set's metadata from `token-sets.json`
2. If `theming.primary_color` exists, updates Nextcloud's `theming:color` config
3. If `theming.background_color` exists, updates Nextcloud's `theming:background` config
4. If `theming.logo` exists, copies the SVG file to Nextcloud's theming storage

## Token Sets Without Theming Data

Not all token sets have complete theming metadata. If a field is missing, Nextcloud's existing theming value for that field is left unchanged. The CSS-based theming still works regardless — theming sync is supplementary.
