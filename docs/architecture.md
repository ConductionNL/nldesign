---
sidebar_position: 9
---

# NL Design for Nextcloud — Extendable Runtime Architecture

**Status:** profile foundation implemented; core-settings bridge proposed

**Evidence snapshot:** 2026-08-08

**Target:** architecture-v1 on the current `main`-based application line

Implementation status is tracked in [feature-ledger.md](./feature-ledger.md).
The current working tree implements the bounded profile/state slice; modules
described for preview, app-data snapshots, CLI recovery, capability brokering,
and automatic core-Theming mutation remain target architecture until the
ledger says otherwise.

## 1. Decision

NL Design is a profile renderer with an optional core-settings bridge.

Those are two cooperating but independent planes:

1. The **profile plane** selects a compiled design profile and projects a small set of semantic values into supported Nextcloud web surfaces. It owns the primary product promise and uses public PHP/runtime APIs, documented CSS variables where available, and explicitly tested surface adapters.
2. The **core-settings bridge** can propose and, where supported, apply instance-branding values owned by Nextcloud's Theming app. It may temporarily use tested private services or narrowly defined internal configuration recipes, but the profile plane must continue to work when every such adapter is absent or disabled.

Private access is therefore allowed, packaged, and useful—not merely theoretical—but it is an interchangeable infrastructure detail. No profile, controller, stored instance state, or ordinary page-render path may know an `OCA\Theming` class name, a Theming config key, or a version-specific side effect.

The intended dependency rule is:

    public controller / template listener
                    |
                    v
             application use case
                    |
                    v
          domain values and stable ports
             /                 \
            v                   v
     app-owned adapters      capability broker
       (always usable)            |
                       +----------+----------+
                       |          |          |
                       v          v          v
                    public     private     manual
                    OCP API   NC adapter   fallback

Deleting `Infrastructure/Nextcloud/Compatibility` must remove automatic core-Theming writes, not break profile discovery, preview, activation, rendering, rollback, settings-page access, or CLI recovery.

## 2. Architectural invariants

1. **The profile path is load-bearing; compatibility adapters are not.** A missing or broken bridge becomes `manual` or `unavailable`, never an app boot failure.
2. **No private dependency during ordinary requests.** Private Theming services are resolved lazily only for administrator capability, plan, apply, verify, or rollback operations.
3. **No generic setting bag.** HTTP requests and profile manifests use typed field IDs and operations. They never accept an arbitrary target app, config key, service name, or server path.
4. **Ownership remains visible.** Profile CSS, profile recommendations, instance identity, administrator policy, user preference, and derived core state are separate data classes.
5. **Selection is not mutation.** Activating a profile never silently writes core Theming state. The administrator confirms a separate field-by-field plan.
6. **Every mutation has a capability and access level.** Read-effective, read-configured, set, reset, asset-write, verify, and restore support are reported separately.
7. **Every automatic write has recovery evidence.** Snapshot before mutation, validate and stage first, reject stale plans, read back, and roll back on failure.
8. **A lifecycle owner is preferred over raw storage.** A private core service that performs required side effects is safer than writing its config keys directly.
9. **The installed app is immutable.** Runtime state, generated previews, uploaded assets, operation records, and snapshots live in app configuration or app data.
10. **No cross-profile value inheritance or package-selected identity.** A ready profile supplies every load-bearing value. The manifest default is required to be null, so only explicit instance state can activate an organisation. Unavailable stored state emits no profile CSS and fails open to native Nextcloud.
11. **Accessibility preferences outrank branding.** The bridge cannot change user themes; profile CSS cannot defeat dark or high-contrast modes.
12. **Private code is removable by construction.** A public API migration adds a preferred driver and retires a compatibility driver without changing profiles, controllers, use cases, or persisted domain data.

## 3. Current foundation and remaining seams

| Current code | Current status | Remaining target seam |
|---|---|---|
| `AppInfo/Application.php` and `Listener/TemplateStylesListener.php` | Public template-event registration; fail-open three-layer injection | Add surface adapters only when measured evidence warrants them |
| `Service/TokenSetService.php` and `Infrastructure/Profile/*` | Versioned manifest envelope with a required null default, ready/source-only status, normalized metadata, bounded immutable file access | Move behind a `ProfileCatalog` port when external profile sources are introduced |
| `Service/ProfileStateService.php` and `Infrastructure/Nextcloud/ProfileStateMutationGuard.php` | App-scoped nullable profile state, strict revisions, exclusive lock with public app-config cache refresh, activation/deactivation rollback, bounded history | Move persistence behind ports before adding app-data snapshots or CLI workflows |
| `Controller/SettingsController.php` | Authorized typed actions using profile/state services | Introduce request DTOs and versioned use cases before adding the bridge API |
| `css/theme.css` | Font and primary interaction mapping with only body/theme-state guards | Add no mapping without semantic, accessibility, and version evidence |
| `Infrastructure/Nextcloud/Compatibility/PrivateThemingProbe.php` | Unregistered read-only private method-shape probe | Replace with capability-driven version adapters; method presence alone never enables a write |
| Retired login-footer and app-menu selectors | Removed from routes, settings, and runtime | Reintroduce only as a bounded surface adapter with accurate semantics and packaged exact-major evidence |

The deleted runtime CSS generator, token editor, broad selector stack, and
general private Theming service are not interfaces to preserve.

## 4. Modules and dependency direction

The PHP namespace layout should make the dependency rule inspectable:

```text
lib/
  Domain/
    Profile/
    Projection/
    Branding/
    Policy/
    Operation/
  Application/
    Profile/
    Branding/
    Recovery/
  Port/
    Profile/
    State/
    Branding/
    Presentation/
  Infrastructure/
    Profile/
    Persistence/
    Nextcloud/
      Presentation/
      PublicApi/
      Compatibility/
        VerifiedByMajor/
        Experimental/
      Manual/
  Controller/
  Listener/
  Settings/
```

Dependencies point inward:

- `Domain` depends on no Nextcloud class.
- `Application` depends on `Domain` and `Port` only.
- `Port` contains interfaces and stable transfer types; it does not import `OCA`.
- `Infrastructure` implements ports and may depend on Nextcloud.
- only `Infrastructure/Nextcloud/Compatibility/**` may import private `OCA\Theming` classes or know raw Theming storage keys;
- controllers, listeners, and settings classes call application services and contain no domain decisions.

An automated architecture test must enforce the private-import rule.

### 4.1 Initial composition

The first composition root should bind explicit implementations rather than recreate today's service graph in new directories:

| Runtime role | Inward-facing contract or use case | First implementation |
|---|---|---|
| Profile discovery | `ProfileCatalog` | `ManifestProfileCatalog` |
| Active state | `ActiveProfileStore` | `AppConfigActiveProfileStore` |
| Profile recovery | `ProfileSnapshotStore` | `AppDataProfileSnapshotStore` |
| Page integration | `SurfaceAdapter` registry | `CoreShellSurfaceAdapter` plus `TemplateProfileListener` |
| Managed-field definitions | code-owned definition registry | `InstanceBrandingDefinitionProvider` |
| Driver selection | `CapabilityBroker` | public, compatibility, and manual driver providers |
| Branding change | `PlanBrandingChange`, `ApplyBrandingPlan` | port-driven application services |
| Operation recovery | `BrandingOperationStore` | `AppDataBrandingOperationStore` |

`Application::register()` wires stable registries and listeners. A future
bridge may register `CompatibilityDriverFactory` as a neutral provider, but
must never resolve a private concrete driver until an administrator use case
asks the broker for capabilities. No bridge provider is registered today.

## 5. Profile plane

### 5.1 Target release artifact

Source design tokens are compiled before packaging. Runtime PHP never downloads, merges, aliases, upgrades, or interprets an upstream token repository.

The current v1 package uses `token-sets.json` and `css/tokens/{id}.css`. The
compiler slice replaces that interim shape with immutable data and web assets
in package locations that Nextcloud can address normally:

```text
profiles/{profile-id}/
  profile.json
  preview.json
css/profiles/{profile-id}/
  core.css
  surface/{surface-id}.css
img/profiles/{profile-id}/
  {approved-assets}
```

`profile.json` contains the stable profile ID and version, provenance, source authority, declared base chain, modes, projection version, compatible Nextcloud range, surface fragments, core-Theming recommendations, asset metadata, integrity hashes, and verification evidence.

`core.css` and any surface fragment contain only the allow-listed Nextcloud projection. They are inert unless an active profile marker is present. They do not contain the complete upstream theme.

### 5.2 Runtime ports

```php
interface ProfileCatalog
{
    /** @return list<ProfileSummary> */
    public function listVisible(): array;

    public function get(ProfileId $id): CompiledProfile;
}

interface ActiveProfileStore
{
    public function read(): ActiveProfileState;

    public function compareAndSet(
        ProfileRevision $expected,
        ActiveProfileState $next,
    ): void;
}

interface ProfileSnapshotStore
{
    public function save(ProfileSnapshot $snapshot): SnapshotId;

    public function get(SnapshotId $id): ProfileSnapshot;
}
```

The target concrete catalogue reads only manifest-declared files and verifies
their hashes. The implemented v1 envelope already prevents filesystem
promotion and bounds every packaged file, but does not yet carry content
digests; hash verification remains a release-compiler gate.

### 5.3 Presentation extension point

A design profile is data. Support for a Nextcloud surface is code plus evidence.

```php
interface SurfaceAdapter
{
    public function descriptor(): SurfaceDescriptor;

    public function supports(NextcloudRuntime $runtime): bool;

    /** @return list<CompiledCssAsset> */
    public function assetsFor(CompiledProfile $profile): array;
}
```

The first adapter targets the core shell and documented variables. Later adapters may target Files, Calendar, Forms, or another app, but each declares the target app/version range, selector budget, modes, test evidence, and failure behavior. Adding a profile normally adds no PHP. Adding a new UI surface may add one adapter.

### 5.4 Request-time injection

`Application::register()` registers listeners for `BeforeTemplateRenderedEvent` and `BeforeLoginTemplateRenderedEvent`. `Application::boot()` performs no profile lookup and constructs no Theming service.

The implemented listener:

1. reads the small app-owned active-profile state;
2. resolves the selected ready profile through the bounded package inventory;
3. calls `OCP\Util::addStyle()` for the fixed fonts/profile/theme stack only
   during a template-render event;
4. catches profile failures, logs a structured diagnostic, and emits no NL
   Design CSS.

Because the selected profile stylesheet is attached only for active state, an
inactive or unavailable profile does not publish its `:root` variables. The two
registered Nextcloud events cover login and ordinary templates. The shared
listener also keeps a request-local idempotence flag, so the style queue receives
the stack once even if both event paths are dispatched in one request.

The compiler and surface-adapter slice will additionally:

1. verify manifest digests for compiled assets;
2. ask the surface registry for compatible fragments;
3. provide the active profile ID as initial state and add a small app-owned
   marker script;
4. root compiled selectors at that exact marker.

In that target slice, the marker script sets `data-nldesign-profile` on the
document element during startup. If initial state or the script fails, marked
CSS remains inert. Preview uses a marker on the isolated preview root and never
changes the document-level active marker. None of those marker capabilities is
claimed as implemented today.

API, OCS, WebDAV, cron, CLI, and background jobs therefore do not instantiate the profile graph.

## 6. Managed core-settings bridge

The bridge is a reusable operation boundary for **code-defined settings domains**, not a generic Nextcloud configuration API. Version 1 instantiates it for instance branding. A future navigation, client-distribution, or other core domain may reuse capability discovery, planning, verification, and operation storage only after it defines its own fields, ownership, authorization, lifecycle, and recovery rules. It does not gain access by adding arbitrary keys to the branding patch.

### 6.1 First bounded domain: instance branding

The first bridge supports these instance-branding fields:

| Domain field | Core owner | Profile may recommend | Instance overlay may set |
|---|---|---:|---:|
| Primary colour | Theming | Yes | Yes |
| Background colour | Theming | Yes | Yes |
| Background mode/image | Theming | Yes | Yes |
| Logo | Theming | Yes | Yes |
| Header logo | Theming | Yes | Yes |
| Favicon | Theming | Yes | Yes |
| Instance name | Theming | No | Yes |
| Web URL | Theming | No | Yes |
| Slogan | Theming | No | Yes |
| Privacy-policy URL | Theming | No | Yes |

`disable-user-theming` is a separate `AdministratorPolicy`, never a profile field and never selected implicitly. Product name, client IDs/URLs, documentation URL, navigation defaults, and per-user theme state remain outside this bridge. They can acquire their own typed module later, but cannot enter through an arbitrary-setting endpoint.

Background is a tagged value, not a string:

```text
InheritCoreDefault | SolidColor(color) | Image(assetId)
```

Each other field operation is also explicit:

```text
Leave | Set(value) | Reset
```

This avoids treating `""`, missing, inherited, reset, and unsupported as the same state.

### 6.2 Stable ports

```php
interface InstanceBrandingReader
{
    public function read(): BrandingSnapshot;
}

interface ManagedSettingDriver
{
    public function id(): DriverId;

    public function capabilities(NextcloudRuntime $runtime): SettingCapabilities;

    public function read(ManagedSettingId $field): ManagedSettingState;

    public function apply(PreparedSettingStep $step): SettingStepResult;

    public function restore(SettingSnapshot $snapshot): SettingStepResult;
}

interface BrandingOperationStore
{
    public function savePlan(BrandingPlan $plan): PlanId;

    public function saveSnapshot(BrandingOperationSnapshot $snapshot): void;

    public function recordResult(BrandingOperationResult $result): void;
}
```

`ManagedSettingId` values must resolve through a code-owned definition registry. A request cannot construct an unknown ID. Each definition supplies value type, validator, owner, whether profiles may recommend it, whether it is policy, and required recovery data.

### 6.3 Capability model

Capabilities are per field and operation, not a single “Theming available” boolean:

```text
field
readConfigured: supported | unsupported
readEffective:  supported | user-sensitive | unsupported
set:            supported | manual | unsupported
reset:          supported | manual | unsupported
assetWrite:     supported | manual | unsupported
verify:         strong | weak | unavailable
restore:        strong | best-effort | unavailable
access:         public | private-verified | internal-config | manual
driverId
testedNextcloudRange
diagnostic
```

The UI presents the access level and excludes unavailable operations from confirmation. `private-verified` means tested by this project for the stated Nextcloud build; it does not mean public or supported by Nextcloud as an app API.

### 6.4 Driver preference

The capability broker selects the safest capable driver independently for each prepared field step:

1. released public `OCP\Theming` manager;
2. versioned and integration-tested private core service driver;
3. field-specific internal-config driver, only when explicitly enabled;
4. manual Theming UI or generated `occ theming:config` instruction;
5. unavailable.

The chosen driver ID, access level, tested version range, side effects, and rollback strength are frozen into the confirmed plan. Apply fails with `STALE_CAPABILITY` if discovery now chooses something different.

The broker never falls through from a failed private write to a raw config write during the same operation. A different access path requires a newly reviewed plan.

## 7. Temporary compatibility drivers

A temporary “cheat” is acceptable only as an implementation of `ManagedSettingDriver`. It cannot add new request DTOs, persistence shapes, profile semantics, or controller branches; those remain stable when the cheat is removed.

### 7.1 Verified private Theming drivers by tested major

The first implementation may include a `VerifiedOcaThemingDriver` family. It
is a deliberate temporary adapter for exact matrix cells that pass, not a
single optimistic version-range claim and not postponed upstream work.

Only that directory may reference:

- `OCA\Theming\ThemingDefaults`;
- `OCA\Theming\ImageManager`;
- Theming-owned config keys read through global `OCP\IAppConfig`;
- version-specific background and cache-buster behavior.

The driver is resolved lazily by `CompatibilityDriverFactory` after all of these checks pass:

1. the exact Nextcloud and Theming versions match a passing adapter fixture;
2. the Theming app is enabled;
3. required classes and methods exist;
4. reflected method signatures match the tested shape;
5. a read-only probe can obtain global configured state and image presence;
6. the administrator's bridge policy allows `private-verified` access.

The bootstrap registers the factory through a neutral provider interface; it does not register or autowire the OCA driver's private constructor as a required application service. The factory performs only read-only discovery. Its result is cached by Nextcloud version, Theming app version, and structural fingerprint, then rechecked before apply. A probe or operation failure opens the driver's circuit for subsequent operations until an administrator probe succeeds or the runtime fingerprint changes. The in-flight operation retains the driver long enough to attempt restoration before the circuit opens.

For exact supported builds whose integration suite passes, `private-verified`
may be the automatic backend after explicit administrator opt-in. An app-config
kill switch can force `public-only` immediately. Unknown builds, failed probes,
or an unavailable Theming app downgrade to manual capability without affecting
profile rendering.

#### Scalar lifecycle

- Read configured presence and value from the strict field-to-key map inside the driver.
- Read global effective values with global/default getters, never current-user colours.
- Validate with this app's typed validator and the behavior exercised by core's own controller.
- Set through `ThemingDefaults::set()` so the core cache buster and caches are updated.
- Reset through `ThemingDefaults::undo()`.
- Read back both configured and effective state and compare against the planned result.

#### Image lifecycle

For `logo`, `logoheader`, `favicon`, or `background`:

1. validate MIME, byte size, dimensions, decoding, and SVG policy, then stage the bytes in NL Design app data and a core temporary file;
2. snapshot existing bytes, MIME state, mode, and digest;
3. call `ImageManager::updateImage(field, temporaryPath)`;
4. capture the returned detected MIME type;
5. resolve the MIME-state key through the driver's strict field map and store it with `ThemingDefaults::set()`;
6. verify `ImageManager::hasImage(field)`, served URL/cache revision, and byte digest where available;
7. on failure, restore the prior bytes and MIME/mode through the same lifecycle.

Calling `updateImage()` without storing the returned MIME is a failed operation even if bytes exist. Raw writes into the Theming app-data layout are forbidden.

For a solid background, the driver follows core's tagged lifecycle: remove/reset the background image, set `backgroundMime` to `backgroundColor`, set the selected background colour, and verify the resulting plain-background state. Switching back to an image writes the image and its detected MIME.

### 7.2 Experimental internal-config drivers

Some scalar settings may exist in core storage before they have a public manager or a complete private service method. They may be exposed through one field-specific driver under `Compatibility/Experimental`, subject to all of these rules:

- the field is a code-defined domain setting, not a caller-supplied config key;
- the driver supports one documented Nextcloud range and one strict storage recipe;
- the recipe includes all known cache, event, validation, derived-state, and read-back effects;
- there is a before snapshot and tested restoration;
- the administrator enables `experimental-internal` mode and sees the access path in the plan;
- the field is never required by a profile or stable support claim;
- image settings and policy/user settings are excluded unless the complete core lifecycle is demonstrably reproduced.

If the side effects are not known, the capability is manual—not “best effort.” Direct `IAppConfig` access being public does not make another app's keys a public contract.

### 7.3 Manual driver

The manual driver is always available when the Theming app is present. It emits:

- a field-by-field link/instruction for Nextcloud's Theming settings;
- exact `occ theming:config` commands where the documented command supports the operation;
- reset commands;
- a warning for fields requiring file transfer or unsupported operations.

The web process never launches `occ`, a shell, or a loopback HTTP request. NL Design JavaScript does not call undocumented `/apps/theming/ajax/*` routes. Those routes are another private transport surface and would move transaction and rollback logic into the browser.

### 7.4 Removal contract

Compatibility drivers are temporary only if removal is tested. CI must run the complete profile workflow with:

- bridge policy `public-only`;
- the private driver excluded from the container;
- Theming disabled or its private classes made unavailable;
- a future public-driver fake taking precedence.

All four runs must reach profile selection, preview, publication, page rendering, profile rollback, and CLI recovery. Only automatic core-setting capabilities may differ.

## 8. Public API migration path

The open `OCP\Theming\IDefaults` proposal is a read-direction signal. Its current shape largely replaces legacy `OCP\Defaults` and includes effective values such as `getColorPrimary()`. It is not a global configured-state snapshot and has no mutation contract.

When a released minimum Nextcloud version supplies it:

1. add `OcpDefaultsReader` behind `InstanceBrandingReader`;
2. prefer it for fields whose global/effective semantics are sufficient;
3. retain configured-state and mutation capabilities from the compatibility driver where still necessary;
4. do not change the profile schema, application use cases, API responses, or stored snapshots.

The desired upstream write API is a separate server-owned manager, provisionally `NCU\Theming\IInstanceBrandingManager` before promotion to `OCP`. It should expose typed global state, capability discovery, set/reset operations, stream-based assets, revision preconditions, core-owned validation and derived effects, structured results, and public change events. Core's settings controller and `theming:config` command should use the same manager before third-party apps rely on it.

A released public manager becomes the first driver in the broker. Fields migrate one by one; the OCA driver shrinks and is eventually deleted.

## 9. Planning, apply, and rollback

### 9.1 Planning

`PlanBrandingChange` receives a profile recommendation, instance overlay, requested operations, and expected profile revision. It:

1. resolves desired values without reading private storage names;
2. obtains current state and capabilities;
3. computes semantic differences;
4. validates every selected value and asset;
5. assigns a driver and access level to each step;
6. computes a core-state revision from configured values, asset digests, and available cache revision;
7. writes an immutable, expiring plan;
8. returns current configured/effective state, proposed operation, source, owner, access level, side effects, rollback strength, and any manual step.

Planning never mutates state.

### 9.2 Apply

`ApplyBrandingPlan` requires the plan ID, expected profile revision, expected core revision, and explicit administrator confirmation. It:

1. rechecks authorization, expiry, revisions, driver identity, and capabilities;
2. stages all assets and validates all steps before the first write;
3. records the complete before snapshot and marks the operation `APPLYING`;
4. applies steps in deterministic order;
5. verifies each step and then the complete resulting state;
6. records `APPLIED` only after verification;
7. on first failure, restores completed steps in reverse order and records `ROLLED_BACK` or `RECOVERY_REQUIRED` truthfully.

Core Theming has no transaction spanning config, app data, cache, and generated assets. The UI must say “verified with rollback,” not “atomic.”

### 9.3 Separation from profile activation

Profile activation and core-branding apply are separate operations with separate revisions and snapshots. The UI may present them as two stages of one administrator workflow, but a bridge failure does not invalidate or undo a safe active profile automatically.

The stored profile records recommendations and the ID of the last bridge operation; it does not claim ownership of the live core values. A later edit in Nextcloud's Theming panel is detected as drift and prompts a new plan. There is no bidirectional synchronization loop.

## 10. Persistence

Use app-scoped `OCP\AppFramework\Services\IAppConfig` for small NL Design state:

```text
schema_version
active_profile_id
active_profile_version
active_projection_hash
profile_revision
previous_profile_snapshot_id
bridge_policy                 public-only | private-verified | experimental-internal
last_branding_operation_id
compatibility_health
```

Use NL Design `IAppData` for larger or binary state:

```text
operations/{operation-id}/plan.json
operations/{operation-id}/before.json
operations/{operation-id}/result.json
operations/{operation-id}/assets/{field}
instance-assets/{content-hash}
preview/{admin-id}/{preview-id}.css
```

Plans and results contain domain field IDs and driver metadata, not raw Theming config keys. Compatibility-only snapshots may contain opaque driver payloads; only the originating driver can decode them.

## 11. HTTP and command surface

Use versioned, intention-revealing routes:

```text
GET  /api/v1/profiles
GET  /api/v1/profile-state
POST /api/v1/profile-preview
POST /api/v1/profile-activate
POST /api/v1/profile-rollback

GET  /api/v1/branding/capabilities
POST /api/v1/branding/plan
POST /api/v1/branding/apply
POST /api/v1/branding/rollback
GET  /api/v1/operations/{operationId}
```

There is deliberately no `/settings/{key}` or `/config/{app}/{key}` route. Controllers use current admin-setting authorization attributes, CSRF protection, strict request DTOs, expected revisions, and structured error codes.

Provide app-owned CLI commands for recovery and inspection:

```text
occ nldesign:status
occ nldesign:profile:activate <id> --expected-revision <revision>
occ nldesign:profile:rollback
occ nldesign:branding:plan <profile-or-overlay>
occ nldesign:branding:rollback <operation-id>
occ nldesign:compatibility:probe
```

These commands call the same application use cases. The manual driver may print core `theming:config` commands, but NL Design commands do not invoke them as subprocesses.

## 12. Extension rules

### Adding a profile

Add pinned source data, compiler mapping/evidence, and first a source-only
inventory record. Promote it to a ready profile only after the projection and
evidence gates pass. Do not add controller code, a PHP enum branch, or
JavaScript conditionals.

### Adding a Nextcloud surface

Add a `SurfaceAdapter`, version constraints, a bounded CSS fragment, selector/output budgets, accessibility modes, and compatibility tests. Do not modify every profile.

### Adding a managed core setting

Add all of the following:

1. a typed domain field and owner classification;
2. a value object and validator;
3. source rules: profile recommendation, instance overlay, policy, or never;
4. one or more drivers with explicit access levels;
5. snapshot and restoration semantics;
6. capability, plan, apply, stale-write, verification, and failure tests;
7. UI copy that exposes ownership and risk.

Adding only a config-key mapping is not a valid extension.

### Adding support for a new Nextcloud major

Run the public API and private lifecycle inventory, execute the adapter contract suite against the packaged app, update reflected-signature probes, record changed side effects, and add the major to capability metadata. Until that passes, profile rendering may be compatible but automatic core-setting writes are manual.

## 13. Required tests and architecture checks

| Boundary | Required evidence |
|---|---|
| Domain/use cases | Unit tests with fake ports; no Nextcloud bootstrap |
| Profile catalogue | Manifest/status/path fixtures now; digest fixtures before compiler promotion; no filesystem promotion |
| Presentation listener | Template-only injection and fail-open behavior |
| Surface adapter | Version, mode, selector budget, visual and accessibility matrix |
| Capability broker | Preference, downgrade, stale capability, kill switch, no fall-through after failure |
| OCA driver | Real packaged set/reset/read-back tests for every field on each explicitly enabled Nextcloud/Theming build |
| Images | MIME registration, `hasImage()`, dimensions, background processing, cache revision, restore bytes |
| Raw config driver | Exact-field recipe, side effects, read-back, restore, and explicit opt-in |
| Operation engine | Stage-before-write, expected revision, reverse rollback, recovery-required result |
| Removal contract | Full profile workflow with compatibility code unavailable |
| Package | Read-only app directory and offline runtime |

Static checks fail if:

- `OCA\Theming` appears outside `Infrastructure/Nextcloud/Compatibility`;
- a Theming raw config key appears in a controller, domain, profile, JavaScript, or template;
- code writes below the installed app path;
- a controller accepts an arbitrary setting/config key;
- compatibility services are resolved from `Application::boot()` or the template listener.

## 14. Migration from the current app

The migration should preserve the current SENERAWA/catalogue work without building new dependencies on it.

### Slice 0 — Characterize and fence

1. Add characterization tests for current profile selection and CSS loading.
2. Introduce the namespace/dependency rule and an `OCA\Theming` import check.
3. Keep the read-only private probe unreachable, then replace it rather than extending it into a mutator.
4. Add the bridge-policy kill switch with `public-only` as a recovery value.

### Slice 1 — Stable profile core

1. Introduce `ProfileCatalog`, `ActiveProfileStore`, and typed profile state.
2. Replace filesystem validity with compiled manifest validity.
3. Move CSS injection to template listeners.
4. Keep native Nextcloud as the fresh-install, explicit deactivation, and failure state. **Implemented.**
5. Add activation/deactivation revision, snapshot, rollback, and CLI recovery. **State and web rollback implemented; CLI recovery remains.**

### Slice 2 — Bridge contracts and manual backend

1. Add branding field value objects, capabilities, plans, and operation persistence.
2. Add public/effective reading where semantics are valid.
3. Add the manual driver and field-by-field plan UI.
4. Prove that all profile tests pass with no private driver.

### Slice 3 — Verified temporary driver

1. Implement version-fingerprinted `VerifiedOcaThemingDriver` instances behind the factory.
2. Test the eleven instance-branding fields against every exact packaged environment proposed for enablement.
3. Test images, MIME state, background modes, cache invalidation, drift, and rollback.
4. Enable `private-verified` only for matrix cells that pass; unknown cells remain manual.

### Slice 4 — Optional experimental fields

Add only demonstrated operator needs through field-specific drivers. Do not expose a generic advanced-config editor.

### Slice 5 — Upstream migration

Add the released `IDefaults` reader when available, pursue the typed mutation manager upstream, prefer public capabilities automatically, and delete private operations as public coverage replaces them.

## 15. Rejected shortcuts

- Extending or forking the core Theming app as the product architecture.
- Calling undocumented Theming AJAX routes from NL Design JavaScript.
- Running `occ` or another shell command from a web request.
- Writing Theming image files or generated assets directly.
- Treating `OCP\IAppConfig` as permission to expose arbitrary cross-app keys.
- Importing `OCA\Theming` in a controller, profile service, listener, settings class, or domain object.
- Storing private config keys or server paths in profile manifests or browser state.
- Automatically syncing on profile selection or polling core settings.
- Claiming atomicity across app config, Theming app data, caches, and generated assets.

## 16. Evidence behind the compatibility boundary

At the inspected Nextcloud 34 commit `628d14cc0d08a35972a0dfe260f24d3570a2acc9`:

- the public `OCP\Defaults` class is read-only;
- the open `OCP\Theming\IDefaults` refactor remains read-only and unmerged;
- core's Theming controller and `theming:config` command mutate scalars through private `ThemingDefaults`;
- image activation calls private `ImageManager::updateImage()`, stores its returned MIME through `ThemingDefaults::set()`, and thereby updates cache state;
- `ThemingDefaults::undo()` owns image deletion and MIME reset;
- the global capabilities response may contain current-user colour/background choices and is not a configured administrator snapshot.

The same central `ThemingDefaults::set()/undo()` and
`ImageManager::updateImage()` lifecycle is present at the inspected stable-32
and stable-33 commits `86cb7a4e385c60813729f2654e1acc9074724271`
and `5d2636bae75bcb535cd42d9f46b34acb06ebe58c`. That similarity supports
testing one adapter family; it does not establish signature compatibility,
enable a matrix cell, or convert those classes into public API.

Authoritative references:

- [Nextcloud public PHP API boundary](https://docs.nextcloud.com/server/stable/developer_manual/digging_deeper/api.html)
- [Nextcloud 34 public `OCP\Defaults`](https://github.com/nextcloud/server/blob/628d14cc0d08a35972a0dfe260f24d3570a2acc9/lib/public/Defaults.php)
- [Open `OCP\Theming\IDefaults` refactor](https://github.com/nextcloud/server/pull/55384)
- [Nextcloud 34 Theming controller lifecycle](https://github.com/nextcloud/server/blob/628d14cc0d08a35972a0dfe260f24d3570a2acc9/apps/theming/lib/Controller/ThemingController.php)
- [Nextcloud 34 `theming:config` lifecycle](https://github.com/nextcloud/server/blob/628d14cc0d08a35972a0dfe260f24d3570a2acc9/apps/theming/lib/Command/UpdateConfig.php)
- [Nextcloud 34 private `ThemingDefaults`](https://github.com/nextcloud/server/blob/628d14cc0d08a35972a0dfe260f24d3570a2acc9/apps/theming/lib/ThemingDefaults.php)
- [Nextcloud 34 private `ImageManager`](https://github.com/nextcloud/server/blob/628d14cc0d08a35972a0dfe260f24d3570a2acc9/apps/theming/lib/ImageManager.php)
- [Template-render events](https://github.com/nextcloud/server/blob/628d14cc0d08a35972a0dfe260f24d3570a2acc9/lib/public/AppFramework/Http/Events/BeforeTemplateRenderedEvent.php)
