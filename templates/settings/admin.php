<?php
/**
 * @var array  $tokenSets
 * @var string|null $currentTokenSet
 */

script('nldesign', 'admin');
style('nldesign', 'admin');

$tokenSetsJson = json_encode(
    $_['tokenSets'],
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
if ($tokenSetsJson === false) {
    $tokenSetsJson = '[]';
}
?>

<div id="nldesign-settings" class="section"
     data-token-sets="<?php p($tokenSetsJson); ?>"
     data-profile-revision="<?php p($_['profileState']['active_profile_revision'] ?? ''); ?>"
     data-previous-profile="<?php p($_['profileState']['previous_profile_snapshot']['profile_id'] ?? ''); ?>"
     data-can-rollback="<?php p(is_array($_['profileState']['previous_profile_snapshot'] ?? null) ? '1' : '0'); ?>"
     data-current-token-set="<?php p($_['currentTokenSet'] ?? ''); ?>">
    <div class="nldesign-settings-header">
        <h2><?php p($l->t('NL Design profiles')); ?></h2>
        <a href="https://nldesign.app" target="_blank" rel="noopener noreferrer" class="nldesign-doc-link">
            <span class="icon-link-external" aria-hidden="true"></span>
            <?php p($l->t('Documentation')); ?>
        </a>
    </div>
    <p class="settings-hint">
        <?php p($l->t('Select a statically gated design profile. The profile changes this app\'s bounded CSS projection; it does not automatically change settings owned by Nextcloud Theming.')); ?>
    </p>

    <p id="nldesign-status" class="nldesign-status" role="status" aria-live="polite"></p>

    <div class="nldesign-token-set-selector">
        <label for="nldesign-token-set-select"><?php p($l->t('Design profile')); ?></label>
        <select id="nldesign-token-set-select" name="nldesign-token-set">
            <option value="__native__" <?php if ($_['currentTokenSet'] === null) : ?>selected<?php endif; ?>>
                <?php p($l->t('Native Nextcloud (no NL Design profile)')); ?>
            </option>
            <?php if ($_['currentTokenSet'] !== null
                && ($_['currentTokenSetAvailable'] ?? false) === false
            ) : ?>
                    <option value="__unavailable__" selected disabled>
                        <?php p($l->t('Stored profile "%s" is unavailable — select a replacement', [$_['currentTokenSet']])); ?>
                    </option>
            <?php endif; ?>
            <?php foreach ($_['tokenSets'] as $tokenSet) : ?>
                <option value="<?php p($tokenSet['id']); ?>"
                        <?php if ($_['currentTokenSet'] === $tokenSet['id']) :
                            ?>selected<?php
                        endif; ?>>
                    <?php p($tokenSet['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="nldesign-option nldesign-revision-row">
        <button type="button" id="nldesign-rollback-profile" class="button button--inline"
                <?php if (is_array($_['profileState']['previous_profile_snapshot'] ?? null) === false) : ?>disabled<?php endif; ?>>
            <?php p($l->t('Roll back to previous profile')); ?>
        </button>
        <em id="nldesign-profile-revision" class="nldesign-meta">
            <?php p($l->t('Active revision: %s', [$_['profileState']['active_profile_revision'] ?? 'N/A'])); ?>
        </em>
    </div>

    <div class="nldesign-preview" id="nldesign-preview">
        <h3><?php p($l->t('Colour preview')); ?></h3>
        <div class="nldesign-preview-box">
            <div class="nldesign-preview-header"></div>
            <div class="nldesign-preview-content">
                <span class="nldesign-preview-button primary"><?php p($l->t('Primary button')); ?></span>
                <span class="nldesign-preview-button"><?php p($l->t('Secondary button')); ?></span>
            </div>
        </div>
    </div>

    <div class="nldesign-theming-manual-bridge">
        <h3><?php p($l->t('Nextcloud Theming hand-off')); ?></h3>
        <p id="nldesign-theming-plan-message" class="settings-hint" aria-live="polite">
            <?php p($l->t('Loading manual recommendations…')); ?>
        </p>
        <ul id="nldesign-theming-plan-steps" class="nldesign-theming-plan-list"></ul>
    </div>

    <div class="nldesign-profile-history">
        <h3><?php p($l->t('Recent profile operations')); ?></h3>
        <ul id="nldesign-profile-history-list" class="nldesign-profile-history-list" aria-live="polite">
            <li><?php p($l->t('Loading profile history…')); ?></li>
        </ul>
    </div>

    <p class="nldesign-info">
        <a href="https://nldesignsystem.nl/" target="_blank" rel="noopener noreferrer">
            <?php p($l->t('Learn more about NL Design System')); ?> ↗
        </a>
    </p>
</div>
