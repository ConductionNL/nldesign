# NL Design for Nextcloud — Product and Architecture Roadmap

**Status:** Profile foundation and bounded installer implemented; one live NC34 candidate flow passed, packaged multi-version matrix pending

**Last reviewed:** 2026-08-08
**Evidence snapshot:** starting baseline `senerawa-token-set` at `720e7a5`; current uncommitted working tree reviewed 2026-08-08
**Planning method:** evidence gates, not calendar promises

Gate 0 artifacts now live in:

- [gate-0-baseline.md](./gate-0-baseline.md)
- [feature-ledger.md](./feature-ledger.md)

This roadmap replaces the earlier implementation checklist. It is intentionally adversarial: it starts from what can fail, distinguishes shipped behavior from repository claims, and removes work that does not serve a defensible product boundary.

The concrete runtime modules, ports, capability broker, temporary private drivers, storage model, and migration slices are specified in the [extendable runtime architecture](./architecture.md). That document is authoritative for implementation structure; this roadmap remains authoritative for sequencing and evidence gates.

Dates and week estimates are deliberately absent. Local reproducibility is a
necessary baseline but cannot substitute for the still-missing packaged
Nextcloud/browser compatibility matrix.

## Implementation checkpoint

The current working tree has completed the bounded profile foundation:

- 40 package inventory records are classified as 8 ready projections and 32
  source-only records; only ready entries are selectable;
- native Nextcloud is the nullable fresh-install and explicit deactivation
  state; enabling the app does not silently select an organisation;
- active state uses app-scoped configuration, strict revisions, a Nextcloud
  exclusive lock, a public app-config cache refresh before the locked read,
  exact profile versions, one-step rollback, explicit persistence failure, and
  bounded version history;
- the administration UI is now a profile library: built-in versions remain
  read-only, while a closed `nldesign-profile-pack/v1` installer can add
  immutable instance-local versions to Nextcloud app data without accepting
  arbitrary CSS, assets, URLs, or Nextcloud settings;
- every installed record contains a normalized descriptor, deterministic CSS,
  digest, timestamp, and actor; reads revalidate the complete record, and
  active or rollback-retained versions cannot be uninstalled;
- installed CSS is exposed only through an immutable digest-addressed route and
  enters the same fonts/profile/core-projection precedence as packaged CSS;
- normal and login templates receive a three-layer core cascade with no
  component or structural selectors;
- ready profiles contain only the four mapped roles (38 declarations across
  all eight profiles), with byte/count budgets and measured light,
  explicit-dark, and system-dark primary-pair contrast where dark values are
  supplied, eliminating cross-profile fallback inheritance and unused runtime
  token payload;
- explicit OpenDyslexic and high-contrast preferences outrank branding;
- runtime package mutation, the token editor prototype, broad global selector
  layers, unused identity assets, and automatic release/sync workflows have
  been removed;
- private `OCA\Theming` code is isolated in an unregistered, non-load-bearing
  compatibility prototype.

The base profile checkpoint has a prerelease line. On 2026-08-08 the current
candidate completed one live Nextcloud 34.0.2 browser flow: two immutable
versions of a synthetic local profile were installed, grouped, activated,
served from distinct digest-addressed CSS URLs on Files, switched, rolled back,
protected while active or retained, and removed. The original application
code, app configuration, and app-data state were then restored exactly.

That is integration evidence for one candidate and two surfaces, not release
or compatibility-matrix evidence. The final package still has to pass clean
install and upgrade paths, dark and accessibility modes, Nextcloud 32/33, and
the declared app surfaces. The live instance also carries administrator custom
CSS with `!important` core-variable overrides. Those overrides correctly win;
visual profile comparison therefore disabled that stylesheet only inside the
test browser tab while the server-side profile and digest routes remained
unchanged. Provenance and identity evidence, the source compiler, and any
automatic core-settings bridge remain gated roadmap work.

## 1. Executive decision

NL Design for Nextcloud will be a **build-time design-profile compiler, evidence-backed catalogue, and Nextcloud adaptation layer**. For NL Design System sources, the preferred semantic seam is the organisation's values for NL Design System Basis/Common tokens—not generated theme CSS and not another organisation's component-token namespace.

It will not be:

- a fork or replacement of Nextcloud's built-in Theming app;
- a generic editor for every Nextcloud CSS variable;
- an implementation of NL Design System components inside Nextcloud;
- a promise that every Nextcloud app can be restyled;
- a source-of-truth repository for an organisation's house style;
- a way to infer legal permission to use a government identity;
- a white-label build system for native mobile or desktop clients.

The app has one job:

> Let an administrator select an approved, versioned design profile, preview its effect, publish it safely, and receive a truthful report of which Nextcloud surfaces are covered.

This is a translation product. It carries house-style decisions into the bounded presentation surface that Nextcloud actually exposes. It does not turn Nextcloud's components into NL Design System components.

The product is successful when profile selection is predictable, reversible, upgrade-safe, accessible, traceable to source tokens, and honest about unsupported surfaces. The number of token files or settings controls is not a success metric.

## 2. Product goal and user promise

### 2.1 Primary user

The primary user is a Nextcloud instance administrator responsible for organisational identity and accessibility. A theme developer or supplier may prepare profiles, but ordinary instance configuration must not require editing PHP, JavaScript, or files inside the installed app.

### 2.2 Core workflow

1. The administrator sees only profiles that are shipped or installed as data.
2. Each profile shows its source, version, verification status, modes, assets, and known compatibility.
3. The administrator previews a profile without changing the instance for other users.
4. The app reports proposed web-CSS changes and any optional Nextcloud Theming changes separately.
5. The administrator publishes one coherent profile.
6. The app can restore the previous known-good configuration without requiring a working themed UI.
7. The compatibility report states which surfaces and apps were tested.

### 2.3 Non-goals for the first stable architecture

- Per-user or per-group house styles.
- Arbitrary freeform CSS.
- Uploading fonts or executable theme bundles.
- Selectively applying individual dependent tokens from a profile.
- Algorithmically inventing dark or high-contrast themes.
- Replacing app-specific UI structures, icons, navigation patterns, or components.
- Prometheus metrics, audit products, email-template replacements, or icon-pack distribution without a demonstrated operator need.

These are not banned forever. They must earn their way back through a concrete use case, a threat model, and compatibility evidence.

## 3. Historical adversarial starting-point audit

> This section records the pre-overhaul state at commit `720e7a5`. Statements
> about “current” code below are audit evidence for the starting point and are
> superseded by the implementation checkpoint and feature ledger above.

The current repository cannot be treated as a single coherent implementation.

### 3.1 There are two divergent products in the same history

- The current branch is based on main and contains the small 0.1.x implementation plus the SENERAWA profile.
- main and development diverged at 4e47d42.
- As audited on 2026-08-08, main has 10 commits not in development; development has 435 commits not in main. The current branch has 11 commits not in development.
- development contains thousands of additional assets and a broad feature set: custom profiles, token editing, per-app and per-group theming, previews, dark variants, fonts, icons, audit logs, metrics, email theming, DTCG ingestion, and configuration sharing.
- Several development documents label those features stable even though they are not on the release branch and have not been reconciled with the current package.

**Disposition:** do not merge development wholesale and do not ignore it. Treat it as a prototype mine. Port only infrastructure or tests that survive the architecture gates in this roadmap.

### 3.2 Completion claims contradict the current runtime

The current documentation and OpenSpec task files describe a seven-layer CSS stack, Theming sync endpoints, a token editor, import/export, and completed admin flows. The current runtime does not support those claims:

- Application.php loads fonts, the selected token file, theme.css, and overrides.css, plus two optional CSS files.
- It does not load defaults.css, utrecht-bridge.css, element-overrides.css, or custom-overrides.css.
- appinfo/routes.php exposes four settings routes. It does not expose the Theming sync or token-editor routes described as complete.
- ThemingService exists but is not wired into the current controller flow.
- TokenRegistry, TokenSetPreviewService, and CustomOverridesService exist but are largely disconnected from the current application.
- The admin template contains a token-editor loading placeholder, while admin.js has no token-editor implementation.

**Disposition:** code plus passing behavior tests outrank checked task boxes. Every feature needs a released, tested, documented status; “implemented somewhere in history” is not a valid status.

### 3.3 The token compiler is not a semantic adapter

The current generator:

- flattens upstream JSON;
- skips aliases instead of resolving them;
- guesses whether a token is a component token from its name;
- strips organisation prefixes;
- renames values into an nldesign namespace;
- emits every discovered organisation as if it were usable.

NL Design System deliberately separates brand, common, and component tokens. Aliases between those levels carry meaning. Skipping aliases and renaming keys does not translate a design system into Nextcloud semantics.

Current token files range from 4 declarations to more than 1,200. That variance is evidence of different upstream shapes, not proof that every profile can theme Nextcloud. A file's existence is only evidence that a source was discovered.

**Disposition:** replace filename-based support with a compiled profile contract and explicit verification status.

### 3.4 Branded fallback values leak one identity into another

defaults.css identifies its fallback as Rijkshuisstijl-based. If a municipality profile is incomplete, a fallback from Rijkshuisstijl produces a hybrid identity. In the current working tree the defaults file is not loaded at all, which creates a different failure: required custom properties can be unresolved.

The admin preview has the same issue. Only 7 of the 40 current manifest entries have a primary colour in Theming metadata. Every other profile previews with Rijkshuisstijl blue.

**Disposition:** missing profile values fall back to the native Nextcloud semantic value, not to another organisation's brand. A profile that lacks the agreed minimum is marked partial or unavailable; it is never silently presented as complete.

### 3.5 The current CSS fights Nextcloud rather than adapting it

The current styles include:

- universal font overrides on html body * and common HTML elements;
- hundreds of important declarations;
- selectors tied to generated Vue attributes;
- direct restructuring of login and application layout;
- forced hiding of the Nextcloud logo;
- a Rijksoverheid-style Nederland logo injected for every selected profile;
- hard-coded white backgrounds and Fira Sans;
- unconditional overrides of variables that Nextcloud also changes for dark mode, high contrast, and user theming.

This can break icon fonts, embedded editors, app layouts, accessibility modes, and future Nextcloud releases. It also makes a municipality profile inherit national-government presentation.

**Disposition:** the first stable adapter maps a bounded set of Nextcloud semantic variables. DOM selectors are permitted only for a named, tested surface that cannot be expressed through a public variable. Universal selectors, generated data attributes, and organisation-specific assets in shared CSS are release blockers.

### 3.6 The proposed Theming sync is both private and under-modelled

ThemingService imports OCA\Theming\ImageManager and OCA\Theming\ThemingDefaults. Nextcloud documents OCP as its public PHP API. OCA classes belong to an app's implementation and may change with a Nextcloud release.

The current service also exposes only primary/background colours, a logo, and a background image. The maintained core Theming command supports a larger documented surface: name, web URL, slogan, legal-notice URL, privacy-policy URL, primary colour, background colour, logo, header logo, favicon, background image, and the separate `disable-user-theming` policy. The private classes can touch still more internal, deployment, and per-user state, but reachability is not a product contract.

The current image write is incomplete. `ImageManager::updateImage()` returns the detected MIME type; core then stores `{imageKey}Mime` and increases the Theming cache buster. `ThemingService::applyImages()` currently discards that MIME result. A file can therefore be copied into Theming app data without becoming a recognized active image. This is evidence for one lifecycle-owning manager, not for exposing more raw setters.

Nextcloud's existing `OCP\Defaults` is a public read facade, not a full instance-branding snapshot or mutator. An open upstream draft proposes replacing the legacy defaults abstraction with `OCP\Theming\IDefaults`. That proposal is directionally important, but it is currently read-oriented, incomplete for the admin branding surface, and not a released dependency.

**Disposition:** keep a separate app. Align the NL Design read port and vocabulary with the upstream `IDefaults` direction without depending on an unmerged interface. Propose a separate typed public mutation manager upstream instead of adding generic setters to `IDefaults`. While that work is pending, a version-specific private adapter may ship as a bounded risk only behind the same application-owned port, capability checks, real integration tests, snapshots, rollback, and a manual fallback. Private access and raw Theming config keys must never leak through the rest of the architecture.

### 3.7 Instance state is at risk of being written into the package

CustomOverridesService writes css/custom-overrides.css inside the installed app directory. Nextcloud app paths may be configured read-only, and app upgrades replace package contents. Runtime state stored there is operationally unsafe.

**Disposition:** immutable shipped profiles belong in the package. Instance configuration belongs in IAppConfig. Uploaded or generated assets belong in app data. No request, background job, migration, or admin action may write into the installed app directory.

### 3.8 The current test commands can report false confidence

- package.json says there are no tests.
- The current branch has no tests directory.
- Composer unit and static-analysis scripts catch failures with “skipping” output in several places.
- The OpenSpec tasks mark behavior complete without executable evidence.
- Large development-branch test inventories do not prove the release branch behaves the same way.

**Disposition:** a missing dependency may be reported as “not run,” but it cannot produce a successful quality gate. Release status requires tests against the packaged artifact and supported Nextcloud versions.

### 3.9 Product and legal claims are inconsistent

The current files variously claim 5, 39, or 40 profiles, a two-layer or seven-layer architecture, CDN or self-hosted fonts, and AGPL or EUPL licensing. The root licence and package metadata do not consistently agree. Some organisation identities have usage restrictions separate from the source-code licence.

**Disposition:** counts and capability claims are generated from the validated release inventory. Code licensing, asset licensing, trademark/identity permission, and profile verification are recorded separately.

### 3.10 NL Design System is a moving ecosystem, not an endorsement registry

NL Design System now describes itself as a collection of design systems working towards reusable Hall of Fame components, patterns, and templates. It is not one complete component suite or one universal government visual identity. Its durable direction is becoming clearer:

- organisation-specific Brand tokens feed shared semantic Common tokens;
- the broad `basis.*` Common-token vocabulary is intended to make components and themes more interchangeable;
- the neutral Start theme supplies accessible defaults that an organisation can change incrementally;
- component APIs and component tokens mature through the Estafettemodel;
- JSON, the Design Tokens Format, Style Dictionary, package publication, Figma, and a Theme Wizard/schema/lint toolchain connect design and code;
- accessibility support is tied to a periodically refreshed Baseline and evidence, not to token-file presence.

The current implementation of that direction is still transitional. At the 2026-08-08 evidence snapshot:

- the Start and Basis theme packages are published as `@nl-design-system-unstable/start-design-tokens` 7.0.0 and `@nl-design-system-unstable/basis-design-tokens` 3.1.1;
- the concrete `basis-design-tokens` package has an open removal task even though the **Basis-token vocabulary** remains a strategic concept;
- `@nl-design-system-community/design-tokens-definition` 1.0.0 is metadata-only and intentionally contains no values, confirming that definitions and theme values are separate artifact roles;
- the new design-token schema 2.2.0 and lint 1.0.0 packages are active and useful, but their own history labels the initial schema work in progress and already contains a major migration;
- the current schema intentionally skips some disabled-colour contrast checks because the Start and Mooi & Anders themes do not pass them, so upstream validation success is not accessibility proof for a projected Nextcloud pair;
- an open Theme Wizard proposal points towards generating a complete accessible theme from a small Basis input, but no released `createTheme()` contract exists yet;
- the themes repository is actively classifying themes for deprecation, migration to the new Basis approach, or continued use;
- its generated theme switcher manually imports a subset of packages and carries no authority, rights, or adapter-verification status;
- the separate index repository says themes are in scope, but its current published implementation generates component progress rather than an authoritative theme catalogue.

Repository location is also not proof of endorsement. The Rijkshuisstijl Community repository calls itself unofficial. Utrecht, Den Haag, and Rotterdam describe their repositories as work in progress. The NL Design System Amsterdam repository is archived, and the archived Amsterdam experiment explicitly says it was not endorsed by the municipality. Those sources may still contain technically useful evidence, but their names cannot be converted into “officially supported organisation” labels.

The current local catalogue collapses all of these distinctions. A CSS filename and a friendly organisation name can become selectable even when the source is archived, unofficial, incomplete, migrated, absent from the current upstream inventory, or maintained elsewhere. “On GitHub under NL Design System,” “published to npm,” “works in the Themes Storybook,” “maintained by the named organisation,” “permitted identity use,” and “verified by this Nextcloud adapter” are six different facts.

**Disposition:** follow the semantic direction while insulating the app from current package topology. Pin every consumed artifact and tool; preserve upstream status and authority as facts; use Start only through declared inheritance; treat upstream inventories as discovery inputs; and assign selectable support only after this project validates the source, projection, rights metadata, and Nextcloud compatibility. No organisation name enters the verified catalogue merely because a folder, package, repository, or component board exists.

## 4. Target architecture

### 4.1 Separation of code, profiles, and instances

The architecture has four layers:

| Layer | Responsibility | Mutability | Storage |
|---|---|---:|---|
| Source artifacts | Token definitions, theme values, declared base themes, assets, licence and provenance | External | Pinned source revision or immutable package artifact |
| Build pipeline | Classify, canonicalize, resolve, validate, project, minimize, and compile profiles | Build-time only | Pinned tooling and generated release assets |
| Nextcloud adapter | Map the normalized contract to supported Nextcloud surfaces | Versioned app code | Installed package |
| Instance state | Active profile, bridge choices, local values, snapshots, uploaded assets | Runtime | IAppConfig and app data |

The flow is:

    pinned source artifacts
      definitions | values | declared base | assets
                         |
                         v
          DTCG canonicalizer and validator
                         |
                         v
             canonical token graph
              aliases and provenance
                         |
                         v
              design-family adapter
        NLDS family: Basis/Common semantic view
                         |
                         v
              Nextcloud projection v1
                         |
                         v
              compiled release profile
                 /        |          \
                v         v           v
          web CSS map  Theming plan  compatibility report
                 \        |          /
                         v
               Nextcloud instance state

Adding a municipality or organisation profile must normally add data, provenance, and evidence—not a PHP enumeration or a new conditional in admin JavaScript.

### 4.2 Ownership boundaries

| Concern | Owner |
|---|---|
| Organisation's canonical house style | The organisation or upstream design-system repository |
| Design Tokens Format | Design Tokens Community Group; accepted dialect and target version pinned by NL Design |
| NL Design System Basis/Common vocabulary and validation guidance | NL Design System upstream; exact consumed artifacts pinned by NL Design |
| Source classification, canonical graph, and Nextcloud projection | NL Design build pipeline |
| Nextcloud variable mapping and targeted shell adapters | NL Design runtime adapter |
| Instance name, web URL, slogan, legal links, logo, header logo, favicon, login background, primary/background colours | Nextcloud Theming app |
| Selection of active profile and optional bridge policy | NL Design instance configuration |
| Whether users may customize colours and backgrounds | Nextcloud administrator policy; never inferred from a design profile |
| Internal UI of a Nextcloud app | That app; NL Design only inherits through documented variables unless a tested adapter exists |
| Dark/high-contrast user preference | Nextcloud accessibility/theming system; it must override brand styling where required |
| Native desktop/mobile client branding | Client build and distribution process, outside this app |

### 4.3 NL Design System alignment and volatility boundary

The architecture binds to the direction of NL Design System, not to a transient monorepo directory or npm package name.

| Treat as a strategic seam | Treat as versioned and replaceable implementation detail |
|---|---|
| Platform-independent token data | Exact source directory layout |
| Brand → Common → Component separation | Legacy Tokens Studio or Style Dictionary input shape |
| `basis.*` as the shared Common-token vocabulary | The current `basis-design-tokens` package |
| Organisation-owned Brand values and prefixes | Current npm scope, package name, and output filenames |
| Start as a neutral reference and explicit base | Current Start package major and component-token inventory |
| Scoped themes and explicit activation | A generated upstream CSS bundle or selector name |
| Evidence-based maturity and accessibility testing | Current Theme Wizard JavaScript API and UI |
| Versioned metadata, aliases, types, and extensions | The current themes/index/theme-switcher inventory mechanism |

The importer distinguishes artifact roles instead of guessing from filenames:

- **definition:** token names, types, extensions, deprecation data, and constraints; it may intentionally contain no values;
- **values:** Brand, Basis/Common, mode, or component values for a named theme;
- **base:** another values artifact inherited in a declared order;
- **asset:** logo, font, icon, or image with separate origin and rights metadata;
- **compiled output:** CSS or JavaScript derived from the preceding inputs; useful for comparison, never the preferred semantic source.

Every source descriptor records the ordered merge graph. A values file must not be rejected merely because a separate definition file owns its metadata, and a metadata-only `tokens.json` must not be mistaken for an incomplete theme.

The build pipeline follows these rules:

1. Canonicalize only documented accepted dialects into a pinned Design Tokens Format target—initially DTCG 2025.10, which the current NL Design System lint package emits. Preserve `$type`, `$value`, aliases, groups, modes, `$extensions`, and deprecation or redirect information that may affect migration.
2. Keep the unresolved alias graph for provenance and diagnostics, and produce a separately resolved view for validation and output. A concrete value without its source chain is insufficient evidence.
3. For NL Design System family profiles, read the `basis.*` Common layer first. Brand values explain where a decision came from; component tokens describe NL Design System components and are not automatically portable to Nextcloud.
4. Map the family semantic view into a small, versioned Nextcloud projection. The projection is a target adapter contract, not a competing universal design-token ontology.
5. Require explicit mappings for older NL Design System themes that do not yet provide the required Basis paths. Prefix stripping, token-name similarity, and “button-to-button” guesses are forbidden.
6. Permit Start-theme values only when the profile declares Start as a pinned base, or when Start is being used as a compiler conformance fixture. Start is never the runtime fallback for an unrelated or broken organisation profile; native Nextcloud remains that fallback.
7. Run pinned upstream schema/lint tooling as a build-time compatibility oracle where it accepts the source. Do not make the installed PHP app depend on Node, npm, the network, or an unreleased Theme Wizard API. A pass from upstream tooling is not sufficient evidence of this adapter's semantics or accessibility.
8. Compile only the source values needed by the Nextcloud projection. Do not ship thousands of unused upstream custom properties merely to preserve the source's layered graph; keep that graph in the build report.
9. Treat a future versioned machine-readable NL Design System theme index as discovery metadata, not automatic trust. Until it actually supplies the required fields, maintain pinned local source descriptors and propose the missing index contract upstream.

The desired upstream theme-index record would identify a stable source ID, publisher and maintainer, repository or package, immutable version, artifact roles, supported modes, maintenance/lifecycle state, and declared licence or identity notice. It must not contain NL Design for Nextcloud's verification status; that remains local evidence.

### 4.4 Nextcloud projection and release-profile contract

A release profile is not “a CSS file.” It combines source facts, a canonical build report, and a bounded Nextcloud projection. It contains:

- stable profile ID and human label;
- design-system family and organisation;
- ordered source artifacts with role, repository/package, exact revision, path, merge order, import date, and integrity hash;
- publisher, maintainer, maintenance state, package channel, and any upstream lifecycle or endorsement claim represented without reinterpretation;
- declared base-theme chain and modes;
- source-code licence, asset licences, and identity-use notice;
- supported light/dark/high-contrast modes as supplied—not invented;
- a bounded set of Nextcloud projection values;
- for each projected value, the source token path, resolved-from chain, mode, transformation, and validation result;
- optional core-Theming recommendations;
- optional profile-owned assets;
- canonicalizer, validator, family-adapter, and compiler versions;
- verification status and evidence;
- known supported and unsupported Nextcloud surfaces.

The first Nextcloud projection should remain small. Candidate groups are:

- brand accent and readable foreground;
- shell/background accent and readable foreground;
- link and focus presentation;
- neutral text, surface, and border values only when explicitly supported;
- status colours only when semantics and contrast are preserved;
- UI typeface only as an opt-in, layout-tested web concern;
- a conservative radius scale that cannot alter layout dimensions.

It must not copy every upstream component token or every Nextcloud CSS variable. A Nextcloud button is not automatically an Utrecht, Den Haag, or `nl` button merely because both are called “button.” For an NL Design System profile, every projected value should normally trace to a Basis/Common path. Any exception needs an explicit rationale and compatibility test.

Core-Theming recommendations are not all design tokens. Profile-owned colours and approved identity assets may provide defaults, but instance name, service URL, slogan, and legal links belong to a separate instance overlay. Resolution is explicit: an instance override wins over a profile recommendation; absence means leave the core value unchanged. Selecting a design profile must never manufacture instance facts or clear existing ones.

### 4.5 Profile status and source-fact model

Every profile has one public status:

- **Discovered:** source exists; not selectable.
- **Compiled:** source parsed into the normalized contract; not yet selectable.
- **Experimental:** selectable only with an explicit warning; compatibility incomplete.
- **Verified:** passes the required profile and surface gates for the current release.
- **Deprecated:** remains readable for migration but cannot be newly selected.
- **Blocked:** excluded for technical, provenance, licensing, or identity-use reasons.

The catalogue defaults to verified profiles. A development mode may expose experimental profiles. File presence never implies verified support.

Verified means verified by this project for the stated technical contract and release matrix. It does not mean that the organisation has endorsed this app, that the profile is an official publication, or that an operator is entitled to use the identity.

Adapter status is not overloaded with upstream facts. The catalogue also exposes separate, non-promotional fields for:

- **source authority:** organisation-published, community/unofficial, third-party, project-owned, or unknown;
- **maintenance:** active, archived, deprecated, or unknown at the pinned source;
- **package channel:** exact upstream scope/tag such as community or unstable, without translating it into a local quality claim;
- **upstream maturity:** retained only where the upstream project assigns it to the consumed artifact;
- **identity rights:** recorded permission, notice/restriction, or unresolved.

The NL Design System Candidate and Hall of Fame Definition of Done is currently a component maturity model. A component status must not be copied onto an organisation theme or this adapter's profile. Likewise, `Verified` must never be rendered as “official.”

### 4.6 CSS adaptation rules

1. Prefer documented Nextcloud CSS custom properties.
2. Make profile CSS inert unless an explicit active-profile class or data attribute is present; do not publish organisation themes globally in :root.
3. Let unmapped values retain Nextcloud's value.
4. Never use another organisation's brand as a fallback.
5. Preserve user dark/high-contrast and accessibility choices.
6. Use render-event injection so API, WebDAV, cron, and non-template requests do not pay the cost.
7. Fail open: a theme failure must not prevent page rendering or administrator recovery.
8. Scope any direct selector to one named surface and one compatibility test.
9. Ban generated Vue data-attribute selectors from release code.
10. Ban universal font or element restyling.
11. Avoid important; every exception requires a comment naming the upstream conflict and a test.
12. Do not change navigation structure or app behavior under the label of theming.
13. Keep organisation assets and rules in the profile, never in the shared adapter.
14. Emit only projected variables and narrowly required declarations; do not bundle a complete upstream NL Design System theme into every Nextcloud page.
15. Preserve source aliases in the build report, but do not require unused source custom properties at runtime merely to keep an alias chain alive.

### 4.7 Core Theming bridge

The built-in Theming app is a peer subsystem, not a base class.

The bridge deliberately includes a tested, versioned private adapter for supported Nextcloud majors while public mutation APIs are absent. That compatibility code is lazily loaded, capability-gated, kill-switchable, and replaceable by a manual backend. Removing it may remove automatic core writes, but must not break profile selection, preview, publication, rendering, rollback, settings access, or recovery. The same operation machinery may later support another bounded core-settings domain, but only through new typed definitions and lifecycle-specific drivers—not by widening this bridge into an arbitrary config-key API.

#### 4.7.1 Field boundary

The bridge models the documented core surface, not every value that a private class happens to read or write.

| Field class | Fields | NL Design treatment |
|---|---|---|
| Instance presentation | `primary_color`, `background_color`, `background`, `logo`, `logoheader`, `favicon` | Supported bridge candidates; a profile may recommend them and an instance may override them |
| Instance identity | `name`, `url`, `slogan`, `imprintUrl`, `privacyUrl` | Supported bridge candidates, but stored in the instance overlay rather than inferred from design tokens |
| Administrator policy | `disable-user-theming` | Visible as context; never changed merely by selecting or applying a profile; any future write is a separately confirmed policy action |
| Client distribution and product defaults | mobile-client URLs and IDs, `productName`, documentation URL | Outside the profile bridge; some may be readable through defaults APIs but they are not part of the documented Theming mutation contract |
| Per-user state | enabled light/dark/high-contrast/font themes, personal colours, personal background | Never read as global state and never overwritten by the bridge |
| Navigation policy | default app order | Outside theming even if currently presented in the same core settings panel |
| Derived implementation state | MIME keys, logo dimensions, cache buster, generated colour variants, generated icons, PWA metadata | Owned entirely by core Theming and changed only as a side effect of its manager |

The background is a tagged choice: inherit the core default, use a colour, or use an image. Header logo and favicon are likewise either inherited/generated, explicitly set, or reset. Empty strings are not a sufficient API model for these states.

#### 4.7.2 Read and write ports

NL Design owns stable domain ports and keeps Nextcloud-version details behind them:

- an `InstanceBrandingReader` returns a global administrator-facing snapshot, including configured value, effective value, inheritance state, asset digest, and supported operations per field;
- `PlanBrandingChange` and `ApplyBrandingPlan` validate, freeze, execute, verify, and recover a typed patch through stable driver ports;
- a `ManagedSettingDriver` reports and implements read, set, reset, asset, verification, and restoration support per field for the current Nextcloud runtime.

The read snapshot must not be derived from the current user's effective colours or background. Core capabilities and `ThemingDefaults::getColorPrimary()` may include personal choices; an administrator change plan needs the global configured and global effective state.

The open upstream `OCP\Theming\IDefaults` draft is the direction for the read side. NL Design should use the same names and meanings where they fit, and a future adapter should consume that interface once it is released in the project's minimum Nextcloud version. Until then, it is a tracked upstream target, not a compile-time or runtime dependency. Fields absent from `IDefaults` remain explicit capabilities rather than being guessed from generic config.

#### 4.7.3 Upstream API direction

Do not turn `IDefaults` into a bag of setters. Effective defaults are widely consumed and can be user-sensitive; administrator mutation has different authorization, validation, concurrency, asset, and rollback requirements.

Propose a separate, server-owned mutation service—tentatively `NCU\Theming\IInstanceBrandingManager` while experimental, followed by `OCP\Theming\IInstanceBrandingManager` if accepted. Its contract should:

- expose a typed global state and capability description;
- accept a typed patch with `leave`, `set`, and `reset` operations;
- validate every value and asset before changing live state;
- stage and copy assets through streams or public file abstractions, never caller-supplied server paths;
- own MIME detection, image processing, background-colour derivation, logo dimensions, cache invalidation, generated assets, and read-back verification;
- reject stale writes through a revision or expected-state token;
- return a structured result that distinguishes success, rolled-back failure, and recovery-required failure;
- emit public change events without exposing private storage keys;
- be used by core's Theming settings controller and `occ theming:config` as well as third-party apps, so there is one canonical lifecycle.

Nextcloud's unstable `NCU` namespace is the appropriate place to test this shape for one major before freezing it as public API. The upstream defaults refactor should be engaged rather than forked: its authors are already separating legacy defaults from a future `OCP\Theming` namespace.

#### 4.7.4 Operation model

The bridge is:

- explicit, one-way, and administrator initiated;
- field-based, with each field marked `leave`, `set`, or `reset`;
- sourced from profile recommendations plus a distinct instance overlay;
- previewed as a separate change plan showing current configured value, current effective value, proposed value, owner, support level, side effects, and reversibility;
- protected by an expected revision so a later core-Theming edit becomes a conflict rather than being overwritten;
- backed by a before-state snapshot containing asset bytes or content-addressed copies, not only URLs;
- validated and staged before mutation, then read back and verified;
- rolled back on the first failure, with any incomplete recovery reported truthfully rather than described as atomic;
- capability-detected and integration-tested per supported Nextcloud major;
- optional, so CSS profile selection still works when the bridge is unavailable.

There is no bidirectional synchronization. Bidirectional sync creates loops and ambiguous ownership when an administrator edits the same field in two panels. A later external edit is detected as drift and presented as a conflict; NL Design does not silently pull it into the profile or push over it.

Preferred implementation order:

1. Freeze the application-owned reader, writer, capability, patch, and snapshot contracts.
2. Implement the manual driver and prove the complete profile workflow with all compatibility code absent.
3. Implement the contained `OCA\Theming` driver for the selected Nextcloud release matrix. It is a milestone deliverable, not an upstream-dependent aspiration, but is enabled only where real lifecycle, rollback, and upgrade tests pass.
4. Add capability probes, a `public-only` kill switch, and automatic downgrade to the manual driver; never fall through to a raw-config write after a failed private operation.
5. Align the reader vocabulary with the upstream `OCP\Theming\IDefaults` draft and track that proposal by revision and status.
6. Contribute the separate experimental mutation-manager design upstream and seek agreement on the lifecycle without blocking the temporary driver.
7. Replace private operations with the public manager field by field as it becomes available; do not change the NL Design API or profile schema during that migration.

Selecting a profile must never silently overwrite core Theming. Applying a branding plan must never change `disable-user-theming`, a user's accessibility choices, client distribution, product identity, or navigation policy as an incidental side effect.

## 5. Surface and app support contract

“Themes Nextcloud” is too broad to be a release claim. Support is assigned per surface and version.

### 5.1 Support labels

- **Supported:** tested in every supported Nextcloud major and required accessibility mode; regressions block release.
- **Compatible:** expected to inherit documented variables and smoke-tested, but not covered as deeply.
- **Best effort:** some brand values may inherit; no layout or visual guarantee.
- **Unsupported:** separate renderer, client, or fragile UI; NL Design does not claim control.
- **Unverified:** not yet tested; this is the default.

### 5.2 Initial surface inventory

| Surface | Mechanism | Roadmap intent |
|---|---|---|
| NL Design admin panel | App-owned UI | Supported first; it is the recovery and configuration surface |
| Nextcloud shell/header/navigation | Semantic variables plus minimal audited selectors | Candidate for supported |
| Login, public share, guest, and error pages | Core Theming plus narrowly scoped adapter | Candidate for supported, each tested separately |
| Files | Nextcloud variables and Nextcloud Vue components | Candidate for supported |
| Settings, Activity, Dashboard | Nextcloud variables/components | Candidate for compatible or supported based on tests |
| Calendar, Contacts, Mail, Talk, Forms, Deck and other store apps | Inheritance when they use Nextcloud variables; app-specific test required | Unverified until added to the matrix |
| Legacy apps with hard-coded styles | Incidental inheritance only | Best effort unless a maintained adapter is justified |
| Nextcloud Office/Collabora, ONLYOFFICE, rich editors, maps, diagrams, canvases, and iframes | Separate application or rendering context | Unsupported unless a dedicated integration is built |
| Server-generated email | Built-in Theming/email template path | Core bridge only; not web CSS |
| Favicons, PWA icons, header logo | Built-in Theming image generation | Core bridge only |
| Desktop and mobile clients | Native client themes/builds | Out of scope |

This table is an inventory, not evidence that the candidate surfaces already pass.

### 5.3 Version and mode policy

As of this review, Nextcloud documents 32, 33, and 34 as maintained releases. Gate 0 must verify that claim at implementation time and choose a matrix that the project can actually sustain.

For each supported major, test:

- default/light;
- dark;
- light high contrast;
- dark high contrast;
- user primary colour enabled and disabled;
- desktop and narrow/mobile web viewport;
- anonymous, authenticated user, and administrator contexts.

Each release also freezes one dated NL Design System Baseline snapshot. Its browser, operating-system, and assistive-technology combinations are test-planning input, not a dependency that floats during a release. The required NL Design System-aligned matrix is the intersection of this app's supported Nextcloud web environments and the relevant combinations in that frozen Baseline; broader Nextcloud claims may require additional cells. Where the two policies do not overlap or test infrastructure is unavailable, the compatibility report records the gap instead of silently dropping it.

Passing that matrix proves only the named profile, adapter version, Nextcloud version, surface, mode, and environment. It does not inherit an accessibility or compliance claim from NL Design System, the Start theme, a component package, or the Baseline itself.

If the project cannot sustain all maintained majors, it must narrow the declared range. It must not retain a wide appinfo range and call untested versions supported.

## 6. Existing feature disposition

This table prevents the large historical backlog from silently becoming the roadmap.

| Existing idea or implementation | Decision | Reason |
|---|---|---|
| Data-driven profile catalogue | Keep | Correct separation of code and available profiles |
| Filesystem scan as support criterion | Replace | Presence is not verification; use a validated manifest |
| NL Design System folder or package presence as support or endorsement | Reject | Repository location, publication, authority, identity rights, and this adapter's verification are different facts |
| Nightly upstream token sync | Reframe | Pin upstream, build deterministically, open a reviewed diff; never auto-promote support |
| Complete upstream generated theme CSS in Nextcloud | Reject | Imports component-specific rules and potentially thousands of irrelevant custom properties into a different component system |
| Theme Wizard or Style Dictionary in the Nextcloud runtime | Reject | Use pinned build-time tools; production must need neither Node nor network access |
| Pinned upstream schema/lint as a build oracle | Add, bounded | Useful compatibility evidence, but not the canonical model and not proof of projected contrast or accessibility |
| Implicit Start-theme fallback | Reject | It hides incomplete organisation data and can create a mixed, untraceable identity; inheritance must be declared |
| Start theme as compiler fixture or declared base | Keep | It exercises the Basis path and can supply an explicit neutral base without pretending to be organisation data |
| Candidate or Hall of Fame label on a profile | Reject | Those statuses currently describe component maturity, not theme authority or Nextcloud compatibility |
| Machine-readable upstream theme index | Track and contribute | Valuable discovery/provenance infrastructure, but it cannot replace local rights and compatibility decisions |
| Rijkshuisstijl defaults for every profile | Reject | Cross-brand contamination |
| Global seven-layer aggressive CSS | Replace | Layer count is not architecture; semantic mapping and precedence are |
| Token editor for all Nextcloud variables | Defer and narrow | Exposes unstable implementation details and permits incoherent combinations |
| Per-token checkboxes in profile apply dialog | Reject for profile application | Dependent tokens must be applied coherently; local overrides are a separate concern |
| Explicit preview and publish | Keep | Required safety boundary |
| Rollback to previous profile | Add to core | Required recovery boundary |
| Automatic Theming sync on selection | Reject | Hidden cross-subsystem mutation and ambiguous ownership |
| Explicit core-Theming change plan | Keep and reframe | Needed for non-CSS branding surfaces |
| Hide slogan with CSS | Retire from core | The selector hid the complete guest footer, including the instance identity link; use Theming-owned state or design a narrower evidenced adapter |
| Replace app-menu icons with labels | Retire from core | The structural selector lacked supported-major evidence; any successor belongs in a separately tested navigation adapter |
| Bounded projected-profile installation | Added, narrow | Closed semantic v1 envelope, immutable app-data versions, generated CSS, and separate activation; not a DTCG/source importer |
| General source-token or configuration import/export | Later | Requires the richer compiler, review plan, migration, rights, assets, secrets, and recovery contracts |
| Freeform custom CSS | Reject from the core app | High security, support, and upgrade cost; a separate expert extension could own it |
| Custom font upload | Later, opt-in | Licensing, MIME, CSP, layout, privacy, and app-data concerns |
| Per-app theming exclusion | Later escape hatch | Potentially useful after the compatibility matrix reveals real failures |
| Per-group or per-user house style | Not in v1 | Caching, identity, support, and state-isolation complexity |
| Algorithmic dark variants | Reject as a support claim | Derived colours are not an organisation-approved theme |
| Source-provided dark variants | Later | Can be supported with provenance and separate accessibility evidence |
| High-contrast profile overriding Nextcloud accessibility | Reject | User accessibility preference has priority |
| Icon-pack distribution | Split from core roadmap | Asset API and licensing product, not necessary for profile adaptation |
| Email template replacement | Reject from v1 | Core Theming already owns server email identity; use the bridge |
| Audit log and metrics | Demand-driven later | Operational products need a consumer and retention/security design |
| Runtime upstream-freshness job | Reject | Supply-chain checks belong in build/release automation |
| Fabricated placeholder municipality logos | Reject | A generated badge is not an organisation's approved identity |

## 7. Delivery roadmap

The sequence is strict. A later milestone cannot start merely because code for it already exists on another branch.

### Gate 0 — Establish one truthful baseline

**Objective:** decide what repository state is being productized.

Recommended branch strategy:

- create a clean architecture-v1 line from the released main baseline;
- reapply the data-driven catalogue/profile work against the new contract;
- retain development as a source of candidate implementations and tests;
- port changes in small, reviewable units;
- do not merge the 435-commit development delta wholesale.

Work:

1. Produce a feature ledger for main, development, and the packaged release:
   - present in code;
   - reachable at runtime;
   - covered by a meaningful test;
   - documented;
   - released.
2. Build and install the exact release package in a clean Nextcloud environment.
3. Replace false-green commands. Missing tests or dependencies produce “not run,” not success.
4. Choose the supported Nextcloud/PHP matrix from currently maintained combinations.
5. Audit every entry in the current catalogue—currently 40—for pinned source, source authority, maintenance state, package channel, identity rights, actual token role, and local adapter evidence. Until classified, each entry is `Discovered`, not supported.
6. Reconcile licence identifiers, repository host, profile count, font delivery, and capability claims.
7. Mark every OpenSpec item as proposed, implemented, verified, or released. A checkmark alone is removed as status evidence.
8. Freeze new feature work until the baseline decision is recorded.
9. Record architecture decisions for:
   - product boundary;
   - state ownership;
   - profile contract;
   - fallback policy;
   - CSS precedence;
   - accepted source-artifact roles and legacy token dialects;
   - the canonical Design Tokens Format version and extension policy;
   - the NL Design System Basis/Common semantic mapping;
   - explicit Start-theme inheritance and its non-fallback role;
   - upstream schema, lint, Theme Wizard, package, and version-pinning policy;
   - local source descriptors and the future upstream-index relationship;
   - profile lifecycle versus source-authority, maintenance, maturity, and rights facts;
   - core-Theming read/write boundary, `IDefaults` alignment, and adapter policy;
   - supported-version policy.

Exit criteria:

- one named baseline commit and branch;
- one reproducible package;
- one test command that fails when a required test fails;
- one feature ledger with no ambiguous “done” state;
- one supported-version decision;
- one pinned NL Design System source-and-tool snapshot;
- every current catalogue entry classified or explicitly quarantined;
- documentation no longer claims all apps, all profiles, or full compliance;
- current dirty SENERAWA work is preserved separately and not accidentally folded into the reset.

Stop condition:

If development cannot reproduce its claimed tests against its packaged artifact, it is not an integration baseline. If main cannot install cleanly, the first implementation task is package repair, not theming expansion.

### Milestone 1 — Prove the safe vertical slice

**Objective:** publish and roll back a small, correct profile without private Theming writes or aggressive CSS.

Work:

1. Define and validate profile-manifest version 1. **Implemented for the
   packaged catalogue and the narrow installed-profile envelope.**
2. Keep a nullable native-Nextcloud state representing “no NL Design
   override,” without inventing a synthetic organisation profile. **Implemented
   in the profile foundation.**
3. Select two representative, legally usable pilot profiles:
   - one actively maintained NL Design System organisation profile whose publisher authority and identity-use basis are recorded;
   - one independent/local profile proving that the adapter is not municipality-specific.
   If no organisation profile clears those gates, use a clearly synthetic project-owned fixture and make no organisation support claim.
4. Pin the NL Design System Start theme as a compiler-conformance fixture. It may be a profile's base only when that inheritance is explicit in the manifest; it is never an invisible runtime fallback.
5. Implement deterministic import for only the semantic fields required by the slice, with a source trace for every emitted value.
6. Store active profile ID, profile version, and previous snapshot in IAppConfig.
   **Implemented.**
7. Store generated or uploaded runtime assets in app data. **Implemented for
   installed profile descriptors and generated CSS; binary assets remain out
   of scope.**
8. Inject styles only on template-render events and fail open. **Implemented.**
9. Map the smallest useful set of Nextcloud variables. **Implemented for the
   current four-role projection; compatibility evidence remains incomplete.**
10. Build an accessible admin flow:
   - inspect;
   - preview for the current administrator;
   - publish;
   - roll back.
11. Show source authority, pinned revision, declared inheritance, adapter status, and known limitations before publication.
12. Provide a CLI recovery command or documented app-config recovery path that does not depend on the themed web UI.
13. Leave core Theming unchanged; show recommendations separately.

Exit criteria:

- works when the app directory is read-only;
- no runtime writes under css, img, js, or other package directories;
- switching and rollback survive restart and app upgrade;
- no Rijkshuisstijl value or asset appears in the independent profile unless explicitly present in that profile;
- no Start-theme value appears unless the manifest declares Start as a base and the trace identifies it;
- every projected value has a reproducible source or transformation trace;
- a malformed or missing profile falls back to neutral Nextcloud;
- API/WebDAV/cron requests do not load the theme service graph;
- the admin can recover after a deliberately invalid profile;
- the package passes tests on the chosen Nextcloud versions.

### Milestone 2 — Establish the web compatibility contract

**Objective:** know exactly what the CSS adapter can and cannot support.

Work:

1. Inventory Nextcloud semantic variables per supported major.
2. Create an allow-list mapping from normalized profile semantics to those variables.
3. Set and enforce a projected-output budget: emitted custom-property count, minified bytes, declarations, assets, and direct selectors. Upstream token count is diagnostic only and must not dictate runtime payload.
4. Remove the universal font rule, generated Vue selectors, shared Rijkshuisstijl assets, and broad layout rewrites.
5. Add a selector budget:
   - each direct selector names its surface;
   - each has a compatibility test;
   - each important declaration has a documented upstream conflict.
6. Test core shell, login, public share, guest/error, Files, Settings, Activity, and Dashboard.
7. Add candidate apps one at a time based on actual deployment demand.
8. Test default, dark, light high contrast, dark high contrast, and user-colour modes.
9. Freeze the current NL Design System Baseline snapshot for the release and test the applicable browser/assistive-technology combinations alongside the Nextcloud matrix.
10. Run independent contrast checks on the actual projected foreground/background pairs plus manual keyboard, focus, zoom, and screen-reader smoke tests. An upstream schema/lint pass is not a substitute.
11. Add visual regression snapshots at representative desktop and narrow widths.
12. Publish the generated compatibility report, output-budget report, and Baseline snapshot with the release.
13. Add a per-app opt-out only if real compatibility failures justify it.

Exit criteria:

- every release claim maps to a passing matrix cell;
- untested apps are labelled unverified, not “supported through CSS variables”;
- user accessibility modes win over the organisation profile;
- no universal selectors or generated framework attributes remain;
- projected CSS stays within the reviewed output budget; a source-theme expansion cannot silently expand runtime CSS;
- profile selection does not alter app behavior or navigation structure;
- known unsupported renderers remain functional with native styling;
- selector breakage on the next Nextcloud candidate release is detected before release.

### Milestone 3 — Build a trustworthy profile supply chain

**Objective:** make adding a profile a data-and-evidence change.

Work:

1. Pin every source artifact and build tool to an immutable revision or exact package version, with integrity data and licence metadata. A branch name, `latest`, or live URL is not a release input.
2. Classify every input by role before parsing:
   - **definition:** names, types, descriptions, examples, constraints, and deprecation metadata; it may intentionally contain no values;
   - **values:** organisation or theme values and aliases;
   - **declared base:** another pinned theme whose values are inherited explicitly;
   - **asset:** an approved font, logo, icon, or image with separate provenance and rights;
   - **compiled output:** disposable build evidence, never canonical source.
3. Document the accepted legacy input dialects and initially use Design Tokens Community Group Format 2025.10 as the pinned canonical graph and generated JSON contract. Preserve token types, aliases, alias chains, groups, extensions, deprecation data, and original paths; reject or quarantine anything that cannot be represented without loss. A format-version change requires an architecture decision and migration fixtures.
4. Use pinned NL Design System schema, upgrade, and lint packages as secondary conformance oracles for NL Design System inputs. Record their versions, warnings, disabled checks, and disagreements with the local canonicalizer. They do not run in Nextcloud and do not define local support by themselves.
5. Build a canonical token graph with deterministic diagnostics for cycles, dangling references, type conflicts, ambiguous merges, unknown extensions, and shadowed values. Preserve source prefixes and per-node provenance instead of flattening unrelated namespaces.
6. Implement versioned design-family adapters:
   - NL Design System profiles map explicitly through the Basis/Common vocabulary;
   - non-NL Design System profiles use their own documented semantic mapping;
   - component-token names and organisation prefixes are never guessed into Nextcloud semantics.
7. Use the pinned Start theme to test the NL Design System family adapter. Permit Start values in another profile only through declared, versioned inheritance; report inherited and overridden values separately.
8. Keep token definitions separate from token values. A definition package can validate or drive documentation and UI without becoming a profile or inventing values.
9. Project only the allow-listed Nextcloud semantics. For every emitted value, record the source path, resolved alias chain, inherited base if any, mode, transform, target, and validation result.
10. Validate required semantic fields and the actual projected contrast pairs independently of upstream validation. Never assume that a valid Start, organisation, Candidate, or Hall of Fame artifact proves contrast in a Nextcloud surface.
11. Minimize compiled CSS to the values and declarations consumed by the selected projection. Enforce variable-count, byte-size, asset, and selector budgets and report budget deltas in review.
12. Generate the release catalogue from validated profile descriptors. Keep local adapter status separate from source authority, maintenance state, package channel, upstream maturity, and identity rights.
13. Record source-code licence, token-data licence, asset licence, trademark/identity notice, logo origin, and operator restrictions independently. Remove fabricated organisation assets.
14. Maintain local source descriptors while NL Design System lacks an authoritative machine-readable theme index. Propose or contribute an upstream index that records source identity, artifact roles, revisions, maintenance, authority claims, licences, modes, and replacement relationships; consume it only as discovery metadata until its governance and schema are dependable.
15. Make upstream automation create a reviewable pull request containing:
    - pinned old and new source/tool revisions and integrity data;
    - source-authority, maintenance, rights, and replacement changes;
    - canonicalizer and upstream-tool diagnostics;
    - semantic and inheritance diff;
    - projected-CSS and budget diff;
    - changed screenshots and compatibility status.
16. Require human approval before a profile becomes verified, its source or declared base changes, a build tool is upgraded, or the family/projection mapping changes.
17. Make two builds from the same inputs byte-for-byte reproducible, excluding explicitly documented timestamps. The packaged app must need no Node runtime, package registry, source repository, CDN, or network access.
18. Generate into a clean staging directory, compare the complete expected inventory, and fail on stale or orphaned profile/CSS/assets. A removed or blocked profile cannot survive because an old generated file remains in the package.

Exit criteria:

- every visible verified profile has provenance, source-fact metadata, rights metadata, a compiler report, and compatibility evidence;
- every accepted artifact has a pinned role and input dialect, and every source alias needed by the projection resolves;
- every projected value is traceable through aliases, inheritance, transforms, and target mapping;
- no component-name or organisation-prefix heuristic determines a Nextcloud semantic value;
- no Start-theme value enters a profile through an implicit fallback;
- the canonical format, NL Design System family adapter, upstream conformance tools, and Nextcloud projection are independently versioned;
- upgrading an upstream source or tool requires a reviewed semantic and output diff;
- a source update cannot silently change a verified profile;
- a new profile requires no PHP or JavaScript enumeration;
- manifest and CSS/profile inventory cannot drift undetected;
- no stale generated file can keep a removed, renamed, or blocked profile alive;
- runtime operation is deterministic and offline with no build tool embedded in the app;
- unsupported or incomplete sources do not appear as ordinary selectable profiles;
- profile counts in product text are generated or omitted.

### Milestone 4 — Add a controlled core-Theming bridge

**Objective:** cover the documented non-CSS identity surface through a stable local contract that can migrate from versioned OCA adapters to the emerging upstream `OCP\Theming` API without changing profiles or administrator workflows.

Work:

1. Freeze a field matrix for the eleven documented instance-branding values:
   - primary colour;
   - background colour;
   - background image or colour-only mode;
   - instance name;
   - web URL;
   - slogan;
   - legal-notice URL;
   - privacy-policy URL;
   - logo;
   - header logo;
   - favicon.
2. Record `disable-user-theming` separately as administrator policy and prove that profile application cannot mutate it.
3. Separate profile recommendations from the instance overlay in the schema and migration plan.
4. Define typed local reader, driver, capabilities, patch, plan/apply, result, and snapshot contracts. Do not expose OCA classes, raw config keys, arbitrary paths, or empty-string reset conventions.
5. Document, for every supported Nextcloud major:
   - public `OCP\Defaults` or released `OCP\Theming\IDefaults` read coverage;
   - global versus current-user semantics;
   - public mutation and reset coverage;
   - private adapter requirements;
   - exact `occ` fallback commands.
6. Track the upstream `OCP\Theming\IDefaults` draft by commit and status. Add the `OCP` reader adapter only after the interface is released; never vendor or impersonate an unmerged OCP interface. This does not block the separately isolated private mutation driver.
7. Prepare and discuss upstream a separate experimental `NCU\Theming` mutation manager. Require core's web controller and `theming:config` command to consume it before treating it as the canonical lifecycle.
8. Implement an apply plan that shows configured, effective, proposed, source, owner, capability, side effect, and reversibility per field.
9. Require explicit field selection and final confirmation. Instance facts are never inferred solely from profile identity.
10. Snapshot old scalar values and original asset bytes, and include an expected revision in the confirmed plan.
11. Validate and stage the full patch before mutation; apply, read back, and verify it; stop and roll back on the first failure.
12. Treat image update and its MIME/config/cache work as one adapter operation. Add a regression test that fails if bytes are stored but `hasImage()` remains false.
13. Implement the verified private adapter as a temporary compatibility driver for matrix cells that pass:
   - isolate all OCA references in one compatibility package;
   - version it for the exact tested Nextcloud range and probe class/method shape before use;
   - load it only during administrator capability, plan, apply, verify, or rollback requests;
   - disable it when capability checks fail;
   - limit writes to the documented allowlist;
   - test set, reset, generated assets, cache invalidation, read-back, rollback, and upgrades against real core Theming behavior;
   - make generated `occ` instructions the fallback.
14. Add a removal-contract test: profile discovery, preview, publication, rendering, rollback, settings access, and CLI recovery pass with compatibility code unavailable.
15. Never run the bridge automatically when a dropdown changes.

Exit criteria:

- profile CSS remains usable with the bridge disabled;
- automatic private writes are available on every declared `private-verified` matrix cell and downgrade cleanly to manual elsewhere;
- the local domain contract contains no Nextcloud-major-specific class or config-key name;
- the released reader can migrate to `OCP\Theming\IDefaults` without changing its consumers;
- no private core class appears outside the compatibility boundary;
- removing the compatibility directory does not break the load-bearing profile workflow;
- every one of the eleven branding fields is explicitly supported, unsupported, or manual-only for each declared Nextcloud major;
- administrator policy, user preference, navigation, client distribution, and derived state cannot enter a profile patch;
- every changed field has before-state recovery;
- image MIME, dimensions, size, and SVG safety are validated;
- an image is not reported as applied until core recognizes and serves it;
- a stale plan cannot overwrite a newer core-Theming edit;
- partial failure leaves a known state, a structured status, and a recovery command;
- email/icons/public branding are claimed only when the core bridge test passes;
- changing core Theming independently does not trigger a sync loop.

### Milestone 5 — Pilot and stable release

**Objective:** release a small product whose claims are narrower than its evidence.

Work:

1. Test fresh install, enable, disable, upgrade, downgrade where supported, uninstall, and reinstall.
2. Test a read-only app path and common container deployment.
3. Test without network access at runtime.
4. Verify Content-Security-Policy, CSRF, admin authorization, output escaping, uploaded assets, and recovery paths.
5. Measure page-load asset count, CSS size, and render overhead.
6. Pilot on independently operated instances with different selected profiles and app mixes.
7. Record failures as compatibility data, not one-off CSS patches.
8. Reconcile appinfo, README, user docs, screenshots, release notes, licence, and profile catalogue against the package.
9. Publish:
   - support matrix;
   - profile inventory and status;
   - migration notes;
   - rollback procedure;
   - known limitations;
   - security and identity-use notes.

Exit criteria:

- all release gates pass against the signed/package-equivalent artifact;
- at least one clean upgrade from the previous release preserves or safely migrates instance state;
- operators can recover without editing package files;
- pilot evidence covers the declared primary workflow;
- no stable claim depends on development-only code;
- unsupported surfaces remain explicit;
- the release can be maintained across the declared Nextcloud range.

### Milestone 6 — Optional expansion

Only consider these after Milestone 5:

- richer DTCG/NL Design System source-profile import; the narrow projected-pack
  installer is already part of the profile foundation and must not expand by
  schema accretion;
- a narrow semantic override editor, not an editor for arbitrary Nextcloud internals;
- configuration export/import with secrets and assets handled explicitly;
- source-provided dark variants;
- a per-app compatibility escape hatch;
- a separately scoped icon-pack or asset capability;
- audit history where an operator has a retention and accountability requirement.

Each optional feature needs its own product hypothesis, threat model, storage design, compatibility impact, and removal plan.

## 8. Quality and evidence gates

### 8.1 Required test layers

| Layer | Required evidence |
|---|---|
| Source descriptor registry | Pinned source, artifact role, authority, maintenance, package channel, rights, replacement, and quarantine fixtures |
| Canonical token graph | Fixtures for accepted dialects, types, aliases, cycles, dangling references, merges, extensions, deprecation, and incomplete inputs |
| Upstream conformance | Pinned NL Design System schema/lint results, expected disagreements, migration fixtures, and a reviewed tool-upgrade diff |
| Design-family adapter | Golden Basis/Common and non-NLDS mappings, declared Start inheritance, and explicit unmapped-token diagnostics |
| Nextcloud projection | Golden source-to-target traces, independent contrast pairs, minimal output, and output-budget enforcement |
| Profile validator | Required fields, contrast, provenance, assets, and status transitions |
| State service | Selection, snapshots, rollback, migration, read-only package |
| CSS contract | Allowed variables, banned selectors, no organisation leakage, deterministic output |
| Core bridge | Per-version integration tests, partial-failure rollback, cache behavior |
| Admin UI | Keyboard, focus, validation, preview isolation, publish confirmation, recovery |
| Surface compatibility | Version × mode × context × surface matrix |
| Package | Install the produced archive, not only the source checkout |
| Documentation | Counts, licence, versions, features, and support claims checked against generated release data |

### 8.2 Accessibility gate

Automated contrast is necessary but insufficient. A release also needs:

- visible keyboard focus;
- no focus loss during preview/publish;
- 200% zoom and reflow checks;
- high-contrast mode precedence;
- forced-colours smoke testing where available;
- screen-reader names for controls and status;
- reduced-motion respect;
- no colour-only status communication;
- verification that custom fonts do not hide glyphs or break icon rendering.

The app may report measured checks. It must not claim WCAG, EN 301 549, or Rijkshuisstijl compliance for an entire Nextcloud installation without a separately defined audit scope.

The release records the exact dated NL Design System Baseline used to plan browser and assistive-technology testing. A newer monthly Baseline does not retroactively change released evidence. Adopting a newer snapshot is a reviewed matrix change, and any combination that cannot be exercised is published as a gap. The Baseline defines test environments; it does not certify this adapter, an organisation profile, third-party Nextcloud apps, or the installation as a whole.

### 8.3 Release invariants

A stable release must have:

- zero runtime writes to the installed app directory;
- zero silent core-Theming mutations;
- zero verified profiles without provenance;
- zero cross-brand fallback values;
- zero implicit Start-theme inheritance;
- zero universal element/font selectors;
- zero generated Vue data-attribute selectors;
- zero stale generated profiles or assets;
- zero runtime dependency on NL Design System build tools or network sources;
- zero required test commands that turn failure into success;
- zero “all apps” or “fully compliant” claims;
- one tested rollback path;
- one compatibility report tied to the release commit.

## 9. Risk register and kill criteria

| Risk | Failure mode | Control | Kill or downgrade criterion |
|---|---|---|---|
| Branch divergence | Features are duplicated, lost, or falsely considered released | Gate 0 ledger and clean baseline | No feature merge until package and tests are reproducible |
| Private Theming API | Nextcloud upgrade breaks settings or silently omits required side effects | Stable local ports; explicit major-to-contract mapping; allowlist; real lifecycle tests | Disable bridge for that major and use generated manual plan |
| Upstream API speculation | An unmerged `OCP\Theming\IDefaults` draft changes or is abandoned after local code depends on it | Track by revision; align vocabulary; bind only after release | Keep the existing reader adapter; never raise minimum support for a draft |
| Over-broad mutation API | Generic setters expose policy, user state, or implementation keys as branding | Separate read and write interfaces; typed patch; field classes | Reject upstream or local API shape until arbitrary-key mutation is impossible |
| Token semantic mismatch | A profile exists but changes nothing or changes the wrong UI concept | Normalized contract and explicit mapping | Profile remains compiled/experimental, never verified |
| Cross-brand fallback | Municipality renders national-government colours/assets | Native Nextcloud fallback only | Release blocked on any organisation leakage |
| CSS cascade conflict | Dark/high-contrast or user themes become unreadable | Precedence contract and mode matrix | A failing accessibility mode blocks the profile/release |
| Fragile selectors | Nextcloud update breaks layout | Variable-first mapping and selector budget | Surface downgraded unless a maintainable adapter exists |
| Immutable deployment | Runtime customization fails or disappears on upgrade | IAppConfig and app data only | Any package-directory write blocks release |
| Upstream drift | Nightly update silently changes identity | Pinned revision, semantic diff, human promotion | No automatic verification or release |
| NL Design System package churn | An unstable package is renamed, split, removed, or changes major semantics | Bind to the canonical graph and family adapter; pin packages and tools; migration fixtures | Keep the previous pin or quarantine the affected source; never redesign runtime around an unreleased package layout |
| Source-authority conflation | A community, archived, experimental, or merely indexed source is advertised as an organisation-approved profile | Separate source facts, identity rights, and local adapter status | Remove the organisation claim or block selection until authority is evidenced |
| Implicit Start contamination | Missing organisation values silently inherit neutral Start values and appear intentional | Declared base with per-value trace; native Nextcloud runtime fallback | Block verification when inherited values are undeclared or materially alter identity |
| Token payload explosion | A layered source emits thousands of unused variables and increases CSS/cascade risk | Projection allowlist, clean generation, variable/byte/selector budgets | Reject the build or hold the source update until output is reduced and reviewed |
| Validator false confidence | Upstream schema/lint accepts input while projected Nextcloud pairs or surfaces remain inaccessible | Treat upstream tools as secondary oracles; run independent projection and surface tests | Profile remains experimental or blocked despite upstream success |
| Licensing/identity misuse | Shipped asset or profile is not legally usable by an operator | Separate provenance and identity-use metadata | Block profile until clarified |
| Admin lockout | Bad CSS hides settings or makes UI unusable | Isolated preview, fail-open injection, CLI rollback | No publish without recovery proof |
| Arbitrary CSS/font upload | Stored XSS, data exfiltration, CSP, or support burden | Out of v1; strict future sandbox and validation | Feature remains separate or rejected |
| Per-group theming | Cached CSS leaks one group's identity to another | Out of v1; explicit cache and privacy design required | No implementation without isolation proof |
| False compliance | Procurement relies on unsupported claims | Evidence-scoped reporting | Remove claim rather than weaken gate |
| Maintenance overload | Too many versions/apps/profiles for available capacity | Narrow support tiers | Reduce declared scope before accepting debt |

## 10. Roadmap metrics

Track outcomes, not feature count:

- percentage of selectable profiles that are verified;
- percentage of verified profiles with complete provenance and rights metadata;
- percentage of projected values with a complete source, alias, inheritance, transform, and target trace—target 100%;
- number of catalogue entries with unknown source authority or unresolved identity rights—target zero for selectable profiles;
- deterministic-build rate;
- upstream source/tool pins changed without a reviewed semantic diff—target zero;
- number of supported matrix cells and their pass rate;
- compiled CSS custom-property count, minified bytes, assets, and budget delta per profile;
- number of direct DOM selectors and documented exceptions;
- rollback success rate in destructive test scenarios;
- time to detect a Nextcloud candidate-release regression;
- number of support claims generated from evidence;
- number of runtime package writes, private API leaks, and cross-brand fallback defects—target zero.

Do not use total token count, total profile count, total icon count, or number of admin controls as evidence of quality.

## 11. Governance

### 11.1 Status vocabulary

Do not force unlike facts into one status field. Use one controlled vocabulary per dimension:

| Dimension | Vocabulary |
|---|---|
| Feature/implementation lifecycle | Proposed, Implemented, Verified, Released, Deprecated, Rejected |
| Local profile-adapter lifecycle | Discovered, Compiled, Experimental, Verified, Deprecated, Blocked |
| Source authority | Organisation-published, Community/unofficial, Third-party, Project-owned, Unknown |
| Source maintenance | Active, Archived, Deprecated, Unknown |
| Package channel | Verbatim upstream scope, version, tag, and stability label |
| Upstream maturity | Verbatim status assigned by upstream to the consumed artifact; absent otherwise |
| Identity rights | Recorded permission, Notice/restriction, Unresolved |

“Done” is ambiguous and should not appear without naming the dimension and state. `Verified` in the feature lifecycle and `Verified` in the adapter lifecycle must be qualified in generated data and prose. Source authority, package publication, component maturity, and identity rights are never inferred from either.

### 11.2 Evidence hierarchy

When sources disagree:

1. behavior of the packaged artifact in a supported environment;
2. executable tests that fail under mutation;
3. source code reachable from the released configuration;
4. architecture decision and specification;
5. user documentation;
6. task checkboxes, screenshots, and marketing copy.

Lower-ranked evidence must be corrected when it conflicts with higher-ranked evidence.

For facts imported from NL Design System or another upstream, prefer the pinned released artifact and its published documentation, then source code and manifests at that pin. Open issues, pull requests, roadmaps, and unreleased main-branch work show possible direction only; they can justify an experiment or tracked decision, never a runtime dependency or a claim that migration has happened. Repository ownership and package publication do not independently prove organisation authority, trademark permission, accessibility, or Nextcloud compatibility.

### 11.3 Change rule

Every roadmap feature must identify:

- user problem;
- product owner;
- architecture owner;
- data owner;
- affected surfaces;
- public/private API use;
- persistence location;
- security and accessibility impact;
- test evidence;
- migration and rollback;
- documentation and support status.

If those fields cannot be answered, the feature is not ready for implementation.

### 11.4 Re-estimation rule

Calendar estimates may be added only after Gate 0. Estimate milestones from measured work and available maintainers. Never turn the optional backlog into a date promise.

## 12. Immediate execution order

The next work should be:

1. Finish the current profile-library change as one reviewable unit: pack
   contract, exact-version state migration, immutable repository, API, UI,
   documentation, tests, and package inventory must agree.
2. Build the release archive from a clean checkout and inspect its complete
   contents, permissions, app-metadata validation, dependency audits, and
   reproducibility. Include an example pack or link to the exact schema used by
   the installed version.
3. Install that archive on a disposable supported Nextcloud instance and prove
   the real administrator path: built-in activation, pack installation without
   activation, multi-version selection, visible CSS switching, light/dark
   preview, rollback, blocked active/rollback removal, inactive removal,
   restart, disable/enable, and upgrade preservation.
4. Add browser screenshots or visual-regression evidence for the library and
   for at least login, Files, and administration settings. Repeat the relevant
   checks in default, dark, and high-contrast preferences; a colour-card preview
   is not evidence that a Nextcloud surface is compatible.
5. Record profile-pack schema evolution rules and a migration policy before a
   v2 field is accepted. Keep assets, fonts, raw CSS, DTCG graphs, and Theming
   settings out of v1.
6. Audit all current catalogue entries as source claims rather than supported
   municipalities, then select one source-authorized NL Design System pilot and
   one independent/local pilot. Use synthetic fixtures when authority or
   identity rights cannot be established.
7. Freeze the richer NL Design System source contract: artifact roles,
   canonical token format, Basis/Common family adapter, explicit Start
   inheritance, pinned toolchain, local descriptors, and upstream-index policy.
8. Implement the build-time source trace and reproducible compiler without
   changing the runtime profile-pack boundary or requiring Node/network access
   inside Nextcloud.
9. Build the first real surface and frozen-Baseline matrix, with a projected
   output budget and explicit unsupported cells.
10. Freeze the core-Theming field/capability matrix and continue the upstream
    API design discussion. Only after the vertical slice and matrix are proven
    should the private core-Theming adapter become an opt-in implementation.

The first implementation milestone is not “finish the endpoints.” It is “prove one safe, reversible translation from a versioned profile into a documented Nextcloud surface.”

## 13. Research basis

This roadmap relies on the following authoritative boundaries:

The repository-level NL Design System observations are pinned to this 2026-08-08 inspection, rather than an unqualified `main` branch:

| Repository | Inspected commit | Relevance |
|---|---|---|
| `nl-design-system/themes` | `4de3931bfd136a45a7898293ac9ef439da89d90e` | Theme packages, Start/Basis values, generated outputs, migration issues |
| `nl-design-system/theme-wizard` | `7c5543fc6198d5416c2d9b41201280cad51af8f3` | Schema, lint, format upgrade, validation limits, proposed generation API |
| `nl-design-system/documentatie` | `349ba766dbf6faeda2dae07743278789f9c2e72a` | Product direction, tokens, Start, Estafettemodel, and Baseline |
| `nl-design-system/index` | `7659c9ef9ed1e39ad76669cc9ee2408cc692f763` | Current machine-readable inventory capability |
| `nl-design-system/architectuur` | `e8b852d0b676af21ec9ea0e95798cf8a443fb3bd` | Common-token and theme-interchange goals |
| `nl-design-system/basis` | `224e0e3236862fb7f398df8d861f77a561bea74a` | Basis component implementation context |
| `nl-design-system/candidate` | `42fd8f85f4162f06162da8f53d94a37c097571ec` | Candidate component packages and maturity scope |
| `nl-design-system/hall-of-fame` | `75dbc40ae402385d681ad5015ffc59ece4517c30` | Current Hall of Fame component-package scope |

- [Nextcloud PHP public API](https://docs.nextcloud.com/server/stable/developer_manual/digging_deeper/api.html): the public PHP API is in the OCP namespace.
- [Nextcloud public `OCP\Defaults`](https://github.com/nextcloud/server/blob/628d14cc0d08a35972a0dfe260f24d3570a2acc9/lib/public/Defaults.php): the released PHP facade reads a subset of effective defaults and has no mutation contract.
- [Draft `OCP\Theming\IDefaults` refactor](https://github.com/nextcloud/server/pull/55384): the open upstream direction is relevant to the read boundary but is not a released dependency or a mutator.
- [Nextcloud theming support for apps](https://docs.nextcloud.com/server/stable/developer_manual/basics/front-end/theming.html): apps inherit supported branding through Nextcloud variables, JavaScript data, and generated icons.
- [Nextcloud CSS guidance](https://docs.nextcloud.com/server/stable/developer_manual/html_css_design/css.html): apps should use Nextcloud variables so Theming and Accessibility can adjust them.
- [Nextcloud built-in Theming scope](https://docs.nextcloud.com/server/stable/admin_manual/configuration_server/theming.html): the core app owns instance identity, colours, logos, login imagery, favicon, and related branding.
- [Nextcloud `theming:config` contract](https://docs.nextcloud.com/server/stable/admin_manual/occ_system.html#theming-config): the documented command defines the supported text, policy, image, and reset surface used for the manual fallback.
- [Nextcloud core Theming command implementation](https://github.com/nextcloud/server/blob/628d14cc0d08a35972a0dfe260f24d3570a2acc9/apps/theming/lib/Command/UpdateConfig.php): image writes include MIME-state registration and core-owned cache invalidation through `ThemingDefaults`.
- [Nextcloud app-path configuration](https://docs.nextcloud.com/server/stable/developer_manual/app_development/intro.html): custom app paths may be read-only.
- [Nextcloud maintained versions](https://docs.nextcloud.com/): maintained release scope must follow the live support window.
- [NL Design System product direction](https://www.nldesignsystem.nl/): NL Design System presents itself as a collection of design systems collaborating toward reusable Hall of Fame building blocks, not a finished universal component library or identity catalogue.
- [NL Design System design-token levels](https://nldesignsystem.nl/handboek/huisstijl/design-tokens/): platform-independent JSON links distinct Brand, Common, and Component levels and can represent modes such as light, dark, and high contrast.
- [NL Design System Basis tokens](https://nldesignsystem.nl/handboek/huisstijl/basis-tokens/): the broad `basis.*` Common vocabulary is the intended interchange seam for themes and components.
- [NL Design System Start theme](https://nldesignsystem.nl/handboek/huisstijl/themas/start-thema/): Start provides neutral accessible values intended for incremental organisation customization, including dark mode; this roadmap therefore treats it as an explicit base and fixture, not an invisible fallback.
- [NL Design System reusable CSS guidance](https://nldesignsystem.nl/handboek/developer/herbruikbare-css/): reusable CSS needs deliberate scoping and separation.
- [NL Design System token conventions](https://nldesignsystem.nl/handboek/developer/design-token-conventie/): organisation/component prefixes and Design Tokens Format semantics are source meaning, not a Nextcloud mapping algorithm.
- [Design Tokens Format Module 2025.10](https://www.designtokens.org/tr/2025.10/format/): the current canonical JSON target emitted by the NL Design System lint package; it is pinned rather than treated as an evergreen runtime contract.
- [NL Design System Theme Wizard](https://github.com/nl-design-system/theme-wizard): active tooling for theme creation and validation is a build-time integration opportunity.
- [Design-tokens schema package](https://github.com/nl-design-system/theme-wizard/tree/main/packages/design-tokens-schema) and [lint package](https://github.com/nl-design-system/theme-wizard/tree/main/packages/design-tokens-lint): the current tools upgrade/validate inputs and merge token sets, but remain versioned dependencies rather than the runtime contract.
- [Pinned schema contrast exception](https://github.com/nl-design-system/theme-wizard/blob/7c5543fc6198d5416c2d9b41201280cad51af8f3/packages/design-tokens-schema/src/theme.ts#L162): current upstream source skips a known class of checks because Start and Mooi & Anders do not comply, requiring independent projected-pair testing here.
- [NL Design System themes repository](https://github.com/nl-design-system/themes): theme packages and generated outputs are useful pinned source artifacts maintained independently of this adapter.
- [Theme-status cleanup issue](https://github.com/nl-design-system/themes/issues/1318) and [Basis-package removal issue](https://github.com/nl-design-system/themes/issues/1137): current package and theme topology is under migration; these open issues inform the volatility boundary but do not prove a completed direction.
- [Generated token-payload issue](https://github.com/nl-design-system/themes/issues/1351): upstream is investigating output minimization after layered themes produced more than 2,200 CSS variables.
- [Proposed `createTheme()` API](https://github.com/nl-design-system/theme-wizard/issues/866): generating an accessible full theme from a small Basis input is a promising open proposal, not an API this app can depend on yet.
- [NL Design System index repository](https://github.com/nl-design-system/index): an upstream machine-readable theme index is directionally useful, while the current implementation remains component-progress oriented.
- [NL Design System Estafettemodel](https://nldesignsystem.nl/handboek/estafettemodel/) and [quality Definition of Done](https://nldesignsystem.nl/project/kwaliteitsaanpak/definition-of-done/): Candidate and Hall of Fame evidence is artifact-specific and should not be copied onto an organisation profile.
- [NL Design System Baseline](https://nldesignsystem.nl/baseline/): the dated browser and assistive-technology matrix is test-planning evidence that changes over time and must be frozen per release.
- Source-authority counterexamples in the official GitHub organisation: [Rijkshuisstijl Community](https://github.com/nl-design-system/rijkshuisstijl-community) says it is unofficial; [Utrecht](https://github.com/nl-design-system/utrecht), [Den Haag](https://github.com/nl-design-system/denhaag), and [Rotterdam](https://github.com/nl-design-system/rotterdam) say work in progress; [Amsterdam](https://github.com/nl-design-system/amsterdam) is archived; and [Amsterdam Experiment](https://github.com/nl-design-system/amsterdam-experiment) is archived and explicitly unendorsed.
- [Documentation issue on the collection model](https://github.com/nl-design-system/documentatie/issues/4232): projects currently assemble components from different organisations because a complete Hall of Fame set does not yet exist.
