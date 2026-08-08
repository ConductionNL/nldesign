---
sidebar_position: 3
---

# Theming Sync

## Status

The production slice currently does **not** apply Theming settings automatically when a token set is selected.

This app does inject profile CSS and controls the UI-level profile surface.
Theming sync is tracked as a separate, removable compatibility path and is
part of the “Slice 2/3” sequencing.

## Planned bridge fields

The compatibility bridge intends to map selected token metadata to these
Nextcloud-owned fields:

- Primary color
- Background color
- Logo

It is intentionally separated from the load-bearing profile path so that profile
selection, preview, and rollback continue to work if the bridge is unavailable.

## Why it matters

Nextcloud's built-in theming still controls surfaces that are not fully covered
by CSS injection (for example email assets and some client surfaces).
Until the bridge is enabled, those surfaces remain on their existing instance
defaults.

## How to use it now (temporary, manual)

The admin page exposes a read-only "Theming bridge (manual only)" panel.

For the selected token set, it renders `/settings/theming-plan`:

- the fields that appear in `token-sets.json` under `theming`;
- suggested values;
- and a plain instruction for the Nextcloud Theming app screen.

No automatic write occurs during profile activation.

Until a safe compatibility driver ships, this is an explicit operator step.

## Recovery trail

The admin page reads `/settings/profile-history` to show the recent activation/rollback
operations for this app instance.

This history is load-bearing for recovery decisions only and does not include private
Theming operations, which remain separate and non-automatic until Slice 3 is implemented.
