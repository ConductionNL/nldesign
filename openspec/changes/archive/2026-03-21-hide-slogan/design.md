# Design: hide-slogan

## Context
Removes Nextcloud slogan from login page via conditionally loaded CSS. Dutch government organizations need clean branded login pages.

## Decisions
1. Boolean app config key 'hide_slogan' stored as '0'/'1'
2. CSS uses display:none + visibility:hidden on footer.guest-box
3. Loaded only when setting is enabled
