---
sidebar_position: 8
---

# Developer reference

Use the root DEVELOPMENT.md as the setup guide. The repository baseline is PHP
8.2, Composer 2, a maintained Node 24 or 26 release, and npm lock-file installs.

## Local verification

Install dependencies:

    composer install
    npm ci --ignore-scripts
    cd docusaurus
    npm ci --ignore-scripts

Run the complete app checks from the repository root:

    composer validate --strict
    composer audit --locked
    composer check
    composer check:coverage
    npm audit --audit-level=high
    npm test
    npm run build

Run the documentation checks from docusaurus:

    npm run check:assets
    npm run audit:runtime
    npm run audit:build
    npm run build

composer check includes PHP syntax, coding standard, PHPMD, Psalm, PHPStan, and
PHPUnit. composer check:coverage requires PCOV or Xdebug and enforces the 75%
line-coverage floor used by CI and release-candidate packaging. Reports remain
under ignored build/coverage/. npm test includes architecture-boundary,
profile-manifest, JavaScript syntax, and stylesheet checks.

## Dependency advisory boundary

At the 2026-08-08 lock-file snapshot, app dependencies, Composer dependencies,
and the generated documentation runtime have zero known advisories. A
forced patched `serialize-javascript` 7.0.5 removes that fixable build-chain
advisory. A scoped `sockjs` override to CommonJS-compatible `uuid` 11.1.1
removes the development-server advisory. The full private Docusaurus graph
still reports 18 high-severity affected package nodes, all propagated from
Docusaurus's unfixed `image-size` parser dependency. The build audit permits
only `GHSA-w3rx-r6r6-pgpr` and `GHSA-5p2g-fcmc-qvqq` through that dependency
graph and fails on a new or severity-escalated finding. These packages are not
present in the static site output dependency surface. Before Docusaurus runs,
the asset gate rejects symlinks and AVIF, ICNS, JXL, HEIF, and HEIC inputs by
both extension and file signature, including renamed files.
Trusted installs disable dependency
lifecycle scripts, CI builds only static output, and publication must never
deploy the Docusaurus development server or its `node_modules` tree.

## Change boundaries

- token-sets.json is the profile inventory; every entry needs one matching CSS
  file, and only statically gated ready projections are selectable;
- only lib/Infrastructure/Nextcloud/Compatibility may reference private
  OCA\Theming classes;
- runtime code must not mutate files below the installed app;
- no controller may accept arbitrary app ids, config keys, or filesystem paths;
- profile writes require the revision observed by the client;
- do not add automatic core-Theming behavior to profile selection.

## Generated assets

npm run build copies the exact Fira Sans allowlist and OFL notice. Generated
output must be unchanged after the build. Profile snapshots are maintained in
the repository; only the 8 entries carrying the minimal projection contract
are runtime profiles. The former unpinned nightly upstream generator is not
part of the trusted build.

## Continuous integration

Code Quality runs PHP 8.2/8.5, Node 24/26, and a complete-history secret scan
with read-only repository permissions. The PHP and release-candidate paths
also validate `appinfo/info.xml` against Nextcloud's official live app schema.
Documentation builds on Node 24 and 26
and deploys only from an explicit workflow dispatch on `main`. Build Release Candidate is also
manual: it verifies the full suite and creates a minimal unsigned artifact. It
does not increment versions, push branches, create releases, sign with secrets,
or publish to the App Store.

Branch Policy enforces the repository's promotion model. It is separate from
quality and release authority.

## Release discipline

Update appinfo/info.xml in a reviewed change. Run the full suite against a
packaged supported Nextcloud matrix. Then build a release candidate explicitly.
Signing and publication remain human-authorized steps outside the current
workflow until protected release environments and provenance are defined.
