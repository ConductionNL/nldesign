# Design: token-editor-ui

## Context
Tabbed admin panel for browsing and editing Nextcloud CSS custom properties with live preview and per-token reset controls. Vanilla JS.

## Decisions
1. Tabs: login, content, status, typography
2. TokenRegistry is single source of truth for editable tokens
3. Live preview via style.setProperty()
4. Changes saved to custom-overrides.css via API
