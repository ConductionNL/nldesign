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
