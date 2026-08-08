# NL Design integration test guide

Never store test credentials in this repository. Use an isolated Nextcloud instance and obtain administrator access through the environment's secret mechanism.

## Before browser testing

```bash
composer check
npm test
npm run build
```

Record the exact Nextcloud version, PHP version, enabled apps, browser, colour scheme, and accessibility preferences.

## Admin flow

1. Open **Administration settings → Theming → NL Design profiles**.
2. Confirm the selector contains the manifest-backed catalogue and no invalid/orphan entry.
3. Change to a profile with a primary-colour hint; verify save, revision update, preview, history, and manual hand-off content.
4. From a second admin session, change the profile, then try saving the stale first session. Verify HTTP 409 behavior and UI resynchronization.
5. Roll back and verify the target, new revision, and history.
6. Deactivate the profile to native Nextcloud; verify the new revision and
   history entry, then verify rollback can restore the profile.

## Surface flow

For each claimed Nextcloud major, test at least:

- login and password-reset surfaces;
- Files and core settings;
- one Vue-heavy app and one legacy app in the supported matrix;
- explicit light/dark, system-following default, high-contrast, and
  OpenDyslexic preferences;
- narrow/mobile viewport and keyboard focus;
- failure with a malformed stored state and a missing selected stylesheet.

Check browser console, failed asset requests, Nextcloud logs, readable focus indicators, contrast of projected pairs, clipping, and selector regressions.

## Explicitly absent

Do not test an automatic Theming sync dialog, token editor, apply-token dialog, or import/export endpoint: architecture v1 does not expose them. Do inspect the read-only Nextcloud Theming hand-off.

## Evidence

Store screenshots, HTTP results, and the tested version/app matrix as dated artifacts. A successful local OCP analysis or one browser session is not a release compatibility claim.
