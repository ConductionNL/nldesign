---
status: reviewed
reviewed_date: 2026-08-08
---

# Administrator Settings Specification

## REQ-ADMIN-001: Delegated settings panel

The app MUST provide an IDelegatedSettings panel in Nextcloud's theming section.
It MUST render native Nextcloud plus the validated ready-profile catalogue,
canonical profile state, rollback availability, manual hand-off, and history.

An empty catalogue MUST preserve an enabled native-Nextcloud selection and show
an explicit message rather than a broken control.

## REQ-ADMIN-002: Least-privilege authorization

Every settings controller action MUST use the AuthorizedAdminSetting PHP
attribute bound to the Admin setting. The delegated config allowlist MUST be
limited to the app-owned state keys used by this panel.

Unauthenticated, ordinary-user, and unauthorized delegated-admin requests MUST
be rejected by Nextcloud before controller logic runs.

## REQ-ADMIN-003: Safe profile writes

Profile publication, deactivation, and rollback MUST accept only a valid 20-character
lowercase hexadecimal expected revision. Missing, malformed, stale, and
source-only values MUST not change state. Compare-and-write MUST run under an
exclusive Nextcloud lock and MUST clear the public Nextcloud app-config cache
before reading canonical state. Invalid profile ids MUST be rejected before
publication.

Successful responses MUST return the nullable current profile, next revision,
previous profile, and explicit rollback availability. Conflicts MUST return
HTTP 409 and the current revision when available. Lock or canonical-persistence
unavailability, including cache-refresh failure, MUST return HTTP 503 and MUST
not report success.

## REQ-ADMIN-004: Safe browser transport

All writes MUST be same-origin JSON requests carrying Nextcloud's CSRF request
token. Non-2xx responses and malformed successful JSON MUST be treated as
failures. A state-changing success response MUST contain a valid nullable
profile id, revision, previous target, and rollback flag before the browser
updates local state.

Response values and history fields MUST be inserted as text, not interpreted as
HTML. External links MUST use noopener and noreferrer.

## REQ-ADMIN-005: Race and failure handling

Controls MUST be disabled during a write. A failed profile save MUST restore
the active profile in both selector and preview. A revision conflict MUST cause
canonical state to be reloaded before another write.
Overlapping manual-plan and history reads MUST ignore superseded responses.

The UI MUST expose status changes through an aria-live status region.

## REQ-ADMIN-006: Honest capability surface

The panel MUST describe core-Theming values as manual recommendations.
Selecting a profile MUST NOT trigger a Theming mutation. Token editing,
import/export, asset upload, and automatic apply controls MUST not be shown
until their corresponding application services and recovery contracts exist.
The retired login-footer and app-menu selector controls MUST NOT be shown.

## REQ-ADMIN-007: State and history

The initial page MUST use server-rendered canonical state and MUST not issue a
redundant initial profile GET. The operation history MUST be bounded by the
state service. Rollback MUST remain unavailable when no previous snapshot
exists or its non-null target is no longer ready. Native Nextcloud is a valid
target.
