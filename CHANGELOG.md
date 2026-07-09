# Changelog

All notable changes to this project will be documented in this file.

## Unreleased

### Fixed
- Corrected the declared licence in `appinfo/info.xml` from `agpl` to `eupl` (EUPL-1.2) to match the bundled `LICENSE`, the SPDX headers, and the rest of the Conduction fleet. Adopters may key compliance on the declared licence, so the App Store listing now states the correct EUPL-1.2 licence.
- Documentation corrected to describe the real bundled, self-hosted Fira Sans delivery (no external CDN) and the true token-set count derived from `token-sets.json`.
- `docs/reference/token-audit.md` scoped its "production-ready" verdict to the five manually-reviewed sets; contrast for all sets is now verified by the automated contrast audit.

## 0.1.0 - Initial Release

- Initial app structure
- Basic Nextcloud integration
