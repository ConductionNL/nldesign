# Custom CSS Overrides Specification

## Problem
Defines the CSS file persistence layer for user-defined token customizations. `custom-overrides.css` is the single write target for all theme editor output. It is loaded last in the CSS stack so user intent always wins. NL Design token set CSS files are read-only presets and are never modified.


## Proposed Solution
Implement Custom CSS Overrides Specification following the detailed specification. Key requirements include:
- Requirement: Custom Overrides File
- Requirement: CSS Stack Load Order
- Requirement: File Format
- Requirement: Read/Write PHP Endpoint
- Requirement: No Database Storage

## Scope
This change covers all requirements defined in the custom-css-overrides specification.

## Success Criteria
- File does not exist on fresh install
- File exists with custom tokens
- Custom override wins over NL Design token set
- Missing file does not break stack
- File is written by the save endpoint
