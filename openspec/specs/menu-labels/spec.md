---
status: retired
reviewed_date: 2026-08-08
retired_date: 2026-08-08
---

# App Menu Labels Retirement Specification

## REQ-MENU-001: No reachable compatibility option

The app MUST NOT expose a menu-label route or settings control and MUST NOT load
the retired structural stylesheet. A legacy `show_menu_labels` app-config value
MUST have no runtime effect.

## REQ-MENU-002: Reintroduction gate

A future navigation adapter MUST identify its supported DOM contract and pass
packaged tests for active, overflow, narrow-header, keyboard, zoom, and
assistive-technology behavior on every claimed Nextcloud major before becoming
reachable.
