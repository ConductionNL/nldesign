# Tasks: rename-nldesign-to-themiq

> App identity + config migration + fleet sweep (ADR-032 `kind: mixed`).
> Checkbox budget: 4 tasks × 2 = 8 unindented `- [ ]` lines (cap 20).

## Implementation Tasks

### Task 1: Move the app identity
- **spec_ref**: `openspec/changes/rename-nldesign-to-themiq/specs/themiq-identity/spec.md#requirement-the-app-identity-must-move-as-one-unit`
- **files**: `appinfo/info.xml`, `lib/**`, `src/**`, `package.json`, `.releaserc.json`, `l10n/*.json`, `.github/workflows/*`
- **acceptance_criteria**:
  - App id, `OCA\Thematiq` namespace, appstore identity, l10n domain (37 files), asset prefixes and release config all change together
  - A repo search finds no stale identity except the transition alias
  - Assets resolve on a RENDERED page, not merely in the source — a wrong app id gives a 404 with no PHP error, so the page renders unstyled and reads as a CSS bug
- [ ] Implement
- [ ] Test

### Task 2: Migrate per-instance configuration
- **spec_ref**: `openspec/changes/rename-nldesign-to-themiq/specs/themiq-identity/spec.md#requirement-per-instance-configuration-must-follow-the-rename`
- **files**: `lib/Repair/MigrateAppConfigToThemiq.php`, `tests/Unit/Repair/MigrateAppConfigToThemiqTest.php`
- **acceptance_criteria**:
  - Copies `oc_appconfig` rows from the old id to the new; an instance with a configured theme keeps it
  - REPORTS the row count copied, so a zero-row run is distinguishable from a job that never ran — without this, every deployment discovers the loss separately in production as "all our theming reverted to default"
  - Idempotent: a second run copies zero and writes nothing
  - Registered as a repair step, never on the install hook
- [ ] Implement
- [ ] Test

### Task 3: Transition alias and its scheduled removal
- **spec_ref**: `openspec/changes/rename-nldesign-to-themiq/specs/themiq-identity/spec.md#requirement-the-old-app-id-must-keep-working-for-one-release`
- **files**: `lib/AppInfo/Application.php`, `openspec/changes/remove-nldesign-alias/`
- **acceptance_criteria**:
  - A consumer still asking for the old id resolves to the renamed app
  - The deprecation log names the CALLING app, so the remaining migration is a list rather than a search
  - The removal change is written in THIS change — a deprecation with no scheduled removal becomes permanent
- [ ] Implement
- [ ] Test

### Task 4: Fleet sweep, counted before and after
- **spec_ref**: `openspec/changes/rename-nldesign-to-themiq/specs/themiq-identity/spec.md#requirement-fleet-callers-must-be-migrated-and-counted`
- **files**: fleet-wide; `portaliq/appinfo/info.xml` dependency declaration
- **acceptance_criteria**:
  - The 468 files outside this repo that named the old id (measured 2026-08-15) are updated
  - The post-sweep count is RE-MEASURED, not inferred from having done the sweep
  - Portaliq's dependency names `themiq` (ADR-086 §6)
  - Each app's own checks pass after its references change — a rename that breaks a consumer's build is not a rename, it is an outage with a changelog entry
- [ ] Implement
- [ ] Test
