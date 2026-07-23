<?php
/**
 * @var array $tokenSets
 * @var string $currentTokenSet
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
