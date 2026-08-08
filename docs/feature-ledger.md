# Feature and runtime ledger

`Implemented` means code exists. `Verified` is reserved for the named evidence level; local unit/static checks do not establish live Nextcloud compatibility. Nothing in this pre-release ledger is `released`.

| Area | Feature | Lifecycle | Runtime reachability | Evidence | Notes |
|---|---|---|---|---|---|
| Profile | Manifest inventory and safe stylesheet resolution | implemented | reachable | automated local | 40 bounded records/files; only 8 ready projections are exposed |
| Profile | Publish or deactivate active profile | implemented | reachable | automated local | Nullable app-scoped canonical state and deterministic initial revision |
| Profile | Locked optimistic concurrency | implemented | reachable | automated local | Strict revision plus Nextcloud exclusive lock; the public app-config cache is cleared before the locked read; stale-cache, stale-revision, cache-failure, and lock-failure paths are unit-tested |
| Profile | One-step rollback | implemented | reachable | automated local | Revision checked; controller refuses a removed target |
| Profile | Bounded transition history | implemented | reachable | automated local | Ten entries; auxiliary write is not canonical state |
| Runtime | Three-layer stylesheet plan | implemented | reachable | automated local | Fonts, ready profile, bounded theme mapping; exact order unit-tested |
| Runtime | Accessibility precedence | implemented | reachable | static/local | Explicit OpenDyslexic and high-contrast preferences are excluded from projection; browser evidence remains required |
| Runtime | Native and invalid-profile containment | implemented | reachable | automated local | Fresh install and explicit deactivation emit no profile CSS; an unavailable stored profile requires explicit admin replacement; corrupt or incomplete canonical state cannot reactivate a stale legacy mirror or rollback fragment |
| Runtime | Package immutability boundary | implemented | reachable | automated static | Boundary script rejects direct runtime file mutation in `lib/` |
| Security | Admin action authorization | implemented | reachable | automated local | PHP attributes on every routed controller action |
| Admin | Colour preview | implemented | reachable | syntax/manual | Uses only validated primary-colour metadata; no browser automation yet |
| Admin | Login-footer CSS toggle | removed | not reachable | code review | Retired because it hid the instance identity link as well as the slogan; legacy config is ignored |
| Admin | Menu-label CSS toggle | removed | not reachable | code review/NC33 measurement | Retired because its structural selector lacked supported-major evidence; legacy config is ignored |
| Theming | Read-only manual hand-off | implemented | reachable | unit/manual | Allowlisted values; never executes core-Theming writes |
| Theming | Private compatibility structural probe | experimental | not registered | static boundary | Read-only method-presence probe; no private service resolution or mutation methods |
| Theming | Public mutation adapter toward future `OCP\Theming` | proposed | not reachable | research | Add only when a released public lifecycle exists |
| Customization | Token editor | deferred | not reachable | architecture | Removed unsafe package-directory persistence |
| Customization | Import/export | deferred | not reachable | architecture | Requires a typed, versioned data format |
| Customization | Apply-token dialog | deferred | not reachable | architecture | Depends on safe override semantics |
| Supply chain | Bundled Fira Sans | implemented | reachable | automated build | Strict eight-file build plus OFL notice; runtime has no font CDN request |
| Supply chain | Amsterdam icon/logo bundle | removed | not reachable | package inspection | Unused assets had a package-specific proprietary notice |
| Evidence | Profile provenance and identity rights | proposed | not applicable | absent/partial | Required per profile before release claims |
| Evidence | Nextcloud major/app surface matrix | proposed | not applicable | absent | Required before compatibility claims |
| Evidence | Rendered accessibility matrix | proposed | not applicable | absent | Required before WCAG/conformance claims |

## Promotion rule

A feature can move to `released` only when code, runtime reachability, rollback/failure behavior, exact-version integration evidence, documentation, and packaging evidence all agree.
