# CSS Architecture Specification

## Problem
Defines the layered CSS architecture that transforms NL Design System tokens into Nextcloud-compatible theming. The architecture uses a design-system-driven approach: `design-systems.json` declares ordered stylesheet bundles, and `Application::boot()` loads the correct bundle for the active token set. Organization-specific tokens cascade correctly, incomplete token sets fall back gracefully, and NL Design System component tokens (using the `--utrecht-*` prefix) are bridged to the `--nldesign-*` namespace. The load order is critical: each layer builds on the previous one.

## Proposed Solution
Implement CSS Architecture Specification following the detailed specification. Key requirements include:
- See full spec for detailed requirements

## Scope
This change covers all requirements defined in the css-architecture specification.

## Success Criteria
- Standard CSS load order for nldesign design system
- Stock Nextcloud design system loads no stylesheets
- Token set CSS loaded after design system stylesheets
- Custom overrides always loaded last
- Conditional CSS loading
