# Hide Slogan — Technical Design

## Overview

The hide-slogan feature removes the Nextcloud slogan/payoff text ("a safe home for all your data") from the login page footer. It is implemented as a conditional CSS injection: when the admin enables the toggle, a dedicated CSS file is appended to the global stylesheet pipeline on every page load. The CSS targets `footer.guest-box`, which is only present on Nextcloud login and guest pages, so no other page is affected.

---

## Architecture

```
Admin UI (templates/settings/admin.php)
  └─ checkbox #nldesign-hide-slogan
        │
        │ change event
        ▼
js/admin.js :: saveSloganSetting(hideSlogan: bool)
  └─ POST /apps/nldesign/settings/slogan  { hideSlogan: bool }
        │
        ▼
lib/Controller/SettingsController.php :: setSloganSetting(bool $hideSlogan)
  └─ IConfig::setAppValue('nldesign', 'hide_slogan', '1'|'0')
        │
        ▼  (next page load / boot)
lib/AppInfo/Application.php :: injectThemeCSS()
  └─ IConfig::getAppValue('nldesign', 'hide_slogan', '0') === '1'
        └─ true  → \OCP\Util::addStyle('nldesign', 'hide-slogan')
        └─ false → (no-op)
              │
              ▼
css/hide-slogan.css  (served to browser)
  └─ footer.guest-box { display: none !important; visibility: hidden !important; }
```

---

## Component Details

### 1. Configuration Storage — `IConfig`

**Key**: `nldesign` / `hide_slogan`
**Type**: string (`'1'` = enabled, `'0'` = disabled)
**Default**: `'0'` (slogan visible)

`IConfig` stores all Nextcloud app settings as strings. The boolean toggle is
therefore persisted as a string literal and must be converted on both write and
read.

**Write** (`SettingsController::setSloganSetting`):
```php
$sloganValue = '0';
if ($hideSlogan === true) {
    $sloganValue = '1';
}
$this->config->setAppValue(Application::APP_ID, 'hide_slogan', $sloganValue);
```

Strict comparison (`=== true`) is used to avoid accidental truthy matches.

**Read** (`Application::injectThemeCSS` and `Settings\Admin::getForm`):
```php
$hideSlogan = $config->getAppValue(self::APP_ID, 'hide_slogan', '0') === '1';
```

Strict string equality (`=== '1'`) produces a clean boolean without loose
comparison side-effects.

---

### 2. Conditional CSS Loading — `Application::injectThemeCSS()`

Called once per request during the boot phase via `IBootstrap::boot()`. The
method loads CSS in a fixed 7-layer order (fonts → defaults → tokens/{set} →
utrecht-bridge → theme → overrides → element-overrides) followed by two
optional feature layers.

```php
// Hide slogan if enabled.
if ($hideSlogan === true) {
    \OCP\Util::addStyle(appName: self::APP_ID, styleName: 'hide-slogan');
}
```

`\OCP\Util::addStyle` appends a `<link rel="stylesheet">` tag to the page HEAD.
The file is served at:
```
/apps/nldesign/css/hide-slogan.css
```

Because the check happens at boot time on every request, toggling the setting
takes effect immediately on the next page load — no cache flush is required.

---

### 3. CSS Selectors — `css/hide-slogan.css`

The file uses three selectors for maximum specificity and cross-version
Nextcloud coverage:

```css
footer.guest-box,
#body-login footer.guest-box,
body.body-login-container footer.guest-box {
    display: none !important;
    visibility: hidden !important;
}
```

| Selector | Covers |
|---|---|
| `footer.guest-box` | Base selector, works on any page that renders the element |
| `#body-login footer.guest-box` | Nextcloud login page (`<body id="body-login">`) |
| `body.body-login-container footer.guest-box` | Nextcloud 28+ login container variant |

**Dual hiding strategy**:
- `display: none` — removes the element from layout flow (no whitespace remains)
- `visibility: hidden` — prevents any visual trace even if `display` is overridden downstream
- Both properties carry `!important` to win specificity against Nextcloud's bundled styles

**Scope**: `footer.guest-box` is exclusively present on login/guest pages. No
other Nextcloud page renders this element, so the rule has zero impact outside
the login flow.

---

### 4. API Endpoint

**Route**: `POST /apps/nldesign/settings/slogan`
**Controller**: `SettingsController::setSloganSetting(bool $hideSlogan)`
**Auth**: `@AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)` — Nextcloud
rejects the request before the controller is reached if the caller is not an
admin.

Request body (JSON):
```json
{ "hideSlogan": true }
```

Success response:
```json
{ "status": "ok", "hideSlogan": true }
```

---

### 5. Admin UI — `templates/settings/admin.php` + `lib/Settings/Admin.php`

`Admin::getForm()` reads `hide_slogan` from `IConfig` and passes the boolean
`$hideSlogan` to the PHP template, which conditionally renders the `checked`
attribute:

```php
<?php if ($_['hideSlogan']): ?>checked<?php endif; ?>
```

The checkbox is wired in `js/admin.js`:
```js
hideSloganCheckbox.addEventListener('change', function () {
    saveSloganSetting(this.checked);
});

function saveSloganSetting(hideSlogan) {
    fetch(OC.generateUrl('/apps/nldesign/settings/slogan'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'requesttoken': OC.requestToken },
        body: JSON.stringify({ hideSlogan: hideSlogan })
    });
}
```

On success the user sees a temporary OC notification:
> "Setting saved successfully. Reload the login page to see changes."

---

## Data Flow Summary

```
User checks/unchecks checkbox
  → change event fires
  → saveSloganSetting(bool) called
  → POST /apps/nldesign/settings/slogan { hideSlogan: bool }
  → setSloganSetting(bool $hideSlogan) stores '1'|'0' in IConfig
  → Next page load: injectThemeCSS() reads '1'|'0'
  → If '1': addStyle('hide-slogan') injects <link> in HEAD
  → Browser loads css/hide-slogan.css
  → footer.guest-box rendered with display:none on login page
```

---

## Files Involved

| File | Role |
|---|---|
| `lib/AppInfo/Application.php` | Boot hook; reads config and conditionally calls `addStyle` |
| `lib/Controller/SettingsController.php` | API handler; converts bool to string and writes to IConfig |
| `lib/Settings/Admin.php` | Admin settings form builder; reads config for template vars |
| `css/hide-slogan.css` | CSS rules targeting `footer.guest-box` on login page |
| `js/admin.js` | Frontend; listens to checkbox, POSTs to API |
| `templates/settings/admin.php` | HTML checkbox, pre-checked from `$hideSlogan` template var |
| `appinfo/routes.php` | Route registration for `POST /settings/slogan` |
