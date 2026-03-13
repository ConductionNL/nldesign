---
status: reviewed
reviewed_date: 2026-02-28
---

# Token Sets Specification

## Purpose
Defines how the NL Design app discovers, validates, stores, and serves design token sets. Token sets are organization-specific CSS files that override default Rijkshuisstijl design tokens, enabling Dutch government organizations to apply their own visual identity to Nextcloud. The system uses filesystem-based discovery combined with a JSON manifest for metadata.

## Requirements

### REQ-TSET-001: Filesystem-Based Discovery
The app MUST discover available token sets by scanning the `css/tokens/` directory for CSS files and merging metadata from `token-sets.json`.

#### Scenario: Token sets discovered from filesystem
- GIVEN the nldesign app is installed
- AND the `css/tokens/` directory contains CSS files (e.g. `rijkshuisstijl.css`, `amsterdam.css`)
- WHEN `TokenSetService::getAvailableTokenSets()` is called
- THEN each `.css` file in `css/tokens/` MUST produce a token set entry
- AND each entry MUST have an `id` derived from the filename without extension
- AND each entry MUST have a `name` and `description`

#### Scenario: Metadata merged from manifest
- GIVEN `token-sets.json` exists and contains an entry with `id: "amsterdam"`
- AND `css/tokens/amsterdam.css` exists on the filesystem
- WHEN the available token sets are retrieved
- THEN the entry for `amsterdam` MUST use the `name` from the manifest ("Gemeente Amsterdam")
- AND the entry MUST use the `description` from the manifest
- AND if the manifest entry has a `theming` object, it MUST be included in the response

#### Scenario: CSS file exists without manifest entry
- GIVEN a file `css/tokens/custom-org.css` exists
- AND `token-sets.json` does NOT contain an entry with `id: "custom-org"`
- WHEN the available token sets are retrieved
- THEN the entry MUST still be returned
- AND the `name` MUST be auto-generated from the id using `ucwords(str_replace('-', ' ', $id))` (e.g. "Custom Org")
- AND the `description` MUST default to "Design tokens for Custom Org"

#### Scenario: Manifest entry exists without CSS file
- GIVEN `token-sets.json` contains an entry with `id: "phantom-org"`
- AND `css/tokens/phantom-org.css` does NOT exist
- WHEN the available token sets are retrieved
- THEN the `phantom-org` entry MUST NOT appear in the results

#### Scenario: Token sets sorted alphabetically
- GIVEN multiple token sets are discovered
- WHEN the list is returned
- THEN the token sets MUST be sorted alphabetically by `name` (case-insensitive)

### REQ-TSET-002: Token Set Manifest Structure
The `token-sets.json` manifest MUST follow a defined schema for each entry.

#### Scenario: Manifest entry with theming metadata
- GIVEN a manifest entry for an organization
- WHEN the entry is valid
- THEN it MUST have a `id` field (string, kebab-case identifier matching the CSS filename)
- AND it MUST have a `name` field (string, human-readable display name)
- AND it MUST have a `description` field (string)
- AND it MAY have a `theming` object with optional keys: `primary_color` (hex), `background_color` (hex), `logo` (relative path)

#### Scenario: Manifest is malformed JSON
- GIVEN `token-sets.json` contains invalid JSON
- WHEN `readManifest()` is called
- THEN it MUST return an empty array
- AND the system MUST still discover token sets from the filesystem (without metadata)

#### Scenario: Manifest is missing
- GIVEN `token-sets.json` does not exist
- WHEN `readManifest()` is called
- THEN it MUST return an empty array
- AND the system MUST still discover token sets with auto-generated names

### REQ-TSET-003: Active Token Set Storage
The active token set MUST be stored in Nextcloud's `IConfig` and default to `rijkshuisstijl`.

#### Scenario: No token set configured
- GIVEN no value has been set for `nldesign:token_set` in IConfig
- WHEN the active token set is queried
- THEN the default value MUST be `rijkshuisstijl`

#### Scenario: Token set persisted via API
- GIVEN an admin selects the `utrecht` token set
- WHEN `POST /settings/tokenset` is called with `tokenSet=utrecht`
- THEN `IConfig::setAppValue('nldesign', 'token_set', 'utrecht')` MUST be called
- AND the response MUST be JSON with `{"status": "ok", "tokenSet": "utrecht"}`

#### Scenario: Token set retrieved via API
- GIVEN the active token set is `amsterdam`
- WHEN `GET /settings/tokenset` is called
- THEN the response MUST be JSON with `{"tokenSet": "amsterdam"}`

### REQ-TSET-004: Token Set Validation
The app MUST validate that a token set is valid before accepting it as the active set.

#### Scenario: Valid token set selected
- GIVEN `css/tokens/utrecht.css` exists on the filesystem
- WHEN `setTokenSet("utrecht")` is called
- THEN `isValidTokenSet("utrecht")` MUST return `true`
- AND the token set MUST be stored in IConfig

#### Scenario: Invalid token set rejected
- GIVEN `css/tokens/nonexistent.css` does NOT exist
- WHEN `setTokenSet("nonexistent")` is called
- THEN `isValidTokenSet("nonexistent")` MUST return `false`
- AND the API MUST return HTTP 400 with `{"error": "Invalid token set"}`
- AND IConfig MUST NOT be updated

#### Scenario: Path traversal prevented
- GIVEN a malicious token set id containing `../` or `/`
- WHEN `isValidTokenSet("../../etc/passwd")` is called
- THEN it MUST return `false`
- AND the filesystem MUST NOT be accessed outside `css/tokens/`

### REQ-TSET-005: Token Set CSS Structure
Each token set CSS file MUST define organization-specific `--nldesign-*` variables on `:root`.

#### Scenario: Complete token set
- GIVEN a token set like `rijkshuisstijl.css`
- WHEN loaded after `defaults.css`
- THEN it MUST override `--nldesign-color-primary` with the organization's primary color
- AND it MUST override `--nldesign-color-primary-text` for accessible text on the primary color
- AND it MAY override any other `--nldesign-*` variable defined in `defaults.css`

#### Scenario: Incomplete token set (partial overrides)
- GIVEN a token set that only defines `--nldesign-color-primary` and `--nldesign-color-primary-text`
- WHEN loaded after `defaults.css`
- THEN all undefined tokens MUST fall back to the Rijkshuisstijl defaults from `defaults.css`
- AND the application MUST render correctly with the partial overrides

#### Scenario: Token set with logo
- GIVEN a token set defines `--nldesign-logo-url: url('../img/logos/amsterdam.svg')`
- WHEN the theme is rendered
- THEN the logo MUST be displayed in the header and login page via `background-image`
- AND the logo MUST be sized and positioned using `--nldesign-logo-center` and related variables

### REQ-TSET-006: Token Sets API Endpoints
The app MUST expose admin-only API endpoints for managing token sets.

#### Scenario: List all available token sets
- GIVEN the admin is authenticated
- WHEN `GET /apps/nldesign/settings/tokensets` is called
- THEN the response MUST be JSON with `{"tokenSets": [...]}` containing all discovered token sets
- AND each token set object MUST have `id`, `name`, `description` fields
- AND token sets with theming metadata MUST include the `theming` object

#### Scenario: Get current token set
- GIVEN the admin is authenticated
- WHEN `GET /apps/nldesign/settings/tokenset` is called
- THEN the response MUST be JSON with `{"tokenSet": "<current-id>"}`

#### Scenario: Set active token set
- GIVEN the admin is authenticated
- AND the token set `denhaag` exists
- WHEN `POST /apps/nldesign/settings/tokenset` is called with `tokenSet=denhaag`
- THEN the response MUST be JSON with `{"status": "ok", "tokenSet": "denhaag"}`
- AND the active token set MUST be updated in IConfig

#### Scenario: Non-admin access denied
- GIVEN a non-admin user is authenticated
- WHEN any `/settings/tokenset` or `/settings/tokensets` endpoint is called
- THEN the request MUST be rejected by the `@AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)` annotation

### REQ-TSET-007: Token Set Count and Coverage
The app MUST support at minimum the documented set of organizations. As of 2026-02-28, there are 39 token sets and 39 manifest entries.

#### Scenario: All required token sets present
- GIVEN the nldesign app is installed
- WHEN the `css/tokens/` directory is scanned
- THEN it MUST contain CSS files for at least: rijkshuisstijl, amsterdam, utrecht, rotterdam, denhaag
- AND the total number of CSS files in `css/tokens/` MUST match the number of entries in `token-sets.json`

#### Scenario: Token set count matches manifest
- GIVEN the `token-sets.json` manifest lists N entries
- WHEN the `css/tokens/` directory is scanned
- THEN each manifest entry MUST have a corresponding CSS file
- AND conversely, each CSS file SHOULD have a corresponding manifest entry (files without manifest entries receive auto-generated names)
