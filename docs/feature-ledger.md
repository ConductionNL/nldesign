# Feature and runtime ledger

`Implemented` means code exists. `Verified` is reserved for the named evidence level; local unit/static checks do not establish live Nextcloud compatibility. Nothing in this pre-release ledger is `released`.

| Area | Feature | Lifecycle | Runtime reachability | Evidence | Notes |
|---|---|---|---|---|---|
| Profile | Composite exact-version catalogue | implemented | reachable | automated local + live NC34 candidate | 40 bounded packaged records/files with 8 ready projections; two installed versions of one local profile were grouped and read from app data on Nextcloud 34.0.2 |
| Profile | Closed profile-pack installation | implemented | reachable | automated local + live NC34 candidate | `nldesign-profile-pack/v1`; bounded metadata/tokens, contrast gate, generated CSS, immutable `id` + `version`, no arbitrary CSS/URLs/assets/settings |
| Profile | Publish or deactivate exact profile version | implemented | reachable | automated local + live NC34 candidate | Nullable app-scoped canonical state, exact version, and deterministic initial revision |
| Profile | Locked optimistic concurrency | implemented | reachable | automated local | Strict revision plus Nextcloud exclusive lock; the public app-config cache is cleared before the locked read; stale-cache, stale-revision, cache-failure, and lock-failure paths are unit-tested |
| Profile | One-step rollback | implemented | reachable | automated local + live NC34 candidate | Revision checked and retains exact prior version; live browser flow confirmed the rollback target cannot be removed |
| Profile | Bounded transition history | implemented | reachable | automated local | Ten exact-version entries; auxiliary write is not canonical state |
| Runtime | Three-layer stylesheet plan | implemented | reachable | automated local + source audit NC32–34 + live NC34 Files | Fonts, exact ready profile, and one range-gated shared core mapping; green and blue installed versions produced distinct computed variables on Files |
| Runtime | Installed stylesheet response | implemented | reachable | automated local + live NC34 Files | Public immutable route requires exact id, version, and SHA-256; both exact-version digest URLs returned generated CSS successfully |
| Runtime | Accessibility precedence | implemented | reachable | static/local + source audit NC32–34 | Projection composes with Nextcloud's theme-manager body state; explicit OpenDyslexic and high-contrast preferences are excluded; browser evidence remains required |
| Runtime | Native and invalid-profile containment | implemented | reachable | automated local | Fresh install and explicit deactivation emit no profile CSS; an unavailable stored profile requires explicit admin replacement; corrupt or incomplete canonical state cannot reactivate a stale legacy mirror or rollback fragment |
| Runtime | Package immutability boundary | implemented | reachable | automated static | Boundary script rejects direct runtime file mutation in `lib/` |
| Security | Admin action authorization | implemented | reachable | automated local | PHP attributes on every routed controller action |
| Admin | Versioned profile library | implemented | reachable | local + live browser NC34 candidate | Search, grouped versions, built-in/installed provenance, light/dark colour preview, installer dialog, active/rollback guards, and removal were exercised end to end |
| Admin | Login-footer CSS toggle | removed | not reachable | code review | Retired because it hid the instance identity link as well as the slogan; legacy config is ignored |
| Admin | Menu-label CSS toggle | removed | not reachable | code review/NC33 measurement | Retired because its structural selector lacked supported-major evidence; legacy config is ignored |
| Theming | Read-only manual hand-off | implemented | reachable | unit/manual | Allowlisted values; never executes core-Theming writes |
| Theming | Private compatibility structural probe | experimental | not registered | static boundary | Read-only method-presence probe; no private service resolution or mutation methods |
| Theming | Public mutation adapter toward future `OCP\Theming` | proposed | not reachable | research | Add only when a released public lifecycle exists |
| Customization | Token editor | deferred | not reachable | architecture | Removed unsafe package-directory persistence |
| Customization | General source/configuration import and export | deferred | not reachable | architecture | The narrow projected-profile installer is not a DTCG source importer or instance-configuration transfer format |
| Customization | Apply-token dialog | deferred | not reachable | architecture | Depends on safe override semantics |
| Supply chain | Bundled Fira Sans | implemented | reachable | automated build | Strict eight-file build plus OFL notice; runtime has no font CDN request |
| Supply chain | Amsterdam icon/logo bundle | removed | not reachable | package inspection | Unused assets had a package-specific proprietary notice |
| Evidence | Profile provenance and identity rights | proposed | not applicable | absent/partial | Required per profile before release claims |
| Evidence | Nextcloud major/app surface matrix | proposed | not applicable | partial | One Nextcloud 34.0.2 admin/Files light-mode candidate flow exists; packaged install/upgrade, NC32/33, other modes, and other apps remain unverified |
| Evidence | Rendered accessibility matrix | proposed | not applicable | absent | Required before WCAG/conformance claims |

## Promotion rule

A feature can move to `released` only when code, runtime reachability, rollback/failure behavior, exact-version integration evidence, documentation, and packaging evidence all agree.
