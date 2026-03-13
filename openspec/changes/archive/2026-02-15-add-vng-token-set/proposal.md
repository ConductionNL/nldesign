# Proposal: add-vng-token-set

## Summary
Add VNG (Vereniging Nederlandse Gemeenten) as a token set to the nldesign app, manually converting the existing VNG design tokens from the tilburg-woo-ui project into the nldesign `--nldesign-*` token format. VNG is not available in the upstream nl-design-system/themes repository, so this requires a manual addition rather than auto-generation.

## Motivation
VNG is a key Dutch government organization (the association of all Dutch municipalities). Their design tokens exist in the tilburg-woo-ui project (`src/styles/nlds/_tokens-vng.scss`) with ~1,140 CSS custom properties, but are not included in the upstream NL Design System themes repository. Adding VNG support to nldesign allows any Nextcloud instance to be themed with VNG's visual identity, serving municipalities that follow VNG branding guidelines.

## Affected Projects
- [x] Project: `nldesign` — Add `css/tokens/vng.css` token file and register in `token-sets.json`

## Scope
### In Scope
- Convert VNG design tokens from tilburg-woo-ui SCSS format to nldesign CSS format
- Map `--tilburg-*` palette/primitive tokens to `--vng-*` organization-specific tokens
- Map relevant tokens to `--nldesign-*` semantic tokens (colors, typography, spacing, borders, etc.)
- Register VNG in `token-sets.json` manifest
- Verify the token set loads correctly in the admin dropdown and applies theme

### Out of Scope
- Modifying the tilburg-woo-ui project itself
- Adding VNG to the upstream nl-design-system/themes repository
- Adding other municipality token sets not in the upstream repo
- Changing the generate-tokens.mjs script (VNG is manual, not auto-generated)
- Modifying PHP code (TokenSetService already discovers tokens dynamically from filesystem)

## Approach
1. Create `css/tokens/vng.css` by converting the SCSS tokens from `tilburg-woo-ui/src/styles/nlds/_tokens-vng.scss`:
   - Extract brand/palette colors as `--vng-*` prefixed tokens
   - Map primary, background, text, status, border, typography, spacing, and animation values to `--nldesign-*` semantic tokens
   - Map Utrecht component tokens through the existing `--nldesign-component-*` layer
2. Add VNG entry to `token-sets.json`
3. Verify in the running Nextcloud instance

## Cross-Project Dependencies
- **tilburg-woo-ui** — Source of VNG design tokens (read-only, no changes needed)
- **nldesign** — Target project; the existing 7-layer CSS cascade and TokenSetService handle dynamic discovery

## Rollback Strategy
Delete `css/tokens/vng.css` and remove the VNG entry from `token-sets.json`. No PHP or structural changes needed, so rollback is a simple file removal.

## Open Questions
None — the token source and target format are well-defined from the previous change.
