# Admin Settings — Group Theming Section Delta

**Spec refs**: `admin-settings`, `per-group-theming` (new, this change), `per-app-theming`
(existing list-section pattern)
**Standards**: WCAG 2.2 AA SC 1.3.1 (labels), SC 2.1.1 (keyboard-operable reorder); Nextcloud
vanilla-template admin settings (no Vue, no build step — REQ-ASET-008)

## ADDED Requirements

### Requirement: Group Theming Mapping Section

The settings panel MUST include a "Group theming" section, implemented as vanilla PHP template
plus vanilla JavaScript (consistent with REQ-ASET-008 and the existing per-app theming list
pattern in `js/admin.js`), that lists the ordered group→token-set mapping rows and lets the
admin add, remove, and reorder them. Each row MUST contain: a group `<select>` (options from
the groups returned by `GET /settings/group-theming`, showing display names), a token-set
`<select>` (same option source as the main token-set dropdown), keyboard-operable move-up /
move-down buttons for priority reordering (no drag-and-drop requirement), and a remove button.
Every control MUST have an accessible name (associated `<label>` or `aria-label`). The section
MUST include an "Add mapping" button, a Save button that POSTs the full ordered mapping to
`/settings/group-theming`, a feedback element announcing success or the server's per-entry
validation error, and a localized hint stating (a) that row order is priority order — the first
matching group wins — and (b) the core-theming limitation: logo, mail templates and Nextcloud
core branding follow the instance default set, not the group set. Rendering an empty state
("No group mappings configured") is required when the mapping is empty. All strings use
`$l->t()` / the JS translation helpers with English source keys.

#### Scenario: Section lists mappings in priority order

- GIVEN a stored mapping `[{gemeente-a → amsterdam}, {gemeente-b → utrecht}]`
- WHEN the admin opens the settings panel
- THEN the group theming section MUST render two rows in that order
- AND each row MUST show the group display name and the token set name in its selects

#### Scenario: Admin adds, reorders, and saves a mapping

- GIVEN the admin adds a row mapping `gemeente-b` to `utrecht` and moves it above an existing
  row using the move-up button
- WHEN they press Save
- THEN the POST body MUST contain the rows in the displayed order
- AND on success the feedback element MUST announce the save
- AND after a panel reload the rows MUST render in the saved order

#### Scenario: Server validation error is surfaced per entry

@e2e exclude error branch — vitest with a mocked 422 response
- GIVEN the server rejects the save with HTTP 422 naming an offending entry
- WHEN the response is handled
- THEN the feedback element MUST display the localized error including the offending
  group/set
- AND the rows MUST remain editable with no silent state reset

#### Scenario: Reordering works with the keyboard alone

- GIVEN focus is on a row's move-up button
- WHEN the admin activates it with Enter or Space
- THEN the row MUST swap with its predecessor
- AND focus MUST remain on the moved row's move-up button (WCAG 2.1.1, no keyboard trap)

#### Scenario: Empty state and limitation hint are shown

- GIVEN no group mappings are configured
- WHEN the section renders
- THEN it MUST show the localized empty state
- AND the hint stating priority ordering and the instance-global core-theming limitation MUST
  be visible whether or not mappings exist
