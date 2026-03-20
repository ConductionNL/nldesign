# Token-Set Apply Dialog Specification

## Problem
Defines the modal dialog shown when an admin selects a new NL Design token set. The dialog shows which Nextcloud CSS variable values would change (resolved current value vs the value from the new token set), lets the admin check or uncheck individual changes, and writes only the checked values to `custom-overrides.css`. The NL Design token set CSS file itself is never applied directly.


## Proposed Solution
Implement Token-Set Apply Dialog Specification following the detailed specification. Key requirements include:
- Requirement: Dialog Trigger
- Requirement: Resolved Value Comparison
- Requirement: Checkbox Selection
- Requirement: Live Preview in Dialog
- Requirement: Apply Action

## Scope
This change covers all requirements defined in the token-set-apply-dialog specification.

## Success Criteria
- Admin selects a new token set
- Admin selects the same token set that is already active
- Dialog shows only changed tokens
- Current value is from custom-overrides.css
- Resolved value is obtained from CSS custom property API
