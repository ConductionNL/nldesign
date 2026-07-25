# Design — lasuite-token-completeness

## Problem framing

Two concerns were conflated in one hand-edited file. The shipped `defaults.css` claims to be the
"Cunningham light root block" but actually carries the **deployed violet** values (`brand-600
#534fc2`, `brand-650 #4844ad`). The real Cunningham npm base is **blue** (`brand-600 #0659C5`). This
change separates:

- **Base (generated, blue):** the 1167-token Cunningham build the MIT npm packages publish.
- **Deployment override (sourced, violet):** the block-5 override observed only in the live La Suite
  Docs bundle, not present in any package.

Keeping these in separate files makes provenance honest (generated vs observed), makes the base
re-generatable, and lets the same base serve both the deployed violet `lasuite` set and a published
blue `cunningham` set.

## The reversible `--c--` → `--lasuite--` mapping

Cunningham names use `--` as the hierarchy separator and single `-` inside a segment, e.g.
`--c--globals--colors--brand-600`. The mapping is a **prefix swap only**, preserving every `--`:

```
--c--<rest>   ⇄   --lasuite--<rest>
```

so `--c--globals--colors--brand-600` → `--lasuite--globals--colors--brand-600`. This is trivially
reversible (swap the leading token back) and lossless — no segment collapsing, no ambiguity. The
generator documents the rule in its header and applies it mechanically to all 1167 tokens.

### Compatibility aliases (why the bridge does not break)

The existing `bridge.css` and `element-overrides.css` read a **collapsed** short form
(`--lasuite-color-brand-650`, `--lasuite-border-radius`, `--lasuite-spacing-md`, …). Rather than
rewrite both consumers in this change (larger blast radius), the generator appends a small,
**explicitly listed** alias block at the end of `defaults.css`:

```css
/* Compatibility aliases — short names consumed by bridge.css / element-overrides.css.
 * Each maps to a canonical generated token above; list is closed and reviewed. */
:root {
  --lasuite-color-brand-650: var(--lasuite--globals--colors--brand-650);
  --lasuite-border-radius:   var(--lasuite--components--button--border-radius);
  /* … the ~37 names the two layers actually read … */
}
```

The alias list is data in the generator (a small map), so it is reviewed and stable, and the
canonical 1167 tokens are the source of truth. A later change may migrate the consumers to canonical
names and drop the alias block — out of scope here.

## Layering

```
fonts → defaults (blue base, generated) → brand-override (violet, sourced)
      → bridge → element-overrides,  then tokens/lasuite (Layer 3)
```

`brand-override.css` redeclares only the tokens block 5 changes (`brand-*`, `logo-*`, and any
brand-derived contextual tokens), on `:root`, after `defaults`. Because both files write plain
`:root`, later wins — the cascade resolves to violet for the `lasuite` bundle. The `cunningham`
bundle simply omits `brand-override`, so it resolves to blue from the same `defaults.css`.

`token-sets.json` `lasuite.primary_color` stays `#4844AD` — it is display metadata for the admin
dropdown/preview, and `#4844AD` is the violet brand-650/logo, which is the correct swatch to show for
the deployed theme. `cunningham.primary_color` is `#1A509F` (blue brand-650, NOT brand-600
`#0659C5`): the shared `bridge.css`/`element-overrides.css` (reused as-is, unmodified, from the
lasuite bundle — see "Rewrite bridge/element-overrides to canonical names now" under Alternatives)
derive `--color-primary` and every brand-accent rule from `--lasuite-color-brand-650` specifically,
the same step that resolves to lasuite's deployed violet `#4844AD`. Pinning `cunningham.primary_color`
to brand-650's blue value keeps the swatch honest about what the design system actually renders,
matching the pattern `lasuite.primary_color` already establishes. (`brand-600 #0659C5` remains the
correct value for the raw *generated token* `--lasuite--globals--colors--brand-600` in
`defaults.css` — that fact is unrelated to and unaffected by this correction.)

## Bridge coverage model

The `nextcloud-variable-mapping` spec defines the audited Nextcloud `--color-*` surface; the concrete
enumeration lives in `css/systems/nldesign/overrides.css` (68 `--color-*` variables) and
`docs/reference/mappings.md`. `bridge.css` currently covers ~30. Completing it means: for every one of
those variables, either (a) map it to a `--lasuite--*` (via a short alias where convenient) with
`!important` at `body[data-themes]`, or (b) leave it as a commented line with a reason — and the
reason for `--color-main-background*`, `--color-background-plain`, and `--background-invert-if-*` is
the existing dark-mode-compat exclusion (`REQ-CSS-007`), which MUST NOT be overridden. Coverage is
"every audited variable appears in bridge.css as a mapping or a reasoned comment", which a test can
assert by diffing the variable name sets.

## Component-parity e2e under load-fragility

Issue #181: the e2e instance falls over under heavy parallel navigation. The parity spec therefore:

- runs **serial** (`test.describe.configure({ mode: 'serial' })`), one page load reused across
  element checks where possible;
- checks a **small** fixed set (button, input, modal, header, table) — one Playwright `test()` per
  element so a flake isolates to one element, not the whole suite;
- reads **computed** styles via `getComputedStyle` in `page.evaluate`, compares against a small table
  of Cunningham reference values (imported constants, not re-derived at runtime), and on mismatch
  throws an assertion message naming the exact property and the expected-vs-actual delta;
- normalises values before compare (rgb() vs hex, `4px` vs `4px`, font-family list whitespace) so a
  notation difference is not reported as a delta (the same `#ffffff`/`#fff` class of false positive
  the audit flagged).

Reference values come from the Cunningham build for the **active** set: violet for `lasuite`
(brand-650 `#4844ad`, radius `4px`, Inter/Marianne stack), blue for `cunningham` (brand-650
`#1a509f`, same radius/font stack — the shared bridge/element-overrides derive every rendered
brand-accent from brand-650, not brand-600). The spec reads the active token set and selects the
matching reference table.

## Drift guard

`scripts/generate-lasuite-tokens.mjs` writes to a path given by an arg/env (default the committed
file). `test:lasuite-tokens` runs it into a temp file and `diff`s against the committed
`defaults.css`; non-empty diff exits non-zero with the first differing tokens printed. This is the
same shape as `tests/l10n/check-l10n-completeness.js` and its `test:l10n:completeness` script, so it
slots into the existing test conventions and CI without new infrastructure. Because the generator is
deterministic and the npm version is pinned in `package.json`, the check is stable; an upstream bump
is a deliberate `package.json` change + regenerate + commit, surfaced by the diff.

## Alternatives considered

- **Regenerate on boot / at runtime.** Rejected — the app has no build step at runtime and CSP-clean
  self-hosting requires committed static CSS. Generation is a dev-time step with committed output,
  matching `generate-tokens.mjs`.
- **Fold the violet into the generated file.** Rejected — it would mislabel observed values as
  generated and re-introduce the provenance lie; and it would block the blue `cunningham` sibling
  from reusing the base.
- **Rewrite bridge/element-overrides to canonical names now.** Deferred — larger diff, and the alias
  block gives full resolution today. Migration can be its own change.
- **Skip the `cunningham` sibling.** It is optional; kept because the blue base is the *published*
  artifact and it costs one token file + two manifest entries reusing existing files. A builder may
  drop it without affecting the `lasuite` deliverable — the spec marks it SHOULD, not MUST.
