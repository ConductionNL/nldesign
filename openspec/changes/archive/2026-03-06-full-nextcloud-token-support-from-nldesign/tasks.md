# Tasks: full-nextcloud-token-support-from-nldesign

## 1. Nextcloud Variable Audit

- [x] 1.1 Extract all CSS custom properties from Nextcloud theming
  - **spec_ref**: `specs/nextcloud-variable-mapping/spec.md#requirement-complete-nextcloud-variable-audit`
  - **files**: `nldesign/mappings.md`
  - **acceptance_criteria**:
    - GIVEN Nextcloud's DefaultTheme.php, CommonThemeTrait.php, and core SCSS files
    - WHEN all CSS custom properties are extracted
    - THEN a complete categorized list is documented in `mappings.md` with every variable, its category, and its `--nldesign-*` mapping or "unmapped" reason

## 2. Defaults Layer

- [x] 2.1 Create defaults.css with all --nldesign-* token defaults
  - **spec_ref**: `specs/nextcloud-variable-mapping/spec.md#requirement-defaults-css-layer`
  - **files**: `nldesign/css/defaults.css`
  - **acceptance_criteria**:
    - GIVEN the complete list of --nldesign-* tokens (brand, status, background, text, border, link, button, component, animation, spacing)
    - WHEN defaults.css is loaded
    - THEN every --nldesign-* token has a sensible Rijkshuisstijl-based default value
    - AND component token defaults reference brand tokens (e.g., button primary background defaults to --nldesign-color-primary)

## 3. Token Generation Script

- [x] 3.1 Create generate-tokens.mjs script
  - **spec_ref**: `specs/extended-token-sets/spec.md#requirement-auto-generated-token-css-files`
  - **files**: `nldesign/scripts/generate-tokens.mjs`, `nldesign/package.json`
  - **acceptance_criteria**:
    - GIVEN the nl-design-system/themes repository cloned locally
    - WHEN `node scripts/generate-tokens.mjs /path/to/themes` is executed
    - THEN CSS files are generated in `css/tokens/` for every organization under `proprietary/`
    - AND each CSS file contains `:root` declarations with `--nldesign-*` prefixed variables
    - AND organization-specific palette colors are preserved with `--{org}-*` prefix
    - AND malformed JSON files are logged as warnings without stopping the script

- [x] 3.2 Create token-sets.json manifest generation
  - **spec_ref**: `specs/extended-token-sets/spec.md#requirement-token-set-manifest`
  - **files**: `nldesign/token-sets.json`, `nldesign/scripts/generate-tokens.mjs`
  - **acceptance_criteria**:
    - GIVEN the generation script processes all upstream organizations
    - WHEN it finishes
    - THEN `token-sets.json` contains an entry for every organization with id, name, and description
    - AND manually added metadata is preserved on subsequent runs

- [x] 3.3 Run generation script to produce all 48+ token CSS files
  - **spec_ref**: `specs/extended-token-sets/spec.md#requirement-support-all-available-token-sets`
  - **files**: `nldesign/css/tokens/*.css`
  - **acceptance_criteria**:
    - GIVEN the generation script is complete
    - WHEN run against the current themes repository
    - THEN 48+ CSS files exist in `css/tokens/`
    - AND existing token sets (rijkshuisstijl, amsterdam, utrecht, denhaag, rotterdam) produce visually equivalent output to the current manual files

## 4. Utrecht Bridge

- [x] 4.1 Create utrecht-bridge.css
  - **spec_ref**: `specs/component-tokens/spec.md#requirement-utrecht-bridge-file`
  - **files**: `nldesign/css/utrecht-bridge.css`
  - **acceptance_criteria**:
    - GIVEN the NL Design System uses --utrecht-* component tokens
    - WHEN utrecht-bridge.css is loaded after token files
    - THEN all --utrecht-* tokens are mapped to --nldesign-component-* equivalents
    - AND the file header clearly states this is temporary and can be removed when NL Design System adopts a vendor-neutral prefix
    - AND tokens fall back to defaults.css values when no --utrecht-* value is defined

## 5. Overrides Rewrite

- [x] 5.1 Rewrite overrides.css with all Nextcloud variables
  - **spec_ref**: `specs/nextcloud-variable-mapping/spec.md#requirement-overrides-css-structure`
  - **files**: `nldesign/css/overrides.css`
  - **acceptance_criteria**:
    - GIVEN the complete Nextcloud variable audit from mappings.md
    - WHEN overrides.css is loaded
    - THEN every Nextcloud CSS variable is present — either mapped to `--nldesign-*` with `!important` or commented out with explanation
    - AND variables are organized by category (primary, background, text, status, border, radius, typography, spacing, animation, etc.)
    - AND intentionally unoverridden variables (e.g., --color-main-background) include "intentionally not overridden" comments

- [x] 5.2 Update theme.css with new token mappings
  - **spec_ref**: `specs/nextcloud-variable-mapping/spec.md#requirement-css-load-order`
  - **files**: `nldesign/css/theme.css`
  - **acceptance_criteria**:
    - GIVEN new --nldesign-* tokens exist for animations, spacing, placeholders, etc.
    - WHEN theme.css is loaded
    - THEN these new tokens are mapped to corresponding Nextcloud CSS variables
    - AND component tokens are mapped to Nextcloud element styling where applicable

## 6. PHP Changes

- [x] 6.1 Create TokenSetService for filesystem-based token discovery
  - **spec_ref**: `specs/extended-token-sets/spec.md#requirement-dynamic-token-set-discovery`
  - **files**: `nldesign/lib/Service/TokenSetService.php`
  - **acceptance_criteria**:
    - GIVEN CSS files exist in `css/tokens/`
    - WHEN TokenSetService::getAvailableTokenSets() is called
    - THEN it returns all token sets with id, name, and description from token-sets.json
    - AND token set validation checks for CSS file existence on disk (no hardcoded array)

- [x] 6.2 Update SettingsController for dynamic token validation
  - **spec_ref**: `specs/extended-token-sets/spec.md#requirement-dynamic-token-set-discovery`
  - **files**: `nldesign/lib/Controller/SettingsController.php`, `nldesign/appinfo/routes.php`
  - **acceptance_criteria**:
    - GIVEN the setTokenSet endpoint receives `tokenSet=groningen`
    - WHEN the controller validates the request
    - THEN it uses TokenSetService to check filesystem, NOT a hardcoded $validSets array
    - AND a new `getAvailableTokenSets()` endpoint is registered and returns all available sets

- [x] 6.3 Update Admin settings and Application bootstrap
  - **spec_ref**: `specs/extended-token-sets/spec.md#requirement-admin-settings-dynamic-dropdown`
  - **files**: `nldesign/lib/Settings/Admin.php`, `nldesign/lib/AppInfo/Application.php`, `nldesign/templates/settings/admin.php`
  - **acceptance_criteria**:
    - GIVEN 48+ token sets are available
    - WHEN the admin opens nldesign settings
    - THEN the dropdown lists all organizations from TokenSetService
    - AND Application.php loads CSS in the correct order: fonts → defaults → tokens/{org} → utrecht-bridge → theme → overrides

## 7. GitHub Actions Workflow

- [x] 7.1 Create sync-tokens.yml workflow
  - **spec_ref**: `specs/token-sync-workflow/spec.md#requirement-nightly-schedule`
  - **files**: `nldesign/.github/workflows/sync-tokens.yml`
  - **acceptance_criteria**:
    - GIVEN the workflow is configured with a nightly cron schedule (3 AM UTC) and workflow_dispatch
    - WHEN triggered
    - THEN it clones nl-design-system/themes, runs generate-tokens.mjs, and checks for changes
    - AND if changes are detected, a PR is created on branch `chore/sync-nldesign-tokens`
    - AND if no changes, the workflow exits successfully without creating a PR

- [x] 7.2 Add change detection and PR update logic
  - **spec_ref**: `specs/token-sync-workflow/spec.md#requirement-change-detection`
  - **files**: `nldesign/.github/workflows/sync-tokens.yml`
  - **acceptance_criteria**:
    - GIVEN a sync PR from a previous run is still open
    - WHEN a new sync run detects additional changes
    - THEN the existing branch and PR are updated rather than creating a duplicate

## 8. Documentation

- [x] 8.1 Create mappings.md documentation table
  - **spec_ref**: `specs/nextcloud-variable-mapping/spec.md#requirement-mappings-documentation`
  - **files**: `nldesign/mappings.md`
  - **acceptance_criteria**:
    - GIVEN all Nextcloud variables have been audited
    - WHEN a developer opens mappings.md
    - THEN they find a table with columns: Nextcloud Variable, NL Design Mapping, Category, Notes
    - AND every Nextcloud variable is listed (mapped or unmapped with reason)

- [x] 8.2 Update README with sources section
  - **spec_ref**: `specs/token-sync-workflow/spec.md#requirement-readme-sources-section`
  - **files**: `nldesign/README.md`
  - **acceptance_criteria**:
    - GIVEN the README is updated
    - WHEN a developer reads it
    - THEN they find links to nl-design-system/themes repo, design tokens handbook, participation guide
    - AND they find an explanation of the nightly sync workflow
    - AND they find instructions for adding a new token set

## 9. Verification

- [x] 9.1 Visual regression test with existing 5 token sets
  - **acceptance_criteria**:
    - GIVEN the regenerated token files for rijkshuisstijl, amsterdam, utrecht, denhaag, rotterdam
    - WHEN compared to the previous manual token files
    - THEN the visual output is equivalent (no unintended color/styling changes)

- [x] 9.2 Test with incomplete token set
  - **acceptance_criteria**:
    - GIVEN a token set that only defines primary colors
    - WHEN selected in admin settings
    - THEN the primary colors use the organization's values
    - AND all other styling falls back to defaults.css values
    - AND no broken/missing styles appear

- [x] 9.3 Test dynamic admin dropdown with all token sets
  - **acceptance_criteria**:
    - GIVEN 48+ token CSS files exist
    - WHEN the admin opens nldesign settings
    - THEN all organizations appear in the dropdown with correct names
    - AND selecting any organization applies the theme correctly

- [x] 9.4 End-to-end workflow test
  - **acceptance_criteria**:
    - GIVEN the generation script and all CSS files are in place
    - WHEN the full CSS load order is applied (fonts → defaults → tokens → bridge → theme → overrides)
    - THEN the Nextcloud instance renders correctly with NL Design styling
    - AND switching between token sets works without page errors
