# Token Sets — Upstream Provenance Fields Delta

**Spec refs**: `token-sets` (REQ-TSET-002 Token Set Manifest Structure), `upstream-freshness`
(consumer of the new fields), `token-sync-workflow` (producer of the new fields)
**Standards**: Semantic Versioning 2.0.0 (`upstreamVersion`), git commit SHA provenance
(`upstreamRef`)

## MODIFIED Requirements

### Requirement: Token Set Manifest Structure

The `token-sets.json` manifest MUST follow a defined schema for each entry. Entries MAY
additionally carry upstream provenance: `upstreamVersion` (string, the semver of the
upstream NL Design System theme package the entry was generated from) and `upstreamRef`
(string, the `nl-design-system/themes` commit SHA the entry was generated from). Both
fields are optional — hand-authored sets (e.g. `nextcloud`, `summer-breeze`) and custom
uploads legitimately have no upstream — and their absence MUST NOT affect discovery,
validation, activation, or rendering in any way. Consumers other than the freshness
comparison MUST ignore the fields; the discovery merge MUST pass them through unmodified.

#### Scenario: Manifest entry with full metadata

- GIVEN a manifest entry for an organization
- WHEN the entry is valid
- THEN it MUST have an `id` field (string, kebab-case identifier matching the CSS filename)
- AND it MUST have a `name` field (string, human-readable display name)
- AND it MUST have a `description` field (string)
- AND it MUST have a `design_system` field (string, referencing an id in `design-systems.json`)
- AND it MAY have a `theming` object with optional keys: `primary_color` (hex), `background_color` (hex), `logo` (relative path), `background` (relative path)
- AND it MAY have an `upstreamVersion` field (string, semver of the upstream theme package at generation time)
- AND it MAY have an `upstreamRef` field (string, commit SHA of `nl-design-system/themes` at generation time)

#### Scenario: Provenance fields are optional and inert

- GIVEN one manifest entry with `upstreamVersion`/`upstreamRef` and one without
- WHEN token sets are discovered, listed, validated, activated, and rendered
- THEN both entries MUST behave identically in every existing flow
- AND the provenance fields MUST be present unmodified in the discovery output for the
  entry that has them
- AND only the upstream-freshness comparison MAY interpret them

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
