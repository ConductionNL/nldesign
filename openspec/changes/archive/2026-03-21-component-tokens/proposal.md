# Component Tokens Specification

## Problem
Introduces component-level NL Design System tokens using the `--nldesign-component-*` prefix, with a temporary bridge file that maps the current `--utrecht-*` component tokens to the nldesign namespace.

## Proposed Solution
Implement Component Tokens Specification following the detailed specification. Key requirements include:
- Requirement: NLDesign Component Token Prefix
- Requirement: Utrecht Bridge File
- Requirement: Component Token Categories
- Requirement: Component Token Defaults

## Scope
This change covers all requirements defined in the component-tokens specification.

## Success Criteria
- Button component token
- Heading component token
- Bridge maps Utrecht tokens to nldesign
- Bridge falls back to defaults
- Bridge file is clearly marked as temporary
