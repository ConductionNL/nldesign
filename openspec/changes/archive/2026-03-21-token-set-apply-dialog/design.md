# Design: token-set-apply-dialog

## Context
Modal dialog showing which Nextcloud CSS variables would change when selecting a new token set. Checkbox selection per change, writes to custom-overrides.css.

## Decisions
1. Resolved value comparison via getComputedStyle()
2. Preview endpoint provides server-side resolved values
3. Only checked values written to custom-overrides.css
4. Token set CSS files are never directly applied
