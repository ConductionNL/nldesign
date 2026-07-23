# Admin Settings — Configuration Bundle Controls Delta

**Spec refs**: `admin-settings` (canonical), `config-portability` (new, this change)
**Standards**: vanilla PHP template + vanilla JS architecture (REQ-ASET-008), WCAG 2.1 AA form
labeling (SC 1.3.1)

## ADDED Requirements

### Requirement: Configuration Bundle Controls

The settings panel MUST provide a "Configuration" block with a **Download configuration** button
(navigating to `GET /settings/config/export`) and an **Upload configuration** control
(multipart `POST /settings/config/import`). After an upload, the panel MUST display the
per-section import result — applied counts on success, or the complete all-or-nothing error
listing on validation failure — as a dismissible message, and on a successfully applied import
MUST refresh the panel so the token set dropdown, toggles, exclusion list, and token editor
reflect the imported state. The controls MUST follow the panel's vanilla-JS architecture (no
Vue, no build step), be localized with English source keys, and have labels programmatically
associated with the controls. The block SHOULD state that this bundle is the OTAP-promotion
path and covers the complete configuration, unlike the overrides-only download.

#### Scenario: Download configuration button

- GIVEN the settings panel is loaded
- WHEN the admin activates "Download configuration"
- THEN the browser MUST download `nldesign-config.json` containing the full bundle

#### Scenario: Successful upload refreshes panel state

- GIVEN a valid bundle whose token set differs from the live one
- WHEN the admin uploads it
- THEN the per-section applied counts MUST be shown
- AND after refresh the dropdown, toggles, exclusion list, and token editor MUST show the
  imported values

#### Scenario: Failed upload shows the full error listing and changes nothing

- GIVEN a bundle with a hard validation error in one section
- WHEN the admin uploads it
- THEN the panel MUST show the complete per-section error listing (HTTP 400 body)
- AND every control MUST still show the pre-upload configuration
