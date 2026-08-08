---
sidebar_position: 5
---

# Retired presentation options

NL Design does not currently expose selector-based presentation toggles. The
old settings, routes, controls, and styles were retired from the load-bearing
app for two different reasons:

- the login-page selector hid Nextcloud's complete guest footer, including the
  configured instance identity link, rather than only a slogan value;
- the app-menu selector depended on version-sensitive header structure and had
  no packaged browser evidence across the supported Nextcloud majors.

Changing an instance slogan belongs in Nextcloud Theming. A future navigation
adaptation should be a separately bounded surface adapter with explicit
responsive, keyboard, zoom, assistive-technology, and evidence for every
supported major.

Legacy `hide_slogan` and `show_menu_labels` app-config values are ignored. They
are harmless, but a system administrator may remove them explicitly:

```bash
php occ config:app:delete nldesign hide_slogan
php occ config:app:delete nldesign show_menu_labels
```
