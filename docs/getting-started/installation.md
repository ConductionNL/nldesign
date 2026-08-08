---
sidebar_position: 1
---

# Installation

The repository does not currently claim a released Nextcloud App Store package.
Install a reviewed GitHub prerelease or build from source.

## Requirements

- Nextcloud 32 through 34, as declared by current app metadata
- PHP 8.2 or newer
- administrator and server access

Local API analysis targets the oldest supported Nextcloud 32 OCP package. Production use still requires integration evidence for the exact Nextcloud major and installed-app set.

## GitHub prerelease

Download `nldesign-VERSION.tar.gz` and its `.sha256` file from the matching
[GitHub prerelease](https://github.com/DROG-group/nldesign/releases). Verify and
extract the archive from a trusted shell:

```bash
sha256sum --check nldesign-VERSION.tar.gz.sha256
tar -xzf nldesign-VERSION.tar.gz -C /path/to/nextcloud/custom_apps
php /path/to/nextcloud/occ app:enable nldesign
```

The archive already contains the top-level `nldesign/` directory and generated
font assets. Preserve the owner and permissions used by the other installed
apps. GitHub prereleases are unsigned integration candidates; they are not App
Store packages.

## Source checkout

Place the repository at `custom_apps/nldesign`, then build its dependencies:

```bash
cd /path/to/nextcloud/custom_apps/nldesign
npm ci --ignore-scripts
npm run build
php /path/to/nextcloud/occ app:enable nldesign
```

The app has no production Composer dependency. Run `composer install` only
when you need the local PHP quality tools. A proper release archive should
already contain generated font files; the installed runtime should not need
Composer, Node, npm, a package registry, or a CDN.

## Disable or remove

```bash
php /path/to/nextcloud/occ app:disable nldesign
```

Disabling stops stylesheet injection. App configuration can remain for a later reinstall. Remove persisted keys separately only when intentionally discarding profile history and settings.
