# Design: add-vng-token-set

## Architecture Overview

This change adds a single CSS file (`css/tokens/vng.css`) and a manifest entry. No PHP, API, database, or architectural changes are needed — the existing TokenSetService dynamically discovers token sets from the filesystem.

The VNG token file follows the same structure as all other token sets in `css/tokens/`:
```
:root {
    /* Organization palette (--vng-*) */
    /* Semantic tokens (--nldesign-*) */
}
```

The 7-layer CSS cascade handles it automatically:
```
fonts → defaults.css → tokens/vng.css → utrecht-bridge.css → theme.css → overrides.css → element-overrides.css
```

## API Design

No API changes. The existing `GET /settings/tokensets` endpoint returns all token sets from the filesystem via TokenSetService, so VNG will appear automatically.

## Database Changes

None.

## Nextcloud Integration

No changes needed. Existing components handle everything:
- `TokenSetService::getAvailableTokenSets()` scans `css/tokens/` directory
- `Application.php` loads `css/tokens/{selected}.css` dynamically
- `Admin.php` populates the dropdown from TokenSetService

## File Structure

```
nldesign/
  css/tokens/
    vng.css              ← NEW: VNG design tokens
  token-sets.json        ← MODIFIED: Add VNG entry
```

## Token Mapping Strategy

### Source
The VNG tokens come from `tilburg-woo-ui/src/styles/nlds/_tokens-vng.scss`:
- ~1,140 CSS custom properties in `.vng-theme` class
- Uses `--tilburg-*` prefix for primitives and `--utrecht-*` for component tokens
- Covers typography (Avenir font), colors, spacing (animal-based naming), borders, forms, and feedback

### Conversion Rules

1. **Palette tokens**: `--tilburg-color-*` → `--vng-color-*` (preserve organization identity)
2. **Semantic tokens**: Map tilburg primitives to `--nldesign-*` equivalents:
   - `--tilburg-color-blue-500` (#003865) → `--nldesign-color-primary`
   - `--tilburg-color-blue-400` (#026596) → `--nldesign-color-primary-hover`
   - `--tilburg-color-red-400` (#bf1a12) → `--nldesign-color-error`
   - `--tilburg-color-green-400` (#01745a) → `--nldesign-color-success`
   - `--tilburg-color-orange-400` (#d45f01) → `--nldesign-color-warning`
   - `--tilburg-color-gray-950` (#001737) → header background
   - `--tilburg-color-black-txt` (#333333) → text color
3. **Typography**: Map Avenir font family and tilburg font sizes
4. **Spacing**: Convert animal-based spacing to `--nldesign-spacing-*` tokens
5. **Borders**: Map tilburg border radius/width tokens
6. **Component tokens**: Do NOT duplicate `--utrecht-*` tokens — those are handled by `utrecht-bridge.css`

### Decision: Skip Utrecht Component Tokens

The source file contains many `--utrecht-*` tokens (textarea, textbox, table, link, button, checkbox, etc.). These should be **excluded** from `vng.css` because:
- The `utrecht-bridge.css` layer already maps `--nldesign-component-*` → `--utrecht-*`
- Including them would create conflicting specificity in the cascade
- Only include `--nldesign-*` semantic and `--vng-*` palette tokens

### Decision: Use Resolved Values, Not References

The source SCSS uses `var()` references (e.g., `var(--tilburg-form-control-color)`). The CSS output should use **resolved hex values** to avoid dependency on tilburg-specific intermediate tokens that won't exist in the nldesign context.

## Security Considerations

None — this is a CSS-only change with no user input, API endpoints, or data processing.

## NL Design System

VNG tokens align with NL Design System conventions:
- Uses the same token architecture (primitives → semantic → component)
- Color palette follows Dutch government design standards
- Typography uses Avenir (VNG corporate font)
- Spacing uses consistent sizing scale

## Trade-offs

| Decision | Alternative | Rationale |
|----------|-------------|-----------|
| Manual CSS file | Auto-generate from SCSS | VNG is not in upstream themes repo; SCSS has tilburg-specific references that need manual resolution |
| Resolved hex values | Preserve var() references | Avoids dependency on intermediate tokens that don't exist in nldesign |
| Skip --utrecht-* tokens | Include all tokens from source | Utrecht bridge handles component tokens; duplicating creates specificity conflicts |
| Single flat file | Separate palette/semantic files | Consistent with all other token sets which are single files |
