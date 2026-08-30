# federated-config-sharing Specification

## Purpose

nldesign owns the instance's theme. OpenRegister owns the federated-config engine that moves
configuration between instances. This spec describes **nldesign's side of that contract only** —
the `IShareableConfigType` it contributes, and the guard that keeps the app bootable when
OpenRegister is absent. The engine itself, its transport, and its authorisation model are
OpenRegister's and are specified there.

This spec exists because the implementation (shipped in #192) carried `@spec` tags pointing at
`openspec/changes/federated-config-sharing/specs/federated-config-sharing/spec.md` — a path that
has **never existed in this repository**. It resolves, relative to the fleet checkout, to
OpenRegister's *change* directory. Two things were wrong with that: a `@spec` target must be a
canonical `openspec/specs/` path and never a change directory, and a spec in another repository
cannot govern this repository's code. nldesign's participation is specified here.

## Requirements

### Requirement: The Theme Is Contributed As A Shareable Config Type

The app MUST contribute exactly one `OCA\OpenRegister\Service\Config\IShareableConfigType` whose
id is `nldesign.theme`, whose display name is `NL Design theme`, and whose discovery topic is
`nldesign-theme`. These three strings are the wire identity of the type: a receiving instance
matches on them, so changing any of them is a breaking change to every already-published share and
MUST be treated as such.

#### Scenario: The type declares its wire identity
@e2e exclude cross-app wire identity with no UI surface — nothing in this app renders these strings; covered by NlDesignThemeShareableConfigTypeTest::testWireIdentityIsStable

- GIVEN the shareable config type
- WHEN its id, display name and topic are read
- THEN they MUST be exactly `nldesign.theme`, `NL Design theme` and `nldesign-theme`

### Requirement: Serialisation Reuses The Config Bundle, And Carries No Secrets

`serialise()` MUST return `{type, version, bundle}` where `bundle` is the output of the app's own
`ConfigBundleService::export()` — the same bundle the admin export/import surface produces, so
there is exactly one definition of "this instance's theme" and a federated share can never drift
from a manual export. It MUST NOT construct a second, parallel serialisation.

The `$selection` parameter of `IShareableConfigType::serialise()` MUST be ignored. A theme is the
instance's single theme configuration; there is nothing to select from. The parameter is mandated
by the interface and cannot be dropped.

Theming carries no credentials, so the bundle MUST NOT be filtered for secrets — but it MUST also
never be extended to carry any. Any future theming value that IS a secret MUST be excluded from
`ConfigBundleService::export()` at source, not filtered here, so the manual export path is
protected by the same decision.

#### Scenario: A serialised theme is the config bundle
@e2e exclude serialisation contract invoked by OpenRegister, never by an nldesign page; covered by NlDesignThemeShareableConfigTypeTest::testSerialiseWrapsTheConfigBundleAndIgnoresTheSelection

- GIVEN an instance with a configured theme
- WHEN the type is serialised with any selection, including an empty one
- THEN the result MUST be `{type: "nldesign.theme", version: "1.0", bundle: <ConfigBundleService::export()>}`
- AND the selection MUST have made no difference to the output

### Requirement: Deserialisation Applies The Bundle Through The Import Path

`deserialise()` MUST apply the incoming bundle through `ConfigBundleService::import()`, the same
validated all-or-nothing path the manual import uses, and MUST return
`{installed: ['nldesign-theme'], import: <the import result>}`. A bundle that is absent or
malformed MUST degrade to an empty array rather than a fatal, so a peer sending a bad payload
cannot take the receiving instance down.

#### Scenario: An incoming theme goes through the validated import path
@e2e exclude deserialisation contract invoked by OpenRegister, never by an nldesign page; covered by NlDesignThemeShareableConfigTypeTest::testDeserialiseAppliesTheInnerBundleThroughImport

- GIVEN a bundle produced by this type
- WHEN it is deserialised
- THEN `ConfigBundleService::import()` MUST be called with the inner `bundle` payload
- AND the result MUST report `installed: ['nldesign-theme']` alongside the import result

#### Scenario: A payload with no bundle key does not fatal
@e2e exclude no UI surface anywhere in this app — a malformed cross-instance payload cannot be produced from a browser; covered by NlDesignThemeShareableConfigTypeTest::testAMalformedPayloadImportsAnEmptyArrayInsteadOfFataling

- GIVEN a payload that omits `bundle`, or whose `bundle` is not an array
- WHEN it is deserialised
- THEN the import MUST be invoked with an empty array
- AND no error MUST be raised

### Requirement: The Bundle Service Is Resolved Lazily

The type MUST resolve `ConfigBundleService` from the container on first use, and MUST NOT take it
as a constructor argument. The shareable-type catalogue is read by any cross-app request that asks
what this instance can share, and that read constructs every registered type. Injecting the bundle
service eagerly would drag the whole theming dependency chain into every such read, in a container
context that may not autowire it.

#### Scenario: Constructing the type resolves nothing
@e2e exclude DI-container behaviour that produces no observable DOM difference; covered by NlDesignThemeShareableConfigTypeTest::testTheBundleServiceIsResolvedLazily

- GIVEN the type is constructed with a container
- WHEN no serialise or deserialise call has been made
- THEN the container MUST NOT have been asked for `ConfigBundleService`

### Requirement: OpenRegister Is A Soft Dependency

OpenRegister is optional at runtime. `appinfo/info.xml` MUST NOT declare it as an `<app>`
dependency, and the listener registration in `lib/AppInfo/Application.php` MUST be guarded by
`class_exists(RegisterShareableConfigTypesEvent::class)`. An instance without OpenRegister MUST
boot, theme, and serve its admin settings exactly as before; the only thing absent is the ability
to share the theme.

The guard is load-bearing for a reason beyond tidiness: `NlDesignThemeShareableConfigType`
names `IShareableConfigType` in its **class header**. PHP resolves a class header at load time and
no lazy-DI arrangement can defer it, so constructing that class on an instance without OpenRegister
is fatal. The guard is what ensures nothing constructs it there.

#### Scenario: The listener is registered only when the engine is present
@e2e exclude boot-path invariant — proving it needs an instance WITHOUT OpenRegister, which the e2e fixture always installs; covered by NlDesignThemeShareableConfigTypeTest::testOpenRegisterRemainsASoftDependency

- GIVEN `lib/AppInfo/Application.php`
- WHEN the shareable-config listener registration is read
- THEN it MUST be inside a `class_exists()` guard on the OpenRegister event class

#### Scenario: A non-matching event is ignored
@e2e exclude event-dispatch branch with no UI surface — an event listener is not reachable from a browser; covered by NlDesignThemeShareableConfigTypeTest::testListenerRegistersOnTheMatchingEventOnly

- GIVEN the listener receives an event that is not a `RegisterShareableConfigTypesEvent`
- WHEN it is handled
- THEN it MUST return without registering anything
