# Teams-capable side rail — specification

**Status:** specification, not yet implemented.
**Constraint set by the product owner:** *"We like to be as version independent as possible."*
**Requirement carried over:** *"We need a cluster menu for the socials. I don't care how."*

This specifies a left app rail built in-house, after
[`side_menu`](https://apps.nextcloud.com/apps/side_menu) was found unable to
provide a persistent rail *with* categories — `MenuContainer.vue:115` is a
precedence chain offering `SimpleSideMenu` (rail, no categories) **or**
`SideMenuWithCategories` (categories, no rail) **or** `TopWideMenu`, never the
combination.

---

## 0. Correction to earlier research

Two claims in the prior round were wrong, and the spec would inherit both if
they were not retracted here.

**The measured app-menu DOM was not Nextcloud's.** The measurement

```
appMenuEntries: 9
entryMarkup: <li data-app-id="activity" class="app-menu-entry" style="order: -1;">…
```

was taken on the live instance with `side_menu` 6.0.1 enabled, and it is
side_menu's markup. Counted inside the running container:

| token | core `dist/` | `custom_apps/side_menu/` |
| --- | --- | --- |
| `data-app-id` | **0** | 3 |
| `app-menu-entry` | **0** | 97 |
| `app-menu__grid` | 18 | 0 |
| `app-menu__waffle` | 11 | 0 |

So the conclusion drawn from it — *"key off `data-app-id`, which is stable"* —
is void: on NC34 the attribute is absent from core entirely and is side_menu's
own. The narrow claim is what the count supports. side_menu very likely forked
its markup from a core version where `.app-menu-entry` did exist (§1 dates it to
NC28), so the attribute may have core ancestry on older releases — which is a
reason to key off nothing in that family, not a reason to trust it on 28–33.

**`docs/teams-look.md` §6 describes a plan against markup that no longer
exists, using files that no longer exist.** It proposes reflowing
`.app-menu-entry` to icon-above-label and cites `css/show-menu-labels.css` as
*"half a Teams rail, already written."* On NC34 that CSS would target nothing in
core — and the question is moot, because `ddf77d1` deleted both
`css/show-menu-labels.css` and `css/element-overrides.css` from this repo.
Neither is in `git ls-files`. §6 is history on both counts.

---

## 1. What changed upstream, and why it helps

**Nextcloud 34 removed the inline horizontal app list.** `core/src/components/AppMenu.vue`
now renders an `NcPopover` whose trigger is a waffle button (`IconDotsGrid`),
with the apps in an `app-menu__grid` inside the popover, plus a separate
`app-menu__current-app` button. There is no row of app entries in the header to
restyle.

That sounds like bad news and is the opposite. The old plan was *"override
core's app menu into a rail"* — permanently fighting markup that core owns and
rewrites. The new position is *"core no longer offers an inline app list; we
supply one."* We render our own markup and never fight a Vue component.

The churn that makes overriding hopeless, measured across five branches:

| | NC28 | NC30–33 | NC34 / master |
| --- | --- | --- | --- |
| container | `.app-menu-main` | `.app-menu__list` | `.app-menu__grid` |
| entry | `.app-menu-entry` | *(AppMenuEntry.vue)* | `AppItem.vue` |
| overflow | `.app-menu-more` | `.app-menu__overflow` | `.app-menu__waffle` |
| label | `.app-menu-entry--label` | — | — |

`AppMenuEntry.vue` exists only in 30–33: absent in 28, absent again in 34. The
**only** class surviving all five is `.app-menu` on the `<nav>` — three renames
in six majors, plus one wholesale restructure.

---

## 2. The stable contract surface

Everything the rail depends on was checked against `stable28`, `stable30`,
`stable33`, `stable34` and `master`.

| Surface | Status across 28→master |
| --- | --- |
| `OCP\INavigationManager` | **additive only.** stable28's `add`, `getAll`, `getActiveEntry`, `setActiveEntry`, `setUnreadCounter` all still present in master; master adds `get`, `getDefaultEntryIds`, `getDefaultEntryIdForUser`, `setDefaultEntryIds` |
| `OCP\AppFramework\Services\IInitialState` | **surface-identical** — `provideInitialState`, `provideLazyInitialState` in both |
| `OCP\Util` | present in all five |
| OCS `GET /ocs/v2.php/core/navigation/apps` | **endpoint** present in all five; the *declaration form* is not — `#[ApiRoute]` is `@since 29.0.0` and the attribute file 404s on stable28, which declares `getAppsNavigation` with `@NoAdminRequired` annotations instead. The route is stable; only the syntax moved |
| `#header`, `#content`, `#nextcloud`, `#skip-actions` | **byte-identical** in `core/templates/layout.user.php` |
| Navigation entry fields | `id`, `name`, `href`, `icon`, `order`, `type`, `app` |

The base template is PHP and stable; the churn is entirely in the Vue layer.
**The rail must live wholly on the left column of that table.**

> **Rule.** No selector in this app may reference a class Nextcloud's Vue
> components emit, and none may reference a `data-v-*` scoped-style hash.
> `nldesign` carried exactly that bet — `.content[data-v-1f87d811]`, in a
> `css/element-overrides.css` that no longer exists — until `ddf77d1` removed
> it. The rule is the lesson kept after the code was deleted, not a live defect.

---

## 3. Architecture

### 3.1 Data — server-side, from the navigation API

```php
// lib/Listener/RailStateListener.php
// The type is passed explicitly even though TYPE_APPS is the default. A
// default that shifts inside the supported range would silently change which
// apps the rail lists — the exact class of drift this spec exists to prevent,
// and invisible if we rely on it. (Checked: the signature is
// `getAll(string $type = self::TYPE_APPS)` byte-identical across stable28,
// stable30, stable33, stable34 and master. Passing it keeps that true by
// construction rather than by continued luck.)
$entries = $this->navigationManager->getAll(INavigationManager::TYPE_APPS);
$this->initialState->provideInitialState('rail', [
    'entries' => $this->groups->assign($entries),
    'groups'  => $this->groups->all(),
    // Server-side, because the alternative is re-deriving it from
    // location.pathname — reintroducing exactly the brittle coupling this
    // whole section exists to avoid. getActiveEntry() is in the stable set.
    'active'  => $this->navigationManager->getActiveEntry(),
]);
```

`getAll()` is the same source core's own menu uses, so the rail lists exactly
the apps the user may see — permissions, admin restrictions and per-user app
enablement are already applied. No DOM scraping, no OCS round trip on page load
(the OCS endpoint remains the fallback for a client-side refresh).

### 3.2 Markup — ours

The rail renders its own DOM under a namespaced root, with no class shared with
core:

```html
<nav id="nldesign-rail" class="nldesign-rail" aria-label="Applications">
  <ul class="nldesign-rail__group" data-group="core">
    <li class="nldesign-rail__entry" data-entry-id="files">
      <a href="/apps/files/" class="nldesign-rail__link">
        <img src="/apps/files/img/app.svg" alt="" class="nldesign-rail__icon">
        <span class="nldesign-rail__label">Files</span>
      </a>
    </li>
  </ul>
  <button class="nldesign-rail__cluster" aria-expanded="false" aria-controls="…">…</button>
</nav>
```

`data-entry-id` carries the navigation entry `id` — **our** attribute, not
core's `data-app-id`, which does not exist.

### 3.3 Mount — the one unavoidable DOM coupling

The rail is injected by `\OCP\Util::addScript` and mounts as the **following
sibling** of `#content`, whose id is byte-stable across all five branches.

```js
const anchor = document.getElementById('content')
if (anchor === null) return       // degrade: core's menu keeps working
anchor.after(rail)
document.documentElement.classList.add('nldesign-rail-active')
```

The class is added on the same line as the mount and nowhere else, so the
offset in §3.4 and the rail itself cannot exist apart.

**Why after, and why a sibling.** DOM order is the accessibility argument:
injecting a `<nav>` of app links *before* `#content` puts every rail entry and
cluster button into the tab order ahead of the main content on every page — a
keyboard regression against core, which ships `#skip-actions` precisely so that
reaching `#content` is one keystroke. Mounting after it keeps the reading order
core designed, and costs no tab stops before the page's actual content.

Sibling rather than child is a containing-block argument, **not** a layout one:
§3.4 positions the rail `fixed`, which takes it out of flow, so `#content`'s
padding would not push it either way. What would break is subtler — a `fixed`
element resolves against the nearest ancestor carrying `transform`, `filter`,
`perspective` or `contain`, not against the viewport. Nextcloud's own components
set those on containers inside `#content` freely, and a rail that silently
re-anchors to a transformed ancestor is exactly the kind of upgrade-triggered
breakage this spec exists to avoid. Staying outside `#content` keeps the
viewport as the containing block.

**`#skip-actions` is read, never written.** It is core's markup, and §2's Rule
applies to it as much as to any Vue class. The rail does not inject a link into
it; mounting after `#content` is what makes one unnecessary, since the rail is
already past the content in tab order. It appears in §2's table as the reason
DOM order matters, not as a surface the app manipulates.

**Degradation is the invariant.** A missing anchor renders nothing and leaves
Nextcloud's own navigation intact — the shell equivalent of the icons app's
"an unmatched fingerprint renders the stock icon." The rail is never the only
way to reach an app: core's waffle menu stays reachable.

### 3.4 Geometry — read, never hardcode

`DefaultTheme.php` on master says `--header-height: 44px`. The live 34.0.2
instance reports **50px**. That six-pixel drift is the entire argument in
miniature: any hardcoded pixel is a latent bug.

```css
/* Our own width lives on :root, not on .nldesign-rail. Custom properties
   inherit to descendants only, and #content is the rail's *sibling* — a value
   declared on the rail is invisible to the rule that has to clear it. */
:root { --nldesign-rail-width: 5rem; }

.nldesign-rail {
  /* The nav needs to be a flex container in its own right. Its children are
     the groups and the cluster button; without this they stack as blocks with
     nothing between them, which is the same mistake as putting `gap` on an
     element whose children are not the things being spaced. */
  display: flex;
  flex-direction: column;
  gap: var(--default-grid-baseline, 4px);

  position: fixed;                    /* inset-* is inert without this */
  inset-block-start: var(--header-height, 50px);
  inset-block-end: 0;
  inset-inline-start: 0;
  inline-size: var(--nldesign-rail-width);
  border-end-end-radius: var(--body-container-radius, 16px);
}

/* gap belongs on the flex container whose children are the entries, not on the
   <nav>, whose children are the groups. */
.nldesign-rail__group {
  display: flex;
  flex-direction: column;
  gap: var(--default-grid-baseline, 4px);
}

/* Gated on a class the script sets only after a successful mount. The
   stylesheet ships on every page via addStyle, so an ungated rule would indent
   content by 5rem wherever the rail did not render: a missing anchor, the
   side_menu deferral in §7, or the login and public-share pages. A dead gutter
   next to no rail is exactly the visible breakage §3.3 promises never happens. */
.nldesign-rail-active #content { padding-inline-start: var(--nldesign-rail-width); }
```

Every core value is read through `var(--x, fallback)` — never assigned, for the
same reason the icons app must not assign `--nldesign-icon-*`: assigning turns
the outcome into a source-order race between two apps that do not control their
relative order. Logical properties (`inline-size`, `inset-block-start`) rather
than physical ones, because Nextcloud ships RTL locales and `isRTL` is already
threaded through `AppMenu.vue`.

---

## 4. The cluster menu

`getAll()` returns no category field, so grouping is ours to define and
persist. This is the spec's one area of real design latitude.

**Model.** An admin-editable map of navigation entry `id` → group id, stored in
appconfig as JSON. Groups have an id, a label, an icon and an order.

An entry falls back to the default group whenever its group **cannot be
resolved** — not merely when the entry is absent from the map. The two differ in
the case that actually happens: an admin assigns `mastodon` to `social`, then
deletes the `social` group and leaves the assignment behind. Keyed on absence,
`assign['mastodon']` is set so the fallback never fires and the app renders
under no group at all. Keyed on resolution, it lands in the default. So **an app
always appears somewhere** — the failure mode that matters, since a rail that
hides an app is worse than one that groups it oddly.

```json
{
  "groups": [
    {"id": "core",    "label": "Work",    "icon": "briefcase", "order": 10},
    {"id": "social",  "label": "Social",  "icon": "users",     "order": 20}
  ],
  "assign": {"files": "core", "spreed": "core", "social": "social", "mastodon": "social"},
  "default": "core"
}
```

This satisfies *"a cluster menu for the socials"* without hardcoding which apps
are social — the administrator decides, and the shipped default merely
pre-populates the obvious cases.

**Interaction.** A group renders as a rail button that opens a flyout listing
its entries. Keyboard: arrow keys move within the rail, `Enter`/`Space` opens a
group, `Escape` closes and restores focus to the trigger, matching the roving
`tabindex` pattern core's own `app-menu__grid` uses.

---

## 5. Responsive behaviour

`--breakpoint-mobile: 1024px`. Below it the rail collapses to a bottom bar of
group buttons only — a 5rem left column on a phone costs more than it gives.
The collapse must also reset `padding-inline-start` on `#content`, or the phone
keeps a 5rem left gutter *and* gains a bottom bar.

**The one admitted exception to §3.4.** A media feature value cannot read a
custom property — `@media (max-width: var(--breakpoint-mobile))` is invalid and
the entire at-rule is discarded, so the rail would simply never collapse. The
breakpoint is therefore a literal, written **once**, in one place, with a
comment naming it as the exception:

```css
/* Literal by necessity: media features cannot read custom properties.
   Mirrors --breakpoint-mobile. The var() fallbacks in §3.4 are also literals,
   but they only apply when the variable is missing; this one applies always,
   which makes it the single value that can silently disagree with the theme. */
@media (max-width: 1024px) {
  .nldesign-rail-active #content { padding-inline-start: 0; }
}
```

The escape hatches — a container query, or a JS-set class — are worth taking if
the app ever needs a second breakpoint. One is not worth the machinery. This is
written from scratch; core's horizontal overflow logic does not translate to a
vertical rail and is not reused.

---

## 6. Composition with `nldesign-icons`

The navigation entry `icon` field is a URL to an app SVG, so the rail renders
`<img src="/apps/files/img/app.svg">` — precisely the population
`nldesign-icons` already substitutes through its `img[src$="…"]` rules. **The
mechanism composes with no extra work:** the rail must render app icons as
`<img>` with the unmodified `icon` value, and must not inline or rewrite them.

**The coverage does not, yet, and the rail is what makes that visible.** The
packs substitute eleven apps — `activity`, `dashboard`, `dav`, `files`,
`notifications`, `office`, `photos`, `privacy`, `settings`, `theming`,
`workflowengine`. Absent are `spreed`, `calendar`, `contacts`, `mail`, and the
social apps §4 is built around. Today those render Nextcloud's own icons, so a
rail's first run shows a visibly mixed set rather than one coherent family —
which matters more in a rail, where the icons sit in a single column and get
compared, than in a header where they are scattered.

This is a coverage gap, not an architecture gap: each missing app needs one
line in `alias-img-*.json`. Extending that table to whatever the instance
actually has installed is a prerequisite for shipping the rail, and belongs to
`nldesign-icons` rather than here.

---

## 7. Coexistence with `side_menu`

`side_menu` 6.0.1 is **enabled on the live instance right now** and renders its
own menu. Running both produces two rails, which is a visible bug rather than a
cosmetic one.

The spec's intended end state is that this app **replaces** side_menu. That is a
production change and needs the product owner's decision, not a spec footnote —
see §9. Until then the app must detect `side_menu` as enabled and refuse to
mount, logging why. Detect-and-defer is the safe default because it fails toward
the working status quo.

---

## 8. Scope and repository

The scope boundary ratified by the product owner on 2026-08-08 — proposed as a
non-goal in [`roadmap.md`](roadmap.md) §2.3 — limits `nldesign` to design tokens
and instance branding, and puts app-menu/rail layout, icon substitution and
component restyling outside it. `nldesign-icons` took the icon half. The rail is
therefore **a third app**.

Proposed: `nldesign-shell`, scaffolded from `nldesign-icons`, which already has
the CI, release pipeline, gate suite and hexagonal layout to copy. Broadening
`nldesign-icons` into a shell app would contradict the ratified boundary and is
**not** proposed here.

---

## 9. Decisions needed before implementation

1. **side_menu** — replace it (uninstall, migrate its config) or ship the rail
   alongside with detect-and-defer? Affects a live instance.
2. **Repository** — confirm `nldesign-shell` as a third app, per §8.
3. **Declared version range** — `nldesign-icons` learned that a support range
   is a promise that must be backed by evidence. The stable-surface table in §2
   covers 28→master, so a wide range is defensible here in a way it was not for
   fingerprints.

   **But the carried static analysis only enforces it if the OCP pin equals the
   declared floor.** `nldesign-icons` pins `nextcloud/ocp: ^33.0` against a
   declared floor of 33 — consistent, and the consistency is the entire
   mechanism, since Psalm and PHPStan type-check against exactly one OCP major.
   (An earlier review draft reported the pin as `^32.0`; the manifest says
   `^33.0`.) The rail is where this stops being automatic: if it declares 28+,
   four methods §2's own table advertises — `get`, `getDefaultEntryIds`,
   `getDefaultEntryIdForUser`, `setDefaultEntryIds`, all `@since 31.0.0` —
   would pass every gate locally and fatal at runtime on a declared-supported
   NC28. A floor below the pinned OCP needs the lowest supported major in the
   CI matrix, or an explicit `@since` lint; without one the gate is decorative.
   The safe subset is stable28's five methods, which is all §3.1 uses — and
   dependabot must not bump the pin past the floor, which the icons repo now
   enforces with an ignore rule.

## 10. Gates

Carried from `nldesign-icons`, which enforces these in CI: phpcs, phpmd,
Psalm, PHPStan, PHPUnit with a 75% statement floor, and stylelint.

Two corrections to that inheritance, both of which have to be built rather than
copied:

- **`ncuictl.py lint` is not one of them.** It lives in `~/projects/infra`, not
  in the app, and no workflow step runs it — `nldesign-icons`'s architecture doc
  names it as a guardrail it does not actually wire up. Theme-case coverage is
  run by hand today. If the rail depends on it, it needs a real CI step.
- **stylelint covers `css/admin.css` only.** The compiled pack stylesheets are
  not linted at all. Copying that config verbatim would leave the rail's own
  stylesheet — the file most of §3.4 is about — uninspected, and both additions
  below would silently check nothing.

Two additions specific to the rail:

- **No churning selector.** A validator fails the build if any stylesheet or
  script references `app-menu-entry`, `app-menu__list`, `app-menu__grid`,
  `app-menu__waffle`, `app-menu-main`, `data-app-id`, or a `data-v-` hash.
- **No assigned core token.** The same check `nldesign-icons` gained: fail on
  assignment of any custom property the app does not own.

Both are cheap, offline, and fail on a deliberately broken input rather than
only passing on a good one.
