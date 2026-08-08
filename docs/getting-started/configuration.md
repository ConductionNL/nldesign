---
sidebar_position: 2
---

# Selecting a profile

Open **Administration settings → Theming → NL Design profiles**.

## Select

Fresh installs remain on **Native Nextcloud (no NL Design profile)**. Leave
that selected or choose a ready profile from the dropdown. Source-only inventory
entries are not shown. Activation and deactivation save immediately as
revision-checked operations under an exclusive server lock. The server refreshes
its app-config cache after acquiring that lock so the comparison uses current
canonical state. Open pages may need a reload before the new stylesheet stack
is visible.

If another administrator changed the profile after this page loaded, the stale update is rejected and the page reloads current state.

## Preview

The preview shows a primary colour only when the manifest declares a valid `theming.primary_color` hint. It does not simulate the full Nextcloud interface and is not compatibility or accessibility evidence.

## Nextcloud Theming hand-off

Some profiles show manual recommendations. They are not applied automatically. Review them against the instance's identity policy before copying them into Nextcloud's own Theming controls.

## Rollback

After changing profile A to profile B, **Roll back to previous profile** can
publish A again. The native state is also a valid rollback target. Rollback
checks the current revision and refuses a non-null target that is no longer a
ready catalogue entry.

There is no token editor, import/export workflow, or apply-token dialog in the current architecture.
The previous login-footer and menu-label selector experiments are also retired;
legacy app-config values for them are ignored.
