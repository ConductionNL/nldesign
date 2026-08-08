---
sidebar_position: 3
---

# Compliance status

This repository does not currently make a blanket Rijkshuisstijl, NL Design System, WCAG, or Dutch-government compliance claim.

The app uses Fira Sans rather than restricted government fonts and does not establish permission to use official identity marks. It also projects CSS onto changing Nextcloud markup, so conformance cannot be inferred from token values alone.

## Evidence required for a claim

- authoritative source and version for each profile value;
- documented transformation and inheritance;
- identity/trademark and redistribution review;
- rendered colour-pair measurements, including interaction states;
- keyboard, focus, zoom, reflow, reduced-motion, dark, and high-contrast tests;
- exact Nextcloud major, browser, and enabled-app matrix; and
- dated evidence with known exceptions and an expiry policy.

The manifest validator and unit tests are release inputs, not a compliance certificate. The prior percentage/checkmark checklist was retired because it mixed static colour inventory with unmeasured rendered behavior.
