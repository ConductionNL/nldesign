# theming-capability

**Spec refs**: openspec/specs/theming-capability/spec.md, openspec/changes/theme-switchable-iconography/specs/icon-packs/spec.md

## MODIFIED Requirements

### Requirement: Theming Capability Payload

The `nldesign` capability value MUST be an object with exactly these keys:

- `version` (string) — the installed nldesign app version;
- `tokenSet` (object) — the active token set: `id` (string; appconfig `token_set`, default
  `"nextcloud"`), `name` (string; the manifest display name, falling back to the id when the
  active set has no manifest entry), `version` (string|null; the manifest's optional version
  field, `null` when undeclared);
- `designSystem` (string) — the resolved design-system id for the active set (via
  `DesignSystemService::getTokenSetMeta()`; `"none"` for stock Nextcloud);
- `iconPacks` (array of strings) — the resolved ordered icon-pack list for the active set, via
  `DesignSystemService::resolveActiveIconPacks()` (`openspec/specs/icon-packs/spec.md`); an empty
  array when no pack is active;
- `wcagLevel` (string|null) — the audited contrast conformance of the active set per the WCAG
  Audit Level requirement;
- `logos` (object) — available logo variant web paths for the active set, keyed by variant name;
  `default` maps to the web path of the manifest's `theming.logo` asset; an empty object when the
  set declares no logo. Additional variants (e.g. `dark`) are reserved for future changes
  (dark-mode-token-variants) — consumers MUST treat unknown variant keys as additive;
- `hideSlogan` (bool) — whether the `hide_slogan` toggle is active;
- `showMenuLabels` (bool) — whether the `show_menu_labels` toggle is active.

The payload MUST reflect the live appconfig state (no stale snapshot across a token-set change)
and MUST contain only branding facts already observable by anyone loading the themed login page —
never admin configuration such as the per-app exclusion list, custom override CSS content, or
filesystem paths outside web asset paths.

#### Scenario: Payload for an active shipped set
- GIVEN the active token set is `rijkshuisstijl` with a manifest entry declaring name
  "Rijkshuisstijl" and `theming.logo: img/logos/rijkshuisstijl.svg`
- AND `hide_slogan` is `'1'` and `show_menu_labels` is `'0'`
- WHEN the capability payload is computed
- THEN `tokenSet` MUST equal `{ "id": "rijkshuisstijl", "name": "Rijkshuisstijl", "version": null }`
  (no version declared in the manifest today)
- AND `logos.default` MUST be the web path resolving to `img/logos/rijkshuisstijl.svg`
- AND `hideSlogan` MUST be `true` and `showMenuLabels` MUST be `false`
- AND `iconPacks` MUST equal the resolved pack list for `rijkshuisstijl`'s design system
  (`["rvo", "open-gemeenten", "den-haag"]`)

#### Scenario: Payload for stock Nextcloud
@e2e exclude default-config branch — PHPUnit on Capabilities
- GIVEN no `token_set` appconfig value is set
- WHEN the capability payload is computed
- THEN `tokenSet.id` MUST be `"nextcloud"`
- AND `designSystem` MUST be `"none"`
- AND `iconPacks` MUST be an empty array
- AND `logos` MUST be an empty object

#### Scenario: Payload for a custom or unknown set degrades, never lies
@e2e exclude fallback branch — PHPUnit on Capabilities
- GIVEN the active token set id has no entry in `token-sets.json` (e.g. an admin-uploaded custom
  set)
- WHEN the capability payload is computed
- THEN `tokenSet.name` MUST fall back to the id and `tokenSet.version` MUST be `null`
- AND `wcagLevel` MUST be `null` (unaudited — the capability MUST NOT fabricate a conformance
  claim)
- AND `iconPacks` MUST be an empty array (no design system to resolve a pack from)

#### Scenario: No admin-only data leaks
@e2e exclude security assertion — PHPUnit asserts payload key allowlist
- GIVEN any configuration state
- WHEN the capability payload is serialized
- THEN it MUST contain only the eight specified keys
- AND it MUST NOT contain the per-app theming exclusion list, custom override CSS, custom
  token-set contents, or any server filesystem path

### Requirement: Capability Robustness

`Capabilities::getCapabilities()` MUST never throw: the Nextcloud capabilities endpoint
aggregates every app's capability, so one throwing provider breaks the document for all clients.
On any internal failure (unreadable manifest, throwing service), the method MUST degrade to a
minimal payload — `version` and `tokenSet.id` from raw appconfig, with `name` falling back to the
id and the remaining fields `null`, `{}`, `[]`, or `false` — and MUST NOT expose exception details
in the payload.

#### Scenario: Throwing dependency degrades gracefully
@e2e exclude error-path branch — PHPUnit with throwing service mocks
- GIVEN an injected service throws during payload computation
- WHEN `getCapabilities()` is called
- THEN it MUST return the `nldesign` key with the minimal payload
- AND `iconPacks` MUST be an empty array
- AND no exception MUST propagate to the OCS layer
- AND no exception message MUST appear in the payload
