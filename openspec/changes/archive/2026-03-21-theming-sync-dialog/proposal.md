# Theming Sync Dialog Specification

## Problem
After an admin selects a different token set in nldesign, offer to automatically update Nextcloud's built-in theming values (primary color, background color, logo, background image) to match the selected token set, preventing a split-brain theming state where CSS tokens and Nextcloud theming are out of sync.

## Proposed Solution
Implement Theming Sync Dialog Specification following the detailed specification. Key requirements include:
- Requirement: Theming Metadata in Token Sets
- Requirement: Get Current Theming Values Endpoint
- Requirement: Update Theming Values Endpoint
- Requirement: Confirmation Dialog After Token Set Change
- Requirement: Dialog Preview Boxes

## Scope
This change covers all requirements defined in the theming-sync-dialog specification.

## Success Criteria
- Token set with full theming metadata
- Token set without theming metadata
- Token set with partial theming metadata
- Retrieve current theming values
- Unauthenticated access denied
