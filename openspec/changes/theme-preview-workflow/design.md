# Design — theme-preview-workflow

## Storage: IConfig user values, not app values, not a DB table

Per-user preview state must be (a) invisible to every other user, (b) survivable across page
loads within a session, (c) cheap to read on every render, (d) possible without DB tables (app
architecture constraint). `IConfig::getUserValue()/setUserValue()` is the only NC primitive
satisfying all four. Keys, under app id `nldesign`:

| user key | value | notes |
|---|---|---|
| `preview_token_set` | token set id (e.g. `amsterdam`, `custom-gemeente-x`) | empty/absent = no preview |
| `preview_expires_at` | unix timestamp as string | set to `time() + 86400` (24h) at start |

## Effective token set resolution (injection-layer contract)

Pseudocode inside the injection layer, after the per-app exclusion guard and before the
design-system resolution:

```
effective = appValue('token_set', 'nextcloud')
try:
    uid = userSession.getUser()?.getUID()
    if uid !== null
       and groupManager.isAdmin(uid)                     # demotion defence
       and userValue(uid,'preview_token_set') !== ''
       and (int) userValue(uid,'preview_expires_at') > time()
       and tokenSetService.isValidTokenSet(previewId):   # deleted-set defence
        effective = previewId
        previewActive = true
catch Throwable:
    pass                                                  # CLI/occ/cron/login page: active set
```

Decisions:

- **Admin re-check at render time.** The endpoints are already admin-only, but a user could be
  demoted while holding a preview value. The `isAdmin()` lookup happens *only* when a
  `preview_token_set` value exists, so the common path (every ordinary user, every request) does
  zero extra work beyond one user-value read that NC serves from its per-user config cache.
- **Lazy expiry, no background job.** The app has no background jobs and this feature does not
  justify one. Expired or invalid preview values are simply ignored at read time;
  `ThemePreviewService::getActivePreview()` MAY delete them opportunistically when it observes
  expiry outside the boot path (i.e. from the controller/settings panel), but the boot path
  itself never writes.
- **Only `token_set` is substituted.** Custom overrides, hide-slogan, menu-labels and the per-app
  exclusion list keep their active values during preview. Previewing those too would multiply
  state combinations for marginal value; the gemeente flow being served is "trial the huisstijl
  token set".
- **Failure posture mirrors the existing per-app guard**: any Throwable in preview resolution
  falls back to the active set (presentation, never security; boot must not crash).

## Banner delivery

- Loaded only when `previewActive` — zero footprint for everyone else.
- State via `IInitialState::provideInitialState('preview', {tokenSet, name, expiresAt})`, read in
  `js/preview-banner.js` with `OCP.InitialState.loadState('nldesign', 'preview')` — complies with
  the initial-state hydra gate (no DOM data-attribute transport).
- The banner is fixed-position, `role="status"`, keyboard-operable buttons, colors from NC CSS
  variables (no hardcoded colors). On apps in the per-app exclusion list, injection is skipped
  entirely (the existing guard runs first), so neither the preview styling nor the banner appears
  there — a deliberate, acceptable consequence: excluded apps render stock in every mode.
- **Publish from the banner routes through the settings panel** rather than calling the publish
  endpoint directly: the instance-wide apply dialog (per-token diff) and the theming-sync dialog
  are settings-page JS and MUST keep running before anything goes instance-wide (their specs are
  unchanged). The banner's Publish is therefore a link to
  `/settings/admin/theming#nldesign-settings`; `js/admin.js` detects the active preview on load
  and opens the existing apply-dialog flow for the previewed set, whose confirmation calls
  `POST /settings/preview/publish` instead of `POST /settings/tokenset`.

## Interaction with render-event-injection (same wave)

The spec deliberately binds the preview check to "the CSS injection layer", defined as: the code
path that resolves `token_set` and emits `Util::addStyle()` calls. Today that is
`Application::injectThemeCSS()`; if change `render-event-injection` lands (before or after this
one), the resolution moves into its `BeforeTemplateRenderedEvent` listener verbatim. Neither
change depends on the other; whichever lands second carries the merge.
