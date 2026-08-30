# Tasks: add-vng-token-set

## 1. Create VNG Token CSS File

- [x] 1.1 Create css/tokens/vng.css with VNG palette and semantic tokens
  - **spec_ref**: `specs/vng-token-set/spec.md#requirement-vng-token-css-file`
  - **files**: `nldesign/css/tokens/vng.css`
  - **acceptance_criteria**:
    - GIVEN the tilburg-woo-ui VNG token source file exists
    - WHEN vng.css is created
    - THEN it contains `:root` declarations with `--vng-color-*` palette tokens using resolved hex values
    - AND it contains `--nldesign-*` semantic tokens mapped to VNG colors (primary=#003865, error=#bf1a12, success=#01745a, warning=#d45f01)
    - AND it contains typography tokens with Avenir font family
    - AND it contains spacing and border radius tokens
    - AND it contains header and background tokens
    - AND no `--utrecht-*` component tokens are present

## 2. Register in Manifest

- [x] 2.1 Add VNG entry to token-sets.json
  - **spec_ref**: `specs/vng-token-set/spec.md#requirement-token-set-manifest-entry`
  - **files**: `nldesign/token-sets.json`
  - **acceptance_criteria**:
    - GIVEN token-sets.json exists with current entries
    - WHEN the VNG entry is added
    - THEN an entry with id "vng", name "VNG Vereniging Nederlandse Gemeenten", and a description exists
    - AND the file remains valid JSON
    - AND existing entries are unchanged

## 3. Verification

- [x] 3.1 Verify VNG token set loads and applies correctly
  - **acceptance_criteria**:
    - GIVEN vng.css exists and token-sets.json includes VNG
    - WHEN the admin selects VNG in nldesign settings
    - THEN the theme applies without errors
    - AND the VNG color palette is visible in the UI
    - AND the admin dropdown lists VNG as a selectable option
