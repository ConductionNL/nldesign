# Admin Settings Specification

## Problem
Defines the admin settings panel for the NL Design app. The settings panel is located in Nextcloud's administration area under the Theming section. It provides controls for selecting the active token set, toggling the hide slogan feature, toggling show menu labels, and previewing the selected theme. The UI is built with vanilla PHP templates and vanilla JavaScript (no Vue or webpack). Additionally, the panel hosts the token editor for customizing individual Nextcloud CSS tokens, and triggers the theming sync dialog when a token set with theming metadata is selected.

## Proposed Solution
Implement Admin Settings Specification following the detailed specification. Key requirements include:
- See full spec for detailed requirements

## Scope
This change covers all requirements defined in the admin-settings specification.

## Success Criteria
- Settings panel appears in admin area
- Settings panel position relative to Nextcloud theming
- Settings panel is absent when app is disabled
- Settings panel loads template with all parameters
- Token sets include design system metadata
