# Token Sets — Dark Mode Manifest Delta

**Spec refs**: `token-sets` (REQ-TSET-002), `dark-mode` (new, this change), `theming-sync`
**Standards**: CSS Media Queries Level 5 (`prefers-color-scheme`), WCAG 2.1 AA

## MODIFIED Requirements

### Requirement: Token Set Manifest Structure

The `token-sets.json` manifest MUST follow a defined schema for each entry.

#### Scenario: Manifest entry with full metadata

- GIVEN a manifest entry for an organization
- WHEN the entry is valid
- THEN it MUST have an `id` field (string, kebab-case identifier matching the CSS filename)
- AND it MUST have a `name` field (string, human-readable display name)
- AND it MUST have a `description` field (string)
- AND it MUST have a `design_system` field (string, referencing an id in `design-systems.json`)
- AND it MAY have a `theming` object with optional keys: `primary_color` (hex),
  `background_color` (hex), `logo` (relative path), `background` (relative path), and
  `logo_dark` (relative path to a dark-surface logo variant within `img/logos/`)

#### Scenario: Dark logo metadata passed through

- GIVEN a manifest entry whose `theming` object contains
  `logo_dark: "img/logos/rijkshuisstijl-dark.svg"`
- WHEN the token sets are retrieved via `GET /settings/tokensets`
- THEN the entry's `theming` object in the response MUST include the `logo_dark` key unchanged
- AND sets without `logo_dark` MUST simply omit the key (no null placeholder)

#### Scenario: Dark logo consumed by the generated dark variant

- GIVEN a token set with `theming.logo_dark` set and an existing dark logo file
- WHEN the set's dark variant is generated (see the `dark-mode` spec)
- THEN the generated `css/tokens/dark/<set>.css` MUST override `--nldesign-logo-url` with the
  dark logo path inside its dark-scoped blocks
- AND the light layer's `--nldesign-logo-url` MUST remain untouched

#### Scenario: Token set file may carry a hand-authored dark block

- GIVEN a token set CSS file containing a top-level
  `@media (prefers-color-scheme: dark) { :root { … } }` block
- WHEN the file is loaded as Layer 3
- THEN the block's presence MUST NOT affect light rendering (Layer 3 light declarations still
  live on plain `:root`)
- AND the block MUST be recognised by dark-variant generation as the hand-authored override
  source (see the `dark-mode` spec's override requirement)

#### Scenario: Manifest is malformed JSON

- GIVEN `token-sets.json` contains invalid JSON
- WHEN `readManifest()` is called
- THEN it MUST return an empty array
- AND the system MUST still discover token sets from the filesystem (without metadata)
- AND the app MUST NOT throw an exception or display an error

#### Scenario: Manifest is missing

- GIVEN `token-sets.json` does not exist
- WHEN `readManifest()` is called
- THEN it MUST return an empty array
- AND the system MUST still discover token sets with auto-generated names and default design_system

#### Scenario: Manifest file unreadable

- GIVEN `token-sets.json` exists but `file_get_contents()` returns `false`
- WHEN `readManifest()` is called
- THEN it MUST return an empty array
- AND the system MUST continue with auto-generated metadata

#### Scenario: Manifest indexed by id

- GIVEN the manifest contains multiple entries
- WHEN `readManifest()` processes them
- THEN entries MUST be indexed by their `id` field
- AND entries without an `id` field MUST be skipped
