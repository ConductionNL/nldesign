# Design: token-sets

## Context
Filesystem-based token set discovery, validation, storage, and serving. Token sets are organization-specific CSS files with JSON manifest metadata. Supports multiple design systems.

## Decisions
1. Filesystem discovery: scan css/tokens/ for .css files
2. Metadata from token-sets.json manifest indexed by id
3. design_system field determines CSS stack
4. Path traversal prevention in isValidTokenSet()
5. Alphabetical sort by name
