# Token Sets Specification

## Problem
Defines how the NL Design app discovers, validates, stores, and serves design token sets. Token sets are organization-specific CSS files that override default Rijkshuisstijl design tokens, enabling Dutch government organizations to apply their own visual identity to Nextcloud. The system uses filesystem-based discovery combined with a JSON manifest for metadata, and supports multiple design systems via a `design_system` field that determines which CSS stack is loaded.

## Proposed Solution
Implement Token Sets Specification following the detailed specification. Key requirements include:
- See full spec for detailed requirements

## Scope
This change covers all requirements defined in the token-sets specification.

## Success Criteria
- Token sets discovered from filesystem
- Metadata merged from manifest
- CSS file exists without manifest entry
- Manifest entry exists without CSS file
- Token sets sorted alphabetically
