# NL Design profiles for Nextcloud

NL Design is a Nextcloud administration app that selects a compiled design profile and projects its CSS tokens onto selected Nextcloud web surfaces.

This repository is pre-release. It contains a hardened profile-selection slice and an architecture for a future, removable bridge to settings owned by Nextcloud's Theming app. It is not a drop-in claim of full organisational identity, accessibility compliance, or compatibility with every Nextcloud app.

## What works now

- A manifest-backed inventory of 40 profile snapshots in `token-sets.json` and `css/tokens/`; 8 currently carry a statically gated `nextcloud-core-v1` projection and are selectable.
- Admin-only profile selection with an initial deterministic revision, opaque transition revisions, stale-write rejection, one-step rollback, and bounded history.
- A three-layer runtime stylesheet plan: local fonts, the selected projection, and a bounded mapping to Nextcloud core custom properties.
- Native Nextcloud is the initial and explicitly selectable state; enabling the app does not choose an organisation's identity.
- Read-only recommendations for fields an administrator may choose to copy into Nextcloud Theming.
- Self-hosted Fira Sans files; runtime page loads do not contact a font CDN.

## Deliberate limits

- Selecting a profile does not write to Nextcloud Theming.
- The isolated `OCA\Theming` compatibility prototype is not registered or load-bearing.
- There is no token editor, custom-override writer, import/export flow, or apply dialog in architecture v1.
- Selector-based login-footer and app-menu presentation toggles are intentionally absent: the retired experiments changed more UI than their labels promised or lacked supported-major browser evidence.
- A `source-only` catalogue entry is retained for provenance work but cannot be selected. Inclusion does not prove endorsement, official status, trademark permission, complete upstream provenance, or conformance.
- CSS coverage varies by Nextcloud surface and app. A live compatibility matrix still has to be built from integration tests.

The governing design and delivery sequence are in [architecture.md](docs/architecture.md) and [roadmap.md](docs/roadmap.md).

## Install from a source checkout

The repository does not currently assert a published Nextcloud App Store release.

```bash
cd /path/to/nextcloud/custom_apps
git clone https://github.com/ConductionNL/nldesign.git
cd nldesign

npm ci --ignore-scripts
npm run build

php /path/to/nextcloud/occ app:enable nldesign
```

The app has no production Composer dependency. Install Composer's development
dependencies only when running the PHP quality suite. A packaged release should
already contain generated font files and need no Composer, Node, package
registry, or CDN access at runtime.

The current app metadata declares maintained Nextcloud 32 through 34 and PHP 8.2 or newer. Static analysis is checked against the oldest supported OCP contract. A real multi-version Nextcloud integration matrix remains a release gate.

## Use

Open **Administration settings → Theming → NL Design profiles**.

Selecting a profile saves immediately. The page shows:

- a colour preview when the profile declares a valid primary-colour hint;
- the active revision and rollback control;
- recent profile operations; and
- manual Nextcloud Theming recommendations, where the manifest contains them.

Open pages may need a reload before the new stylesheet stack is visible everywhere.
Selecting **Native Nextcloud** deactivates the profile without disabling the app,
and the same revision/rollback contract applies.

## Runtime architecture

The load-bearing path uses public Nextcloud application APIs:

```text
token-sets.json + css/tokens/{id}.css
                    |
                    v
TokenSetService -> ProfileStateService -> TemplateStylesListener
                         |
                         v
                  app-scoped IAppConfig
```

The stylesheet order is explicit and unit-tested:

```text
fonts
tokens/{active-profile}
theme
```

Private Nextcloud Theming classes may only appear below `lib/Infrastructure/Nextcloud/Compatibility/`. The architecture check prevents those names and installed-app file writes from leaking into the normal runtime path.

## Development

Requirements:

- PHP 8.2 or newer and Composer 2
- a maintained Node.js 24 or 26 release and npm

```bash
composer install
npm ci --ignore-scripts

composer check
npm test
npm run build
composer audit --locked
npm audit --audit-level=high
```

Documentation:

```bash
cd docusaurus
npm ci --ignore-scripts
npm run build
```

See [DEVELOPMENT.md](DEVELOPMENT.md) for the complete workflow.

## Adding a profile

1. Add `css/tokens/{id}.css` using a lowercase kebab-case id.
2. Add exactly one matching `source-only` entry to `token-sets.json`.
3. Document the source version, transformation, rights status, and known gaps.
4. To make it selectable, reduce it to the four `nextcloud-core-v1` projection properties, change its status to `ready`, and add only evidenced, allowlisted manual-Theming hints. Approved assets must be local.
5. Run `npm run check:manifest`, the PHP suite, and representative Nextcloud surface tests.

Directory discovery is not the product API: the manifest and compiled stylesheet must both exist.

## Assets and rights

The app code is licensed under EUPL-1.2. Fira Sans is bundled under the SIL Open Font License 1.1.

Organisation names, logos, colours, and other identity material can have usage
conditions independent of the code licence. The package no longer distributes
organization-specific logos or backgrounds. The previously copied Amsterdam
Design System icon/logo package was unused and its package explicitly excluded
those assets from the package's open-source licence, so it was also removed. Do
not interpret a profile name as permission to represent that organisation.

## Authors

[Conduction](https://conduction.nl)
