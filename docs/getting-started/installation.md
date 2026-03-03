---
sidebar_position: 1
---

# Installation

## From the Nextcloud App Store

1. Log in to your Nextcloud instance as an administrator
2. Go to **Apps** (top-right menu)
3. Search for **NL Design** in the app store
4. Click **Download and enable**

The app is active immediately — it applies the default **Rijkshuisstijl** theme on installation.

## Manual Installation

If you prefer to install manually or need a specific version:

1. Download the latest release from [GitHub Releases](https://github.com/ConductionNL/nldesign/releases)
2. Extract the archive to your Nextcloud `custom_apps` directory:
   ```bash
   tar -xzf nldesign-*.tar.gz -C /var/www/html/custom_apps/
   ```
3. Set correct ownership:
   ```bash
   chown -R www-data:www-data /var/www/html/custom_apps/nldesign
   ```
4. Enable the app via the command line:
   ```bash
   php occ app:enable nldesign
   ```

## Requirements

- Nextcloud 28 or later
- PHP 8.1 or later
- No database required — NL Design uses Nextcloud's built-in configuration storage

## What happens on install

When NL Design is enabled, it injects CSS files into every page load using Nextcloud's `\OCP\Util::addStyle()` mechanism. The CSS files override Nextcloud's default styling with NL Design System tokens.

By default, the **Rijkshuisstijl** (Dutch national government) theme is applied. You can change this in the [admin settings](configuration).

## Uninstalling

Disabling or removing the app immediately restores Nextcloud's default styling. No configuration data is lost — if you re-enable the app, your previous token set selection is remembered.
