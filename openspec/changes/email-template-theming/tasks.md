## 1. Template class

- [ ] 1.1 Create `lib/Mail/NLDesignEMailTemplate.php` (`OCA\NLDesign\Mail\NLDesignEMailTemplate`)
      extending `OC\Mail\EMailTemplate`. Keep the parent constructor untouched (the class is
      instantiated by `OC\Mail\Mailer::makeTemplate()` with a fixed argument list —
      `lib/private/Mail/Mailer.php:131-143` in the server checkout; adding constructor
      parameters would fatal). SPDX `@license`/`@copyright` docblock per hydra gate.
- [ ] 1.2 Add a private lazy resolver `getEmailThemingService(): ?EmailThemingService` using
      `\OCP\Server::get()` inside `try/catch (\Throwable)` returning `null` on any failure.
      NOTE for the semantic-auth/unsafe-resolver gates: this resolver gates *presentation
      fallback only* (branded vs stock mail), never an authorization decision — document that
      in the method docblock.
- [ ] 1.3 Override `addHeader()`: when the service resolves and reports an active nldesign
      token set, render the header with the token-set primary color and the token-set logo
      (absolute URL via `IURLGenerator`); otherwise call `parent::addHeader()`. Reuse the
      parent's `$header` HEREDOC structure — only the substituted color/logo values change,
      so email-client-tested markup stays intact.
- [ ] 1.4 Override `addBodyButton()` / `addBodyButtonGroup()` analogously: substitute the
      token-set primary color for `ThemingDefaults::getDefaultColorPrimary()` in the button
      background, and the token-set `--nldesign-color-primary-text` (default `#ffffff`) for
      the button text color; fall back to parent on any missing value.
- [ ] 1.5 Override `addFooter()`: call the parent first, then append the configured footer
      block — org name, "Toegankelijkheidsverklaring" link, "Privacyverklaring" link (only
      non-empty values, order fixed) — to BOTH `$this->htmlBody` (small-print styled table
      row matching the parent's footer markup) and `$this->plainBody` (plain lines
      `Name`, `Toegankelijkheidsverklaring: <url>`, `Privacy: <url>`). URLs are
      href-attribute-escaped; all text is HTML-escaped.

## 2. Service + toggle plumbing

- [ ] 2.1 Create `lib/Service/EmailThemingService.php` with:
      `getFooterConfig(): array` / `setFooterConfig(...)` over IConfig app values
      `email_footer_org_name`, `email_footer_accessibility_url`, `email_footer_privacy_url`
      (URL values validated: `https://` or `http://` scheme only, max 2048 chars, else 422);
      `getActiveEmailTheme(): ?array` returning `[primaryColor, primaryTextColor, logoUrl]`
      derived from the active token set (`token-sets.json` `theming` metadata first,
      `TokenSetPreviewService::getResolvedColors()` fallback, `null` when the active set is
      `nextcloud`/none); `isEnabled(): bool` (system value `mail_template_class` equals
      `OCA\NLDesign\Mail\NLDesignEMailTemplate::class`);
      `getState(): array` distinguishing `disabled` / `enabled` / `foreign` (some OTHER class
      is configured) / plus a `configReadOnly` boolean from the `config_is_read_only` system
      value; `enable(): void` / `disable(): void` writing/deleting the system value —
      `enable()` MUST throw a typed exception when a foreign class is configured, and both
      MUST throw a typed `ConfigReadOnlyException`-style exception when config.php is
      read-only (catch `\OCP\HintException` from the write path too, since read-only
      detection via the flag can miss filesystem-level read-only config.php).
- [ ] 2.2 Add `SettingsController::getEmailTheming()` (GET) returning state + footer config,
      and `SettingsController::setEmailTheming()` (POST, params `enabled`, `orgName`,
      `accessibilityUrl`, `privacyUrl`). Both `#[AuthorizedAdminSetting(Admin::class)]`, no
      `NoCSRFRequired` (same posture as the other settings routes). `setEmailTheming` maps
      the read-only exception to HTTP 409 with
      `{"error": "config_read_only", "occEnable": "...", "occDisable": "..."}` carrying the
      exact occ command strings, and the foreign-class case to HTTP 409
      `{"error": "foreign_mail_template_class", "class": "<fqcn>"}`. Footer config saves MUST
      still succeed (app config is always writable) even when the toggle part fails —
      apply footer first, toggle second, and report per-part status.
- [ ] 2.3 Register routes in `appinfo/routes.php`:
      `['name' => 'settings#getEmailTheming', 'url' => '/settings/email-theming', 'verb' => 'GET']`,
      `['name' => 'settings#setEmailTheming', 'url' => '/settings/email-theming', 'verb' => 'POST']`.

## 3. Admin UI (vanilla JS — no Vue)

- [ ] 3.1 `lib/Settings/Admin.php` `getForm()`: add email-theming state, footer values, and
      the occ command strings to the template parameters.
- [ ] 3.2 `templates/settings/admin.php`: new "Email template" section — enable checkbox,
      three labeled text inputs (organization name, toegankelijkheidsverklaring URL, privacy
      statement URL), Save button, and a hidden `.nldesign-email-occ-hint` block containing
      the two occ commands in `<code>` elements. When state is `foreign`, render the checkbox
      disabled with an explanatory note naming the configured class.
- [ ] 3.3 `js/admin.js`: wire the section — load via GET on init, save via POST; on 409
      `config_read_only` un-flip the checkbox and reveal the occ-hint block; on 409
      `foreign_mail_template_class` show the naming note. All user-facing strings via
      `t('nldesign', ...)` with ENGLISH keys.
- [ ] 3.4 Add the new translatable strings to `l10n/` (en source + nl).

## 4. Tests

- [ ] 4.1 `tests/unit/Mail/NLDesignEMailTemplateTest.php` — rendering unit tests
      (instantiate the template directly with mocked `Defaults`/`IURLGenerator`/l10n
      factory, injecting a stubbed `EmailThemingService` via a test seam or overridable
      resolver):
      (a) with an active token set (`#154273`, logo URL), `renderHtml()` contains the
      token-set color in the header and button markup and the absolute logo URL;
      (b) the configured footer org name + both URLs appear in the HTML part AND as lines
      in `renderText()`;
      (c) with the service resolver returning `null`, HTML and plain output are identical
      to a stock `EMailTemplate` render (fallback proof);
      (d) the plain-text part of a full email (header/heading/body/button/footer) still
      contains the button URL and standard footer — plain part intact, no HTML leakage;
      (e) footer values containing `<script>` / `"` are escaped in the HTML part.
- [ ] 4.2 `tests/unit/Service/EmailThemingServiceTest.php` — enable/disable write and delete
      the system value; enable with a foreign class configured throws; read-only config
      throws the typed exception; footer URL validation rejects `javascript:` and
      non-URL junk; `getActiveEmailTheme()` returns manifest theming for `amsterdam`,
      preview-resolved colors for a set without `theming`, and `null` for `nextcloud`.
- [ ] 4.3 Controller test: `setEmailTheming` returns 409 + occ strings when the service
      throws read-only; footer-only save succeeds in the same read-only situation.

## 5. Verify

- [ ] 5.1 Run PHPUnit in the nextcloud:34 container
      (`docker run --rm -v $PWD:/app -w /app <nc-image> php vendor/bin/phpunit`) — all new
      tests green; `composer check:strict` passes (SPDX headers, PHPCS, Psalm, PHPStan).
- [ ] 5.2 Live on the 8080 dev instance: activate the `amsterdam` token set, enable the
      email toggle in the nldesign admin panel, then run
      `occ mail:send-test admin@example.com` (or trigger a password-reset mail) with a local
      mailhog/smtp catcher and confirm the received HTML shows the `#004699` header/button
      color, the Amsterdam logo, and the configured footer links; confirm the plain-text
      alternative part still reads correctly.
- [ ] 5.3 Live negative path: set `config_is_read_only => true` in config.php (or
      `chmod 444` it inside the container), flip the toggle in the UI, and confirm the 409
      path shows the occ instructions and the checkbox reverts; then run the shown
      `occ config:system:set mail_template_class ...` command and confirm branded mail works.
      Restore config.php writability afterwards.
- [ ] 5.4 Live fallback proof: with the toggle enabled, `occ app:disable nldesign`, send a
      test mail, and confirm stock-branded mail is delivered (Mailer's `class_exists` guard
      falls back — no error in the log beyond that). Re-enable the app.
