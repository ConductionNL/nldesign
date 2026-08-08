---
sidebar_position: 4
---

# Admin settings

The app adds **NL Design profiles** to Nextcloud's Theming administration section. Every API action behind this panel requires Nextcloud's `AuthorizedAdminSetting` permission for this delegated settings class.

## Profile selector

The selector lists entries only when all conditions are true:

- the id is declared in `token-sets.json`; and
- the record is `ready` with projection `nextcloud-core-v1`; and
- a readable stylesheet resolves inside `css/tokens/`.

Changing the selector saves immediately. The browser sends the revision it
observed; the server acquires an exclusive Nextcloud lock, clears Nextcloud's
public app-config cache, reads canonical state, and then compares and writes.
A concurrent change produces a conflict and reloads canonical state instead of
overwriting it. Temporary lock or cache-refresh failure changes nothing.

**Native Nextcloud (no NL Design profile)** is always present. It is the
fresh-install state and a typed deactivation operation, not a synthetic
organisation profile.

## Colour preview

The small preview uses `theming.primary_color` only when the manifest provides a valid six-digit hex value. Profiles without that optional hint use a neutral/current-Nextcloud fallback. The preview is not a screenshot or compatibility proof.

## Rollback and history

After an actual profile or native-state transition, the app retains one
previous snapshot. Rollback is revision-checked. A native target remains valid;
a profile target is disabled if it is no longer ready.

The page also shows the ten most recent profile transitions stored by this app.
It records a generic operation source rather than a user identifier: this is a
small recovery trail, not a general security audit log.

Manual-plan and history reads use latest-request-wins guards. An older response
cannot overwrite recommendations for a profile selected while that request was
in flight, and state-changing responses are validated before their revision is
accepted by the browser.

## Nextcloud Theming hand-off

The hand-off panel renders allowlisted recommendations from the profile manifest. It never executes them. A logo, background, or colour remains owned by Nextcloud Theming until an administrator applies it there.

## Retired presentation experiments

The panel no longer exposes login-footer or app-menu CSS toggles. The former
footer rule also hid the instance identity link; the menu rule had no adequate
cross-version browser evidence. Legacy stored values are ignored. See
[Retired presentation options](./toggles.md) for the cleanup commands and the
evidence required before a bounded surface adapter could replace either idea.

## Not present in architecture v1

The settings page does not contain a token editor, import/export controls, or an apply-token comparison dialog. Those older documentation claims were removed because the associated runtime path wrote into the installed app package.
