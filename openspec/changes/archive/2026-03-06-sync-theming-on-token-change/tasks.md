# Tasks: sync-theming-on-token-change

## 1. Data Layer — Theming Metadata

- [x] 1.1 Add theming metadata to token-sets.json entries
  - **spec_ref**: `specs/theming-sync-dialog/spec.md#requirement-theming-metadata-in-token-sets`
  - **files**: `nldesign/token-sets.json`
  - **acceptance_criteria**:
    - GIVEN token-sets.json has existing entries
    - WHEN theming metadata is added to entries that have known primary/background colors
    - THEN entries like rijkshuisstijl, amsterdam, utrecht, denhaag, rotterdam, vng include a `theming` object with `primary_color` and `background_color`
    - AND entries without known theming values have no `theming` field
    - AND the file remains valid JSON

- [x] 1.2 Create img/logos/ and img/backgrounds/ directories with initial images
  - **spec_ref**: `specs/theming-sync-dialog/spec.md#requirement-bundled-organization-images`
  - **files**: `nldesign/img/logos/`, `nldesign/img/backgrounds/`
  - **acceptance_criteria**:
    - GIVEN token sets with theming metadata referencing logo/background paths
    - WHEN the referenced files are checked
    - THEN they exist on disk and are valid image files (SVG/PNG/JPG/WebP)

## 2. Backend — TokenSetService Update

- [x] 2.1 Update TokenSetService to include theming metadata in output
  - **spec_ref**: `specs/theming-sync-dialog/spec.md#requirement-theming-metadata-in-token-sets`
  - **files**: `nldesign/lib/Service/TokenSetService.php`
  - **acceptance_criteria**:
    - GIVEN token-sets.json entries with theming metadata
    - WHEN getAvailableTokenSets() is called
    - THEN each token set in the response includes the `theming` object if present in the manifest
    - AND entries without theming metadata omit the field entirely

## 3. Backend — Theming Endpoints

- [x] 3.1 Add GET /settings/theming endpoint
  - **spec_ref**: `specs/theming-sync-dialog/spec.md#requirement-get-current-theming-values-endpoint`
  - **files**: `nldesign/lib/Controller/SettingsController.php`, `nldesign/appinfo/routes.php`
  - **acceptance_criteria**:
    - GIVEN the admin is authenticated
    - WHEN GET /settings/theming is called
    - THEN the response includes primary_color, background_color, logo_url, background_url, has_custom_logo, has_custom_background
    - AND non-admin access returns 403

- [x] 3.2 Add POST /settings/theming endpoint for colors
  - **spec_ref**: `specs/theming-sync-dialog/spec.md#requirement-update-theming-values-endpoint`
  - **files**: `nldesign/lib/Controller/SettingsController.php`, `nldesign/appinfo/routes.php`
  - **acceptance_criteria**:
    - GIVEN valid hex colors are submitted
    - WHEN POST /settings/theming is called with primary_color and/or background_color
    - THEN Nextcloud's theming values are updated via IConfig
    - AND the cachebuster is incremented
    - AND invalid hex colors return 400

- [x] 3.3 Add image upload support to POST /settings/theming
  - **spec_ref**: `specs/theming-sync-dialog/spec.md#requirement-update-theming-values-endpoint`
  - **files**: `nldesign/lib/Controller/SettingsController.php`
  - **acceptance_criteria**:
    - GIVEN a logo or background path is submitted
    - WHEN POST /settings/theming is called with logo and/or background
    - THEN the file is read from nldesign's app directory and uploaded via ImageManager
    - AND path traversal attempts return 400
    - AND non-existent files return 400

## 4. Frontend — Dropdown Selector

- [x] 4.1 Replace radio buttons with select dropdown in admin template
  - **spec_ref**: `specs/token-set-dropdown/spec.md#requirement-dropdown-token-set-selector`
  - **files**: `nldesign/templates/settings/admin.php`, `nldesign/css/admin.css`
  - **acceptance_criteria**:
    - GIVEN the admin opens nldesign settings
    - WHEN the page loads
    - THEN a select dropdown is rendered with all token sets as options
    - AND the currently active token set is pre-selected
    - AND options are sorted alphabetically by name

- [x] 4.2 Update admin.js for dropdown change handler
  - **spec_ref**: `specs/token-set-dropdown/spec.md#requirement-dropdown-token-set-selector`
  - **files**: `nldesign/js/admin.js`
  - **acceptance_criteria**:
    - GIVEN the admin selects a different token set from the dropdown
    - WHEN the selection changes
    - THEN the token set is saved via POST /settings/tokenset
    - AND the preview section updates optimistically
    - AND a success/error notification is shown

## 5. Frontend — Theming Sync Dialog

- [x] 5.1 Add dialog HTML and CSS
  - **spec_ref**: `specs/theming-sync-dialog/spec.md#requirement-dialog-preview-boxes`
  - **files**: `nldesign/js/admin.js`, `nldesign/css/admin.css`
  - **acceptance_criteria**:
    - GIVEN the dialog is triggered
    - WHEN it renders
    - THEN it shows a modal overlay with Current and Proposed Nextcloud-style preview boxes (230x140px, background + logo overlay)
    - AND a comparison table listing only values that differ
    - AND Cancel and "Update theming" buttons

- [x] 5.2 Add dialog trigger logic after token set change
  - **spec_ref**: `specs/theming-sync-dialog/spec.md#requirement-confirmation-dialog-after-token-set-change`
  - **files**: `nldesign/js/admin.js`
  - **acceptance_criteria**:
    - GIVEN the admin selects a token set with theming metadata
    - WHEN the token set save succeeds
    - THEN GET /settings/theming is called to fetch current NC values
    - AND if any values differ, the dialog is shown
    - AND if no values differ or no theming metadata exists, no dialog appears

- [x] 5.3 Wire up dialog confirm/cancel actions
  - **spec_ref**: `specs/theming-sync-dialog/spec.md#requirement-dialog-user-actions`
  - **files**: `nldesign/js/admin.js`
  - **acceptance_criteria**:
    - GIVEN the dialog is displayed
    - WHEN the admin clicks "Update theming"
    - THEN POST /settings/theming is called with all differing values
    - AND a success notification is shown and styles are refreshed
    - WHEN the admin clicks "Cancel"
    - THEN no theming values are changed and the dialog closes

## 6. Verification

- [x] 6.1 End-to-end test: select token set with theming, confirm dialog, verify NC theming updated
  - **acceptance_criteria**:
    - GIVEN VNG token set has theming metadata with primary_color, background_color, and logo
    - WHEN the admin selects VNG from the dropdown and confirms the dialog
    - THEN Nextcloud's primary color, background color, and logo match VNG values
    - AND the theming page at /settings/admin/theming reflects the changes

- [x] 6.2 Test dropdown with all token sets, verify alphabetical sorting and selection
  - **acceptance_criteria**:
    - GIVEN 39+ token sets exist
    - WHEN the admin opens the dropdown
    - THEN all entries are listed alphabetically
    - AND selecting any entry saves it correctly
