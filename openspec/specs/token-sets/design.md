---
status: reviewed
reviewed_date: 2026-08-08
---

# Profile Catalogue — Technical Design

## Role and statuses

The package inventory consists of `token-sets.json` plus one matching
`css/tokens/{id}.css` file per record. A record is either:

- `source-only`: retained input for provenance and future projection work, but
  unavailable at runtime; or
- `ready`: administrator-selectable only with
  `projection: nextcloud-core-v1` and the required semantic properties.

The current package happens to contain 40 files and records, of which 8 are
ready. The release gate derives those counts from the manifest instead of
encoding them in code. Status and projection checks prevent incomplete profiles
from inheriting another organisation's identity.

## Immutable package boundary

`PackagedProfileFiles` resolves the app path through `IAppManager`. It accepts
only bounded regular files whose resolved paths remain inside the expected
manifest, token, or asset directory. Symlinks, oversized files, unreadable
files, and unapproved asset extensions are rejected.

`TokenSetService` parses and caches the manifest once per request. It validates
lowercase kebab-case ids, normalizes bounded display metadata, rejects an
ambiguous duplicate identity by failing the runtime catalogue closed, exposes
only ready profiles, and allowlists manual Theming hints. It never scans
directories to invent availability.

## Active profile state

`ProfileStateService` owns canonical app-scoped JSON state in
`active_profile_state`. It includes active profile id, opaque revision,
previous snapshot, update time, and a bounded non-personal operation source. Initial
state has a deterministic revision; later revisions include randomness.

Every write requires the observed revision and runs inside a Nextcloud
exclusive lock. After acquiring the lock, `ProfileStateMutationGuard` clears
the public app-config cache before canonical state is read and compared. This
prevents another request's committed state from being hidden by request-local
cache. Canonical state is written first. Legacy mirror keys and a ten-entry
history are independent best-effort outputs, so they cannot undo a successful
publish. One-step rollback follows the same lock, cache-refresh, and revision
contract. The legacy `token_set` mirror is read only when canonical state is
absent. A present but malformed or incomplete canonical record discards all
partial fields and fails to native Nextcloud rather than reactivating a stale
mirror or rollback fragment. Canonical and history JSON
are size-bounded before decoding so damaged app config cannot impose unbounded
parsing work on requests.

## Rendering

`TemplateStylesListener` reads canonical state and adds the three stylesheets
returned by `RuntimeStylesheetPlan` only when the active profile remains ready.
The catalogue's required `default_profile` field may only be null, so package
data cannot select an organisation. An unavailable stored profile emits no profile CSS rather than
inheriting another organisation's identity. Explicit deactivation is revisioned
and rollback-capable. The listener catches failures so branding cannot take
down a page. No runtime package file is generated or modified.

## Build-time integrity

`scripts/validate-token-sets.mjs` rejects malformed records, status/projection
inconsistency, missing required ready properties, unsafe CSS, path escapes,
oversized files, unapproved or missing assets, missing stylesheets, and orphan
or unexpected token-directory entries. A ready projection may contain only
the four consumed properties, at most ten declarations across modes, no escape
sequences or URLs, and only the exact system-dark media branch. Explicit-dark values
must be repeated for system-dark/default accounts, and every supplied mode has
measured 4.5:1 primary pairs.
Source-only records cannot declare Theming hints.
