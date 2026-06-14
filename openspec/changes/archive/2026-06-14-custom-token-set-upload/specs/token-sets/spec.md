# Spec delta: Token Sets (custom-token-set-upload)

Extends discovery so that admin-uploaded custom token sets (appconfig manifest, `custom-*` id namespace) merge with shipped sets (`token-sets.json`). Filesystem stays the source of truth for availability.

## MODIFIED Requirements

### Requirement: REQ-TSET-001: Filesystem-Based Discovery
The app MUST discover available token sets by scanning the `css/tokens/` directory for CSS files and merging metadata from `token-sets.json` for shipped sets and from the `custom_token_sets` appconfig manifest for uploaded sets (files matching `custom-*.css`).

#### Scenario: Token sets discovered from filesystem
- GIVEN the nldesign app is installed
- AND the `css/tokens/` directory contains CSS files (e.g. `rijkshuisstijl.css`, `amsterdam.css`)
- WHEN `TokenSetService::getAvailableTokenSets()` is called
- THEN each `.css` file in `css/tokens/` MUST produce a token set entry
- AND each entry MUST have an `id` derived from the filename without extension (via `basename($file, '.css')`)
- AND each entry MUST have a `name`, `description`, and `design_system` field

#### Scenario: Metadata merged from manifest
- GIVEN `token-sets.json` exists and contains an entry with `id: "amsterdam"`
- AND `css/tokens/amsterdam.css` exists on the filesystem
- WHEN the available token sets are retrieved
- THEN the entry for `amsterdam` MUST use the `name` from the manifest ("Gemeente Amsterdam")
- AND the entry MUST use the `description` from the manifest
- AND the entry MUST use the `design_system` from the manifest (default: "nldesign")
- AND if the manifest entry has a `theming` object, it MUST be included in the response

#### Scenario: Custom set metadata merged from appconfig manifest
@e2e exclude discovery merge — PHPUnit on TokenSetService
- GIVEN `css/tokens/custom-gemeente-voorbeeld.css` exists
- AND the `custom_token_sets` appconfig manifest contains an entry for `custom-gemeente-voorbeeld` with name "Gemeente Voorbeeld" and a `theming` object
- WHEN the available token sets are retrieved
- THEN the entry MUST use the name, description, and theming metadata from the appconfig manifest
- AND the entry MUST be marked as custom (`custom: true`) so the dropdown can label it

#### Scenario: CSS file exists without manifest entry
- GIVEN a file `css/tokens/custom-org.css` exists
- AND neither `token-sets.json` nor the `custom_token_sets` appconfig manifest contains an entry with `id: "custom-org"`
- WHEN the available token sets are retrieved
- THEN the entry MUST still be returned
- AND the `name` MUST be auto-generated from the id using `ucwords(str_replace('-', ' ', $id))` (e.g. "Custom Org")
- AND the `description` MUST default to "Design tokens for Custom Org"
- AND the `design_system` MUST default to "nldesign"

#### Scenario: Manifest entry exists without CSS file
- GIVEN `token-sets.json` or the `custom_token_sets` appconfig manifest contains an entry with `id: "phantom-org"`
- AND `css/tokens/phantom-org.css` does NOT exist
- WHEN the available token sets are retrieved
- THEN the `phantom-org` entry MUST NOT appear in the results
- AND the filesystem MUST be the source of truth for available sets

#### Scenario: Shipped manifest wins on impossible id collision
@e2e exclude defensive branch — PHPUnit on the merge order
- GIVEN an entry with the same id exists in both `token-sets.json` and the `custom_token_sets` appconfig manifest (should be impossible given the `custom-` namespace, but defensively)
- WHEN the available token sets are retrieved
- THEN the shipped `token-sets.json` metadata MUST take precedence
- AND the collision MUST be logged at warning level

#### Scenario: Token sets sorted alphabetically
- GIVEN multiple token sets are discovered, shipped and custom
- WHEN the list is returned
- THEN the token sets MUST be sorted alphabetically by `name` (case-insensitive via `strcasecmp`) across both groups
