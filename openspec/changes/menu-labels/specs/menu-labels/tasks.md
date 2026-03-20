# Menu Labels Tasks

- [x] **T01**: Register `POST /settings/menulabels` route — `appinfo/routes.php`
- [x] **T02**: Implement `SettingsController::setMenuLabelsSetting(bool $showMenuLabels)` storing `'1'`/`'0'` in IConfig and returning `{"status":"ok","showMenuLabels":<bool>}` — `lib/Controller/SettingsController.php`
- [x] **T03**: Read `show_menu_labels` config flag in `Settings\Admin::getForm()` and pass `$showMenuLabels` bool to the template — `lib/Settings/Admin.php`
- [x] **T04**: Add "Show text labels in app menu" checkbox (id `nldesign-show-menu-labels`) to the admin settings template — `templates/settings/admin.php`
- [x] **T05**: Add `saveMenuLabelsSetting()` JS function that POSTs `{showMenuLabels: bool}` to `/apps/nldesign/settings/menulabels` with CSRF token — `js/admin.js`
- [x] **T06**: Wire checkbox change event to `saveMenuLabelsSetting()` in admin JS — `js/admin.js`
- [x] **T07**: Create `show-menu-labels.css` with icon-hiding rules (`display:none !important; visibility:hidden !important`) for `.app-menu-icon` and `.app-menu-entry__icon` — `css/show-menu-labels.css`
- [x] **T08**: Add label visibility and typography rules to `show-menu-labels.css` (`display:inline-block`, `opacity:1`, `font-size:14px`, `font-weight:400`, `white-space:nowrap`, `position:static`, `transform:none`, `max-width:none`) — `css/show-menu-labels.css`
- [x] **T09**: Add active label bold rule (`font-weight:600` on `.app-menu-entry--active .app-menu-entry__label`) to `show-menu-labels.css` — `css/show-menu-labels.css`
- [x] **T10**: Add active indicator suppression (`::before` pseudo-element `background-color:transparent; opacity:0`) to `show-menu-labels.css` — `css/show-menu-labels.css`
- [x] **T11**: Add menu entry layout rules (`height:var(--header-height)`, `min-width:80px`, `width:auto`, `flex-shrink:0`) to `show-menu-labels.css` — `css/show-menu-labels.css`
- [x] **T12**: Add entry link flex-column centering rules (`display:flex`, `flex-direction:column`, `align-items:center`, `justify-content:center`, `height:100%`, `padding:0`) to `show-menu-labels.css` — `css/show-menu-labels.css`
- [x] **T13**: Read `show_menu_labels` config flag in `Application::injectThemeCSS()` and conditionally call `\OCP\Util::addStyle('nldesign', 'show-menu-labels')` as the last CSS layer — `lib/AppInfo/Application.php`
