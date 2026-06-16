# Design: admin-settings

## Context
Admin settings panel in Nextcloud's Theming section. Vanilla PHP template + vanilla JS (no Vue/webpack). Provides token set dropdown, hide slogan toggle, menu labels toggle, live preview, token editor mount point, and theming sync trigger.

## Goals / Non-Goals
**Goals:** Settings panel registration, template with all parameters, token set dropdown, feature toggles, live preview, XSS prevention
**Non-Goals:** Vue-based UI, per-user settings, real-time multi-admin sync

## Decisions
1. Vanilla PHP template with script/style injection via Nextcloud helpers
2. All endpoints gated with `@AuthorizedAdminSetting`
3. Data attributes on root div for JS initialization
4. `p(json_encode(...))` for XSS prevention

## File Changes
- `lib/Settings/Admin.php` — Panel registration and template response
- `templates/settings/admin.php` — PHP template with all controls
- `js/admin.js` — Vanilla JS event handlers
- `css/admin.css` — Admin panel styling
