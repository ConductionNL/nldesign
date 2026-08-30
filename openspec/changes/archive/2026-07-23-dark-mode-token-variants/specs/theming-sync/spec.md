# Theming Sync — Dark Logo Delta

**Spec refs**: `theming-sync` (REQ-SYNC-001, REQ-SYNC-004, REQ-SYNC-010), `dark-mode` (new, this
change), nextcloud/server#47357 (open upstream request for a dark logo slot in core theming)
**Standards**: OWASP Path Traversal Prevention, WCAG 2.1 AA (logo visibility on dark surfaces)

## MODIFIED Requirements

### Requirement: Theming Metadata in Token Sets

The system MUST support an optional `theming` object in a token set manifest that defines values
suitable for synchronization with Nextcloud's built-in theming system.

#### Scenario: Token set with full theming metadata

- GIVEN the `token-sets.json` entry for `rijkshuisstijl` has a `theming` object
- WHEN the metadata is read
- THEN the `theming` object MUST contain `primary_color` (hex string, e.g. `"#154273"`)
- AND it MUST contain `background_color` (hex string, e.g. `"#F5F6F7"`)
- AND it MAY contain `logo` (relative path, e.g. `"img/logos/rijkshuisstijl.svg"`)
- AND it MAY contain `logo_dark` (relative path to a dark-surface logo variant, e.g.
  `"img/logos/rijkshuisstijl-dark.svg"`)

#### Scenario: Token set with logo and background theming

- GIVEN a token set entry has `theming.logo` and `theming.background` fields
- WHEN the metadata is read
- THEN `logo` MUST be a relative path within `img/logos/`
- AND `background` MUST be a relative path within `img/backgrounds/`
- AND both paths MUST reference files that exist in the nldesign app directory

#### Scenario: Dark logo path validated like the light logo

- GIVEN a token set entry has a `theming.logo_dark` field
- WHEN the metadata is validated (manifest audit or sync request)
- THEN `logo_dark` MUST satisfy the same rules as `logo`: no path traversal, path within
  `img/logos/`, file exists in the app directory
- AND a `logo_dark` value violating any rule MUST be rejected with the same error shapes
  REQ-SYNC-004 defines for `logo`

#### Scenario: Dark logo is not synced to Nextcloud core theming

- GIVEN a token set with `theming.logo_dark`
- WHEN theming sync is applied
- THEN `logo_dark` MUST NOT be passed to `ImageManager::updateImage()` (Nextcloud core has a
  single logo slot — a dark slot is the open upstream request nextcloud/server#47357)
- AND the dark logo MUST instead be delivered by nldesign's generated dark variant stylesheet
  (see the `dark-mode` spec)

#### Scenario: Token set without theming metadata

- GIVEN a token set entry in `token-sets.json` has no `theming` key
- WHEN the token set is retrieved via the API
- THEN the `theming` field MUST be absent from the response
- AND theming sync MUST NOT be offered for this token set in the admin UI

#### Scenario: Theming metadata included in API response

- GIVEN a token set with theming metadata is retrieved via `GET /settings/tokensets`
- WHEN the response is generated
- THEN the token set object MUST include the `theming` object with all its fields, including
  `logo_dark` when present
- AND the frontend can use this data to display the theming sync dialog

#### Scenario: Partial theming metadata accepted

- GIVEN a token set has `theming: {"primary_color": "#004699"}` with no background_color, logo,
  or logo_dark
- WHEN the metadata is read
- THEN only the `primary_color` MUST be available for syncing
- AND missing fields MUST NOT cause errors

### Requirement: Theming Sync Dialog (Frontend)

The admin JavaScript MUST show a confirmation dialog when switching to a token set that has
theming metadata, allowing the admin to review and approve theming changes.

#### Scenario: Dialog shown for token set with theming metadata

- GIVEN the admin selects a token set that has a `theming` object
- WHEN the token set selection is saved
- THEN the JavaScript MUST call `checkAndShowThemingDialog()`
- AND a modal dialog MUST appear showing the proposed theming changes

#### Scenario: Dialog shows color comparison

- GIVEN the theming dialog opens
- WHEN the current theming values differ from the token set's proposed values
- THEN the dialog MUST show current vs proposed colors with visual swatches
- AND the admin MUST be able to see the difference before confirming

#### Scenario: Dialog offers the dark logo when present

- GIVEN the selected token set's `theming` object contains `logo_dark`
- WHEN the theming dialog opens
- THEN the dialog MUST render a dark-logo preview row (the dark logo shown on a dark swatch
  background)
- AND the row MUST carry an explanatory note (i18n key in English) that the dark logo is applied
  by nldesign's dark stylesheet because Nextcloud core has no dark logo slot
- AND confirming the dialog MUST NOT add a `logo_dark` field to the
  `POST /settings/theming` request

#### Scenario: Dialog omits the dark logo row when absent

- GIVEN the selected token set's `theming` object has no `logo_dark`
- WHEN the theming dialog opens
- THEN no dark-logo row MUST be rendered

#### Scenario: Dialog not shown for sets without theming metadata

- GIVEN the admin selects a token set without a `theming` object
- WHEN the token set selection is saved
- THEN no theming sync dialog MUST be shown
- AND the token set MUST be applied without further prompts

#### Scenario: Admin confirms theming sync

- GIVEN the theming dialog is shown
- WHEN the admin clicks the confirm/apply button
- THEN `POST /apps/nldesign/settings/theming` MUST be called with the proposed values
- AND on success, Nextcloud's theming MUST be updated

#### Scenario: Admin cancels theming sync

- GIVEN the theming dialog is shown
- WHEN the admin clicks cancel
- THEN the dialog MUST close
- AND no theming changes MUST be applied
- AND the token set selection MUST still take effect (CSS tokens change, but Nextcloud core
  theming remains unchanged)
