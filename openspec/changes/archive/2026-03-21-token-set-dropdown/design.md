# Design: token-set-dropdown

## Context
Replace radio button list with searchable <select> dropdown for token set selection, scaling to 400+ entries.

## Decisions
1. Native HTML <select> element for browser-native search
2. Alphabetical sorting in TokenSetService
3. Selection triggers save + optional apply dialog
