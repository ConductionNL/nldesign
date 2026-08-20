# themiq-identity Delta: rename-nldesign-to-themiq

**Status**: in-progress
**Scope**: nldesign → themiq
**OpenSpec changes**:

- [rename-nldesign-to-themiq](../../)

## Purpose

Renames the app to `themiq` under the fleet's `-iq` convention, carrying the
app id, namespace, appstore identity, l10n domain and every fleet reference
together, and migrating per-instance configuration so a renamed app is not a
differently-named app with none of its settings. Related: ADR-086 §6.

## ADDED Requirements

### Requirement: The app identity MUST move as one unit

The app id, the PHP namespace, the appstore identity, the l10n domain, the
asset prefixes and the release configuration SHALL change in one change. The
repository SHALL contain no reference to the old id afterwards except the
deliberate transition alias.

#### Scenario: No stale identity remains

- **WHEN** the repository is searched for the old app id and namespace
- **THEN** the only matches are the transition alias and its deprecation notice

#### Scenario: Assets resolve under the new id

- **GIVEN** a page that loads this app's scripts and styles
- **WHEN** it renders
- **THEN** every asset resolves
- **AND** this is asserted on the rendered page, because a missing asset under
  a wrong app id is a 404 with no PHP error — the page renders unstyled and
  reads as a CSS bug

### Requirement: Per-instance configuration MUST follow the rename

A repair step SHALL copy the app's `oc_appconfig` rows from the old id to the
new one, report how many it moved, and be idempotent.

#### Scenario: An instance keeps its theme after the rename

- **GIVEN** an instance with a configured theme under the old id
- **WHEN** the renamed app is enabled
- **THEN** the same theme is active

#### Scenario: The migration reports what it moved

- **WHEN** the repair step runs
- **THEN** it reports the number of configuration rows copied
- **AND** a run that copies none is distinguishable from a run that did not
  execute — otherwise every deployment discovers the loss separately, in
  production, as "all our theming reverted to default"

#### Scenario: Re-running changes nothing

- **GIVEN** the repair step has completed
- **WHEN** it runs again
- **THEN** it reports zero rows copied and writes nothing

### Requirement: The old app id MUST keep working for one release

A transition alias SHALL resolve the old app id to the renamed app for one
release, logging a deprecation that names the calling app.

#### Scenario: A consumer on the old id still works

- **GIVEN** a fleet app still requesting the old id
- **WHEN** it loads
- **THEN** it resolves to the renamed app
- **AND** a deprecation is logged naming that caller, so the remaining
  migration work is a list rather than a search

#### Scenario: Alias removal is scheduled, not intended

- **GIVEN** this change is merged
- **WHEN** the backlog is inspected
- **THEN** a separate removal change exists naming the alias and the release
  after which it goes

### Requirement: Fleet callers MUST be migrated and counted

Every reference to the old app id outside this repository SHALL be updated.
The count SHALL be measured before and after, not estimated.

#### Scenario: The caller count reaches zero

- **GIVEN** 468 files outside this repository named the old app id when the
  change was written
- **WHEN** the sweep completes
- **THEN** a re-count finds none outside the alias
- **AND** the re-count is run fresh rather than inferred from the sweep having
  been performed

#### Scenario: Portaliq depends on the renamed app

- **GIVEN** Portaliq's theming dependency (ADR-086 §6)
- **WHEN** its dependency declaration is inspected
- **THEN** it names `themiq`
