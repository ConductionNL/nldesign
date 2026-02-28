---
status: reviewed
reviewed_date: 2026-02-28
---

# Theming Sync Specification

## Purpose
Defines how the NL Design app synchronizes design token values with Nextcloud's built-in theming system. When a token set includes theming metadata (primary color, background color, logo), the app can update Nextcloud's `ThemingDefaults` and `ImageManager` to ensure consistency between the NL Design CSS layer and Nextcloud's core theming (which controls background images, server branding, and email templates).

## Requirements

### REQ-SYNC-001: Theming Metadata in Token Sets
Token sets MAY include a `theming` object in the manifest that defines values suitable for Nextcloud's built-in theming system.

#### Scenario: Token set with full theming metadata
- GIVEN the `token-sets.json` entry for `rijkshuisstijl` has a `theming` object
- WHEN the metadata is read
- THEN the `theming` object MUST contain `primary_color` (hex string, e.g. `"#154273"`)
- AND it MUST contain `background_color` (hex string, e.g. `"#F5F6F7"`)
- AND it MAY contain `logo` (relative path, e.g. `"img/logos/rijkshuisstijl.svg"`)

#### Scenario: Token set without theming metadata
- GIVEN a token set entry in `token-sets.json` has no `theming` key
- WHEN the token set is retrieved via the API
- THEN the `theming` field MUST be absent from the response
- AND theming sync MUST NOT be offered for this token set

### REQ-SYNC-002: Get Current Theming Values
The app MUST provide an API endpoint to retrieve current Nextcloud theming values for comparison with token set metadata.

#### Scenario: Retrieve theming values
- GIVEN the admin is authenticated
- WHEN `GET /apps/nldesign/settings/theming` is called
- THEN the response MUST be JSON with fields: `primary_color` (string), `background_color` (string), `logo_url` (string), `background_url` (string), `has_custom_logo` (boolean), `has_custom_background` (boolean)

#### Scenario: No custom theming configured
- GIVEN no custom theming has been applied in Nextcloud
- WHEN `GET /apps/nldesign/settings/theming` is called
- THEN `primary_color` MUST be an empty string
- AND `background_color` MUST be an empty string
- AND `has_custom_logo` MUST be `false`
- AND `has_custom_background` MUST be `false`

### REQ-SYNC-003: Color Validation
All color values submitted to the theming sync API MUST be validated as valid hex color strings.

#### Scenario: Valid hex color accepted
- GIVEN a request with `primary_color: "#154273"`
- WHEN `validateColors()` processes the parameter
- THEN validation MUST pass (return `null`)
- AND both 3-digit (`#abc`) and 6-digit (`#aabbcc`) hex formats MUST be accepted

#### Scenario: Invalid color rejected
- GIVEN a request with `primary_color: "not-a-color"`
- WHEN `validateColors()` processes the parameter
- THEN validation MUST fail
- AND the API MUST return HTTP 400 with error message `"Invalid hex color for primary_color: not-a-color"`

#### Scenario: Empty color field skipped
- GIVEN a request with `primary_color: ""`
- WHEN `validateColors()` processes the parameter
- THEN the empty field MUST be skipped (not validated, not applied)

#### Scenario: Color fields validated
- GIVEN any request to `POST /apps/nldesign/settings/theming`
- WHEN colors are validated
- THEN the system MUST check both `primary_color` and `background_color` parameters
- AND the hex regex MUST be `/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/`

### REQ-SYNC-004: Image Path Validation
All image paths submitted to the theming sync API MUST be validated against path traversal attacks and allowed directories.

#### Scenario: Valid logo path accepted
- GIVEN a request with `logo: "img/logos/amsterdam.svg"`
- WHEN `validateImagePaths()` processes the parameter
- THEN validation MUST pass
- AND the file MUST exist at `{appPath}/img/logos/amsterdam.svg`

#### Scenario: Path traversal prevented
- GIVEN a request with `logo: "../../etc/passwd"`
- WHEN `validateImagePaths()` processes the parameter
- THEN validation MUST fail with error `"Invalid image path for logo: path traversal not allowed"`

#### Scenario: Absolute path rejected
- GIVEN a request with `logo: "/etc/passwd"`
- WHEN `validateImagePaths()` processes the parameter
- THEN validation MUST fail with error `"Invalid image path for logo: path traversal not allowed"`

#### Scenario: Path outside allowed directories rejected
- GIVEN a request with `logo: "lib/Controller/SettingsController.php"`
- WHEN `validateImagePaths()` processes the parameter
- THEN validation MUST fail with error `"Invalid image path for logo: must be in img/logos/ or img/backgrounds/"`

#### Scenario: Non-existent image rejected
- GIVEN a request with `logo: "img/logos/nonexistent.svg"`
- AND the file does not exist on the filesystem
- WHEN `validateImagePaths()` processes the parameter
- THEN validation MUST fail with error `"Image file not found: img/logos/nonexistent.svg"`

#### Scenario: Image fields validated
- GIVEN any request to `POST /apps/nldesign/settings/theming`
- WHEN images are validated
- THEN the system MUST check both `logo` and `background` parameters
- AND paths MUST start with either `img/logos/` or `img/backgrounds/`

### REQ-SYNC-005: Apply Colors to Nextcloud Theming
The app MUST apply validated color values to Nextcloud's `ThemingDefaults` service.

#### Scenario: Primary color applied
- GIVEN a valid request with `primary_color: "#004699"`
- WHEN `applyColors()` is called
- THEN `ThemingDefaults::set('primary_color', '#004699')` MUST be called
- AND `"primary_color"` MUST appear in the list of updated fields

#### Scenario: Background color applied
- GIVEN a valid request with `background_color: "#FFFFFF"`
- WHEN `applyColors()` is called
- THEN `ThemingDefaults::set('background_color', '#FFFFFF')` MUST be called
- AND `"background_color"` MUST appear in the list of updated fields

#### Scenario: Multiple colors applied
- GIVEN a valid request with both `primary_color` and `background_color`
- WHEN `applyColors()` is called
- THEN both colors MUST be applied
- AND both keys MUST appear in the updated list

#### Scenario: Empty color ignored
- GIVEN a request where `primary_color` is empty or not set
- WHEN `applyColors()` is called
- THEN `ThemingDefaults::set()` MUST NOT be called for `primary_color`

### REQ-SYNC-006: Apply Images to Nextcloud Theming
The app MUST apply validated image paths to Nextcloud's `ImageManager` service.

#### Scenario: Logo image applied
- GIVEN a valid request with `logo: "img/logos/amsterdam.svg"`
- AND the file exists at `{appPath}/img/logos/amsterdam.svg`
- WHEN `applyImages()` is called
- THEN `ImageManager::updateImage('logo', '{appPath}/img/logos/amsterdam.svg')` MUST be called
- AND `"logo"` MUST appear in the list of updated fields

#### Scenario: Background image applied
- GIVEN a valid request with `background: "img/backgrounds/default.jpg"`
- AND the file exists
- WHEN `applyImages()` is called
- THEN `ImageManager::updateImage('background', '{fullPath}')` MUST be called

### REQ-SYNC-007: Update Theming API Endpoint
The app MUST provide an admin-only API endpoint for updating Nextcloud theming values.

#### Scenario: Successful theming update
- GIVEN the admin is authenticated
- AND a valid request with `primary_color: "#154273"` and `logo: "img/logos/rijkshuisstijl.svg"`
- WHEN `POST /apps/nldesign/settings/theming` is called
- THEN color validation MUST run first
- AND image path validation MUST run second
- AND if both pass, colors MUST be applied
- AND images MUST be applied
- AND the response MUST be JSON with `{"status": "ok", "updated": ["primary_color", "logo"]}`

#### Scenario: Validation failure stops processing
- GIVEN a request with `primary_color: "invalid"` and `logo: "img/logos/valid.svg"`
- WHEN `POST /apps/nldesign/settings/theming` is called
- THEN color validation MUST fail first
- AND the response MUST be HTTP 400 with the error message
- AND no colors or images MUST be applied

#### Scenario: Non-admin access denied
- GIVEN a non-admin user is authenticated
- WHEN `POST /apps/nldesign/settings/theming` is called
- THEN the request MUST be rejected by the `@AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)` annotation

### REQ-SYNC-008: Theming Dependencies
The theming sync feature MUST depend on the Nextcloud `theming` app for `ThemingDefaults` and `ImageManager`.

#### Scenario: ThemingService dependencies injected
- GIVEN the nldesign app is loaded
- WHEN `ThemingService` is constructed
- THEN it MUST receive `ImageManager`, `ThemingDefaults`, and `IAppManager` via constructor injection
- AND it MUST NOT instantiate these dependencies directly
