# Changelog

All notable changes to this project will be documented in this file.

## Unreleased

### Changed
- Style injection moved from `Application::boot()` (every request) to a `ThemeInjectionListener` on `BeforeTemplateRenderedEvent`/`BeforeLoginTemplateRenderedEvent` (only actual template renders) — same stylesheets, same cascade order, same excluded-app behavior by default. Adds an occ-only `themed_contexts` appconfig key to selectively unthemed a render context (`user`/`login`/`guest`/`public`/`error`); absent (the default) themes every context exactly as before. See `openspec/changes/render-event-injection/`.

### Security
- Hardened `CustomTokenSetValidator::isForbiddenValue()` to reject declaration values containing a semicolon (`;`) or a CSS comment marker (`/*`, `*/`), closing a CSS-injection gap where a single accepted `--nldesign-*`/`--{slug}-*` declaration's value could smuggle an arbitrary extra declaration (e.g. `background: url(...)`) past the name whitelist into the `:root {}` block served to every anonymous visitor (login page, share links). Applies to both the CSS upload path and the W3C Design Tokens JSON path (`CustomTokenSetController::mapFromJson()`), which shares the same gate. Only new uploads are affected — a custom token set uploaded before this fix is not retroactively re-validated; the served `custom-*.css` file for an existing set is unchanged until it is re-uploaded. See `openspec/changes/harden-custom-token-set-value-validation/`.

### Fixed
- Corrected the declared licence in `appinfo/info.xml` from `agpl` to `eupl` (EUPL-1.2) to match the bundled `LICENSE`, the SPDX headers, and the rest of the Conduction fleet. Adopters may key compliance on the declared licence, so the App Store listing now states the correct EUPL-1.2 licence.
- Documentation corrected to describe the real bundled, self-hosted Fira Sans delivery (no external CDN) and the true token-set count derived from `token-sets.json`.
- `docs/reference/token-audit.md` scoped its "production-ready" verdict to the five manually-reviewed sets; contrast for all sets is now verified by the automated contrast audit.

## 0.1.0 - Initial Release

- Initial app structure
- Basic Nextcloud integration
