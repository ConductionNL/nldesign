# Admin Settings — Preview Controls Delta

**Spec refs**: `admin-settings` (canonical), `theme-preview` (new, this change)
**Standards**: WCAG 2.1 AA form labeling (SC 1.3.1), vanilla PHP template + vanilla JS
architecture (REQ-ASET-008)

## ADDED Requirements

### Requirement: Session Preview Controls

The settings panel MUST provide a "Preview in my session" button adjacent to the token set
dropdown that starts a per-user preview of the currently selected set (via
`POST /settings/preview`) instead of applying it instance-wide. While the requesting admin has
an active preview, the panel MUST display an active-preview row showing the previewed set's name
with Publish and Discard controls, and `js/admin.js` MUST detect the active preview on load so a
banner-initiated Publish opens the existing apply-dialog flow for the previewed set (whose
confirmation calls `POST /settings/preview/publish` rather than `POST /settings/tokenset`). All
new controls MUST follow the panel's existing vanilla-JS architecture (no Vue, no build step),
be localized via `$l->t()` / `t('nldesign', …)` with English source keys, and carry labels
programmatically associated with their controls.

#### Scenario: Preview button starts a session-only preview

- GIVEN the admin selects "Gemeente Amsterdam" in the token set dropdown
- WHEN they activate "Preview in my session"
- THEN the client MUST call `POST /settings/preview` with the selected id
- AND the instance-wide `token_set` app value MUST NOT change
- AND after reload the panel MUST show the active-preview row for "Gemeente Amsterdam"

#### Scenario: Active-preview row offers Publish and Discard

- GIVEN the requesting admin has an active preview
- WHEN the settings panel loads
- THEN a row MUST show the previewed set's name and Publish and Discard controls
- AND Discard MUST call `DELETE /settings/preview` and refresh the panel state
- AND Publish MUST run the existing apply dialog (and theming-sync dialog when applicable)
  before calling `POST /settings/preview/publish`

#### Scenario: Panel without an active preview is unchanged

- GIVEN the requesting admin has no active preview
- WHEN the settings panel loads
- THEN no active-preview row MUST render and every pre-existing control MUST behave exactly as
  specified by the other requirements of this spec
