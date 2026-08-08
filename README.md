# NL Design profiles for Nextcloud

NL Design is a Nextcloud administration app that selects an exact version of a bounded design profile and projects it onto selected Nextcloud web surfaces.

This repository is pre-release. It contains a hardened profile-selection slice and an architecture for a future, removable bridge to settings owned by Nextcloud's Theming app. It is not a drop-in claim of full organisational identity, accessibility compliance, or compatibility with every Nextcloud app.

## What works now

- A manifest-backed inventory of 40 profile snapshots in `token-sets.json` and `css/tokens/`; 8 currently carry a statically gated `nextcloud-core-v1` projection and are selectable.
- A profile library that combines read-only built-in profiles with immutable versions installed from the closed `nldesign-profile-pack/v1` JSON format.
- Admin-only exact-version selection with an initial deterministic revision, opaque transition revisions, stale-write rejection, one-step rollback, and bounded version history.
- Light/dark previews, profile search, version selection, and safe removal of installed versions that are neither active nor retained for rollback.
- A three-layer runtime stylesheet plan: local fonts, the selected projection,
  and one range-gated `nextcloud-core-v1` mapping shared by Nextcloud 32–34.
- Native Nextcloud is the initial and explicitly selectable state; enabling the app does not choose an organisation's identity.
- Read-only recommendations for fields an administrator may choose to copy into Nextcloud Theming.
- Self-hosted Fira Sans files; runtime page loads do not contact a font CDN.

## Deliberate limits

- Selecting a profile does not write to Nextcloud Theming.
- Existing administrator custom CSS remains authoritative and can override the
  projected Nextcloud variables. Resolve those explicit overrides when
  evaluating or adopting a profile; the app does not silently fight them.
- The isolated `OCA\Theming` compatibility prototype is not registered or load-bearing.
- There is no raw TokenFile CRUD, arbitrary CSS upload, custom-override writer, generic configuration export, or automatic core-Theming apply flow. The profile installer accepts only the small closed semantic projection and generates CSS inside the app.
- Selector-based login-footer and app-menu presentation toggles are intentionally absent: the retired experiments changed more UI than their labels promised or lacked supported-major browser evidence.
- A `source-only` catalogue entry is retained for provenance work but cannot be selected. Inclusion does not prove endorsement, official status, trademark permission, complete upstream provenance, or conformance.
- CSS coverage varies by Nextcloud surface and app. A live compatibility matrix still has to be built from integration tests.

The governing design and delivery sequence are in [architecture.md](docs/architecture.md) and [roadmap.md](docs/roadmap.md).

## Install a prerelease package

Download the archive and checksum from the matching
[GitHub prerelease](https://github.com/DROG-group/nldesign/releases), then:

```bash
sha256sum --check nldesign-VERSION.tar.gz.sha256
tar -xzf nldesign-VERSION.tar.gz -C /path/to/nextcloud/custom_apps
php /path/to/nextcloud/occ app:enable nldesign
```

The archive contains the required top-level `nldesign/` directory. Preserve the
owner and permissions used by the other apps in `custom_apps`. Prereleases are
unsigned integration candidates, not Nextcloud App Store releases.

## Install from a source checkout

```bash
cd /path/to/nextcloud/custom_apps
git clone https://github.com/DROG-group/nldesign.git
cd nldesign

npm ci --ignore-scripts
npm run build

php /path/to/nextcloud/occ app:enable nldesign
```

The app has no production Composer dependency. Install Composer's development
dependencies only when running the PHP quality suite. A packaged release should
already contain generated font files and need no Composer, Node, package
registry, or CDN access at runtime.

The current app metadata declares maintained Nextcloud 32 through 34 and PHP
8.2 or newer. Static analysis is checked against the oldest supported OCP
contract, and the load-bearing CSS uses only the documented variable and theme
state intersection audited across those majors. A real multi-version Nextcloud
integration matrix remains a release gate.

## Use

Open **Administration settings → Theming → NL Design profiles**.

Selecting an exact profile version saves immediately. The page shows:

- light and dark colour previews;
- grouped profiles with immutable version selectors and provenance labels;
- the active revision and rollback control;
- recent profile operations; and
- manual Nextcloud Theming recommendations, where the manifest contains them.

Use **Install profile** to add a local JSON pack. A pack must follow
[`nldesign-profile-pack/v1`](examples/profile-pack.v1.json), use a new immutable
`id` + `version` identity, and contain only the allowlisted font and colour
roles. The server rejects unknown fields, arbitrary CSS/JavaScript, remote
assets, invalid colours, and primary/text pairs below the enforced contrast
threshold. Built-in versions cannot be overwritten. Installed versions are
stored in Nextcloud app data, not in the installed app directory.

Open pages may need a reload before the new stylesheet stack is visible everywhere.
Selecting **Native Nextcloud** deactivates the profile without disabling the app,
and the same revision/rollback contract applies.

## Runtime architecture

The load-bearing path uses public Nextcloud application APIs:

```text
packaged manifest/CSS ----+
                          +--> TokenSetService --> ProfileStateService
installed profile records-+          |                    |
        in IAppData                   v                    v
                         TemplateStylesListener     app-scoped IAppConfig
                                  |
             packaged CSS or digest-addressed generated CSS
```

The stylesheet order is explicit and unit-tested. For a built-in profile it is:

```text
fonts
tokens/{active-profile}
compatibility/nextcloud-core-v1
```

An installed profile uses the same `fonts → profile → core projection`
precedence, but the middle layer is served by a public, immutable,
digest-addressed CSS route. The route revalidates the stored record before
returning generated CSS.

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

## Adding or installing a profile

For a built-in release profile:

1. Add `css/tokens/{id}.css` using a lowercase kebab-case id.
2. Add exactly one matching `source-only` entry to `token-sets.json`.
3. Document the source version, transformation, rights status, and known gaps.
4. To make it selectable, reduce it to the four `nextcloud-core-v1` projection properties, change its status to `ready`, and add only evidenced, allowlisted manual-Theming hints. Approved assets must be local.
5. Run `npm run check:manifest`, the PHP suite, and representative Nextcloud surface tests.

Directory discovery is not the product API: the manifest and compiled stylesheet must both exist.

For an instance-local profile, prepare a profile-pack document from the
provided example and install it through the administration page. Increment the
semantic version for every content change; core and prerelease SemVer forms are
supported, while build metadata is intentionally excluded from the path-safe
identity. The app never mutates or overwrites an installed version in place.

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
