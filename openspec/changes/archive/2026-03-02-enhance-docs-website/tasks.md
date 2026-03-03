## 1. Directory Structure & File Moves

- [x] 1.1 Create `docs/getting-started/`, `docs/features/`, and `docs/reference/` directories
- [x] 1.2 Move `docs/tokens.md` to `docs/reference/tokens.md` with `sidebar_position` frontmatter
- [x] 1.3 Move `docs/mappings.md` to `docs/reference/mappings.md` with `sidebar_position` frontmatter
- [x] 1.4 Move `docs/compliance.md` to `docs/reference/compliance.md` with `sidebar_position` frontmatter
- [x] 1.5 Move `docs/token-audit.md` to `docs/reference/token-audit.md` with `sidebar_position` frontmatter
- [x] 1.6 Move `docs/assets.md` to `docs/reference/assets.md` with `sidebar_position` frontmatter
- [x] 1.7 Move `docs/brand-identity.md` to `docs/reference/brand-identity.md` with `sidebar_position` frontmatter
- [x] 1.8 Move `docs/icons.md` to `docs/reference/icons.md` with `sidebar_position` frontmatter
- [x] 1.9 Move `docs/development.md` to `docs/reference/development.md` with `sidebar_position` frontmatter
- [x] 1.10 Add `docs/reference/_category_.json` with label "Reference" and `sidebar_position: 3`

## 2. Documentation Landing Page

- [x] 2.1 Create `docs/intro.md` with `slug: /` frontmatter, high-level overview of nldesign, and links to getting started, features, and reference sections

## 3. Getting Started Section

- [x] 3.1 Create `docs/getting-started/_category_.json` with label "Getting Started" and `sidebar_position: 1`
- [x] 3.2 Create `docs/getting-started/installation.md` covering installation from Nextcloud App Store and manual installation
- [x] 3.3 Create `docs/getting-started/configuration.md` covering admin settings, token set selection, and optional toggles

## 4. Features Section

- [x] 4.1 Create `docs/features/_category_.json` with label "Features" and `sidebar_position: 2`
- [x] 4.2 Create `docs/features/token-sets.md` with overview of all 39 token sets (name, org, primary color) generated from `token-sets.json`
- [x] 4.3 Create `docs/features/css-architecture.md` explaining the 7-layer CSS system with a diagram
- [x] 4.4 Create `docs/features/theming-sync.md` explaining how token set selection syncs Nextcloud primary color, background, and logo
- [x] 4.5 Create `docs/features/admin-settings.md` explaining the admin panel with token set dropdown and preview
- [x] 4.6 Create `docs/features/toggles.md` covering hide-slogan and show-menu-labels optional features
- [x] 4.7 Create `docs/features/app-compatibility.md` as the integration guide for Nextcloud app developers

## 5. Homepage Enhancements

- [x] 5.1 Update `docusaurus/src/components/HomepageFeatures/index.js` with 6 feature cards (token sets, CSS architecture, compliance, configuration, compatibility, open source)
- [x] 5.2 Update `docusaurus/src/pages/index.js` to link Documentation button to `/docs` instead of `/docs/tokens`
- [x] 5.3 Update `docusaurus/docusaurus.config.js` footer docs link to point to `/docs` instead of `/docs/tokens`

## 6. Verification

- [x] 6.1 Run `npm run build` in `docusaurus/` and fix any broken link warnings
- [x] 6.2 Verify sidebar shows Getting Started, Features, Reference as collapsible groups in correct order
