# Menu Labels Technical Design

## Overview

The Show Menu Labels feature replaces icon-based navigation in the Nextcloud header with text labels. It is entirely CSS-driven: a single stylesheet (`show-menu-labels.css`) is conditionally injected at boot time based on an `IConfig` toggle.

---

## Configuration Storage

### Key

| Aspect | Value |
|--------|-------|
| App ID | `nldesign` |
| Config key | `show_menu_labels` |
| Enabled value | `'1'` (string) |
| Disabled value | `'0'` (string) |
| Default | `'0'` (disabled) |

### Read Pattern

```php
$showMenuLabels = $config->getAppValue(self::APP_ID, 'show_menu_labels', '0') === '1';
```

The value is read as a PHP `bool` via strict string comparison. The default `'0'` ensures the feature is opt-in.

### Write Pattern

```php
$menuLabelValue = '0';
if ($showMenuLabels === true) {
    $menuLabelValue = '1';
}
$this->config->setAppValue(Application::APP_ID, 'show_menu_labels', $menuLabelValue);
```

The boolean parameter from the HTTP request is converted to a `'0'`/`'1'` string before storage, matching Nextcloud's `IConfig` convention.

---

## API Endpoint

### Route

```
POST /apps/nldesign/settings/menulabels
```

Registered in `appinfo/routes.php`:

```php
['name' => 'settings#setMenuLabelsSetting', 'url' => '/settings/menulabels', 'verb' => 'POST'],
```

### Controller Method

`SettingsController::setMenuLabelsSetting(bool $showMenuLabels)` in `lib/Controller/SettingsController.php`.

- Annotation: `@AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)` — restricts access to Nextcloud admins.
- Input: JSON body `{ "showMenuLabels": true|false }`
- Output: `JSONResponse` with `{ "status": "ok", "showMenuLabels": <bool> }`

### Frontend Trigger

In `js/admin.js`, the checkbox with id `nldesign-show-menu-labels` fires `saveMenuLabelsSetting()` on change:

```js
var showMenuLabelsCheckbox = document.getElementById('nldesign-show-menu-labels');
if (showMenuLabelsCheckbox) {
    showMenuLabelsCheckbox.addEventListener('change', function() {
        var showMenuLabels = this.checked;
        saveMenuLabelsSetting(showMenuLabels);
    });
}
```

`saveMenuLabelsSetting()` posts to `OC.generateUrl('/apps/nldesign/settings/menulabels')` with `Content-Type: application/json` and the Nextcloud CSRF request token.

---

## Admin Settings UI

### Settings Class

`lib/Settings/Admin.php` reads `show_menu_labels` from `IConfig` and passes it as `$showMenuLabels` (bool) to the template.

### Template

`templates/settings/admin.php` renders a checkbox bound to id `nldesign-show-menu-labels`. The `checked` state is set from the template variable:

```php
<input type="checkbox"
       name="nldesign-show-menu-labels"
       id="nldesign-show-menu-labels"
       class="checkbox"
       <?php if ($_['showMenuLabels']): ?>checked<?php endif; ?>>
<label for="nldesign-show-menu-labels">
    <?php p($l->t('Show text labels in app menu (hide icons)')); ?>
</label>
```

The settings section appears under the Nextcloud "Theming" admin panel (`getSection()` returns `'theming'`, priority `50`).

---

## Conditional CSS Loading

### Boot Sequence

`Application::injectThemeCSS()` is called from `boot()` during the Nextcloud application bootstrap. It reads the config flag and conditionally registers the stylesheet as the 8th CSS layer, after all core theme files:

```
1. fonts
2. defaults
3. tokens/{tokenSet}
4. utrecht-bridge
5. theme
6. overrides
7. element-overrides
8. hide-slogan          (conditional — only if hide_slogan === '1')
9. show-menu-labels     (conditional — only if show_menu_labels === '1')
```

Registration call:

```php
if ($showMenuLabels === true) {
    \OCP\Util::addStyle(appName: self::APP_ID, styleName: 'show-menu-labels');
}
```

No stylesheet is injected when the feature is disabled — there is no default/empty file loaded.

---

## CSS Implementation

File: `css/show-menu-labels.css`

### Icon Hiding

Targets both the legacy `.app-menu-icon` and the current `.app-menu-entry__icon` selectors to handle all Nextcloud versions:

```css
#header nav.app-menu .app-menu-icon,
#header nav.app-menu .app-menu-entry__icon {
    display: none !important;
    visibility: hidden !important;
}
```

Both `display: none` and `visibility: hidden` are applied for defence-in-depth against Nextcloud's own `!important` rules that may override one or the other.

### Label Visibility

Labels are hidden by default in Nextcloud (positioned off-screen or clipped). The CSS overrides all positioning, transform, and visibility properties:

```css
#header nav.app-menu .app-menu-entry__label {
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
    font-size: 14px !important;
    font-weight: 400 !important;
    padding: 0 8px !important;
    line-height: 1.4 !important;
    white-space: nowrap !important;
    text-align: center !important;
    vertical-align: middle !important;
    position: static !important;
    top: auto !important;
    bottom: auto !important;
    left: auto !important;
    right: auto !important;
    transform: none !important;
    max-width: none !important;
}
```

The `position: static` + `transform: none` overrides are necessary because Nextcloud's `AppMenuEntry.vue` renders labels with absolute positioning and transforms to place them below icons, which this feature must undo.

### Active Item Typography

Bold weight distinguishes the current app:

```css
#header nav.app-menu .app-menu-entry--active .app-menu-entry__label {
    font-weight: 600 !important;
}
```

### Active Indicator Suppression

Nextcloud's `AppMenuEntry.vue` renders a black dot (via `::before` pseudo-element) below the active icon. With icons hidden and labels shown the dot appears detached and must be removed:

```css
#header nav.app-menu .app-menu-entry--active::before {
    background-color: transparent !important;
    opacity: 0 !important;
}
```

### Menu Entry Layout

Each menu entry must expand to full header height and accommodate variable-width label text:

```css
#header nav.app-menu .app-menu-entry {
    height: var(--header-height) !important;
    min-width: 80px !important;
    width: auto !important;
    flex-shrink: 0 !important;
}
```

`var(--header-height)` is a Nextcloud CSS custom property — using it keeps the entry height consistent with the header across themes and screen sizes.

The inner link is made a flex column to vertically centre the label text:

```css
#header nav.app-menu .app-menu-entry__link {
    height: 100% !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 0 !important;
}
```

---

## CSS Selector Scope

All selectors are scoped under `#header nav.app-menu` to prevent unintentional side-effects on other navigation contexts (e.g. sidebar menus, app navigation lists).

---

## No JavaScript Side-Effects

The feature is purely CSS. No runtime DOM manipulation or JavaScript toggling is required. The stylesheet is either present in the page (enabled) or absent (disabled), determined entirely at server boot time.
