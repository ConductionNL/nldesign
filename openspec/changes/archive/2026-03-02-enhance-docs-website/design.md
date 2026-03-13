## Context

The nldesign Docusaurus documentation site (deployed to `nldesign.app`) already has a complete technical infrastructure:
- Docusaurus 3.7.0 with React 18, Mermaid support, dark mode
- Auto-generated sidebar from `docs/` folder
- GitHub Actions CI/CD deploying to gh-pages on push to `development`
- Open Webconcept green theme with Poppins font
- Custom homepage with feature cards

The existing `docs/` folder contains 8 flat markdown files focused on technical reference: token architecture, CSS variable mappings, compliance checklists, brand identity tables, icon integration, and development guides. These are valuable but assume the reader already knows what nldesign is and why they'd use it.

The pipelinq documentation site uses the identical Docusaurus setup but organizes content into root-level overview pages plus a `features/` subdirectory with per-feature documentation.

## Goals / Non-Goals

**Goals:**
- Reorganize docs into a clear hierarchy: introduction → getting started → features → reference
- Add user-facing content that explains what nldesign does, how to install it, and how to configure it
- Create a token set showcase page so organizations can see themselves listed
- Add an integration guide for Nextcloud app developers
- Improve the homepage to better explain the app's value proposition

**Non-Goals:**
- Changing the Docusaurus infrastructure (config, dependencies, CI workflow — all working fine)
- Adding API documentation (nldesign has minimal API — just admin settings endpoints)
- Adding interactive demos or live token previews (future enhancement)
- Translating documentation to Dutch (future enhancement)

## Decisions

### 1. Directory structure: subdirectories over flat files

**Decision**: Reorganize `docs/` into `getting-started/`, `features/`, and `reference/` subdirectories.

**Rationale**: Mirrors the pipelinq pattern. Docusaurus auto-sidebar generates a clean navigation from directory structure. Flat files create a long, unstructured sidebar — subdirectories create collapsible sections.

**Alternatives considered**:
- Keep flat + manual sidebar ordering via `sidebar_position` frontmatter — workable but doesn't scale as docs grow
- Single long page — poor for discoverability

### 2. Move existing docs to `reference/` rather than rewriting

**Decision**: Move existing technical docs (tokens.md, mappings.md, compliance.md, token-audit.md, assets.md, brand-identity.md) to `docs/reference/` with minimal edits (just add frontmatter for sidebar ordering).

**Rationale**: The existing content is accurate and valuable — it just needs a better home. Rewriting would be wasted effort. New user-facing content goes in `getting-started/` and `features/`.

### 3. Add `docs/intro.md` as the docs landing page

**Decision**: Create `docs/intro.md` with `slug: /` to serve as the documentation entry point.

**Rationale**: Docusaurus convention. Without an intro page, the first sidebar item becomes the landing — currently `assets.md` which is not a good entry point. The intro page provides a high-level overview with links to key sections.

### 4. Homepage enhancement: richer feature cards with SVG icons

**Decision**: Update `HomepageFeatures/index.js` with more descriptive feature cards covering: 39 Token Sets, 7-Layer CSS Architecture, Government Compliance, Easy Configuration, App Compatibility, and Open Source.

**Rationale**: Current 3 cards are generic. 6 cards with brief descriptions better communicate the breadth of nldesign's capabilities. Use inline SVG icons from the existing Amsterdam Design System icon set (already in the repo).

### 5. Homepage CTA links to intro page

**Decision**: Change the homepage "Documentation" button to link to `/docs/intro` instead of `/docs/tokens`.

**Rationale**: New visitors should land on an overview page, not a technical token reference.

## Risks / Trade-offs

- **[Broken internal links]** → Moving files to subdirectories may break cross-references between docs. Mitigation: Docusaurus `onBrokenLinks: 'warn'` will catch these during build. Fix all warnings before merging.
- **[Content duplication]** → The intro page may overlap with README.md content. Mitigation: Keep intro.md focused on "what and why", link to detailed pages for "how". README.md remains the GitHub-facing entry point.
- **[Sidebar ordering]** → Auto-generated sidebar sorts alphabetically by default. Mitigation: Use `sidebar_position` frontmatter in each file to control order explicitly.
