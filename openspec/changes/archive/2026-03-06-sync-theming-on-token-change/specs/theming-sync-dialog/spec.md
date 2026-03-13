# Theming Sync Dialog Specification

## Purpose
After an admin selects a different token set in nldesign, offer to automatically update Nextcloud's built-in theming values (primary color, background color, logo, background image) to match the selected token set, preventing a split-brain theming state where CSS tokens and Nextcloud theming are out of sync.

## ADDED Requirements

### Requirement: Theming Metadata in Token Sets
Each token set entry in `token-sets.json` MAY include a `theming` object with optional fields: `primary_color`, `background_color`, `logo`, and `background`.

#### Scenario: Token set with full theming metadata
- GIVEN a token set entry in `token-sets.json` has a `theming` object with `primary_color`, `background_color`, `logo`, and `background` fields
- WHEN the token sets are loaded via `GET /settings/tokensets`
- THEN all theming fields SHALL be included in the response for that token set

#### Scenario: Token set without theming metadata
- GIVEN a token set entry in `token-sets.json` has no `theming` object
- WHEN the token sets are loaded via `GET /settings/tokensets`
- THEN the `theming` field SHALL be absent from the response for that token set

#### Scenario: Token set with partial theming metadata
- GIVEN a token set entry has a `theming` object with only `primary_color` defined
- WHEN the token sets are loaded
- THEN only `primary_color` SHALL be included in the `theming` response
- AND absent fields SHALL NOT appear (no null values)

### Requirement: Get Current Theming Values Endpoint
The system MUST provide a `GET /settings/theming` endpoint that returns the current Nextcloud theming values for comparison in the dialog.

#### Scenario: Retrieve current theming values
- GIVEN the admin is authenticated
- WHEN `GET /settings/theming` is called
- THEN the response SHALL include `primary_color`, `background_color`, `logo_url`, `background_url`, `has_custom_logo`, and `has_custom_background`
- AND color values SHALL be hex strings
- AND image URLs SHALL be absolute paths to the current NC theming images

#### Scenario: Unauthenticated access denied
- GIVEN the requester is not an admin
- WHEN `GET /settings/theming` is called
- THEN the response SHALL be a 403 status

### Requirement: Update Theming Values Endpoint
The system MUST provide a `POST /settings/theming` endpoint that updates Nextcloud's built-in theming values.

#### Scenario: Update colors only
- GIVEN the admin sends `{ "primary_color": "#003865", "background_color": "#003865" }`
- WHEN `POST /settings/theming` is called
- THEN Nextcloud's primary color and background color SHALL be updated to the provided values
- AND the theming cachebuster SHALL be incremented
- AND the response SHALL list `["primary_color", "background_color"]` in the `updated` array

#### Scenario: Update logo
- GIVEN the admin sends `{ "logo": "img/logos/vng.svg" }`
- WHEN `POST /settings/theming` is called
- THEN the file at the given path within nldesign's app directory SHALL be uploaded as Nextcloud's logo via ImageManager
- AND the response SHALL include `"logo"` in the `updated` array

#### Scenario: Update background image
- GIVEN the admin sends `{ "background": "img/backgrounds/vng.jpg" }`
- WHEN `POST /settings/theming` is called
- THEN the file at the given path within nldesign's app directory SHALL be uploaded as Nextcloud's background via ImageManager
- AND the response SHALL include `"background"` in the `updated` array

#### Scenario: Invalid hex color rejected
- GIVEN the admin sends `{ "primary_color": "not-a-color" }`
- WHEN `POST /settings/theming` is called
- THEN the response SHALL be a 400 status with an error message
- AND no theming values SHALL be changed

#### Scenario: Path traversal rejected
- GIVEN the admin sends `{ "logo": "../../etc/passwd" }`
- WHEN `POST /settings/theming` is called
- THEN the response SHALL be a 400 status with an error message
- AND no files SHALL be uploaded

#### Scenario: Non-existent image rejected
- GIVEN the admin sends `{ "logo": "img/logos/nonexistent.svg" }`
- WHEN `POST /settings/theming` is called
- THEN the response SHALL be a 400 status with an error message
- AND no files SHALL be uploaded

### Requirement: Confirmation Dialog After Token Set Change
The system MUST display a confirmation dialog after a token set with theming metadata is selected, showing a comparison of current vs. proposed theming values.

#### Scenario: Dialog shown for token set with theming metadata
- GIVEN the admin selects a token set that has a `theming` object
- WHEN the token set is saved successfully
- THEN a dialog SHALL appear showing the current and proposed theming values
- AND only fields that differ between current and proposed SHALL be displayed

#### Scenario: Dialog not shown for token set without theming metadata
- GIVEN the admin selects a token set without a `theming` object
- WHEN the token set is saved successfully
- THEN no dialog SHALL appear
- AND the token set change SHALL complete normally

#### Scenario: Dialog not shown when values already match
- GIVEN the admin selects a token set whose theming values already match Nextcloud's current values
- WHEN the token set is saved successfully
- THEN no dialog SHALL appear

### Requirement: Dialog Preview Boxes
The confirmation dialog MUST display Nextcloud-style theming preview boxes showing the visual effect of the proposed changes.

#### Scenario: Current preview reflects active theming
- GIVEN the dialog is displayed
- WHEN the "Current" preview box is rendered
- THEN it SHALL show Nextcloud's current background color as the box background
- AND if a custom background image exists, it SHALL be shown
- AND the current logo SHALL be overlaid in the center

#### Scenario: Proposed preview reflects token set theming
- GIVEN the dialog is displayed for a token set with theming metadata
- WHEN the "Proposed" preview box is rendered
- THEN it SHALL show the token set's `background_color` as the box background
- AND if the token set has a `background` image, it SHALL be shown
- AND if the token set has a `logo`, it SHALL be overlaid in the center

### Requirement: Dialog User Actions
The dialog MUST provide Cancel and Update actions.

#### Scenario: User confirms update
- GIVEN the dialog is displayed
- WHEN the admin clicks "Update theming"
- THEN `POST /settings/theming` SHALL be called with all differing theming values
- AND a success notification SHALL be shown
- AND the page SHALL refresh styles to reflect the new theming

#### Scenario: User cancels update
- GIVEN the dialog is displayed
- WHEN the admin clicks "Cancel"
- THEN no theming values SHALL be changed
- AND the token set CSS change SHALL remain applied (only Nextcloud theming is skipped)

### Requirement: Bundled Organization Images
Organization logos and background images MUST be stored as static files within the nldesign app directory.

#### Scenario: Logo file stored correctly
- GIVEN a token set has `"logo": "img/logos/vng.svg"` in its theming metadata
- WHEN the file path is resolved
- THEN the file SHALL exist at `nldesign/img/logos/vng.svg`
- AND it SHALL be a valid image file (SVG, PNG, JPG, or WebP)

#### Scenario: Background file stored correctly
- GIVEN a token set has `"background": "img/backgrounds/vng.jpg"` in its theming metadata
- WHEN the file path is resolved
- THEN the file SHALL exist at `nldesign/img/backgrounds/vng.jpg`
- AND it SHALL be a valid image file (PNG, JPG, WebP)
