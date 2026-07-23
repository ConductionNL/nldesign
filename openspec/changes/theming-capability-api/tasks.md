## 1. Capability class

- [ ] 1.1 Create `lib/Capabilities.php` (`OCA\NLDesign\Capabilities`) implementing
      `OCP\Capabilities\IPublicCapability` with full SPDX `@license`/`@copyright` docblock.
      Constructor-inject `IConfig`, `IAppManager` (app version), `IURLGenerator` (logo web
      paths), `DesignSystemService`, `TokenSetService`, `ShippedTokenSetAuditService`, and
      `ICacheFactory`.
- [ ] 1.2 Implement `getCapabilities(): array` returning
      `['nldesign' => ['version', 'tokenSet' => ['id','name','version'], 'designSystem',
      'wcagLevel', 'logos', 'hideSlogan', 'showMenuLabels']]` exactly as specced in
      `specs/theming-capability/spec.md`:
      - `tokenSet.id` from appconfig `token_set` (default `nextcloud`); `name`/`version` from the
        `token-sets.json` entry via `TokenSetService::getAvailableTokenSets()` (fall back to the
        id as name and `null` version for custom/unknown sets);
      - `designSystem` from `DesignSystemService::getTokenSetMeta()` (`design_system`, default
        `nldesign`);
      - `logos` from the set's `theming.logo` manifest path converted to a web path via
        `IURLGenerator` (`{ "default": <path> }`, `{}` when absent);
      - `hideSlogan`/`showMenuLabels` from appconfig `hide_slogan`/`show_menu_labels` `'1'`
        comparisons (same semantics as `Application::injectThemeCSS()`).
- [ ] 1.3 Implement `wcagLevel` computation: for a shipped set, run
      `ShippedTokenSetAuditService::auditSet()` — `"AAA"` when the set declares
      `contrast_level: "AAA"` and passes at AAA thresholds, else `"AA"` when it passes at AA,
      else `"fail"`; `null` for custom/unknown sets. Cache the resolved level in an
      `ICacheFactory` distributed cache keyed by active token set id (TTL ≤ 1 hour) so
      capabilities requests never re-parse token CSS; a cache miss computes and stores.
- [ ] 1.4 Wrap the whole payload computation in a try/catch: on any Throwable, return the minimal
      payload (`version` + `tokenSet.id` from raw appconfig, remaining fields `null`/`{}`/`false`)
      — the capabilities endpoint aggregates all apps and one throwing capability breaks it for
      every client. Never expose exception details.

## 2. Registration

- [ ] 2.1 In `lib/AppInfo/Application.php` `register()`, add
      `$context->registerCapability(Capabilities::class);` with the corresponding `use` import,
      and update the method docblock (it currently claims no bootstrap-time registration is
      required — keep the AppHost/health paragraph, add the capability sentence). Add `@spec`
      tags per the spec-coverage gate.

## 3. Tests

- [ ] 3.1 New `tests/Unit/CapabilitiesTest.php` covering:
      - full payload shape and key presence for a shipped set (mock config `token_set` =
        `rijkshuisstijl`): id/name echo the manifest, `designSystem` resolved, `logos.default`
        is the manifest logo web path, toggles reflect config;
      - default config (no `token_set` set) → `tokenSet.id === 'nextcloud'`,
        `designSystem === 'none'`, `logos === {}` (JSON empty object semantics);
      - custom/unknown token set id → name falls back to the id, `version` and `wcagLevel` are
        `null`;
      - `wcagLevel` cache behaviour: second call within TTL does not re-invoke the audit service
        (mock expects exactly one `auditSet` call);
      - degradation: a throwing injected service yields the minimal payload, no exception
        escapes `getCapabilities()`.
- [ ] 3.2 Run the PHP suite in the nextcloud:34 container
      (`docker run --rm -v $PWD:/app -w /app <nc-image> php vendor/bin/phpunit -c phpunit-unit.xml`)
      and `composer check:strict`; both green.

## 4. Verify

- [ ] 4.1 Deploy to the 8080 dev instance (respect bind-mount topology; restart apache so
      opcache picks up the new class: `apachectl -k restart` in the container if needed).
- [ ] 4.2 Live curl, authenticated:
      `curl -s -u admin:admin -H "OCS-APIRequest: true" "http://localhost:8080/ocs/v2.php/cloud/capabilities?format=json" | jq '.ocs.data.capabilities.nldesign'`
      → object present with all seven keys and values matching the current admin-panel state.
- [ ] 4.3 Live curl, unauthenticated (IPublicCapability contract):
      `curl -s -H "OCS-APIRequest: true" "http://localhost:8080/ocs/v2.php/cloud/capabilities?format=json" | jq '.ocs.data.capabilities.nldesign'`
      → same object present without credentials.
- [ ] 4.4 Flip state and re-read: switch the active token set in the admin panel (browser-1,
      `/settings/admin/nldesign`), toggle hide-slogan, re-run the curl from 4.2 and confirm
      `tokenSet.id`, `wcagLevel`, `logos`, and `hideSlogan` all changed accordingly (proves the
      audit cache invalidates on token-set change).
