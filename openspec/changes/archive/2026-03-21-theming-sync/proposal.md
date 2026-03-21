# Theming Sync Specification

## Problem
Defines how the NL Design app synchronizes design token values with Nextcloud's built-in theming system. When a token set includes theming metadata (primary color, background color, logo, background image), the app can update Nextcloud's `ThemingDefaults` and `ImageManager` to ensure consistency between the NL Design CSS layer and Nextcloud's core theming (which controls background images, server branding, and email templates). This prevents a split-brain state where CSS tokens show one color scheme but Nextcloud's internal theming references another.

## Proposed Solution
Implement Theming Sync Specification following the detailed specification. Key requirements include:
- See full spec for detailed requirements

## Scope
This change covers all requirements defined in the theming-sync specification.

## Success Criteria
- Token set with full theming metadata
- Token set with logo and background theming
- Token set without theming metadata
- Theming metadata included in API response
- Partial theming metadata accepted
