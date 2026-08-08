---
sidebar_position: 5
---

# Brand identity evidence

The ready profiles are technical projections, not certified copies of each
organization's complete identity system.

token-sets.json is the current runtime catalogue. css/tokens contains the
compiled values. Together they answer what this app ships; they do not prove
who approved a value, whether it is still current upstream, or whether every
use is legally and semantically appropriate.

## Current identity surface

The current package can project color, typography, radius, and selected
component values through CSS. Seven profiles also contain a validated
primary-color recommendation for manual use in Nextcloud Theming.

The package ships no organization-specific logos or backgrounds. Instance
logos remain controlled by Nextcloud Theming. This avoids presenting an
unverified asset collection as licensed identity material.

## Evidence still needed per profile

Before a profile can be described as verified, its source descriptor should
record:

- upstream repository or official publication;
- immutable version, commit, or release;
- exact source-token paths and transformation rules;
- semantic rationale for each value projected into Nextcloud;
- licensing and identity-use terms;
- contrast measurements and representative browser results;
- review date, reviewer, and supported Nextcloud surface matrix.

The present catalogue lacks that complete evidence for most profiles.

## Interactive color is not automatically brand color

Nextcloud's primary color drives controls and focus-adjacent UI. An
organization's most recognizable identity color may be intended only for a
logo, header, or decorative surface. A compiler must therefore map by semantic
role, not by choosing the first upstream color called primary or brand.

The manual Theming hints in the manifest are recommendations only. An
administrator remains responsible for confirming them against the instance's
identity policy.
