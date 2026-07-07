# NL Design — Features

NL Design is a Nextcloud theming app that applies Dutch government design standards (Rijkshuisstijl and other NL Design System token sets) to every part of the Nextcloud interface. It functions as a Nextcloud Theme Editor: administrators select a pre-built organization token set or fine-tune individual CSS variables, and the result is propagated to both the NL Design CSS layer and Nextcloud's built-in theming system.

NL Design has no direct GEMMA component mapping — it is a cross-cutting infrastructure concern that ensures WCAG AA accessibility and Dutch government branding across all Conduction apps.

## Standards Compliance

| Standard | Status | Description |
|----------|--------|-------------|
| NL Design System | Beschikbaar | `--nldesign-*` token namespace; 41 organization token sets |
| Rijkshuisstijl | Beschikbaar | National government visual identity token set |
| WCAG 2.1 AA | Beschikbaar | Contrast, font size, and spacing tokens enforced per token set |
| Digitoegankelijk (EN 301 549) | Beschikbaar | Accessible colour and typography via design tokens |
| DCAT-AP NL | N.v.t. | Not applicable — theming layer only |

## Features

| Feature | Description | Docs |
|---------|-------------|------|
| [Token Sets](./token-sets.md) | 39+ organization-specific CSS token sets; searchable dropdown; auto-generated from upstream NL Design System | [token-sets.md](./token-sets.md) |
| [Token Editor UI](./token-editor.md) | Tabbed admin panel for editing all Nextcloud `--color-*` CSS variables with live preview and per-token reset | [token-editor.md](./token-editor.md) |
| [CSS Architecture](./css-architecture.md) | 7-layer CSS load order: design system → token set → custom overrides; `design-systems.json` controls bundles | [css-architecture.md](./css-architecture.md) |
| [Custom CSS Overrides](./css-architecture.md) | `custom-overrides.css` — single write target for all edits; loaded last; token set files are read-only | [css-architecture.md](./css-architecture.md) |
| [Token Set Apply Dialog](./apply-dialog.md) | Before applying a token set, shows old → new value diff per token; admin checks/unchecks individual changes | [apply-dialog.md](./apply-dialog.md) |
| [Theming Sync](./theming-sync.md) | After token set selection, syncs Nextcloud's primary color and background color via the theming API | [theming-sync.md](./theming-sync.md) |
| [Token Import/Export](./import-export.md) | Download `custom-overrides.css`; upload a saved file to restore or share a token configuration | [import-export.md](./import-export.md) |
| [Admin Settings](./admin-settings.md) | Admin panel under Theming: token set selector, toggles (hide slogan, show menu labels), token editor | [admin-settings.md](./admin-settings.md) |
| [Hide Slogan](./toggles.md) | Removes Nextcloud's default login-page slogan for a clean government-branded login | [toggles.md](./toggles.md) |
| [Show Menu Labels](./toggles.md) | Replaces header app icons with text labels; improves discoverability per Dutch government UX guidelines | [toggles.md](./toggles.md) |
| [Component Tokens](./token-sets.md) | `--nldesign-component-*` prefix bridging `--utrecht-*` component tokens to the nldesign namespace | [token-sets.md](./token-sets.md) |
| [App Compatibility](./app-compatibility.md) | Integration guide for other Nextcloud apps to ensure CSS-variable compatibility with nldesign | [app-compatibility.md](./app-compatibility.md) |
| [Prometheus Metrics](./css-architecture.md) | Active token set, custom override count, theming sync operations — in Prometheus text format | — |

## Architecture

NL Design operates as a pure CSS layer — no database tables. Configuration is stored in `IAppConfig`. The CSS stack loads in a defined order:

1. Design system base CSS (`design-systems.json` bundle)
2. Organization token set (`css/tokens/{id}.css`)
3. Custom overrides (`custom-overrides.css`) — always loaded last

The token sync workflow (nightly GitHub Actions) pulls updates from the upstream `nl-design-system/themes` repository and opens PRs when token changes are detected.

## GEMMA Mapping

| GEMMA Component | NL Design Role |
|-----------------|----------------|
| N.v.t. | Cross-cutting theming infrastructure for all Conduction Nextcloud apps |
