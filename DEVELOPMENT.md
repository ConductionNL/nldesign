# Development

## Requirements

- PHP 8.2 or newer
- Composer 2
- a maintained Node.js 24 or 26 release
- npm
- Nextcloud 32, 33, and 34 test instances for integration work

The repository does not prescribe a specific Docker environment or personal filesystem path.

## Setup

```bash
composer install
npm ci --ignore-scripts
npm run build
```

The root JavaScript build only produces bundled Fira Sans assets. Profile CSS is already compiled and validated against `token-sets.json`.

## Required local gates

```bash
composer validate --strict
composer check
composer check:coverage
composer audit --locked

npm test
npm run build
npm audit --audit-level=high
```

`composer check` runs PHP syntax checks, PHPCS, PHPMD, Psalm, PHPStan, and PHPUnit without masking failures. `composer check:coverage` requires PCOV or Xdebug and enforces at least 75% line coverage; reports stay under ignored `build/coverage/`. `npm test` checks architecture boundaries, the profile manifest, JavaScript syntax, and CSS.
CI and the release-candidate workflow additionally validate `appinfo/info.xml`
against Nextcloud's official app metadata schema.

## Nextcloud integration

Mount or copy the app into a test instance's `custom_apps/nldesign` directory, then run:

```bash
php occ app:enable nldesign
```

When hot-replacing files in an already running test instance, gracefully reload
PHP-FPM after adding or removing PHP classes. Long-lived workers can otherwise
retain stale application autoload state: the CLI router may list a new route
while web requests still return 404. This hot-deploy step is not a substitute
for testing the normal packaged install and upgrade lifecycle.

Exercise at least login, Files, admin settings, dark/high-contrast preferences, profile change, stale-save conflict, and rollback before claiming compatibility with a Nextcloud major. Local OCP analysis is not a replacement for this matrix.

NC32–34 currently share `css/compatibility/nextcloud-core-v1.css`. Keep the
major allowlist explicit, but do not create a per-major copy unless a source and
browser audit demonstrates a semantic difference in a property or theme-state
mechanism the app actually consumes.

## Documentation site

The Docusaurus site reads the root `docs/` directory.

```bash
cd docusaurus
npm ci --ignore-scripts
npm start
```

Production check:

```bash
npm run build
```

The documentation workflow builds on supported branches and deploys only after
an explicit workflow dispatch. Keep build-tool advisories separate from the
distributable Nextcloud app's dependency surface and record any unresolved
upstream advisory explicitly.

## Adding or changing profiles

- Treat `token-sets.json` as the catalogue contract.
- Keep ids lowercase kebab-case and one-to-one with `css/tokens/{id}.css`.
- Give every ready built-in profile an immutable semantic `version`.
- Do not add arbitrary Theming keys or filesystem paths.
- Record provenance and identity-asset rights; a colour/token implementation is not proof of official endorsement.
- Run `npm run check:manifest` and representative visual/accessibility tests.

Instance-local profiles use the separate `nldesign-profile-pack/v1` contract.
Do not add raw CSS, URLs, assets, or Nextcloud settings to that envelope. Change
content by installing a new version; never edit an installed record in place.

## Runtime-state rule

The installed app is immutable. Runtime code must not write CSS, uploads,
generated previews, or snapshots below the app path. Small activation state
uses app-scoped `IAppConfig`. Validated installed profile descriptors and their
deterministically generated CSS are integrity-checked records in Nextcloud app
data under `installed-profiles/`.
