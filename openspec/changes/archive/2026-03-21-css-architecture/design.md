# Design: css-architecture

## Context
Layered CSS architecture: design-systems.json declares ordered stylesheet bundles, Application::boot() loads the correct bundle. 7 layers: fonts, defaults, utrecht-bridge, theme, overrides, element-overrides, custom-overrides. Plus conditional CSS (hide-slogan, show-menu-labels).

## Decisions
1. design-systems.json is the single source of truth for stylesheet bundles
2. "none" design system loads no stylesheets (stock Nextcloud)
3. Custom overrides always loaded last
4. Conditional CSS loaded after custom overrides
