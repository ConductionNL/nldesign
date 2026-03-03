## ADDED Requirements

### Requirement: Documentation landing page
The documentation site SHALL have an `intro.md` file at `docs/intro.md` that serves as the entry point for all documentation. It SHALL provide a high-level overview of what nldesign is, link to key sections (getting started, features, reference), and use the Docusaurus `slug: /` frontmatter to become the docs root.

#### Scenario: Visitor opens documentation
- **WHEN** a visitor navigates to `/docs/` on the documentation site
- **THEN** they see the intro page with a description of nldesign, its purpose, and navigation links to getting started, features, and reference sections

### Requirement: Getting started guide
The documentation SHALL include a `docs/getting-started/` directory containing an installation and configuration guide. The guide SHALL cover: installing the app from the Nextcloud App Store, selecting a token set via admin settings, and verifying the theme is applied.

#### Scenario: New administrator follows getting started guide
- **WHEN** an administrator reads the getting started guide
- **THEN** they find step-by-step instructions for installing nldesign, navigating to admin settings, selecting a token set, and confirming the theme is active

#### Scenario: Getting started sidebar ordering
- **WHEN** the documentation site sidebar renders
- **THEN** the "Getting Started" section appears as the first section after the intro page (sidebar_position: 1)

### Requirement: Features overview section
The documentation SHALL include a `docs/features/` directory with pages covering: token sets overview, 7-layer CSS architecture, theming sync, admin settings panel, optional toggles (hide slogan, menu labels), and icon library.

#### Scenario: Visitor browses features
- **WHEN** a visitor expands the "Features" section in the sidebar
- **THEN** they see individual pages for token sets, CSS architecture, theming sync, admin settings, toggles, and icons

#### Scenario: Token sets showcase page
- **WHEN** a visitor opens the token sets feature page
- **THEN** they see a listing of all 39 available token sets with organization name, primary color, and description

### Requirement: Reference section with existing docs
The documentation SHALL include a `docs/reference/` directory containing the existing technical documentation: tokens.md, mappings.md, compliance.md, token-audit.md, assets.md, and brand-identity.md. Each file SHALL retain its existing content and have `sidebar_position` frontmatter for ordering.

#### Scenario: Existing doc content preserved
- **WHEN** a visitor navigates to a reference page (e.g., token mappings)
- **THEN** they see the same content that was previously at the root of the docs folder

#### Scenario: Reference sidebar ordering
- **WHEN** the documentation site sidebar renders
- **THEN** the "Reference" section appears after "Features" (sidebar_position: 3)

### Requirement: Integration guide for app developers
The documentation SHALL include a page explaining how Nextcloud app developers can ensure their apps are compatible with nldesign. It SHALL cover: using standard Nextcloud CSS variables, avoiding hardcoded colors, testing with nldesign enabled, and using the `--nldesign-*` token namespace.

#### Scenario: App developer reads integration guide
- **WHEN** a Nextcloud app developer reads the integration guide
- **THEN** they find concrete guidance on CSS variable usage, color avoidance patterns, and testing procedures to ensure nldesign compatibility

### Requirement: Enhanced homepage feature cards
The homepage SHALL display feature cards that describe nldesign's key capabilities: token sets, CSS architecture, government compliance, easy configuration, app compatibility, and open source nature. Each card SHALL have a title and a brief description.

#### Scenario: Visitor lands on homepage
- **WHEN** a visitor opens the documentation site root URL
- **THEN** they see a hero section with the app name and tagline, plus 6 feature cards summarizing nldesign's capabilities

#### Scenario: Homepage documentation link
- **WHEN** a visitor clicks the "Documentation" button on the homepage
- **THEN** they are navigated to the docs intro page (`/docs/intro`), not to the tokens reference page

### Requirement: Documentation builds without errors
The Docusaurus build SHALL complete without errors after all documentation changes. All internal links between pages SHALL resolve correctly. The auto-generated sidebar SHALL reflect the new directory structure.

#### Scenario: Clean build after reorganization
- **WHEN** `npm run build` is executed in the `docusaurus/` directory
- **THEN** the build completes successfully with no broken link errors

#### Scenario: Sidebar reflects directory structure
- **WHEN** the built site is served
- **THEN** the sidebar shows sections for Getting Started, Features, and Reference as collapsible groups
