<?php
/**
 * @var array $tokenSets
 * @var string $currentTokenSet
 */

script('nldesign', 'admin');
style('nldesign', 'admin');
?>

<div id="nldesign-settings" class="section"
	 data-token-sets="<?php p(json_encode($_['tokenSets'])); ?>"
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

	<div class="nldesign-preview" id="nldesign-preview">
		<h3><?php p($l->t('Preview')); ?></h3>
		<div class="nldesign-preview-box">
			<div class="nldesign-preview-header"></div>
			<div class="nldesign-preview-content">
				<button class="nldesign-preview-button primary"><?php p($l->t('Primary Button')); ?></button>
				<button class="nldesign-preview-button"><?php p($l->t('Secondary Button')); ?></button>
			</div>
		</div>
	</div>

	<!-- Token Editor Panel — mounted by admin.js -->
	<div id="nldesign-token-editor" style="margin-top:2em">
		<p class="settings-hint"><?php p($l->t('Loading token editor…')); ?></p>
	</div>

	<p class="nldesign-info">
		<a href="https://nldesignsystem.nl/" target="_blank" rel="noopener noreferrer">
			<?php p($l->t('Learn more about NL Design System')); ?> ↗
		</a>
	</p>
</div>
