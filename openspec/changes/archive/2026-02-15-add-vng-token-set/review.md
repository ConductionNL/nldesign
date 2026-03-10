# Review: add-vng-token-set

## Summary
- Tasks completed: 3/3
- GitHub issues closed: 4/4 (tracking #20 + tasks #21, #22, #23)
- Spec compliance: **PASS** (with 2 warnings)

## Verification Results

### Requirement: VNG Token CSS File — PASS
- `css/tokens/vng.css` exists (186 lines, 6104 bytes)
- Uses `:root` scope
- Contains `--nldesign-*` semantic tokens and `--vng-*` palette tokens
- All values are resolved hex (0 `var()` references)

### Requirement: VNG Semantic Color Mapping — PASS
- `--nldesign-color-primary: #003865` — correct (VNG dark blue)
- `--nldesign-color-primary-hover: #026596` — correct
- `--nldesign-color-primary-text: #ffffff` — correct
- `--nldesign-color-primary-light: #e6f6ff` — correct (VNG blue-100)
- `--nldesign-color-error: #bf1a12` — correct (VNG red-400)
- `--nldesign-color-success: #01745a` — correct (VNG green-400)
- `--nldesign-color-warning: #d45f01` — correct (VNG orange-400)
- `--nldesign-color-text: #333333` — correct (VNG black-txt)
- `--nldesign-color-text-muted: #5b6e8a` — correct (VNG gray-600)

### Requirement: VNG Typography Tokens — PASS (with caveat)
- `--nldesign-font-family` includes 'Avenir' as primary font — correct
- Sans-serif fallback chain specified — correct

### Requirement: VNG Spacing and Border Tokens — PASS
- `--nldesign-border-radius: 8px` — correct (VNG border-radius-md)
- Border radius variants defined (small: 4px, large: 16px, rounded/pill: 999px)

### Requirement: VNG Header and Background Tokens — PASS
- `--nldesign-color-header-background: #003865` — correct
- `--nldesign-color-header-text: #ffffff` — WCAG contrast 12.0:1 (passes AAA)

### Requirement: Token Set Manifest Entry — PASS
- `token-sets.json` contains `"id": "vng"`, `"name": "VNG Vereniging Nederlandse Gemeenten"` — correct
- Valid JSON confirmed
- 39 total token sets (38 existing + 1 VNG)

### Requirement: No Utrecht Component Token Duplication — PASS
- 0 `--utrecht-*` tokens found in vng.css — correct

## Findings

### CRITICAL
None.

### WARNING
- [ ] **Spec references `--nldesign-spacing-*` tokens that don't exist in the token architecture.** The spec's "VNG Spacing and Border Tokens" requirement says `--nldesign-spacing-*` tokens SHALL map to VNG values, but no `--nldesign-spacing-*` tokens exist anywhere — not in defaults.css, not in any other token set. The spacing architecture uses component-level tokens (e.g., `--nldesign-component-table-cell-padding-*`). The spec should be updated to match the actual token architecture. (spec_ref: `specs/vng-token-set/spec.md#requirement-vng-spacing-and-border-tokens`)
- [ ] **Spec uses non-existent token name `--nldesign-typography-font-family`.** The spec references `--nldesign-typography-font-family` but the actual token name in defaults.css and all other token sets is `--nldesign-font-family`. The implementation correctly uses `--nldesign-font-family`. Only `nijmegen.css` has the `--nldesign-typography-font-family` variant (likely a bug in that file). (spec_ref: `specs/vng-token-set/spec.md#requirement-vng-typography-tokens`)

### SUGGESTION
- The spec's font size scenario says "heading and body font sizes SHALL be derived from the VNG typography scale" but no token set in the architecture overrides heading font sizes (they all rely on defaults.css). This is consistent behavior — the spec wording is overly prescriptive for this token layer. Consider updating the spec to say "font sizes MAY be overridden" rather than SHALL.
- VNG's animal-based spacing system (giraffe, elephant, dog, cat, etc.) could be preserved as `--vng-space-*` palette tokens for reference, similar to how `--vng-color-*` palette tokens are preserved. Not needed for functionality but aids traceability back to VNG source tokens.

## Recommendation
**APPROVE** — All MUST/SHALL requirements that exist in the actual token architecture are met. The two warnings are spec naming/reference issues (the spec references tokens that don't exist in the architecture), not implementation gaps. Safe to archive with `/opsx:archive`.
