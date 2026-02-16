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
	<h2><?php p($l->t('NL Design System Theme')); ?></h2>
	<p class="settings-hint">
		<?php p($l->t('Select which Dutch government design token set to apply to your Nextcloud instance.')); ?>
	</p>

	<div class="nldesign-token-set-selector">
		<label for="nldesign-token-set-select"><?php p($l->t('Design token set')); ?></label>
		<select id="nldesign-token-set-select" name="nldesign-token-set">
			<?php foreach ($_['tokenSets'] as $tokenSet): ?>
				<option value="<?php p($tokenSet['id']); ?>"
						<?php if ($_['currentTokenSet'] === $tokenSet['id']): ?>selected<?php endif; ?>>
					<?php p($tokenSet['name']); ?>
				</option>
			<?php endforeach; ?>
		</select>
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

	<p class="nldesign-info">
		<a href="https://nldesignsystem.nl/" target="_blank" rel="noopener noreferrer">
			<?php p($l->t('Learn more about NL Design System')); ?> ↗
		</a>
	</p>
</div>
