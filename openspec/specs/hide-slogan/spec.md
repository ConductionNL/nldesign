---
status: reviewed
reviewed_date: 2026-02-28
---

# Hide Slogan Specification

## Purpose
Defines the "Hide Slogan" feature that removes the Nextcloud slogan/payoff text from the login page. Dutch government organizations typically need to present a clean, branded login page without Nextcloud's default slogan ("a safe home for all your data"). When enabled, the footer element on the login page that contains this slogan is completely hidden.

## Requirements

### REQ-SLGN-001: Configuration Storage
The hide slogan setting MUST be stored in Nextcloud's `IConfig` as a string value.

#### Scenario: Setting stored as enabled
- GIVEN the admin enables the hide slogan feature
- WHEN `POST /apps/nldesign/settings/slogan` is called with `hideSlogan=true`
- THEN `IConfig::setAppValue('nldesign', 'hide_slogan', '1')` MUST be called
- AND the response MUST be JSON with `{"status": "ok", "hideSlogan": true}`

#### Scenario: Setting stored as disabled
- GIVEN the admin disables the hide slogan feature
- WHEN `POST /apps/nldesign/settings/slogan` is called with `hideSlogan=false`
- THEN `IConfig::setAppValue('nldesign', 'hide_slogan', '0')` MUST be called
- AND the response MUST be JSON with `{"status": "ok", "hideSlogan": false}`

#### Scenario: Default value when not configured
- GIVEN no value has been set for `nldesign:hide_slogan`
- WHEN the setting is read during boot
- THEN the default value MUST be `'0'` (disabled)
- AND the slogan MUST be visible on the login page

### REQ-SLGN-002: Conditional CSS Loading
The hide-slogan CSS file MUST only be loaded when the feature is enabled.

#### Scenario: Feature enabled loads CSS
- GIVEN `IConfig` returns `'1'` for `hide_slogan`
- WHEN `Application::injectThemeCSS()` runs during boot
- THEN `\OCP\Util::addStyle('nldesign', 'hide-slogan')` MUST be called
- AND the CSS file MUST be loaded after the 7 core CSS layers

#### Scenario: Feature disabled skips CSS
- GIVEN `IConfig` returns `'0'` for `hide_slogan`
- WHEN `Application::injectThemeCSS()` runs during boot
- THEN `hide-slogan` CSS MUST NOT be loaded

### REQ-SLGN-003: Slogan Element Hiding
When the feature is enabled, the login page footer containing the slogan MUST be completely hidden.

#### Scenario: Footer element hidden with display none
- GIVEN the hide-slogan CSS is loaded
- WHEN the login page renders
- THEN `footer.guest-box` MUST have `display: none !important`
- AND `visibility: hidden !important`

#### Scenario: Multiple selector coverage
- GIVEN the hide-slogan CSS is loaded
- WHEN the login page renders
- THEN the CSS MUST target these selectors for maximum coverage:
  - `footer.guest-box`
  - `#body-login footer.guest-box`
  - `body.body-login-container footer.guest-box`

#### Scenario: Slogan visible when feature disabled
- GIVEN the hide-slogan CSS is NOT loaded
- WHEN the login page renders
- THEN the `footer.guest-box` element MUST display normally
- AND the Nextcloud slogan/payoff text MUST be visible

### REQ-SLGN-004: Login Page Only
The hide slogan CSS MUST only affect the login page footer and MUST NOT affect other footer elements.

#### Scenario: Non-login page footers unaffected
- GIVEN the hide-slogan CSS is loaded
- AND the user is on a non-login page (e.g. Files app)
- WHEN the page renders
- THEN no footer elements MUST be hidden
- AND the selectors MUST be specific to `.guest-box` footer elements (only present on login/guest pages)

### REQ-SLGN-005: Boolean Conversion
The controller MUST correctly convert the boolean API parameter to a string for IConfig storage.

#### Scenario: True boolean converted to string '1'
- GIVEN the API receives `hideSlogan` as boolean `true`
- WHEN `setSloganSetting(true)` is called
- THEN the value stored in IConfig MUST be the string `'1'`
- AND the comparison MUST use strict equality (`=== true`)

#### Scenario: False boolean converted to string '0'
- GIVEN the API receives `hideSlogan` as boolean `false`
- WHEN `setSloganSetting(false)` is called
- THEN the value stored in IConfig MUST be the string `'0'`

#### Scenario: Boot phase reads and compares correctly
- GIVEN `IConfig` stores `'1'` for `hide_slogan`
- WHEN `Application::injectThemeCSS()` reads the value
- THEN it MUST compare with `=== '1'` to get boolean `true`
- AND it MUST NOT use loose comparison that could match other truthy values

### REQ-SLGN-006: API Endpoint
The app MUST expose an admin-only API endpoint for toggling the hide slogan setting.

#### Scenario: Toggle slogan hiding on
- GIVEN the admin is authenticated
- WHEN `POST /apps/nldesign/settings/slogan` is called with `hideSlogan=true`
- THEN the setting MUST be stored as `'1'` in IConfig
- AND the response MUST confirm success with `{"status": "ok", "hideSlogan": true}`

#### Scenario: Toggle slogan hiding off
- GIVEN the admin is authenticated
- WHEN `POST /apps/nldesign/settings/slogan` is called with `hideSlogan=false`
- THEN the setting MUST be stored as `'0'` in IConfig
- AND the response MUST confirm success with `{"status": "ok", "hideSlogan": false}`

#### Scenario: Non-admin access denied
- GIVEN a non-admin user is authenticated
- WHEN `POST /apps/nldesign/settings/slogan` is called
- THEN the request MUST be rejected by the `@AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)` annotation

### REQ-SLGN-007: Dual Hiding Strategy
The hide slogan CSS MUST use both `display: none` and `visibility: hidden` to ensure complete removal.

#### Scenario: Both hiding mechanisms applied
- GIVEN the hide-slogan CSS is loaded
- WHEN the selectors are processed
- THEN `display: none !important` MUST be set (removes from layout flow)
- AND `visibility: hidden !important` MUST be set (ensures no visual trace)
- AND both properties MUST use `!important` to override any Nextcloud styles

### Current Implementation Status

**Fully implemented:**
- Configuration storage: `Application.php` reads `hide_slogan` from `IConfig` with default `'0'`, compares with `=== '1'` (line 80)
- API endpoint: `POST /apps/nldesign/settings/slogan` mapped in `appinfo/routes.php` (line 10) to `SettingsController::setSloganSetting()` (`lib/Controller/SettingsController.php` lines 161-175)
- Boolean conversion: `setSloganSetting(bool $hideSlogan)` uses strict `=== true` to convert to `'1'`/`'0'` string (lines 163-166)
- Conditional CSS loading: `Application::injectThemeCSS()` loads `hide-slogan` CSS only when `$hideSlogan === true` (lines 112-114)
- CSS file: `css/hide-slogan.css` (17 lines) targets `footer.guest-box`, `#body-login footer.guest-box`, and `body.body-login-container footer.guest-box` with both `display: none !important` and `visibility: hidden !important`
- Admin-only access: `@AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)` annotation on `setSloganSetting()`
- Settings panel checkbox: `templates/settings/admin.php` renders `#nldesign-hide-slogan` checkbox with correct checked state and label text
- JavaScript handler: `js/admin.js` calls `saveSloganSetting()` on checkbox change via `POST /apps/nldesign/settings/slogan`

**Not yet implemented:**
- All requirements in this spec are fully implemented.

### Standards & References
- Rijkshuisstijl guidelines: Dutch government login pages should present clean, branded appearance without third-party slogans
- WCAG AA: hiding decorative text does not affect accessibility; the `display: none` approach correctly removes elements from the accessibility tree
- Nextcloud login page structure: `footer.guest-box` is the standard container for the slogan on guest/login pages

### Specificity Assessment
- This spec is highly specific and directly implementable. Every scenario maps 1:1 to the implementation.
- All CSS selectors, IConfig keys, API endpoints, boolean conversion logic, and conditional loading behavior are precisely defined.
- No ambiguities or open questions remain -- this spec is complete as-is.
