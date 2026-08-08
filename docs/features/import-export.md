---
sidebar_position: 4.3
---

# Import and export

## Status: deferred

The app currently has no import/export endpoint or UI.

The removed prototype treated CSS text as instance state and wrote it below the installed app path. A future portable format must instead be a validated, versioned data document. It must reject arbitrary selectors, properties, paths, app ids, and Nextcloud configuration keys; importing must create a reviewable plan before publishing state.

Do not write `custom-overrides.css` into `custom_apps/nldesign`. Such files are outside the supported runtime model and can disappear or break upgrades.
