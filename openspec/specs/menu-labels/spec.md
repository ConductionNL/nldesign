---
status: reviewed
reviewed_date: 2026-02-28
---

# Show Menu Labels Specification

## Purpose
Defines the "Show Menu Labels" feature that replaces app menu icons in the Nextcloud header with text labels. When enabled, the header navigation displays application names (e.g. "Files", "Mail", "Calendar") instead of icons, improving discoverability and accessibility for users unfamiliar with Nextcloud's icon-based navigation.

## Requirements

### REQ-MLBL-001: Configuration Storage
The show menu labels setting MUST be stored in Nextcloud's `IConfig` as a string value.

#### Scenario: Setting stored as enabled
- GIVEN the admin enables the show menu labels feature
- WHEN `POST /apps/nldesign/settings/menulabels` is called with `showMenuLabels=true`
- THEN `IConfig::setAppValue('nldesign', 'show_menu_labels', '1')` MUST be called
- AND the response MUST be JSON with `{"status": "ok", "showMenuLabels": true}`

#### Scenario: Setting stored as disabled
- GIVEN the admin disables the show menu labels feature
- WHEN `POST /apps/nldesign/settings/menulabels` is called with `showMenuLabels=false`
- THEN `IConfig::setAppValue('nldesign', 'show_menu_labels', '0')` MUST be called
- AND the response MUST be JSON with `{"status": "ok", "showMenuLabels": false}`

#### Scenario: Default value when not configured
- GIVEN no value has been set for `nldesign:show_menu_labels`
- WHEN the setting is read during boot
- THEN the default value MUST be `'0'` (disabled)
- AND menu icons MUST be displayed normally

### REQ-MLBL-002: Conditional CSS Loading
The show-menu-labels CSS file MUST only be loaded when the feature is enabled.

#### Scenario: Feature enabled loads CSS
- GIVEN `IConfig` returns `'1'` for `show_menu_labels`
- WHEN `Application::injectThemeCSS()` runs during boot
- THEN `\OCP\Util::addStyle('nldesign', 'show-menu-labels')` MUST be called
- AND the CSS file MUST be loaded after the 7 core CSS layers

#### Scenario: Feature disabled skips CSS
- GIVEN `IConfig` returns `'0'` for `show_menu_labels`
- WHEN `Application::injectThemeCSS()` runs during boot
- THEN `show-menu-labels` CSS MUST NOT be loaded

### REQ-MLBL-003: Icon Hiding
When the feature is enabled, app menu icons MUST be hidden.

#### Scenario: App menu icons hidden
- GIVEN the show-menu-labels CSS is loaded
- WHEN the header navigation renders
- THEN `#header nav.app-menu .app-menu-icon` MUST have `display: none !important`
- AND `#header nav.app-menu .app-menu-entry__icon` MUST have `display: none !important`
- AND both selectors MUST also have `visibility: hidden !important`

### REQ-MLBL-004: Label Display
When the feature is enabled, app menu labels MUST be visible and properly styled.

#### Scenario: Labels made visible
- GIVEN the show-menu-labels CSS is loaded
- WHEN the header navigation renders
- THEN `#header nav.app-menu .app-menu-entry__label` MUST have `display: inline-block !important`
- AND `visibility: visible !important`
- AND `opacity: 1 !important`

#### Scenario: Label typography
- GIVEN labels are visible
- WHEN the styling is applied
- THEN font-size MUST be `14px`
- AND font-weight MUST be `400` for normal items
- AND font-weight MUST be `600` for the active item (`.app-menu-entry--active .app-menu-entry__label`)
- AND white-space MUST be `nowrap` to prevent text wrapping

#### Scenario: Label positioning
- GIVEN labels are visible
- WHEN the styling is applied
- THEN `position` MUST be `static` (overriding any absolute positioning)
- AND `transform` MUST be `none` (removing any transforms)
- AND `max-width` MUST be `none` (preventing truncation)
- AND text-align MUST be `center`

### REQ-MLBL-005: Menu Entry Layout
When labels are shown, menu entries MUST be properly sized and laid out.

#### Scenario: Menu entry dimensions
- GIVEN the show-menu-labels CSS is loaded
- WHEN menu entries render
- THEN `.app-menu-entry` MUST have `height: var(--header-height)` for full header height
- AND `min-width: 80px` for minimum label space
- AND `width: auto` to accommodate label text
- AND `flex-shrink: 0` to prevent compression

#### Scenario: Menu entry link layout
- GIVEN labels are visible
- WHEN `.app-menu-entry__link` renders
- THEN it MUST have `display: flex`, `flex-direction: column`, `align-items: center`, `justify-content: center`
- AND `height: 100%` for full entry height
- AND `padding: 0` to reset default padding

### REQ-MLBL-006: Active Item Indicator
When labels are shown, the default Nextcloud active item indicator (black dot) MUST be disabled.

#### Scenario: Default active indicator removed
- GIVEN the show-menu-labels CSS is loaded
- AND an app menu entry has the `app-menu-entry--active` class
- WHEN the `::before` pseudo-element renders
- THEN `background-color` MUST be `transparent !important`
- AND `opacity` MUST be `0 !important`

### REQ-MLBL-007: API Endpoint
The app MUST expose an admin-only API endpoint for toggling the show menu labels setting.

#### Scenario: Toggle menu labels on
- GIVEN the admin is authenticated
- WHEN `POST /apps/nldesign/settings/menulabels` is called with `showMenuLabels=true`
- THEN the setting MUST be stored as `'1'` in IConfig
- AND the response MUST confirm success

#### Scenario: Toggle menu labels off
- GIVEN the admin is authenticated
- WHEN `POST /apps/nldesign/settings/menulabels` is called with `showMenuLabels=false`
- THEN the setting MUST be stored as `'0'` in IConfig
- AND the response MUST confirm success

#### Scenario: Non-admin access denied
- GIVEN a non-admin user is authenticated
- WHEN `POST /apps/nldesign/settings/menulabels` is called
- THEN the request MUST be rejected by the `@AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)` annotation

### Current Implementation Status

**Fully implemented:**
- Configuration storage: `Application.php` reads `show_menu_labels` from `IConfig` with default `'0'`, compares with `=== '1'` (line 81)
- API endpoint: `POST /apps/nldesign/settings/menulabels` mapped in `appinfo/routes.php` (line 11) to `SettingsController::setMenuLabelsSetting()` (`lib/Controller/SettingsController.php` lines 186-200)
- Boolean conversion: `setMenuLabelsSetting(bool $showMenuLabels)` uses strict `=== true` to convert to `'1'`/`'0'` (lines 188-191)
- Conditional CSS loading: `Application::injectThemeCSS()` loads `show-menu-labels` CSS only when `$showMenuLabels === true` (lines 117-119)
- CSS file: `css/show-menu-labels.css` (66 lines) implements all requirements:
  - Icons hidden: `.app-menu-icon` and `.app-menu-entry__icon` with `display: none !important` and `visibility: hidden !important` (lines 10-14)
  - Labels visible: `.app-menu-entry__label` with `display: inline-block !important`, `visibility: visible !important`, `opacity: 1 !important` (lines 17-35)
  - Label typography: `font-size: 14px`, `font-weight: 400` normal / `600` active, `white-space: nowrap` (lines 22-24, 38-40)
  - Label positioning: `position: static`, `transform: none`, `max-width: none`, `text-align: center` (lines 29-35)
  - Menu entry dimensions: `height: var(--header-height)`, `min-width: 80px`, `width: auto`, `flex-shrink: 0` (lines 59-64)
  - Menu entry link layout: `display: flex`, `flex-direction: column`, `align-items: center`, `justify-content: center`, `height: 100%`, `padding: 0` (lines 49-56)
  - Active indicator removed: `.app-menu-entry--active::before` with `background-color: transparent !important` and `opacity: 0 !important` (lines 43-46)
- Admin-only access: `@AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)` annotation on `setMenuLabelsSetting()`
- Settings panel checkbox: `templates/settings/admin.php` renders `#nldesign-show-menu-labels` checkbox with correct checked state and label text
- JavaScript handler: `js/admin.js` calls `saveMenuLabelsSetting()` on checkbox change via `POST /apps/nldesign/settings/menulabels`

**Not yet implemented:**
- All requirements in this spec are fully implemented.

### Standards & References
- WCAG 2.1 AA: Text labels improve discoverability and accessibility for users who cannot identify icons (SC 1.3.1 Info and Relationships, SC 3.3.2 Labels or Instructions)
- NL Design System: Government users may be unfamiliar with Nextcloud icon conventions; text labels align with government UX guidelines for clarity
- Nextcloud header navigation: `.app-menu-entry`, `.app-menu-entry__icon`, `.app-menu-entry__label` are standard Nextcloud component classes from `AppMenuEntry.vue`

### Specificity Assessment
- This spec is highly specific and directly implementable. Every CSS selector, property, and value is precisely defined.
- All scenarios map 1:1 to the implementation in `css/show-menu-labels.css`.
- No ambiguities or open questions remain -- this spec is complete as-is.
- Minor note: The spec does not address responsive behavior (what happens when many labels overflow the header width). The implementation uses `flex-shrink: 0` which could cause overflow on narrow screens.
