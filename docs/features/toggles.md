---
sidebar_position: 5
---

# Optional Toggles

NL Design provides two optional CSS-based toggles that adjust Nextcloud's interface beyond color theming.

## Hide Login Slogan

**Setting:** `nldesign:hide_slogan`

When enabled, hides the tagline text ("a safe home for all your data") shown below the Nextcloud logo on the login page.

**Why use it:**
- Government organizations often have their own branding guidelines that don't include Nextcloud's default slogan
- Creates a cleaner, more professional login page
- Reduces visual clutter for public-facing instances

**How it works:** When enabled, NL Design loads an additional CSS file (`css/hide-slogan.css`) that sets `display: none` on the slogan element.

## Show Menu Labels

**Setting:** `nldesign:show_menu_labels`

When enabled, displays text labels next to the icons in Nextcloud's left sidebar navigation.

**Why use it:**
- Improves accessibility — icon-only navigation can be confusing for users unfamiliar with Nextcloud
- Meets Dutch government accessibility guidelines (WCAG AA) which recommend text alternatives for icons
- Especially helpful for organizations onboarding many new users

**How it works:** When enabled, NL Design loads `css/show-menu-labels.css` which overrides Nextcloud's default icon-only sidebar layout to include text labels.

## Enabling via Command Line

Both toggles can be set via the Nextcloud `occ` command:

```bash
# Hide the login slogan
php occ config:app:set nldesign hide_slogan --value=1

# Show menu labels in the sidebar
php occ config:app:set nldesign show_menu_labels --value=1

# Disable (set back to 0)
php occ config:app:set nldesign hide_slogan --value=0
```

## Theming per App

**Setting:** `nldesign:disabled_apps` (JSON array of app ids, default `[]`)

By default the NL Design theme applies to every Nextcloud app. The **Theming per app** section in the admin panel lists each enabled app with a checkbox (checked = themed). Unchecking an app and saving adds it to an exclusion list: that app's pages then render with **stock Nextcloud styling**.

**Why use it:**
- Roll a municipal huisstijl out incrementally instead of all-or-nothing
- Quarantine a complex third-party app (or one mid-migration) that breaks or looks wrong under the theme, without losing theming everywhere else

**How it works:** On every page render, NL Design resolves the app id from the request path (`/apps/{appid}`) and, if that app is excluded, skips **all** style injection for the request — design-system stylesheets, the token set, `custom-overrides.css`, and both toggles above.

**Trade-off (by design):** suppression is request-scoped, so on an excluded app's pages the global header and navigation also render unthemed — that app's pages are fully stock Nextcloud. The login page, settings pages, and share links are never affected by the exclusion list and always stay themed. The ids `nldesign`, `settings`, and `theming` can never be excluded.

```bash
# Exclude the Calendar app from theming
php occ config:app:set nldesign disabled_apps --value='["calendar"]'

# Back to theming every app
php occ config:app:set nldesign disabled_apps --value='[]'
```
