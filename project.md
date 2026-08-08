# NL Design project contract

## Product

NL Design is a pre-release Nextcloud administration app that can select one statically gated profile and project a small CSS contract onto Nextcloud core. Native Nextcloud remains the initial and explicitly restorable state. The package inventories 40 manifest-backed profile snapshots; 8 currently declare the `nextcloud-core-v1` projection and are selectable.

The load-bearing product is the profile plane. A future bridge to state owned by Nextcloud Theming is optional, removable, and must not break profile selection or rendering when absent.

## Current implementation

- PHP 8.2+ and the Nextcloud App Framework
- Vanilla PHP admin template and JavaScript
- App-scoped `OCP\AppFramework\Services\IAppConfig`
- Manifest plus packaged CSS; no app database tables
- Three ordered runtime CSS layers with only root/theme-state guards, never component or structural selectors
- Self-hosted Fira Sans build output
- EUPL-1.2 app code

Profile activation and deactivation are revision-checked and retain one rollback snapshot plus bounded history. Nextcloud Theming recommendations are read-only. Token editing, import/export, and automatic Theming mutation are deferred.

## Runtime stylesheet contract

1. `css/fonts.css`
2. `css/tokens/{profile}.css`
3. `css/compatibility/nextcloud-core-v1.css`

The order is implemented by `RuntimeStylesheetPlan` and covered by unit tests.
An explicit runtime allowlist maps Nextcloud 32–34 to the shared core contract;
unknown majors emit no NL Design stack. A separate contract is added only for
an audited semantic delta.

## Boundaries

- `token-sets.json` is the catalogue; filesystem discovery is not an availability API, and `source-only` entries are not runtime profiles.
- Runtime writes below the installed app path are forbidden.
- Private `OCA\Theming` names are allowed only in `lib/Infrastructure/Nextcloud/Compatibility/`.
- The private compatibility prototype is currently unregistered.
- Organisational profile names and values are not an endorsement or rights grant.
- Accessibility and app/version compatibility require measured evidence, not a repository-wide claim. Explicit high-contrast and OpenDyslexic preferences outrank the profile projection.

## Verification

Run `composer check`, `npm test`, both dependency audits, the asset build, and the Docusaurus build. Live release evidence must additionally cover the supported Nextcloud-version and app-surface matrix.
