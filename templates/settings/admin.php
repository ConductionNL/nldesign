<?php
/**
 * @var array       $tokenSets
 * @var string|null $currentTokenSet
 * @var string|null $currentProfileVersion
 * @var array       $runtimeCompatibility
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

$previousSnapshot = $_['profileState']['previous_profile_snapshot'] ?? null;
$runtimeCompatibility = $_['runtimeCompatibility'] ?? [];
$runtimeSupported = ($runtimeCompatibility['supported'] ?? false) === true;
$runtimeVersion = is_string($runtimeCompatibility['runtime_version'] ?? null)
    ? $runtimeCompatibility['runtime_version']
    : 'unknown';
$runtimeAdapter = is_string($runtimeCompatibility['adapter_id'] ?? null)
    ? $runtimeCompatibility['adapter_id']
    : '';
?>

<div id="nldesign-settings" class="section"
     data-token-sets="<?php p($tokenSetsJson); ?>"
     data-profile-revision="<?php p($_['profileState']['active_profile_revision'] ?? ''); ?>"
     data-previous-profile="<?php p(is_array($previousSnapshot) ? ($previousSnapshot['profile_id'] ?? '') : ''); ?>"
     data-previous-profile-version="<?php p(is_array($previousSnapshot) ? ($previousSnapshot['profile_version'] ?? '') : ''); ?>"
     data-can-rollback="<?php p(is_array($previousSnapshot) ? '1' : '0'); ?>"
     data-runtime-supported="<?php p($runtimeSupported ? '1' : '0'); ?>"
     data-runtime-adapter="<?php p($runtimeAdapter); ?>"
     data-current-token-set="<?php p($_['currentTokenSet'] ?? ''); ?>"
     data-current-profile-version="<?php p($_['currentProfileVersion'] ?? ''); ?>">
    <header class="nldesign-settings-header">
        <div>
            <p class="nldesign-eyebrow"><?php p($l->t('Appearance')); ?></p>
            <h2><?php p($l->t('NL Design profiles')); ?></h2>
            <p class="settings-hint">
                <?php p($l->t('Install, preview and activate versioned design profiles without changing files in the app package.')); ?>
            </p>
            <?php if ($runtimeSupported === true) : ?>
                <p class="nldesign-runtime-status is-supported" title="<?php p($runtimeAdapter); ?>">
                    <?php p($l->t('Nextcloud %s · version-specific projection ready', [$runtimeVersion])); ?>
                </p>
            <?php else : ?>
                <p class="nldesign-runtime-status is-unsupported" role="alert">
                    <?php p($l->t('Nextcloud %s has no verified NL Design projection adapter. Profile CSS will not be loaded.', [$runtimeVersion])); ?>
                </p>
            <?php endif; ?>
        </div>
        <a href="https://nldesign.app" target="_blank" rel="noopener noreferrer" class="nldesign-doc-link">
            <span class="icon-link-external" aria-hidden="true"></span>
            <?php p($l->t('Documentation')); ?>
        </a>
    </header>

    <p id="nldesign-status" class="nldesign-status" role="status" aria-live="polite"></p>

    <section class="nldesign-active-panel" aria-labelledby="nldesign-active-heading">
        <div class="nldesign-active-copy">
            <p class="nldesign-eyebrow"><?php p($l->t('Active profile')); ?></p>
            <h3 id="nldesign-active-heading"><?php p($l->t('Loading profile…')); ?></h3>
            <p id="nldesign-active-description" class="nldesign-active-description"></p>
            <div id="nldesign-active-badges" class="nldesign-badges"></div>
            <div class="nldesign-active-actions">
                <button type="button" id="nldesign-deactivate-profile" class="button"
                        data-nldesign-action="deactivate">
                    <?php p($l->t('Use native Nextcloud')); ?>
                </button>
                <button type="button" id="nldesign-rollback-profile" class="button"
                        data-nldesign-action="rollback"
                        <?php if (is_array($previousSnapshot) === false) : ?>disabled<?php endif; ?>>
                    <?php p($l->t('Roll back')); ?>
                </button>
            </div>
        </div>

        <div class="nldesign-preview-column">
            <div class="nldesign-preview-toolbar" role="group" aria-label="<?php p($l->t('Preview colour mode')); ?>">
                <button type="button" class="nldesign-mode-button active" data-preview-mode="light"
                        aria-pressed="true">
                    <?php p($l->t('Light')); ?>
                </button>
                <button type="button" class="nldesign-mode-button" data-preview-mode="dark"
                        aria-pressed="false">
                    <?php p($l->t('Dark')); ?>
                </button>
            </div>
            <div class="nldesign-preview-box" data-preview-current-mode="light">
                <div class="nldesign-preview-topbar">
                    <span class="nldesign-preview-mark" aria-hidden="true"></span>
                    <span class="nldesign-preview-search" aria-hidden="true"></span>
                    <span class="nldesign-preview-avatar" aria-hidden="true"></span>
                </div>
                <div class="nldesign-preview-body">
                    <div class="nldesign-preview-sidebar" aria-hidden="true">
                        <span></span><span></span><span></span>
                    </div>
                    <div class="nldesign-preview-content">
                        <span class="nldesign-preview-title" aria-hidden="true"></span>
                        <span class="nldesign-preview-line" aria-hidden="true"></span>
                        <div class="nldesign-preview-actions">
                            <span class="nldesign-preview-button primary"><?php p($l->t('Primary')); ?></span>
                            <span class="nldesign-preview-button"><?php p($l->t('Secondary')); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="nldesign-library" aria-labelledby="nldesign-library-heading">
        <div class="nldesign-section-heading">
            <div>
                <p class="nldesign-eyebrow"><?php p($l->t('Profile library')); ?></p>
                <h3 id="nldesign-library-heading"><?php p($l->t('Available profiles')); ?></h3>
                <p class="settings-hint">
                    <?php p($l->t('Built-in profiles are read-only. Installed versions remain separate and can be removed independently.')); ?>
                </p>
            </div>
            <button type="button" id="nldesign-open-installer" class="button primary"
                    data-nldesign-action="install-open">
                <?php p($l->t('Install profile')); ?>
            </button>
        </div>

        <label class="nldesign-search" for="nldesign-profile-search">
            <span class="icon-search" aria-hidden="true"></span>
            <span class="nldesign-visually-hidden"><?php p($l->t('Search profiles')); ?></span>
            <input type="search" id="nldesign-profile-search"
                   placeholder="<?php p($l->t('Search profiles')); ?>"
                   autocomplete="off">
        </label>

        <div id="nldesign-profile-grid" class="nldesign-profile-grid" aria-live="polite"></div>
        <p id="nldesign-library-empty" class="nldesign-library-empty" hidden>
            <?php p($l->t('No profiles match this search.')); ?>
        </p>
    </section>

    <details class="nldesign-operations">
        <summary>
            <span><?php p($l->t('Operations and Nextcloud Theming')); ?></span>
            <em id="nldesign-profile-revision" class="nldesign-meta">
                <?php p($l->t('Revision %s', [$_['profileState']['active_profile_revision'] ?? 'N/A'])); ?>
            </em>
        </summary>

        <div class="nldesign-operations-grid">
            <section class="nldesign-theming-manual-bridge" aria-labelledby="nldesign-theming-heading">
                <h4 id="nldesign-theming-heading"><?php p($l->t('Nextcloud Theming hand-off')); ?></h4>
                <p id="nldesign-theming-plan-message" class="settings-hint" aria-live="polite">
                    <?php p($l->t('Loading manual recommendations…')); ?>
                </p>
                <ul id="nldesign-theming-plan-steps" class="nldesign-theming-plan-list"></ul>
            </section>

            <section class="nldesign-profile-history" aria-labelledby="nldesign-history-heading">
                <h4 id="nldesign-history-heading"><?php p($l->t('Recent profile operations')); ?></h4>
                <ul id="nldesign-profile-history-list" class="nldesign-profile-history-list" aria-live="polite">
                    <li><?php p($l->t('Loading profile history…')); ?></li>
                </ul>
            </section>
        </div>
    </details>

    <p class="nldesign-info">
        <a href="https://nldesignsystem.nl/" target="_blank" rel="noopener noreferrer">
            <?php p($l->t('Learn more about NL Design System')); ?> ↗
        </a>
    </p>

    <dialog id="nldesign-installer" class="nldesign-installer">
        <div class="nldesign-dialog-header">
            <div>
                <p class="nldesign-eyebrow"><?php p($l->t('Profile library')); ?></p>
                <h3><?php p($l->t('Install a profile pack')); ?></h3>
            </div>
            <button type="button" id="nldesign-close-installer" class="nldesign-icon-button"
                    aria-label="<?php p($l->t('Close')); ?>">×</button>
        </div>
        <p class="settings-hint">
            <?php p($l->t('Choose a nldesign-profile-pack/v1 JSON file. The app validates semantic roles and generates the CSS itself; uploaded CSS and executable assets are not accepted.')); ?>
        </p>
        <label class="nldesign-file-drop" for="nldesign-profile-pack-file">
            <span class="nldesign-file-icon" aria-hidden="true">JSON</span>
            <strong><?php p($l->t('Choose profile pack')); ?></strong>
            <span id="nldesign-file-name"><?php p($l->t('No file selected')); ?></span>
            <input type="file" id="nldesign-profile-pack-file" accept="application/json,.json">
        </label>
        <div id="nldesign-install-summary" class="nldesign-install-summary" hidden></div>
        <p id="nldesign-install-error" class="nldesign-install-error" role="alert" hidden></p>
        <div class="nldesign-dialog-actions">
            <button type="button" id="nldesign-cancel-installer" class="button">
                <?php p($l->t('Cancel')); ?>
            </button>
            <button type="button" id="nldesign-install-profile" class="button primary"
                    data-nldesign-action="install" disabled>
                <?php p($l->t('Validate and install')); ?>
            </button>
        </div>
    </dialog>
</div>
