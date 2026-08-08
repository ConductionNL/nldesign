---
status: reviewed
reviewed_date: 2026-08-08
---

# Nextcloud Theming Bridge — Technical Design

## Current decision

Profile activation and Nextcloud Theming are separate operations.

The production path only builds a manual hand-off plan. It does not write
settings owned by the core Theming app, upload images, or register private
Theming services.

This separation is deliberate:

- the profile plane owns NL Design CSS selection and rollback;
- the branding plane owns any future mutation of Nextcloud Theming;
- a failure or incompatibility in the branding plane must never prevent profile
  selection, page rendering, settings access, or profile rollback.

## Current component flow

    token-sets.json
        -> TokenSetService
        -> ManualThemingPlanBuilder
        -> GET /settings/theming-plan
        -> administrator reviews the recommendations

TokenSetService exposes only normalized, allowlisted hints:

- primary_color and background_color as six-digit hexadecimal colors;
- logo under img/logos;
- background under img/backgrounds.

ManualThemingPlanBuilder repeats this validation and returns an explicitly
non-executing result with mode manual and appliesAutomatically false.

## Compatibility experiment

`lib/Infrastructure/Nextcloud/Compatibility/PrivateThemingProbe.php` is an
isolated, read-only prototype for detecting the minimum method shape researched
for a future version-specific adapter. It references private
`OCA\Theming\ThemingDefaults` and `OCA\Theming\ImageManager` class names but
does not resolve either service and exposes no mutation method.

It is intentionally:

- outside controllers, settings, domain services, and render listeners;
- absent from application registration and routes;
- not autowired into the production graph;
- not a fallback for failed public operations;
- not a supported automatic capability.

Method presence is diagnostic only. It cannot produce `private-verified`
capability without reflected-signature checks and packaged lifecycle tests.

The repository architecture check rejects OCA\Theming references outside this
compatibility directory.

## Public API direction

The preferred future backend is a public OCP\Theming contract. A public
IDefaults-style API can improve read compatibility, but reading effective
defaults is not equivalent to a complete mutation API. Automatic application
must wait for a supported mutation contract or for a separately verified,
version-gated private adapter.

A future bridge must use a neutral branding plan and driver interface. Driver
selection belongs in infrastructure and must produce an explicit capability:

- public-supported;
- private-verified for one exact tested version cell;
- manual-only.

Unknown versions, failed probes, or a kill switch must resolve to manual-only.
They must never fall through to raw configuration writes.

## Preconditions for automatic application

Automatic application is deferred until all of these exist:

1. typed branding plans with old and new values;
2. a capability probe for every declared Nextcloud/Theming version cell;
3. validation and staging before the first write;
4. snapshot, read-back verification, and compensating rollback;
5. operation revision checks and an audit trail;
6. integration tests against packaged Nextcloud instances;
7. a public-only kill switch and documented recovery path.

Profile dropdown changes must never trigger this bridge implicitly.
