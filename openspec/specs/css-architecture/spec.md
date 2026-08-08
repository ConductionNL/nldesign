---
status: reviewed
reviewed_date: 2026-08-08
---

# CSS Projection Specification

## REQ-CSS-001: Exact precedence

The stylesheets MUST be returned in this order: fonts, the active ready
profile, `compatibility/nextcloud-core-v1`. The order MUST be represented by a pure
`RuntimeStylesheetPlan` with unit coverage.

## REQ-CSS-002: Safe runtime selection

Only a ready profile accepted by `TokenSetService` MAY be interpolated into a
stylesheet name. The catalogue's null default MUST initialize native Nextcloud
with no stored state. Missing, source-only, or invalid stored active state MUST
emit no profile CSS and preserve native Nextcloud presentation.

Styles MUST be attached from normal and login template events. State,
catalogue, or planning exceptions MUST be logged and MUST NOT abort response
rendering.

Only explicitly allowlisted Nextcloud majors MAY receive the projection.
NC32–34 MUST resolve to the shared `nextcloud-core-v1` contract; an unknown
major MUST emit no NL Design stylesheet stack. A separate major contract MUST
have evidence of a semantic difference in a consumed property or theme-state
mechanism.

## REQ-CSS-003: Complete profile projection

Every ready profile MUST define `--nldesign-font-family`,
`--nldesign-color-primary`, `--nldesign-color-primary-text`, and
`--nldesign-color-primary-hover`. The runtime MUST NOT fill missing values from
another organisation's defaults or generate CSS at runtime.
It MUST contain no other declarations, except dark-mode overrides of those same
properties, and MUST remain within the declared count and byte budgets.
An explicit dark projection MUST provide the same colour overrides for
Nextcloud's system-following default theme under
`@media (prefers-color-scheme: dark)`.

## REQ-CSS-004: Bounded mapping

`compatibility/nextcloud-core-v1.css` MUST map only the reviewed font and
primary interaction roles to Nextcloud core custom properties. It MAY use
root/body theme-state guards but MUST NOT contain component or structural
Nextcloud selectors.
User theme calculations and unmapped framework variables remain owned by
Nextcloud.

## REQ-CSS-005: Accessibility precedence

The projection MUST NOT override the explicit OpenDyslexic font. It MUST NOT
map profile colours when an explicit Nextcloud high-contrast theme is active
or the operating system requests more contrast.

## REQ-CSS-006: Local font assets

The build MUST produce weights 400 and 700, normal and italic, in WOFF and
WOFF2. Every face MUST use `font-display: swap`. The exact SIL OFL 1.1 notice
MUST ship beside the output. Missing source or licence input MUST fail the
build.

## REQ-CSS-007: No unevidenced presentation selectors

The runtime MUST NOT load the retired login-footer or app-menu selector styles.
A future selector-based surface adapter MUST define accurate semantics, a
bounded selector budget, failure isolation, and packaged browser evidence for
every claimed Nextcloud major before becoming reachable.

## REQ-CSS-008: Evidence limits

Passing static checks MUST NOT be represented as proof of visual, cross-app,
dark-mode, responsive, browser, or WCAG compatibility. Declared release
support requires packaged browser and integration evidence.
