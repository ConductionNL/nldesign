# NL Design System Compliance — Delta Spec

## Purpose
Updates the existing nl-design shared spec to reflect the app's repositioning as a Nextcloud Theme Editor with NL Design System support. The token-set selection flow changes: selecting a token set now opens the apply dialog (promoting chosen values to custom overrides) rather than directly loading the token set CSS. Existing requirements for accessibility and WCAG compliance are unchanged.

## MODIFIED Requirements

### Requirement: Supported Token Sets
The nldesign app MUST support all organization token sets available in the configured token set registry. When an admin selects a token set, the system MUST open the token-set apply dialog to let the admin choose which values to promote to `custom-overrides.css`. The token set CSS file MUST continue to load as a base layer in the CSS stack regardless of which values the admin has promoted.

#### Scenario: Admin selects a token set
- GIVEN the admin is on the theming settings page
- WHEN the admin selects a token set from the dropdown
- THEN the token-set apply dialog MUST open (see `token-set-apply-dialog` spec)
- AND the CSS stack base layer MUST switch to the selected token set's CSS file
- AND the change MUST NOT take full visual effect until the dialog is confirmed or cancelled

#### Scenario: Incomplete token set renders correctly
- GIVEN a token set only defines a subset of available `--nldesign-*` tokens
- WHEN that token set is selected and the apply dialog is confirmed
- THEN the defined tokens MUST use the token-set values (or admin-chosen overrides)
- AND undefined tokens MUST fall back to sensible defaults from `defaults.css`
- AND no visual breakage MUST occur for undefined tokens

#### Scenario: Admin cancels the apply dialog
- GIVEN the admin opens the apply dialog for a new token set but clicks Cancel
- WHEN the dialog closes
- THEN the active token set MUST revert to the previously active set
- AND `custom-overrides.css` MUST remain unchanged
- AND no visual change MUST persist

## ADDED Requirements

### Requirement: App Identity
The app MUST be positioned as a **Nextcloud Theme Editor with NL Design System support** in its user-facing name, description, and admin settings heading. The scope is broader than NL Design loading: it encompasses editing any Nextcloud CSS custom property.

#### Scenario: Admin reads the settings panel heading
- GIVEN the admin opens the theming section
- WHEN the nldesign panel renders
- THEN the heading MUST communicate the dual capability: theme editing + NL Design presets
- AND the wording MUST NOT imply the app is exclusively for NL Design themes

### Requirement: Editable Nextcloud Tokens
The app MUST allow admins to directly edit Nextcloud `--color-*` CSS custom properties via the token editor panel, independently of any NL Design token set selection.

#### Scenario: Admin edits a token without selecting any NL Design token set
- GIVEN no NL Design token set is applied (or "none" is selected)
- WHEN the admin edits `--color-primary` in the token editor
- THEN the value MUST be written to `custom-overrides.css`
- AND it MUST take effect without requiring a token set to be active

#### Scenario: Custom override takes precedence over NL Design token set
- GIVEN `--color-primary: #AA0000` is in `custom-overrides.css`
- AND the active NL Design token set maps `--color-primary` to `#154273`
- WHEN the browser resolves `--color-primary`
- THEN the resolved value MUST be `#AA0000` (custom override wins)
