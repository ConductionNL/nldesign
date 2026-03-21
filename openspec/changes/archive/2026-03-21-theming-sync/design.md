# Design: theming-sync

## Context
Synchronizes design token values with Nextcloud's built-in theming (ThemingDefaults + ImageManager) to prevent split-brain theming state.

## Decisions
1. Theming metadata in token-sets.json (primary_color, background_color, logo, background)
2. Color validation via hex regex
3. Image validation for path traversal and directory restrictions
4. ThemingDefaults::set() for colors, ImageManager::updateImage() for images
