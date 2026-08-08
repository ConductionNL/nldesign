# Quick start

This guide is for a source checkout. It assumes a Nextcloud 32–34 test instance and administrator access.

## 1. Prepare the app

```bash
cd /path/to/nextcloud/custom_apps/nldesign
composer install
npm ci --ignore-scripts
npm run build
```

The build copies eight pinned Fira Sans files into `css/fonts/`. It fails if the package or an expected file is missing.

## 2. Enable it

```bash
php /path/to/nextcloud/occ app:enable nldesign
```

For Docker, run the same `occ app:enable nldesign` command in the Nextcloud container as the web-server user.

## 3. Select a profile

1. Sign in as an administrator.
2. Open **Administration settings → Theming**.
3. Find **NL Design profiles**.
4. Leave **Native Nextcloud** selected or choose one of the ready profiles. Source-only inventory entries are not shown.

The selection is saved immediately. Reload another page to verify the runtime styles. If another administrator changed the profile concurrently, the page rejects the stale save and reloads current state.

The app starts in the native state and does not implicitly apply Rijkshuisstijl
or another organisation's profile. Returning to **Native Nextcloud** is a
revision-checked transition and can be rolled back like a profile change.

## 4. Treat Nextcloud Theming separately

The **Nextcloud Theming hand-off** panel is read-only. When a profile declares recommendations such as a primary colour, copy them manually only if they match the instance's identity policy.

Profile activation does not change Nextcloud's logo, background, email branding, mobile-client branding, or other Theming-owned state.

## 5. Verify

- In browser developer tools, font requests should resolve under `/apps/nldesign/css/fonts/`, not a third-party CDN.
- `data-current-token-set` and the revision shown on the settings page should update after a successful save.
- **Roll back to previous profile** should become available after the first actual profile change.
- `php occ config:app:get nldesign token_set` should show the compatibility mirror of the active profile, or an empty value for native Nextcloud.

## Troubleshooting

Run the local gates first:

```bash
composer check
npm test
```

If the catalogue is empty, run `npm run check:manifest` and confirm each manifest id has a matching readable `css/tokens/{id}.css` file. If styles appear stale, hard-reload the browser and inspect Nextcloud's application log for an `NL Design styles could not be attached` warning.

Do not edit or write runtime state into the installed app directory. Profile state belongs in Nextcloud app configuration; generated or uploaded assets belong in app data when those features are implemented.
