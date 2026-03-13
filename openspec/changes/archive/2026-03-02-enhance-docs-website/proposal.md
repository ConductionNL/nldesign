## Why

The nldesign Docusaurus documentation site already exists and builds correctly, but its content is purely technical reference material (token mappings, compliance checklists, audit reports). It lacks user-facing documentation that explains what nldesign is, how to install and configure it, and how organizations can adopt it. Compared to pipelinq — which has a structured `features/` subdirectory with clear feature-by-feature documentation — nldesign's docs are flat and assume prior knowledge. For a theming app aimed at Dutch government organizations, approachable documentation is critical for adoption.

## What Changes

- Add a **getting started guide** covering installation, first configuration, and token set selection
- Add a **features overview** page explaining what nldesign does at a high level (7-layer CSS, 39 token sets, theming sync)
- Reorganize docs into logical subdirectories: `getting-started/`, `features/`, `reference/` (move existing technical docs here)
- Add a **token set gallery/showcase** page listing all 39 available token sets with their organizations
- Add an **integration guide** explaining how other Nextcloud apps can ensure compatibility with nldesign
- Improve the **homepage** (`src/pages/index.js`) with more descriptive feature cards and better call-to-action
- Add a `docs/index.md` or `docs/intro.md` as a proper documentation landing page (Docusaurus convention)

## Capabilities

### New Capabilities
- `docs-content`: Documentation content structure — getting started guide, features overview, token set showcase, integration guide, and reorganized reference section

### Modified Capabilities
_(none — no existing spec-level behavior changes, this is purely documentation)_

## Impact

- **Files changed**: `docs/` folder (new and reorganized markdown files), `docusaurus/src/pages/index.js` (homepage), `docusaurus/src/components/HomepageFeatures/index.js` (feature cards)
- **No PHP/backend changes**: This is documentation only
- **No dependency changes**: Docusaurus setup is already complete and working
- **Deployment**: Existing GitHub Actions workflow will auto-deploy on merge to `development`
- **Risk**: Low — documentation changes don't affect app functionality
