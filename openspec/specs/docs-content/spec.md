---
status: reviewed
reviewed_date: 2026-08-08
---

# Documentation Content Specification

## REQ-DOCS-001: Truthful landing page

docs/intro.md MUST be the Docusaurus documentation root and MUST describe the
implemented profile plane before future roadmap capabilities. It MUST not claim
automatic Nextcloud Theming mutation, token editing, import/export, or App
Store publication when those capabilities are absent.

## REQ-DOCS-002: Install and configure

The getting-started section MUST distinguish a reviewed packaged install from
a source build. Until App Store publication is verified, it MUST state that no
App Store release is claimed.

Configuration instructions MUST cover selecting a ready profile, reloading
affected pages, understanding revisions and rollback, treating Theming values
as manual recommendations, and identifying the retired selector experiments as
unavailable rather than configurable features.

## REQ-DOCS-003: Current versus deferred capabilities

Feature pages MUST label each capability as implemented, manual, experimental,
or deferred. Pages retained for a deferred token editor, import/export flow, or
apply dialog MUST be explicit design notes and MUST not read as operating
instructions.

The token-set page MUST distinguish package inventory from runtime
availability: currently 40 manifest records and matching stylesheets, with 8
ready projections and 32 source-only records.

## REQ-DOCS-004: Architecture and roadmap

Documentation MUST explain the separation between ready profile state and
instance-owned Nextcloud Theming state. It MUST identify private OCA\Theming
usage as an isolated, unregistered compatibility experiment and describe the
public OCP\Theming direction.

Security, compatibility, accessibility, and standards claims MUST name their
evidence and residual gaps. Static checks MUST not be presented as browser,
cross-app, or WCAG certification.

## REQ-DOCS-005: Developer reference

Reference material MUST direct app developers toward stable Nextcloud variables
and semantic markup, warn against hardcoded colors and generated scoped
selectors, and require testing with representative profiles and Nextcloud
versions.

## REQ-DOCS-006: Reproducible site

The documentation project MUST use a supported Node version, install from its
lock file, pass the shipped-runtime audit, build without broken links, and
produce index.html. Build-only dependency advisories with no upstream fix MUST
be recorded as residual risk rather than hidden. Any temporary exception MUST
allowlist exact advisories and their dependency chains, reject new or escalated
findings, and carry an executable input-mitigation test.
