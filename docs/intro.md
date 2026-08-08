---
sidebar_position: 0
slug: /
---

# NL Design profiles

NL Design is a pre-release Nextcloud administration app for selecting a statically gated design profile and projecting a bounded CSS contract onto Nextcloud core.

## Current scope

- Select from 8 ready projections. The package also retains 32 source-only inventory entries that cannot be activated.
- Save profile state with revision checks, rollback, and bounded history.
- Preview a declared primary-colour hint.
- Read manual recommendations for selected Nextcloud Theming fields.
- Return explicitly to native Nextcloud presentation under the same revision
  and rollback contract.

Profile selection does **not** automatically change settings owned by Nextcloud Theming. Architecture v1 also has no token editor, import/export workflow, or apply dialog.
The former login-slogan and app-menu-label selector experiments are retired and
are not configurable features.

## Evidence and identity

The catalogue is an inventory, not a claim that every source is official, complete, endorsed, legally usable for every operator, or verified across every Nextcloud app. Even `ready` means only that the bounded projection contract is present; organisational identity rights and release compatibility evidence remain separate from the app's EUPL-1.2 code licence.

## Start here

1. [Install a source or packaged build](getting-started/installation.md).
2. [Select and verify a profile](getting-started/configuration.md).
3. Read the [current feature ledger](feature-ledger.md) before relying on a capability in production.
4. Use the [architecture](architecture.md) and [roadmap](roadmap.md) for development decisions.
