---
status: reviewed
reviewed_date: 2026-08-08
---

# Administrator Settings — Technical Design

## Scope

The settings panel controls the app-owned profile plane:

- select a ready profile;
- return explicitly to native Nextcloud;
- roll back once to the previous profile;
- inspect the active revision and bounded operation history;
- inspect manual recommendations for Nextcloud Theming.

It does not edit design-token files, import packages, upload assets, or apply
Nextcloud Theming settings.

## Server-side composition

Admin implements IDelegatedSettings and is placed in the theming section. It
receives TokenSetService and ProfileStateService.
getForm obtains the validated catalogue and canonical profile state. When a
stored profile is unavailable, it renders an explicit disabled placeholder so
the administrator must select a replacement; it does not display another
organisation as if it were active.

The setting declares only these delegated app-config patterns:

- active_profile_state, active_profile_revision, profile_state_history, and
  token_set.

The template receives data, renders labels with Nextcloud escaping helpers, and
contains no executable profile content.

## HTTP boundary

SettingsController is a thin admin-only transport. Every public action carries
AuthorizedAdminSetting(settings: Admin::class).

| Method and route | Purpose |
| --- | --- |
| GET /settings/tokenset | Canonical profile state and metadata |
| POST /settings/tokenset | Revision-checked profile publication |
| POST /settings/deactivate | Revision-checked native-Nextcloud transition |
| POST /settings/rollback | Revision-checked one-step rollback |
| GET /settings/profile-history | Bounded operation history |
| GET /settings/theming-plan | Non-executing manual hand-off |

There is no arbitrary config route and no automatic Theming route.

Profile publication validates both the ready profile id and required revision;
deactivation is a separate typed action and also requires the revision.
The actor is a bounded generic operation source; this recovery trail does not
retain a Nextcloud user id. The Nextcloud mutation guard owns exclusive locking
and public app-config cache refresh; the state service performs transitions only
after that refresh. The controller maps conflicts to 409 and lock, cache, or
canonical persistence unavailability to 503.

## Browser behavior

js/admin.js uses one request helper for same-origin JSON:

- includes credentials;
- includes Nextcloud's request token and JSON content type on writes;
- rejects non-success HTTP responses, malformed successful JSON, and invalid
  profile-state response shapes;
- renders server data with textContent rather than HTML.

While a request is in flight, profile and rollback controls are
disabled. A failed profile save restores the active selection and preview.
A revision conflict reloads canonical state. Rollback is enabled for a native target or when the previous profile
still exists in the catalogue.

Initial state comes from the rendered template; JavaScript does not perform a
duplicate initial profile request. Manual recommendations and history are
loaded independently. Sequence counters make those reads latest-request-wins so
an older response cannot overwrite data for a newly selected profile.

## Failure policy

Catalogue, plan, and history failures are visible but do not grant write
capability. A profile write never silently retries after a conflict. The
settings page remains useful if the private compatibility prototype is deleted.
