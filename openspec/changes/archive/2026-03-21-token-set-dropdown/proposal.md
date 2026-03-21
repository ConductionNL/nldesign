# Token Set Dropdown Specification

## Problem
Replace the radio button list for token set selection with a searchable dropdown (`<select>`) that scales to 400+ entries, improving usability as the number of available token sets grows.

## Proposed Solution
Implement Token Set Dropdown Specification following the detailed specification. Key requirements include:
- Requirement: Dropdown Token Set Selector
- Requirement: Token Sets Sorted Alphabetically
- Requirement: Preview Updates on Selection

## Scope
This change covers all requirements defined in the token-set-dropdown specification.

## Success Criteria
- Dropdown renders with all token sets
- Dropdown is searchable via browser native behavior
- Token set selection triggers save
- Dropdown handles 400+ entries
- Token sets appear in alphabetical order
