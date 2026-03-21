# Token Editor UI Specification

## Problem
Provides a tabbed admin settings panel for browsing and editing all editable Nextcloud CSS custom properties (`--color-*`) with live preview and per-token reset controls. Changes are previewed in the browser before being committed to `custom-overrides.css`.


## Proposed Solution
Implement Token Editor UI Specification following the detailed specification. Key requirements include:
- Requirement: Token Editor Panel
- Requirement: Functional Tab Groups
- Requirement: Excluded Token Registry
- Requirement: Editable Token Input
- Requirement: Live Preview

## Scope
This change covers all requirements defined in the token-editor-ui specification.

## Success Criteria
- Admin opens settings
- Non-admin user visits settings
- Admin selects Login page tab
- Every editable token appears in exactly one tab
- Admin attempts to set excluded token via API
