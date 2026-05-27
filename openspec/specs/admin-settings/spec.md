---
status: implemented
reviewed_date: 2026-02-28
enriched_date: 2026-03-20
---

# Admin Settings Specification

## Purpose
Defines the admin settings panel for the NL Design app. The settings panel is located in Nextcloud's administration area under the Theming section. It provides controls for selecting the active token set, toggling the hide slogan feature, toggling show menu labels, and previewing the selected theme. The UI is built with vanilla PHP templates and vanilla JavaScript (no Vue or webpack). Additionally, the panel hosts the token editor for customizing individual Nextcloud CSS tokens, and triggers the theming sync dialog when a token set with theming metadata is selected.

## Requirements

### REQ-ASET-001: Settings Panel Registration
The admin settings panel MUST be registered in the Nextcloud Theming section with a defined priority.

#### Scenario: Settings panel appears in admin area
- GIVEN the nldesign app is enabled
- WHEN the admin navigates to Settings -> Administration -> Theming
- THEN an "NL Design System Theme" section MUST appear
- AND it MUST have priority 50 (via `Admin::getPriority()`)
- AND it MUST be in the `theming` section (via `Admin::getSection()`)

#### Scenario: Settings panel position relative to Nextcloud theming
- GIVEN Nextcloud's built-in theming settings have default priority
- WHEN the admin views the Theming settings page
- THEN the NL Design section MUST appear below the default Nextcloud theming section
- AND both sections MUST be independently scrollable

#### Scenario: Settings panel is absent when app is disabled
@e2e exclude Requires disabling the app mid-session — not safe to test in shared environment.
- GIVEN the nldesign app is not enabled
- WHEN the admin navigates to Settings -> Administration -> Theming
- THEN the "NL Design System Theme" section MUST NOT appear
- AND no nldesign CSS MUST be injected into the page

### REQ-ASET-002: Template Response and Parameters
@e2e exclude PHP controller internals (TemplateResponse parameters, default config values) — not testable via browser UI; covered by unit tests.
The settings form MUST return a `TemplateResponse` with all required parameters for the admin template.

#### Scenario: Settings panel loads template with all parameters
- GIVEN the admin opens the NL Design settings panel
- WHEN `Admin::getForm()` is called
- THEN it MUST return a `TemplateResponse` for `settings/admin`
- AND the template parameters MUST include `tokenSets` (array of all available token sets from `TokenSetService::getAvailableTokenSets()`)
- AND the template parameters MUST include `currentTokenSet` (string, current active token set id, default `'nextcloud'`)
- AND the template parameters MUST include `hideSlogan` (boolean, from IConfig `hide_slogan` compared with `=== '1'`)
- AND the template parameters MUST include `showMenuLabels` (boolean, from IConfig `show_menu_labels` compared with `=== '1'`)

#### Scenario: Token sets include design system metadata
- GIVEN `Admin::getForm()` retrieves token sets
- WHEN the `tokenSets` parameter is populated
- THEN each token set object MUST have `id`, `name`, `description`, and `design_system` fields
- AND token sets with theming metadata MUST include the `theming` object

#### Scenario: Default values for fresh installation
- GIVEN nldesign is freshly installed with no configuration
- WHEN `Admin::getForm()` is called
- THEN `currentTokenSet` MUST be `'nextcloud'`
- AND `hideSlogan` MUST be `false`
- AND `showMenuLabels` MUST be `false`

### REQ-ASET-003: Token Set Selector Dropdown
The settings panel MUST provide a searchable dropdown for selecting the active design token set from all available sets.

#### Scenario: Dropdown populated with token sets
- GIVEN the settings panel is loaded
- AND there are multiple token sets available (discovered from `css/tokens/` directory)
- WHEN the dropdown renders
- THEN it MUST be a `<select>` element with id `nldesign-token-set-select`
- AND it MUST contain an `<option>` for each token set
- AND each option MUST show the token set `name` as display text
- AND each option MUST use the token set `id` as the `value` attribute
- AND each option MUST include a `data-design-system` attribute with the design system id
- AND the currently active token set MUST have the `selected` attribute

#### Scenario: Admin selects a different token set
@e2e exclude POSTs to IConfig and triggers theming-sync dialog — covered by token-set-apply-dialog spec-coverage tests which use Cancel to avoid mutating shared env.
- GIVEN the admin changes the dropdown to "Gemeente Amsterdam"
- WHEN the selection is saved (via JavaScript calling `POST /apps/nldesign/settings/tokenset`)
- THEN the active token set MUST be updated in IConfig
- AND the preview box MUST update to reflect the new token set's colors
- AND if the selected token set has theming metadata, the theming sync dialog MUST be triggered

#### Scenario: Token set with stock Nextcloud design system
@e2e exclude Requires saving a specific token set and verifying CSS injection — mutates IConfig; CSS injection is a PHP boot-time concern not testable via DOM assertion.
- GIVEN the admin selects the "Nextcloud" token set (design_system: "none")
- WHEN the selection is saved
- THEN no nldesign design system stylesheets MUST be loaded
- AND Nextcloud's default theming MUST be preserved
- AND a badge indicating "Stock Nextcloud" MUST appear next to the dropdown

#### Scenario: Dropdown label is associated with select
- GIVEN the settings panel renders
- THEN a `<label>` with text "Design token set" (localized) MUST have `for="nldesign-token-set-select"`
- AND this MUST satisfy WCAG AA form labeling requirements (SC 1.3.1)

#### Scenario: Design system badge updates on selection
- GIVEN the admin selects a token set with design_system "nldesign"
- WHEN the dropdown change event fires
- THEN the `#nldesign-design-system-badge` element MUST update to show the design system name

### REQ-ASET-004: Live Preview Box
The settings panel MUST include a preview box that shows the visual effect of the selected token set.

#### Scenario: Preview box renders with token set colors
- GIVEN the settings panel is loaded
- WHEN the preview section renders
- THEN it MUST show a preview header bar (colored based on the token set)
- AND it MUST show a "Primary Button" styled with the token set's primary colors
- AND it MUST show a "Secondary Button" styled with the token set's secondary colors
- AND the preview MUST be contained in a `.nldesign-preview-box` element

#### Scenario: Preview updates on token set change
@e2e exclude Requires triggering dropdown change and asserting CSS color on preview element — overlaps with token-set-apply-dialog trigger tests which use Cancel to avoid saving.
- GIVEN the admin selects a different token set from the dropdown
- WHEN the JavaScript handles the change event
- THEN the preview box MUST update its colors to reflect the newly selected token set
- AND the update MUST happen without a full page reload

#### Scenario: Preview reflects Rijkshuisstijl defaults for unknown sets
@e2e exclude Requires a token set not in the hardcoded color map — no such token set guaranteed to exist in the test environment.
- GIVEN the admin selects a token set that is not in the hardcoded `tokenSetColors` map
- WHEN the preview renders
- THEN it MUST fall back to Rijkshuisstijl default colors (#154273 primary, #ffffff text)

### REQ-ASET-005: Hide Slogan Checkbox
The settings panel MUST include a checkbox to toggle the hide slogan feature.

#### Scenario: Checkbox reflects enabled state
- GIVEN the hide slogan setting is enabled (value `'1'` in IConfig)
- WHEN the settings panel loads
- THEN the `#nldesign-hide-slogan` checkbox MUST be checked
- AND the checkbox MUST have `class="checkbox"` for Nextcloud styling

#### Scenario: Checkbox reflects disabled state
@e2e exclude Requires IConfig to be set to disabled state — not guaranteed in shared env; initial-state tested by checkbox-presence test in admin-settings spec-coverage.
- GIVEN the hide slogan setting is disabled (value `'0'` in IConfig)
- WHEN the settings panel loads
- THEN the `#nldesign-hide-slogan` checkbox MUST NOT be checked

#### Scenario: Checkbox label text and accessibility
- GIVEN the settings panel renders
- THEN the checkbox label MUST read "Hide Nextcloud slogan/payoff on login page" (localized via `$l->t()`)
- AND the label MUST have `for="nldesign-hide-slogan"` to associate with the input
- AND this MUST satisfy WCAG AA SC 1.3.1 (Info and Relationships)

#### Scenario: Checkbox change triggers API call
@e2e exclude API call assertion (POST /settings/slogan) — not testable via DOM; would also toggle shared-env IConfig state.
- GIVEN the admin toggles the hide slogan checkbox
- WHEN the change event fires
- THEN JavaScript MUST call `POST /apps/nldesign/settings/slogan` with `hideSlogan` as a boolean
- AND the response MUST be checked for success before confirming the change

### REQ-ASET-006: Show Menu Labels Checkbox
The settings panel MUST include a checkbox to toggle the show menu labels feature.

#### Scenario: Checkbox reflects enabled state
- GIVEN the show menu labels setting is enabled (value `'1'` in IConfig)
- WHEN the settings panel loads
- THEN the `#nldesign-show-menu-labels` checkbox MUST be checked

#### Scenario: Checkbox reflects disabled state
@e2e exclude Requires IConfig to be set to disabled state — not guaranteed in shared env; covered by initial-state assertions in admin-settings spec-coverage.
- GIVEN the show menu labels setting is disabled (value `'0'` in IConfig)
- WHEN the settings panel loads
- THEN the `#nldesign-show-menu-labels` checkbox MUST NOT be checked

#### Scenario: Checkbox label text and accessibility
- GIVEN the settings panel renders
- THEN the checkbox label MUST read "Show text labels in app menu (hide icons)" (localized via `$l->t()`)
- AND the label MUST have `for="nldesign-show-menu-labels"` to associate with the input

#### Scenario: Checkbox change triggers API call
@e2e exclude API call assertion (POST /settings/menulabels) — not testable via DOM; would also toggle shared-env IConfig state.
- GIVEN the admin toggles the show menu labels checkbox
- WHEN the change event fires
- THEN JavaScript MUST call `POST /apps/nldesign/settings/menulabels` with `showMenuLabels` as a boolean

### REQ-ASET-007: External Documentation Links
The settings panel MUST include external links to relevant documentation with proper security attributes.

#### Scenario: Documentation link rendered
- GIVEN the settings panel is loaded
- WHEN the header section renders
- THEN it MUST contain an anchor tag linking to `https://nldesign.app`
- AND the link MUST have `target="_blank"` and `rel="noopener noreferrer"` for security
- AND the link text MUST read "Documentation" (localized via `p($l->t())`)
- AND the link MUST include a `span.icon-link-external` visual indicator

#### Scenario: NL Design System info link rendered
- GIVEN the settings panel is loaded
- WHEN the info section renders
- THEN it MUST contain an anchor tag linking to `https://nldesignsystem.nl/`
- AND the link MUST have `target="_blank"` and `rel="noopener noreferrer"` for security
- AND the link text MUST read "Learn more about NL Design System" (localized) with an arrow indicator

#### Scenario: Links open in new tab without security risk
@e2e exclude rel/target attribute presence is verified by admin-settings spec-coverage link tests; behaviour of window.opener is browser-intrinsic and not testable via DOM assertion.
- GIVEN the admin clicks any external link in the settings panel
- WHEN the link opens
- THEN `rel="noopener noreferrer"` MUST prevent the opened page from accessing `window.opener`
- AND `target="_blank"` MUST open in a new tab/window

### REQ-ASET-008: Vanilla Implementation (No Vue)
@e2e exclude Implementation-architecture requirement (template type, build tooling) — verified by code inspection and the fact that the admin page loads correctly; not testable via browser UI.
The admin settings MUST be implemented using vanilla PHP templates and vanilla JavaScript without Vue, webpack, or any frontend build step.

#### Scenario: Template is plain PHP
- GIVEN the settings template at `templates/settings/admin.php`
- WHEN the template is loaded
- THEN it MUST use `script('nldesign', 'admin')` to load vanilla JS
- AND it MUST use `style('nldesign', 'admin')` to load admin-specific CSS
- AND it MUST NOT reference any webpack bundles or Vue components
- AND it MUST NOT use `<div id="app">` or Vue mounting points

#### Scenario: XSS prevention via output escaping
- GIVEN dynamic values are rendered in the template
- WHEN token set data is output in HTML attributes
- THEN `p(json_encode(...))` MUST be used for the `data-token-sets` attribute (the `p()` helper HTML-escapes the JSON output)
- AND `p()` helper MUST be used for individual value output (escapes HTML)
- AND localized strings MUST use `p($l->t(...))` for safe output

#### Scenario: No build step required
- GIVEN a developer modifies the admin template or JavaScript
- WHEN they want to test the changes
- THEN the changes MUST take effect immediately without running any build command
- AND no `node_modules/`, `package.json`, or webpack config MUST be required for the admin UI

### REQ-ASET-009: Admin-Only Access Control
All settings endpoints and the settings panel MUST be restricted to administrators.

#### Scenario: Settings panel restricted to admin
@e2e exclude Requires a non-admin session — test environment only has the admin user.
- GIVEN a non-admin user navigates to the admin settings area
- WHEN Nextcloud checks the `ISettings` implementation
- THEN the NL Design settings panel MUST NOT be visible to non-admin users

#### Scenario: API endpoints restricted to admin via annotation
@e2e exclude Requires a non-admin session to test the 403 response — test environment only has the admin user.
- GIVEN the `@AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)` annotation on all controller methods
- WHEN a non-admin user calls any `/settings/*` endpoint
- THEN the request MUST be rejected with an appropriate error response
- AND no configuration changes MUST be persisted

#### Scenario: Admin with valid session can access all endpoints
- GIVEN an admin user is authenticated with a valid session
- WHEN any `/settings/*` endpoint is called
- THEN the request MUST be processed normally
- AND the response MUST include the expected data

### REQ-ASET-010: Token Editor Panel Integration
The settings panel MUST include a mount point for the token editor that allows customizing individual Nextcloud CSS tokens.

#### Scenario: Token editor mount point rendered
- GIVEN the settings panel is loaded
- WHEN the template renders
- THEN a `<div id="nldesign-token-editor">` element MUST be present
- AND it MUST have a loading indicator text "Loading token editor..." (localized)
- AND the JavaScript MUST populate this element with the token editor UI on page load

#### Scenario: Token editor loads override data from API
@e2e exclude Network-request assertion (GET /settings/overrides) — not testable via DOM; tab rendering is covered by token-editor-ui spec-coverage tests.
- GIVEN the settings panel loads and JavaScript initializes
- WHEN the token editor mounts
- THEN it MUST call `GET /apps/nldesign/settings/overrides` to fetch the registry, tabs, and current overrides
- AND it MUST render a tabbed interface for browsing tokens by category

#### Scenario: Token editor saves changes via API
@e2e exclude Network-request assertion (POST /settings/overrides) — not testable via DOM; clicking Save would mutate shared-env custom-overrides.css.
- GIVEN the admin modifies a token value in the editor
- WHEN the save action is triggered
- THEN it MUST call `POST /apps/nldesign/settings/overrides` with the updated overrides map
- AND the response MUST confirm success before applying changes

### REQ-ASET-011: Settings Hint Text
The settings panel MUST include instructional text explaining the purpose of the controls.

#### Scenario: Settings hint rendered
- GIVEN the settings panel is loaded
- WHEN the hint section renders
- THEN a `<p class="settings-hint">` element MUST display the text "Select a Dutch government design token set as a base, or customize individual Nextcloud CSS tokens below." (localized)
- AND the hint MUST appear between the header and the token set selector

#### Scenario: Hint text is localized
@e2e exclude Requires Dutch-language Nextcloud session — test environment uses English locale.
- GIVEN the admin has their Nextcloud language set to Dutch
- WHEN the settings panel loads
- THEN the hint text MUST be displayed in Dutch via the `$l->t()` localization function

#### Scenario: Hint text provides sufficient context
@e2e exclude Subjective copy-quality assertion — not verifiable via automated DOM test; hint text presence is covered by admin-settings spec-coverage.
- GIVEN a first-time admin user opens the settings panel
- WHEN they read the hint text
- THEN they MUST understand that they can either select a preset token set OR customize individual tokens
- AND the two-action guidance prevents confusion about the panel's dual purpose

### REQ-ASET-012: Data Attributes for JavaScript Initialization
The settings panel MUST pass configuration data to JavaScript via HTML data attributes.

#### Scenario: Token sets data attribute
- GIVEN the settings panel renders
- WHEN the `#nldesign-settings` div is output
- THEN it MUST have a `data-token-sets` attribute containing JSON-encoded array of all token sets
- AND the JSON MUST be HTML-escaped via `p(json_encode(...))` to prevent XSS

#### Scenario: Current token set data attribute
- GIVEN the active token set is "amsterdam"
- WHEN the `#nldesign-settings` div is output
- THEN it MUST have a `data-current-token-set` attribute with value "amsterdam"
- AND the value MUST be HTML-escaped via `p()`

#### Scenario: JavaScript reads data attributes on initialization
@e2e exclude Internal JS initialization detail — observable effect (correct dropdown state + preview) is covered by admin-settings spec-coverage; data-attribute presence is also verified.
- GIVEN the admin.js script loads
- WHEN it initializes the settings panel
- THEN it MUST read token set data from `data-token-sets` and parse it as JSON
- AND it MUST read the current token set from `data-current-token-set`
- AND these values MUST drive the initial state of the dropdown and preview

### REQ-ASET-013: Localization Support
@e2e exclude l10n system verification — requires Dutch-language session and translation file inspection; not testable via browser UI in English-only test environment.
All user-visible text in the settings panel MUST be localizable via Nextcloud's l10n system.

#### Scenario: All static text uses l10n
- GIVEN the settings template renders
- WHEN user-visible text is output
- THEN every string MUST use `$l->t()` or `p($l->t())` for localization
- AND this includes: section title, button labels, checkbox labels, hint text, link text, and loading text

#### Scenario: Dutch translation available
- GIVEN the admin has Nextcloud set to Dutch language
- WHEN the settings panel loads
- THEN all localizable strings MUST display in Dutch if translations are provided
- AND the app MUST include Dutch (nl) as a supported locale

#### Scenario: English fallback
- GIVEN the admin has a language set for which no translation exists
- WHEN the settings panel loads
- THEN all strings MUST fall back to English (the source strings)

### Current Implementation Status

**Fully implemented:**
- Settings panel registration in the `theming` section with priority 50 (`lib/Settings/Admin.php`: `getSection()` returns `'theming'`, `getPriority()` returns `50`)
- `Admin::getForm()` returns a `TemplateResponse` for `settings/admin` with all four required parameters: `tokenSets`, `currentTokenSet`, `hideSlogan`, `showMenuLabels` (`lib/Settings/Admin.php` lines 73-106)
- Token set dropdown populated from `TokenSetService::getAvailableTokenSets()` with `<option>` elements using `id` as value, `name` as display text, and `data-design-system` attribute (`templates/settings/admin.php` lines 27-36)
- Token set selection saves via JS `POST /apps/nldesign/settings/tokenset` (`js/admin.js`)
- Live preview box with `.nldesign-preview-box`, preview header bar, primary and secondary buttons (`templates/settings/admin.php` lines 63-72)
- Hide slogan checkbox with id `nldesign-hide-slogan`, checked state from `$_['hideSlogan']`, label text "Hide Nextcloud slogan/payoff on login page" (`templates/settings/admin.php` lines 39-49)
- Show menu labels checkbox with id `nldesign-show-menu-labels`, checked state from `$_['showMenuLabels']`, label text "Show text labels in app menu (hide icons)" (`templates/settings/admin.php` lines 51-61)
- External link to `https://nldesign.app` with `target="_blank"` and `rel="noopener noreferrer"` (`templates/settings/admin.php` lines 16-19)
- External link to `https://nldesignsystem.nl/` with arrow indicator (`templates/settings/admin.php` lines 80-82)
- Vanilla PHP template loads `script('nldesign', 'admin')` and `style('nldesign', 'admin')` with no Vue/webpack (`templates/settings/admin.php` lines 7-8)
- XSS prevention via `p(json_encode(...))` for `data-token-sets` and `p()` for other values (`templates/settings/admin.php` lines 12-13)
- `@AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)` annotation on all controller methods (`lib/Controller/SettingsController.php`)
- Token editor mount point at `#nldesign-token-editor` (`templates/settings/admin.php` lines 74-77)
- Data attributes on `#nldesign-settings` div for JS initialization (`templates/settings/admin.php` lines 11-13)
- All user-visible text uses `$l->t()` for localization
- Design system badge element `#nldesign-design-system-badge` (`templates/settings/admin.php` line 36)

**Not yet implemented:**
- All requirements in this spec are fully implemented.

### Standards & References
- NL Design System community: https://nldesignsystem.nl/
- Nextcloud `ISettings` interface for admin settings registration
- Nextcloud `TemplateResponse` for server-side rendered PHP templates
- WCAG 2.1 AA: form labels are associated with inputs via `for`/`id` attributes (SC 1.3.1, SC 3.3.2)
- OWASP XSS prevention via PHP `p()` helper for HTML escaping
- Nextcloud l10n: `IL10N::t()` for translatable strings
