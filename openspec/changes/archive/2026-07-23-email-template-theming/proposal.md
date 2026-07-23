---
kind: code
---

## Why

Every email Nextcloud sends today — password reset, share notification, welcome mail — renders
with stock Nextcloud branding (or, at best, the single primary color the theming-sync dialog
pushed into `ThemingDefaults`). For a gemeente that has activated an NL Design System token set,
this is the single most visible branding gap left: the workplace is themed, the mail that lands
in a citizen's or employee's inbox is not. "Emails with wrong/giant logo" is a named end-user
pain in the rollout research, and email/notification template branding without writing a PHP app
is a HIGH-ranked chronic wish upstream: nextcloud/server#2071 has 59 comments (plus server#3268
and 6+ forum threads), and Nextcloud's own enterprise documentation answers it with "write a
custom app" — i.e. deep email branding is effectively enterprise-only today. No app in the
Customization store category does this.

The platform has exactly one sanctioned deep-branding path for mail: the `mail_template_class`
system config. `OC\Mail\Mailer::makeTemplate()` (server checkout,
`lib/private/Mail/Mailer.php:131-133`) reads it and instantiates any class that
`is_a($class, \OC\Mail\EMailTemplate::class, true)` — extending the private-namespace
`OC\Mail\EMailTemplate` is therefore the mechanism the server itself provides and guards, not a
hack. Crucially, the same code path falls back to the stock template when the class does not
exist (e.g. nldesign was disabled but the config value was left behind), so the failure mode is
"unbranded mail", never "no mail".

nldesign already owns everything the template needs: the active token set id (IConfig
`token_set`), per-set `theming.primary_color` / `theming.logo` metadata in `token-sets.json`,
and `TokenSetPreviewService::getResolvedColors()` for sets without explicit theming metadata.
What is missing is the template class, a compliance-grade footer (org name,
toegankelijkheidsverklaring URL per the Besluit digitale toegankelijkheid expectation that every
digital channel links its accessibility statement — cf. server#49620 — and a privacy URL), and
an admin toggle. The toggle needs careful handling: `mail_template_class` is a *system* config
(config.php), which cannot be written when `config_is_read_only` is set — common on hardened /
containerized gov instances — so the UI must fail gracefully and hand the admin the exact `occ`
commands instead.

## What Changes

- New class `lib/Mail/NLDesignEMailTemplate.php` extending `OC\Mail\EMailTemplate`. Because
  `Mailer::makeTemplate()` constructs the class with a fixed argument list (Defaults,
  IURLGenerator, l10n IFactory, logo dimensions, emailId, data), the subclass resolves nldesign
  services lazily via `\OCP\Server::get()` inside its methods, wrapped so that ANY failure
  (nldesign disabled mid-flight, unreadable manifest) falls back to the parent's stock
  rendering — email sending must never break because of theming.
- Header background color, heading color, and button color/background derive from the active
  token set (`theming.primary_color`, else resolved `--nldesign-color-primary`, else
  `ThemingDefaults` — the existing behavior). Header logo derives from the token set's
  `theming.logo` when present (served as an absolute URL via nldesign's `imagePath` assets),
  else falls back to `ThemingDefaults::getLogo()`.
- Configurable footer appended below the standard footer text: organization name,
  toegankelijkheidsverklaring URL, privacy statement URL — three new nldesign app-config values
  (`email_footer_org_name`, `email_footer_accessibility_url`, `email_footer_privacy_url`),
  editable in the admin panel. Empty values are simply omitted. Both the HTML part AND the
  plain-text part get the footer lines; the plain-text part otherwise stays byte-compatible
  with stock output.
- New service `lib/Service/EmailThemingService.php`: reads/writes the three footer app-config
  values, reports toggle state, and encapsulates the `mail_template_class` system-config
  write/clear (via `IConfig::setSystemValue`/`deleteSystemValue`) including the read-only
  detection and the "another class is already configured" guard (never clobber a foreign
  `mail_template_class` value; surface it instead).
- Admin toggle "Use NL Design email template" in the nldesign settings panel
  (`templates/settings/admin.php` + `js/admin.js` section + two routes
  `GET/POST /settings/email-theming` on `SettingsController`, admin-only via
  `#[AuthorizedAdminSetting(Admin::class)]`, CSRF-protected). When config.php is read-only the
  POST returns a structured 409-style error and the UI shows the manual instructions verbatim:
  `occ config:system:set mail_template_class --value "OCA\\NLDesign\\Mail\\NLDesignEMailTemplate"`
  to enable, `occ config:system:delete mail_template_class` to disable.
- New canonical spec `openspec/specs/email-theming/spec.md` (delta in this change).
- No DB tables, no Vue, no new dependencies. Not BREAKING: default is off; nothing changes for
  instances that do not flip the toggle.

## Impact

- `lib/Mail/NLDesignEMailTemplate.php` — new (new `OCA\NLDesign\Mail` namespace dir).
- `lib/Service/EmailThemingService.php` — new.
- `lib/Controller/SettingsController.php` — two new methods (`getEmailTheming`,
  `setEmailTheming`).
- `appinfo/routes.php` — two new routes.
- `lib/Settings/Admin.php` — pass email-theming state + footer values into the template
  parameters.
- `templates/settings/admin.php`, `js/admin.js`, `css/admin.css` (if needed) — toggle + three
  footer inputs + read-only fallback instructions block.
- `l10n/` — new English-keyed strings (nl translations).
- `tests/unit/Mail/NLDesignEMailTemplateTest.php`,
  `tests/unit/Service/EmailThemingServiceTest.php` — new.
- `openspec/specs/email-theming/spec.md` — new canonical spec (via this change's delta).
- Related changes (cross-reference only, no overlap): `upstream-token-freshness` (first
  background job; unrelated surface), `custom-font-upload` (fonts are deliberately NOT used in
  email HTML — email clients don't load webfonts reliably; the template sticks to the stock
  font stack).
