---
sidebar_position: 4.1
---

# Token editor

## Status: deferred

Architecture v1 does not expose an individual-token editor.

The earlier prototype stored generated CSS as `css/custom-overrides.css` inside the installed app. That is not a safe persistence boundary: package directories may be read-only, upgrades replace them, and one instance's mutable state must not become distributable source.

A future editor must first define:

- a typed and versioned override schema;
- validation for values and supported semantic tokens;
- app-config or app-data persistence;
- preview, publish, rollback, and migration semantics; and
- accessibility and dark/high-contrast interaction tests.

Until then, profile CSS is immutable build output. Local source edits are development changes, not an administrator feature.
