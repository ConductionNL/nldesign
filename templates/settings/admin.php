<?php
/**
 * @var array $tokenSets
 * @var string $currentTokenSet
 * @var array{state: string, configReadOnly: bool, foreignClass: ?string} $emailThemingState
 * @var array{orgName: string, accessibilityUrl: string, privacyUrl: string} $emailFooterConfig
 * @var string $occEnableCommand
 * @var string $occDisableCommand
 */

// Load the pure token/colour transforms first so admin.js can consume them via
// window.NldesignTokenTransforms (admin.js falls back to inline copies if absent).
script('nldesign', 'lib/tokenTransforms');
script('nldesign', 'admin');
style('nldesign', 'admin');
?>

<div id="nldesign-settings" class="section"
	 data-token-sets="<?php p(json_encode($_['tokenSets'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)); ?>"
	 data-current-token-set="<?php p($_['currentTokenSet']); ?>">
	<div class="nldesign-settings-header">
		<h2><?php p($l->t('NL Design System Theme')); ?></h2>
		<a href="https://nldesign.app" target="_blank" rel="noopener noreferrer" class="nldesign-doc-link">
			<span class="icon-link-external"></span>
			<?php p($l->t('Documentation')); ?>
		</a>
	</div>
	<p class="settings-hint">
		<?php p($l->t('Select a Dutch government design token set as a base, or customize individual Nextcloud CSS tokens below.')); ?>
	</p>

	<div class="nldesign-token-set-selector">
		<label for="nldesign-token-set-select"><?php p($l->t('Design token set')); ?></label>
		<select id="nldesign-token-set-select" name="nldesign-token-set">
			<?php foreach ($_['tokenSets'] as $tokenSet): ?>
				<option value="<?php p($tokenSet['id']); ?>"
						data-design-system="<?php p($tokenSet['design_system'] ?? 'nldesign'); ?>"
						<?php if ($_['currentTokenSet'] === $tokenSet['id']): ?>selected<?php endif; ?>>
					<?php p($tokenSet['name']); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<span id="nldesign-design-system-badge" class="nldesign-badge"></span>
	</div>

	<!-- Custom token set upload (eigen huisstijl) -->
	<div class="nldesign-custom-token-sets" id="nldesign-custom-token-sets" style="margin-top:2em">
		<h3><?php p($l->t('Custom token sets')); ?></h3>
		<p class="settings-hint">
			<?php p($l->t('Upload your own house style as a token set, either as an NL Design CSS file (--nldesign-* variables) or a W3C Design Tokens JSON file. Uploaded sets appear in the dropdown above.')); ?>
		</p>
		<div class="nldesign-upload-form">
			<label for="nldesign-upload-name"><?php p($l->t('Token set name')); ?></label>
			<input type="text" id="nldesign-upload-name" class="nldesign-upload-name"
				   placeholder="<?php p($l->t('e.g. Gemeente Voorbeeld')); ?>"
				   maxlength="64">
			<input type="file" id="nldesign-upload-input" accept=".css,.json,.tokens.json" style="display:none">
			<button type="button" id="nldesign-upload-btn" class="button">
				<?php p($l->t('Choose file and upload')); ?>
			</button>
		</div>
		<div id="nldesign-upload-result" class="nldesign-import-result" role="status" aria-live="polite" style="display:none"></div>
		<div id="nldesign-custom-set-list" class="nldesign-custom-set-list" role="group"
			 aria-label="<?php p($l->t('Custom token sets')); ?>">
			<p class="settings-hint"><?php p($l->t('Loading custom token sets…')); ?></p>
		</div>
	</div>

	<!-- Custom font upload -->
	<div class="nldesign-custom-fonts" id="nldesign-custom-fonts" style="margin-top:2em">
		<h3><?php p($l->t('Custom fonts')); ?></h3>
		<p class="nldesign-license-notice">
			<?php p($l->t('Only upload fonts your organization holds a license to self-host. Licensing responsibility rests with the uploader.')); ?>
		</p>
		<p class="settings-hint">
			<?php p($l->t('Upload a WOFF2 font file (max 2 MB, 20 fonts max) and assign it to the body text or heading role. Uploaded fonts are self-hosted — no external requests.')); ?>
		</p>
		<div class="nldesign-upload-form">
			<label for="nldesign-font-name"><?php p($l->t('Font display name')); ?></label>
			<input type="text" id="nldesign-font-name" class="nldesign-font-name"
				   placeholder="<?php p($l->t('e.g. Rijks Sans')); ?>"
				   maxlength="64">
			<label for="nldesign-font-role"><?php p($l->t('Font role')); ?></label>
			<select id="nldesign-font-role" name="nldesign-font-role">
				<option value="body"><?php p($l->t('Body text')); ?></option>
				<option value="heading"><?php p($l->t('Heading')); ?></option>
			</select>
			<input type="file" id="nldesign-font-input" accept=".woff2" style="display:none">
			<button type="button" id="nldesign-font-upload-btn" class="button">
				<?php p($l->t('Choose font and upload')); ?>
			</button>
		</div>
		<div id="nldesign-font-upload-result" class="nldesign-import-result" role="status" aria-live="polite" style="display:none"></div>
		<div id="nldesign-font-list" class="nldesign-custom-set-list" role="group"
			 aria-label="<?php p($l->t('Custom fonts')); ?>">
			<p class="settings-hint"><?php p($l->t('Loading fonts…')); ?></p>
		</div>
	</div>

	<!-- Hide Slogan/Payoff Option -->
	<div class="nldesign-option">
		<input type="checkbox"
			   name="nldesign-hide-slogan"
			   id="nldesign-hide-slogan"
			   class="checkbox"
			   <?php if ($_['hideSlogan']): ?>checked<?php endif; ?>>
		<label for="nldesign-hide-slogan">
			<?php p($l->t('Hide Nextcloud slogan/payoff on login page')); ?>
		</label>
	</div>

	<!-- Show Menu Labels Option -->
	<div class="nldesign-option">
		<input type="checkbox"
			   name="nldesign-show-menu-labels"
			   id="nldesign-show-menu-labels"
			   class="checkbox"
			   <?php if ($_['showMenuLabels']): ?>checked<?php endif; ?>>
		<label for="nldesign-show-menu-labels">
			<?php p($l->t('Show text labels in app menu (hide icons)')); ?>
		</label>
	</div>

	<!-- Theming per app — exclude individual apps from nldesign theming -->
	<div class="nldesign-app-theming" id="nldesign-app-theming" style="margin-top:2em">
		<h3><?php p($l->t('Theming per app')); ?></h3>
		<p class="settings-hint">
			<?php p($l->t('Choose which apps the NL Design theme applies to. Unchecking an app makes its pages render with stock Nextcloud styling, including the header on those pages.')); ?>
		</p>
		<div id="nldesign-app-theming-list" class="nldesign-app-theming-list" role="group"
			 aria-label="<?php p($l->t('Theming per app')); ?>">
			<p class="settings-hint"><?php p($l->t('Loading apps…')); ?></p>
		</div>
		<button type="button" id="nldesign-app-theming-save" class="button">
			<?php p($l->t('Save app theming')); ?>
		</button>
		<span id="nldesign-app-theming-feedback" class="nldesign-app-theming-feedback" role="status" aria-live="polite"></span>
	</div>

	<!-- Group theming — map Nextcloud groups to token sets for shared-instance
	     multi-tenant huisstijl (openspec/specs/per-group-theming/spec.md).
	     Row order IS priority order; keyboard-operable move-up/move-down
	     buttons instead of drag-and-drop. -->
	<div class="nldesign-group-theming" id="nldesign-group-theming" style="margin-top:2em">
		<h3><?php p($l->t('Group theming')); ?></h3>
		<p class="settings-hint">
			<?php p($l->t('Map Nextcloud groups to token sets so different gemeenten sharing one instance each see their own house style. Row order is priority order: for a user in multiple mapped groups, the first matching row wins.')); ?>
		</p>
		<p class="settings-hint">
			<?php p($l->t('Logo, mail templates, and other Nextcloud core branding always follow the instance default token set above — they are not per-group. Only this token-set stylesheet layer differs per group.')); ?>
		</p>
		<div id="nldesign-group-theming-list" class="nldesign-group-theming-list" role="group"
			 aria-label="<?php p($l->t('Group theming')); ?>">
			<p class="settings-hint"><?php p($l->t('Loading group mappings…')); ?></p>
		</div>
		<button type="button" id="nldesign-group-theming-add" class="button">
			<?php p($l->t('Add mapping')); ?>
		</button>
		<button type="button" id="nldesign-group-theming-save" class="button primary">
			<?php p($l->t('Save group theming')); ?>
		</button>
		<span id="nldesign-group-theming-feedback" class="nldesign-group-theming-feedback" role="status" aria-live="polite"></span>
	</div>

	<!-- Email template theming — mail_template_class toggle + compliance footer -->
	<div class="nldesign-email-theming" id="nldesign-email-theming" style="margin-top:2em"
		 data-state="<?php p($_['emailThemingState']['state']); ?>"
		 data-config-read-only="<?php p($_['emailThemingState']['configReadOnly'] ? '1' : '0'); ?>"
		 data-foreign-class="<?php p($_['emailThemingState']['foreignClass'] ?? ''); ?>">
		<h3><?php p($l->t('Email template')); ?></h3>
		<p class="settings-hint">
			<?php p($l->t('Brand password-reset, share-notification, and other system emails with the active token set\'s color and logo. If nldesign is later disabled, Nextcloud automatically falls back to the stock email template — mail is never blocked by this setting.')); ?>
		</p>

		<?php if ($_['emailThemingState']['state'] === 'foreign'): ?>
			<p class="nldesign-email-foreign-note" role="alert">
				<?php p($l->t('A different mail template class is already configured ({class}); nldesign will not overwrite it.', ['class' => $_['emailThemingState']['foreignClass']])); ?>
			</p>
		<?php endif; ?>

		<div class="nldesign-option">
			<input type="checkbox"
				   name="nldesign-email-theming-enabled"
				   id="nldesign-email-theming-enabled"
				   class="checkbox"
				   <?php if ($_['emailThemingState']['state'] === 'enabled'): ?>checked<?php endif; ?>
				   <?php if ($_['emailThemingState']['state'] === 'foreign'): ?>disabled<?php endif; ?>>
			<label for="nldesign-email-theming-enabled">
				<?php p($l->t('Use NL Design email template')); ?>
			</label>
		</div>

		<div class="nldesign-email-footer-fields">
			<label for="nldesign-email-footer-org-name"><?php p($l->t('Organization name')); ?></label>
			<input type="text" id="nldesign-email-footer-org-name" class="nldesign-email-footer-input"
				   value="<?php p($_['emailFooterConfig']['orgName']); ?>"
				   placeholder="<?php p($l->t('e.g. Gemeente Voorbeeld')); ?>" maxlength="2048">

			<label for="nldesign-email-footer-accessibility-url"><?php p($l->t('Accessibility statement URL')); ?></label>
			<input type="url" id="nldesign-email-footer-accessibility-url" class="nldesign-email-footer-input"
				   value="<?php p($_['emailFooterConfig']['accessibilityUrl']); ?>"
				   placeholder="https://example.org/toegankelijkheidsverklaring" maxlength="2048">

			<label for="nldesign-email-footer-privacy-url"><?php p($l->t('Privacy statement URL')); ?></label>
			<input type="url" id="nldesign-email-footer-privacy-url" class="nldesign-email-footer-input"
				   value="<?php p($_['emailFooterConfig']['privacyUrl']); ?>"
				   placeholder="https://example.org/privacy" maxlength="2048">
		</div>

		<button type="button" id="nldesign-email-theming-save" class="button">
			<?php p($l->t('Save email template settings')); ?>
		</button>
		<span id="nldesign-email-theming-feedback" class="nldesign-email-theming-feedback" role="status" aria-live="polite"></span>

		<div class="nldesign-email-occ-hint" id="nldesign-email-occ-hint" role="alert" style="display:none">
			<p><?php p($l->t('config.php is read-only. Run one of the following commands manually to enable or disable the email template:')); ?></p>
			<p><code id="nldesign-email-occ-enable"><?php p($_['occEnableCommand']); ?></code></p>
			<p><code id="nldesign-email-occ-disable"><?php p($_['occDisableCommand']); ?></code></p>
		</div>
	</div>

	<div class="nldesign-preview" id="nldesign-preview">
		<div class="nldesign-preview-head">
			<h3><?php p($l->t('Preview')); ?></h3>
			<div class="nldesign-preview-switch" role="tablist" aria-label="<?php p($l->t('Preview view')); ?>">
				<button type="button" class="nldesign-preview-switch-btn active" data-view="app" aria-selected="true"><?php p($l->t('App')); ?></button>
				<button type="button" class="nldesign-preview-switch-btn" data-view="login" aria-selected="false"><?php p($l->t('Login')); ?></button>
			</div>
		</div>

		<!-- App-shell preview: navbar, menu, content + table widget, sidebar, open modal -->
		<div class="nldesign-preview-stage" data-view="app">
			<div class="nl-mini">
				<div class="nl-mini__navbar">
					<span class="nl-mini__logo"></span>
					<span class="nl-mini__navitem nl-mini__navitem--active"></span>
					<span class="nl-mini__navitem"></span>
					<span class="nl-mini__navitem"></span>
					<span class="nl-mini__navspacer"></span>
					<span class="nl-mini__avatar"></span>
				</div>
				<div class="nl-mini__body">
					<nav class="nl-mini__menu">
						<span class="nl-mini__menuitem nl-mini__menuitem--active"><?php p($l->t('Dashboard')); ?></span>
						<span class="nl-mini__menuitem"><?php p($l->t('Orders')); ?></span>
						<span class="nl-mini__menuitem"><?php p($l->t('Reports')); ?></span>
						<span class="nl-mini__menuitem"><?php p($l->t('Settings')); ?></span>
					</nav>
					<main class="nl-mini__content">
						<div class="nl-mini__widget">
							<div class="nl-mini__widget-head"><?php p($l->t('Orders')); ?></div>
							<table class="nl-mini__table">
								<thead><tr><th></th><th></th><th></th></tr></thead>
								<tbody>
									<tr><td></td><td></td><td><span class="nl-mini__pill nl-mini__pill--primary"></span></td></tr>
									<tr><td></td><td></td><td><span class="nl-mini__pill nl-mini__pill--warning"></span></td></tr>
									<tr><td></td><td></td><td><span class="nl-mini__pill nl-mini__pill--info"></span></td></tr>
								</tbody>
							</table>
						</div>
					</main>
					<aside class="nl-mini__sidebar">
						<div class="nl-mini__sidebar-head"><?php p($l->t('Details')); ?></div>
						<span class="nl-mini__line"></span>
						<span class="nl-mini__line nl-mini__line--short"></span>
						<span class="nl-mini__line"></span>
					</aside>
				</div>
				<div class="nl-mini__modal-overlay">
					<div class="nl-mini__modal">
						<div class="nl-mini__modal-head"><?php p($l->t('Dialog')); ?></div>
						<div class="nl-mini__modal-body">
							<span class="nl-mini__line"></span>
							<span class="nl-mini__line nl-mini__line--short"></span>
						</div>
						<div class="nl-mini__modal-actions">
							<button type="button" class="nl-btn nl-btn--primary"><?php p($l->t('Primary')); ?></button>
							<button type="button" class="nl-btn nl-btn--secondary"><?php p($l->t('Secondary')); ?></button>
							<button type="button" class="nl-btn nl-btn--warning"><?php p($l->t('Alert')); ?></button>
							<button type="button" class="nl-btn nl-btn--error"><?php p($l->t('Danger')); ?></button>
							<button type="button" class="nl-btn nl-btn--info"><?php p($l->t('Info')); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Login-page preview -->
		<div class="nldesign-preview-stage" data-view="login" hidden>
			<div class="nl-login">
				<div class="nl-login__logo"></div>
				<div class="nl-login__card">
					<span class="nl-login__field"></span>
					<span class="nl-login__field"></span>
					<button type="button" class="nl-btn nl-btn--primary nl-login__submit"><?php p($l->t('Log in')); ?></button>
				</div>
				<div class="nl-login__slogan"></div>
			</div>
		</div>
	</div>

	<!-- Token Editor Panel — mounted by admin.js -->
	<div id="nldesign-token-editor" style="margin-top:2em">
		<p class="settings-hint"><?php p($l->t('Loading token editor…')); ?></p>
	</div>

	<!-- Upstream token updates — opt-in daily freshness check against
	     nl-design-system/themes (openspec/specs/upstream-freshness/spec.md).
	     Disabled by default; the toggle label discloses the contacted host.
	     No apply control anywhere in this block — informational only, the
	     update path remains the reviewed sync-workflow release. -->
	<div class="nldesign-upstream-freshness" id="nldesign-upstream-freshness" style="margin-top:2em">
		<h3><?php p($l->t('Upstream token updates')); ?></h3>
		<p class="settings-hint">
			<?php p($l->t('Optionally check once a day whether the upstream NL Design System themes have new tokens. This is the only outbound network request this app makes; it contacts api.github.com and never applies anything automatically — you always review and apply updates yourself.')); ?>
		</p>
		<div class="nldesign-option">
			<input type="checkbox"
				   id="nldesign-upstream-freshness-toggle"
				   class="checkbox">
			<label for="nldesign-upstream-freshness-toggle">
				<?php p($l->t('Check daily for upstream token updates (contacts api.github.com)')); ?>
			</label>
		</div>
		<p class="settings-hint" id="nldesign-upstream-freshness-lastchecked"></p>
		<div id="nldesign-upstream-freshness-notices"
			 class="nldesign-upstream-freshness-notices"
			 role="group"
			 aria-label="<?php p($l->t('Upstream token updates')); ?>"></div>
	</div>

	<!-- Theming audit log — who changed which theming setting, from what, to
	     what, and when. Evidence for accessibility/WCAG-EM audits. -->
	<div class="nldesign-audit-log" id="nldesign-audit-log" style="margin-top:2em">
		<h3><?php p($l->t('Theming audit log')); ?></h3>
		<p class="settings-hint">
			<?php p($l->t('A record of theming configuration changes: who changed what, from what, to what, and when. Useful evidence for accessibility audits.')); ?>
		</p>
		<table class="nldesign-audit-table" id="nldesign-audit-table">
			<thead>
				<tr>
					<th><?php p($l->t('Timestamp')); ?></th>
					<th><?php p($l->t('User')); ?></th>
					<th><?php p($l->t('Action')); ?></th>
					<th><?php p($l->t('Details')); ?></th>
				</tr>
			</thead>
			<tbody id="nldesign-audit-table-body">
				<tr><td colspan="4" class="settings-hint"><?php p($l->t('Loading audit log…')); ?></td></tr>
			</tbody>
		</table>
		<button type="button" id="nldesign-audit-download-btn" class="button">
			<?php p($l->t('Download full log')); ?>
		</button>
	</div>

	<p class="nldesign-info">
		<a href="https://nldesignsystem.nl/" target="_blank" rel="noopener noreferrer">
			<?php p($l->t('Learn more about NL Design System')); ?> ↗
		</a>
	</p>
</div>
