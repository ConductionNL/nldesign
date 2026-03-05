# Hide Slogan Tasks

- [x] **T01**: Register `POST /settings/slogan` route in the Nextcloud router — `appinfo/routes.php`
- [x] **T02**: Add `setSloganSetting(bool $hideSlogan)` method to `SettingsController` with `@AuthorizedAdminSetting` annotation and boolean-to-string conversion (`true` → `'1'`, `false` → `'0'`) — `lib/Controller/SettingsController.php`
- [x] **T03**: Read `hide_slogan` config key in `Application::injectThemeCSS()` with strict `=== '1'` comparison and default `'0'` — `lib/AppInfo/Application.php`
- [x] **T04**: Add conditional `\OCP\Util::addStyle('nldesign', 'hide-slogan')` call guarded by `if ($hideSlogan === true)`, positioned after the 7 core CSS layers — `lib/AppInfo/Application.php`
- [x] **T05**: Create `css/hide-slogan.css` with three selectors (`footer.guest-box`, `#body-login footer.guest-box`, `body.body-login-container footer.guest-box`) each applying `display: none !important` and `visibility: hidden !important` — `css/hide-slogan.css`
- [x] **T06**: Read `hide_slogan` config value in `Admin::getForm()` and pass boolean `$hideSlogan` to the template — `lib/Settings/Admin.php`
- [x] **T07**: Add the hide-slogan checkbox to the admin settings template, conditionally rendering `checked` from the `$hideSlogan` template variable — `templates/settings/admin.php`
- [x] **T08**: Wire the `#nldesign-hide-slogan` checkbox change event in `admin.js` and implement `saveSloganSetting(hideSlogan)` POSTing JSON to `/apps/nldesign/settings/slogan` with the Nextcloud request token — `js/admin.js`
