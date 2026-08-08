---
status: reviewed
reviewed_date: 2026-08-08
---

# Nextcloud Theming Bridge Specification

## REQ-THEME-001: Manual production behavior

The current production application MUST expose validated Theming
recommendations without mutating Nextcloud Theming state.

### Scenario: profile contains Theming hints

- GIVEN an authorized administrator requests a plan for a valid profile
- WHEN GET /apps/nldesign/settings/theming-plan is called
- THEN the response MUST identify mode manual
- AND appliesAutomatically MUST be false
- AND no Theming setting or image MUST be changed.

### Scenario: profile activation

- WHEN an administrator activates a profile
- THEN only NL Design profile state MAY change
- AND no core-Theming operation MUST run.

## REQ-THEME-002: Strict hint validation

Only primary_color, background_color, logo, and background MAY appear as plan
steps. Colors MUST be six-digit hexadecimal values. Asset paths MUST remain
under their allowlisted app asset directories. Unknown or malformed hints MUST
be omitted.

## REQ-THEME-003: Authorized transport

The plan endpoint MUST use the AuthorizedAdminSetting PHP attribute and reject
unknown profiles. No apply, restore, upload, or arbitrary-setting route SHALL
exist in the current production slice.

## REQ-THEME-004: Private API containment

References to OCA\Theming classes MUST occur only below
lib/Infrastructure/Nextcloud/Compatibility. Compatibility classes MUST NOT be
registered or injected into a production controller, listener, or settings
service until their exact version cell is verified.

The current structural probe MUST NOT resolve private services or expose a
mutation method. Method presence alone MUST NOT be reported as a supported
capability.

Deleting the compatibility directory MUST leave profile discovery, activation,
rendering, settings access, and rollback functional.

## REQ-THEME-005: Fail-safe capability selection

A future automatic bridge MUST prefer a supported public OCP\Theming mutation
API. A private driver MAY be selected only for an exact tested version and
structural fingerprint. Unknown or failed capability checks MUST return a
manual-only result.

Raw writes to the Theming app configuration are forbidden as a compatibility
fallback.

## REQ-THEME-006: Transaction and recovery

Before any future automatic write, the bridge MUST validate the complete plan
and capture the current values. It MUST read back applied values, record the
operation, and attempt compensating rollback after partial failure.

Branding operation revisions MUST be independent from profile activation
revisions. A branding failure MUST NOT roll back a successfully published NL
Design profile automatically.
