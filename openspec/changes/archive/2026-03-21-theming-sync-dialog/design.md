# Design: theming-sync-dialog

## Context
Modal dialog shown when admin selects a new token set, offering to sync Nextcloud theming values (primary color, background, logo) to match the selected set.

## Decisions
1. Dialog shows preview of changes with current vs proposed values
2. Checkbox per change for selective application
3. Triggered by token set dropdown change event
