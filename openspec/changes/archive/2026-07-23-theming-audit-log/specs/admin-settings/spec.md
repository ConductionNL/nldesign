# Admin Settings — Audit Log Panel Delta

**Spec refs**: `admin-settings` (canonical), `theming-audit` (new, this change)
**Standards**: vanilla PHP template + vanilla JS architecture (REQ-ASET-008), OWASP XSS
prevention (audit content is attacker-influenceable via set names and MUST be escaped)

## ADDED Requirements

### Requirement: Theming Audit Log Panel

The settings panel MUST include a "Theming audit log" block showing the most recent 20 audit
entries in a table with columns Timestamp, User, Action, and Details, populated on panel load
from `GET /settings/audit?limit=20`, plus a **Download full log** control pointing at
`GET /settings/audit/export`. The table MUST render all entry values as text (escaped — entry
details can contain admin-supplied names), show an empty-state message when no entries exist,
and follow the panel's vanilla-JS, no-build-step architecture with localized English-source
strings.

#### Scenario: Recent entries are shown after a change

- GIVEN the admin has just changed the token set
- WHEN the settings panel reloads
- THEN the audit table's top row MUST show the `token_set_changed` entry with the admin's uid,
  the old and new set ids, and the timestamp

#### Scenario: Empty state

- GIVEN no audit entries exist yet
- WHEN the panel loads
- THEN the block MUST show a localized empty-state message instead of an empty table

#### Scenario: Entry content is escaped

- GIVEN an audit entry whose details contain markup-like characters (e.g. a custom set named
  `<img src=x>`)
- WHEN the table renders
- THEN the characters MUST appear as literal text and MUST NOT be parsed as HTML

#### Scenario: Full log download from the panel

- GIVEN entries exist
- WHEN the admin activates "Download full log"
- THEN the browser MUST download `nldesign-audit.jsonl` containing every retained entry
