---
sidebar_position: 4.3
---

# Profile installation and portable configuration

## Status: bounded profile installation implemented; general export deferred

The administration page can install immutable instance-local profile versions
from `nldesign-profile-pack/v1` JSON. This is a profile installer, not raw
TokenFile CRUD and not a general Nextcloud configuration importer.

The v1 envelope contains bounded metadata, an exact semantic version, the
`nextcloud-core-v1` projection identifier, one allowlisted local font stack,
and complete light plus optional dark primary-colour roles. It cannot contain
CSS, JavaScript, selectors, URLs, assets, paths, app ids, or Nextcloud setting
keys. Unknown fields fail validation. The app compiles deterministic CSS and
stores one integrity-checked app-data record per `id` + `version`.

Versions use SemVer core and optional prerelease identifiers. Leading zeroes in
numeric identifiers and `+build` metadata are rejected so the same path-safe
string is the identity everywhere.

Installing does not activate a profile. Activation remains a separate,
revision-checked operation. Built-in versions are read-only. An installed
version cannot be removed while active or while retained as the immediate
rollback target. Content changes require a new version; the same identity is
idempotent only when its content hash matches exactly.

See the repository's
[`examples/profile-pack.v1.json`](https://github.com/DROG-group/nldesign/blob/main/examples/profile-pack.v1.json)
for the current envelope.

Still deferred:

- exporting activation state or full instance configuration;
- importing DTCG/NL Design System source graphs at runtime;
- assets or custom fonts;
- semantic local overrides; and
- configuration plans for Nextcloud Theming.

Those formats need their own review, rights, migration, secrets, and recovery
contracts. They must not be smuggled into the profile-pack schema.

Do not write `custom-overrides.css` into `custom_apps/nldesign`. Such files are outside the supported runtime model and can disappear or break upgrades.
