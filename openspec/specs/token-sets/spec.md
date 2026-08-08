---
status: reviewed
reviewed_date: 2026-08-08
---

# Profile Catalogue Specification

## REQ-PROFILE-001: Manifest-backed catalogue

The package inventory MUST originate from `token-sets.json`. Every record MUST
have one matching bounded regular CSS file inside `css/tokens`. Filesystem
discovery MUST NOT create runtime profiles.

Only status `ready` with projection `nextcloud-core-v1` MAY be administrator-
selectable. Status `source-only` MUST remain unavailable and MUST NOT declare
Theming hints.

## REQ-PROFILE-002: Release integrity

The release validator MUST derive the inventory size from the manifest and
require a one-to-one record/stylesheet mapping and at least one ready profile.
The token directory MUST contain no other files, directories, or symlinks.
The required `default_profile` field MUST be null so package data cannot
activate an organisation. The validator MUST reject
duplicates, malformed ids or metadata, inconsistent status/projection,
unsupported hints, invalid values, missing assets, and orphan files. Ready
stylesheets MUST contain only the four consumed properties, stay at or below
ten declarations and 32 KiB, contain no CSS escapes, and pass 4.5:1
primary/text and hover/text checks in each supplied mode. The only permitted
at-rule is `@media (prefers-color-scheme: dark)`, and it MAY contain only the
`[data-theme-default]` fallback paired with an explicit `[data-theme-dark]`
projection. Those two dark branches MUST override all three projected colours
with the same resolved values. Ready projections MUST contain no URLs; profile
assets remain separate manifest fields. It MUST NOT encode the current
catalogue count in application or validation logic.

## REQ-PROFILE-003: Path and resource safety

Identifiers MUST match bounded lowercase kebab case. Traversal, separators,
uppercase ids, symlinks, path escapes, unreadable files, oversized files, and
non-allowlisted asset types MUST be rejected using resolved paths and file
metadata rather than string concatenation alone.

## REQ-PROFILE-004: Metadata boundary

Runtime metadata MUST contain normalized id, name, description, source, status,
and projection. Optional ready-profile Theming hints MUST be limited to
six-digit hexadecimal `primary_color` and `background_color`, plus contained
local `logo` and `background` files in the approved directories. Unknown or
malformed optional metadata MUST not be exposed.
Duplicate valid identifiers MUST make the runtime catalogue unavailable rather
than select one declaration by order.

## REQ-PROFILE-005: Revisioned and locked publication

Canonical state MUST be stored through app-scoped `IAppConfig`. Publication,
deactivation, and rollback MUST require a syntactically valid expected revision
and MUST perform compare-and-write while holding an exclusive Nextcloud lock.
After acquiring the lock and before reading canonical state, the implementation
MUST clear Nextcloud's public app-config cache.

### Scenario: current revision

- GIVEN the submitted revision matches canonical state under the lock
- WHEN a different ready profile is published
- THEN canonical state MUST be written first
- AND a new opaque revision and previous snapshot MUST be returned
- AND compatibility mirrors and bounded history MAY be attempted afterward.

### Scenario: stale or absent revision

- GIVEN the revision is stale, malformed, or absent
- WHEN publication or rollback is requested
- THEN the request MUST fail without changing canonical state.

### Scenario: unavailable lock

- GIVEN the exclusive lock cannot be acquired
- WHEN publication or rollback is requested
- THEN no state MUST change
- AND the route MUST report temporary unavailability.

### Scenario: unavailable cache refresh

- GIVEN the lock is acquired but the app-config cache cannot be refreshed
- WHEN publication or rollback is requested
- THEN canonical state MUST not be read or changed
- AND the route MUST report temporary unavailability.

### Scenario: auxiliary failure

- GIVEN canonical publication succeeds
- AND a compatibility mirror or history write fails
- THEN canonical state MUST remain active
- AND each auxiliary failure MUST be logged independently.

### Scenario: corrupt or incomplete canonical state with stale fragments

- GIVEN the canonical state key exists but cannot produce one complete active
  id/revision pair
- AND the legacy mirror names a profile
- WHEN profile state is read
- THEN the legacy mirror MUST be ignored
- AND any partial rollback snapshot MUST be ignored
- AND native Nextcloud MUST be returned with a deterministic recovery revision.

Canonical state and history JSON MUST be bounded before decoding. Oversized
canonical state MUST follow the same native fallback, and oversized history
MUST be ignored.

## REQ-PROFILE-006: Authorized administration

Every state and history action MUST carry the `AuthorizedAdminSetting`
attribute for the NL Design delegated setting. Profile id and revision MUST be
validated before any write. Actor and stored text fields MUST be bounded and
must reject control characters.

## REQ-PROFILE-007: Ordered, fail-open rendering

Normal and login templates MUST load fonts, the active ready profile, and theme
in that order. The catalogue default MUST be present and null, and fresh state
MUST initialize native Nextcloud.
Unavailable stored state MUST emit no profile CSS and preserve native
Nextcloud. Read or injection failure MUST be logged and MUST NOT abort the
Nextcloud response. Activation MUST NOT modify Nextcloud Theming settings or
package files.

Explicit deactivation MUST transition to native Nextcloud under the same lock,
revision, history, and rollback contract as profile activation.
