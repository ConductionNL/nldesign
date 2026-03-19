---
status: reviewed
reviewed_date: 2026-02-28
---

# Admin Settings Specification

## Purpose
Defines the admin settings panel for the NL Design app. The settings panel is located in Nextcloud's administration area under the Theming section. It provides controls for selecting the active token set, toggling the hide slogan feature, toggling show menu labels, and previewing the selected theme. The UI is built with vanilla PHP templates and vanilla JavaScript (no Vue or webpack).

## Requirements

### REQ-ASET-001: Settings Panel Registration
The admin settings panel MUST be registered in the Nextcloud Theming section.

#### Scenario: Settings panel appears in admin area
- GIVEN the nldesign app is enabled
- WHEN the admin navigates to Settings -> Administration -> Theming
- THEN an "NL Design System Theme" section MUST appear
- AND it MUST have priority 50 (via `Admin::getPriority()`)
- AND it MUST be in the `theming` section (via `Admin::getSection()`)

#### Scenario: Settings panel loads template
- GIVEN the admin opens the NL Design settings panel
- WHEN `Admin::getForm()` is called
- THEN it MUST return a `TemplateResponse` for `settings/admin`
- AND the template parameters MUST include `tokenSets` (array of all available token sets)
- AND the template parameters MUST include `currentTokenSet` (string, current active token set id)
- AND the template parameters MUST include `hideSlogan` (boolean)
- AND the template parameters MUST include `showMenuLabels` (boolean)

### REQ-ASET-002: Token Set Selector
The settings panel MUST provide a dropdown for selecting the active design token set.

#### Scenario: Dropdown populated with token sets
- GIVEN the settings panel is loaded
- AND there are multiple token sets available (discovered from `css/tokens/` directory)
- WHEN the dropdown renders
- THEN it MUST contain an `<option>` for each token set
- AND each option MUST show the token set `name` as display text
- AND each option MUST use the token set `id` as the `value` attribute
- AND the currently active token set MUST have the `selected` attribute

#### Scenario: Admin selects a different token set
- GIVEN the admin changes the dropdown to "Gemeente Amsterdam"
- WHEN the selection is saved (via JavaScript calling `POST /apps/nldesign/settings/tokenset`)
- THEN the active token set MUST be updated
- AND the preview box MUST update to reflect the new token set's colors

### REQ-ASET-003: Live Preview Box
The settings panel MUST include a preview box that shows the visual effect of the selected token set.

#### Scenario: Preview box renders
- GIVEN the settings panel is loaded
- WHEN the preview section renders
- THEN it MUST show a preview header bar (colored based on the token set)
- AND it MUST show a "Primary Button" styled with the token set's primary colors
- AND it MUST show a "Secondary Button" styled with the token set's secondary colors
- AND the preview MUST be contained in a `.nldesign-preview-box` element

### REQ-ASET-004: Hide Slogan Checkbox
The settings panel MUST include a checkbox to toggle the hide slogan feature.

#### Scenario: Checkbox reflects current state
- GIVEN the hide slogan setting is enabled (value `'1'` in IConfig)
- WHEN the settings panel loads
- THEN the `#nldesign-hide-slogan` checkbox MUST be checked

#### Scenario: Checkbox reflects disabled state
- GIVEN the hide slogan setting is disabled (value `'0'` in IConfig)
- WHEN the settings panel loads
- THEN the `#nldesign-hide-slogan` checkbox MUST NOT be checked

#### Scenario: Checkbox label text
- GIVEN the settings panel renders
- THEN the checkbox label MUST read "Hide Nextcloud slogan/payoff on login page" (localized via `$l->t()`)

### REQ-ASET-005: Show Menu Labels Checkbox
The settings panel MUST include a checkbox to toggle the show menu labels feature.

#### Scenario: Checkbox reflects current state
- GIVEN the show menu labels setting is enabled (value `'1'` in IConfig)
- WHEN the settings panel loads
- THEN the `#nldesign-show-menu-labels` checkbox MUST be checked

#### Scenario: Checkbox reflects disabled state
- GIVEN the show menu labels setting is disabled (value `'0'` in IConfig)
- WHEN the settings panel loads
- THEN the `#nldesign-show-menu-labels` checkbox MUST NOT be checked

#### Scenario: Checkbox label text
- GIVEN the settings panel renders
- THEN the checkbox label MUST read "Show text labels in app menu (hide icons)" (localized via `$l->t()`)

### REQ-ASET-006: External Links
The settings panel MUST include external links to relevant documentation.

#### Scenario: Documentation link rendered
- GIVEN the settings panel is loaded
- WHEN the header section renders
- THEN it MUST contain an anchor tag linking to `https://nldesign.app`
- AND the link MUST have `target="_blank"` and `rel="noopener noreferrer"` for security
- AND the link text MUST read "Documentation" (localized)

#### Scenario: Info link rendered
- GIVEN the settings panel is loaded
- WHEN the info section renders
- THEN it MUST contain an anchor tag linking to `https://nldesignsystem.nl/`
- AND the link MUST have `target="_blank"` and `rel="noopener noreferrer"` for security
- AND the link text MUST read "Learn more about NL Design System" (localized) with an arrow indicator (`↗`)

### REQ-ASET-007: Vanilla Implementation (No Vue)
The admin settings MUST be implemented using vanilla PHP templates and vanilla JavaScript without Vue, webpack, or any frontend build step.

#### Scenario: Template is plain PHP
- GIVEN the settings template at `templates/settings/admin.php`
- WHEN the template is loaded
- THEN it MUST use `script('nldesign', 'admin')` to load vanilla JS
- AND it MUST use `style('nldesign', 'admin')` to load admin-specific CSS
- AND it MUST NOT reference any webpack bundles or Vue components
- AND it MUST NOT use `<div id="app">` or Vue mounting points

#### Scenario: XSS prevention
- GIVEN dynamic values are rendered in the template
- WHEN token set data is output in HTML attributes
- THEN `p(json_encode(...))` MUST be used for the `data-token-sets` attribute (the `p()` helper HTML-escapes the JSON output)
- AND `p()` helper MUST be used for individual value output (escapes HTML)
- AND localized strings MUST use `p($l->t(...))` for safe output

### REQ-ASET-008: Admin-Only Access Control
All settings endpoints and the settings panel MUST be restricted to administrators.

#### Scenario: Settings panel restricted to admin
- GIVEN a non-admin user navigates to the admin settings area
- WHEN Nextcloud checks the `ISettings` implementation
- THEN the NL Design settings panel MUST NOT be visible to non-admin users

#### Scenario: API endpoints restricted to admin
- GIVEN the `@AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)` annotation on all controller methods
- WHEN a non-admin user calls any `/settings/*` endpoint
- THEN the request MUST be rejected with an appropriate error response

### Current Implementation Status

**Fully implemented:**
- Settings panel registration in the `theming` section with priority 50 (`lib/Settings/Admin.php`: `getSection()` returns `'theming'`, `getPriority()` returns `50`)
- `Admin::getForm()` returns a `TemplateResponse` for `settings/admin` with all four required parameters: `tokenSets`, `currentTokenSet`, `hideSlogan`, `showMenuLabels` (`lib/Settings/Admin.php` lines 75-107)
- Token set dropdown populated from `TokenSetService::getAvailableTokenSets()` with `<option>` elements using `id` as value and `name` as display text (`templates/settings/admin.php` lines 27-35)
- Token set selection saves via JS `POST /apps/nldesign/settings/tokenset` (`js/admin.js` `saveTokenSet()` function)
- Live preview box with `.nldesign-preview-box`, preview header bar, primary and secondary buttons (`templates/settings/admin.php` lines 61-70)
- Hide slogan checkbox with id `nldesign-hide-slogan`, checked state from `$_['hideSlogan']`, label text "Hide Nextcloud slogan/payoff on login page" (`templates/settings/admin.php` lines 38-47)
- Show menu labels checkbox with id `nldesign-show-menu-labels`, checked state from `$_['showMenuLabels']`, label text "Show text labels in app menu (hide icons)" (`templates/settings/admin.php` lines 49-59)
- External link to `https://nldesign.app` with `target="_blank"` and `rel="noopener noreferrer"` (`templates/settings/admin.php` line 16-19)
- External link to `https://nldesignsystem.nl/` with arrow indicator (`templates/settings/admin.php` lines 78-80)
- Vanilla PHP template loads `script('nldesign', 'admin')` and `style('nldesign', 'admin')` with no Vue/webpack (`templates/settings/admin.php` lines 7-8)
- XSS prevention via `p(json_encode(...))` for `data-token-sets` and `p()` for other values (`templates/settings/admin.php` lines 12-13)
- `@AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)` annotation on all controller methods (`lib/Controller/SettingsController.php`)
- All routes defined in `appinfo/routes.php`

**Additional features beyond spec (implemented):**
- Token editor panel with custom override CRUD, import/export, tabs, live preview (`js/admin.js` lines 375-936, `templates/settings/admin.php` lines 72-75)
- Token set apply dialog comparing current vs new values (`js/admin.js` lines 726-903)
- Theming sync dialog with color swatch comparison (`js/admin.js` lines 113-297)

**Not yet implemented:**
- All requirements in this spec are fully implemented.

### Standards & References
- NL Design System community: https://nldesignsystem.nl/
- Nextcloud `ISettings` interface for admin settings registration
- Nextcloud `TemplateResponse` for server-side rendered PHP templates
- WCAG AA: form labels are associated with inputs via `for`/`id` attributes
- OWASP XSS prevention via PHP `p()` helper for HTML escaping

### Specificity Assessment
- This spec is highly specific and directly implementable as-is. Every requirement has concrete scenarios with exact selectors, API endpoints, and expected values.
- The spec does not mention the token editor panel, custom overrides CRUD, import/export functionality, or the token set apply dialog -- these are additional features that exist in the implementation but are not covered by this spec.
- Minor gap: REQ-ASET-003 defines preview color updates but the implementation only has hardcoded colors for 8 token sets in `tokenSetColors` -- the spec does not specify how preview colors are sourced for all 39 token sets.
- Open question: Should the token editor panel and custom overrides functionality be covered by a separate spec or added to this one?
