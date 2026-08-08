---
sidebar_position: 6
---

# Asset policy

The release package currently contains only the app icons, generated local
Fira Sans webfonts, and empty directories reserved for reviewed profile assets.
It does not ship municipality logos, an official Rijkslogo, a generic icon
library, remote image references, or embedded data-URI logos.

## Fonts

Fira Sans comes from the pinned @fontsource/fira-sans npm dependency. Its
copyright and SIL Open Font License notice ship as `css/fonts/OFL-1.1.txt`.
The root build copies exactly these local files:

- weights 400 and 700;
- normal and italic styles;
- WOFF and WOFF2 formats.

scripts/build-fonts.js fails if any expected source file is absent. Runtime
pages make no font-CDN or package-registry request.

The package does not contain RijksoverheidSansWebText. Using Fira Sans is a
technical fallback, not proof of Rijkshuisstijl conformance.

## Logos and backgrounds

img/logos and img/backgrounds are intentionally empty in the current package.
Nextcloud's configured instance logo remains the runtime owner and fallback.

A future asset may enter one of these directories only when the change records:

1. the exact source and immutable revision;
2. copyright licence and required attribution;
3. trademark or identity-use authorization where relevant;
4. file hash, media type, byte size, and dimensions;
5. sanitization evidence for SVG;
6. the profile and surface that actually use it.

The repository's EUPL-1.2 licence does not itself grant rights in a third
party's logo, trade name, or visual identity.

## CSS asset boundary

The packaged-profile validator rejects:

- remote, protocol-relative, file, JavaScript, and data URLs;
- unresolved source-token placeholders;
- @import, expression, behavior, and -moz-binding constructs;
- local asset paths outside ../../img/logos or ../../img/backgrounds;
- missing, symlinked, or escaping local files.

This keeps the installed app self-contained and prevents a profile from
silently creating a third-party request.

## Adding an asset

Do not add an asset solely because an upstream token package contains it.
First establish the rendering need and rights. Then add the reviewed file,
reference it through an allowlisted profile or manual-Theming hint, and run:

    npm run check:manifest
    npm run stylelint

Automatic upload into Nextcloud Theming remains deferred.
