<?php
/**
 * @var array $tokenSets
 * @var string $currentTokenSet
 */

script('nldesign', 'admin');
style('nldesign', 'admin');
?>

<div id="nldesign-settings" class="section">
	<h2><?php p($l->t('NL Design System Theme')); ?></h2>
	<p class="settings-hint">
		<?php p($l->t('Select which Dutch government design token set to apply to your Nextcloud instance.')); ?>
	</p>

	<div class="nldesign-token-sets">
		<?php foreach ($_['tokenSets'] as $key => $tokenSet): ?>
			<div class="nldesign-token-set">
				<input type="radio"
					   name="nldesign-token-set"
					   id="nldesign-token-set-<?php p($key); ?>"
					   value="<?php p($key); ?>"
					   <?php if ($_['currentTokenSet'] === $key): ?>checked<?php endif; ?>>
				<label for="nldesign-token-set-<?php p($key); ?>">
					<strong><?php p($tokenSet['name']); ?></strong>
					<span class="nldesign-token-set-description">
						<?php p($tokenSet['description']); ?>
					</span>
				</label>
			</div>
		<?php endforeach; ?>
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
