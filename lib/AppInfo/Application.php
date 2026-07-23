<?php

/**
 * NL Design Application Bootstrap.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category  Application
 * @package   OCA\NLDesign
 * @author    Conduction <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://codeberg.org/Conduction/nldesign
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-1
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-2
 */

declare(strict_types=1);

namespace OCA\NLDesign\AppInfo;

use OCA\NLDesign\Capabilities;
use OCA\NLDesign\Service\AppThemingService;
use OCA\NLDesign\Service\CustomOverridesService;
use OCA\NLDesign\Service\DesignSystemService;
use OCA\NLDesign\Service\FontService;
use OCA\NLDesign\Themes\NLDesignTheme;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IURLGenerator;

/**
 * Main application class for NL Design.
 *
 * Bootstraps the NL Design theme system and injects design tokens.
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-1
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-2
 */
class Application extends App implements IBootstrap
{
    public const APP_ID = 'nldesign';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(appName: self::APP_ID);
    }//end __construct()

    /**
     * Register services and providers.
     *
     * No bootstrap-time service registration is required for health: the
     * `/api/health` endpoint is served by the thin `Controller\HealthController`
     * subclass of the OpenRegister AppHost engine's GenericHealthController
     * (ADR-040). The subclass is autoloaded only when the route is dispatched,
     * never at bootstrap, so OpenRegister is a SOFT/optional dependency for
     * health only — Nextcloud still boots and nldesign still themes when
     * OpenRegister is absent (a request to /api/health would then degrade
     * rather than fatal the app). The declarative checks live in
     * `src/manifest.json` and use only the OR-independent primitives
     * (database, filesystem, appEnabled) — never orAvailable, and no OR-object
     * metrics. The `Capabilities` class IS registered here — it is the app's
     * first real `register()`-time registration — so the huisstijl is exposed
     * on every capabilities document without any request-time cost.
     *
     * @param IRegistrationContext $context The registration context.
     *
     * @return void
     *
     * @spec openspec/changes/adopt-apphost-2026-06-16/tasks.md#task-2
     * @spec openspec/specs/theming-capability/spec.md
     */
    public function register(IRegistrationContext $context): void
    {
        // Health endpoint served by the thin Controller\HealthController
        // subclass of the AppHost engine — no explicit registration needed.
        // Public huisstijl capability — see lib/Capabilities.php.
        $context->registerCapability(Capabilities::class);
    }//end register()

    /**
     * Boot the application.
     *
     * @param IBootContext $context The boot context.
     *
     * @return void
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-1
     */
    public function boot(IBootContext $context): void
    {
        $serverContainer = $context->getServerContainer();

        // Inject our CSS variables.
        $this->injectThemeCSS(serverContainer: $serverContainer);
    }//end boot()

    /**
     * Inject theme CSS files based on configuration.
     *
     * @param mixed $serverContainer The server container.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess) - \OCP\Util::addStyle() is the Nextcloud API for CSS injection
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-2
     * @spec openspec/specs/custom-fonts/spec.md
     */
    private function injectThemeCSS($serverContainer): void
    {
        // Per-app theming guard: if the app currently being rendered is in the
        // admin's exclusion list, skip ALL nldesign style injection so its pages
        // render as stock Nextcloud. Resolution failures (occ/cron, no path info)
        // fail open to themed — theming is presentation, never security.
        if ($this->isThemingDisabled(serverContainer: $serverContainer) === true) {
            return;
        }

        $config         = $serverContainer->get(\OCP\IConfig::class);
        $tokenSet       = $config->getAppValue(self::APP_ID, 'token_set', 'nextcloud');
        $hideSlogan     = $config->getAppValue(self::APP_ID, 'hide_slogan', '0') === '1';
        $showMenuLabels = $config->getAppValue(self::APP_ID, 'show_menu_labels', '0') === '1';

        // 1. Resolve which design system this token set uses.
        $dsService      = $serverContainer->get(DesignSystemService::class);
        $tokenSetMeta   = $dsService->getTokenSetMeta($tokenSet);
        $designSystemId = $tokenSetMeta['design_system'] ?? 'nldesign';
        $designSystem   = $dsService->getDesignSystem($designSystemId);

        // 2. Load design system stylesheets in declared order.
        // For "none" (stock Nextcloud) this array is empty — no CSS loads.
        foreach ($designSystem['stylesheets'] as $stylesheet) {
            \OCP\Util::addStyle(application: self::APP_ID, file: $stylesheet);
        }

        // 3. Load token values (only when a design system reads --nldesign-* vars).
        if ($designSystemId !== 'none') {
            \OCP\Util::addStyle(application: self::APP_ID, file: 'tokens/'.$tokenSet);
            // Functional contrast fix shared by all design systems: app icons
            // that carry their white fill on <path> vanish on light surfaces
            // in the NC 34 app-management list (see css/icon-contrast.css).
            \OCP\Util::addStyle(application: self::APP_ID, file: 'icon-contrast');
            // Functional contrast fix shared by all design systems: our error
            // fill is a saturated brand red where Nextcloud's is pale, so the
            // components painting --color-error-text on it lose all contrast
            // (see css/error-contrast.css).
            \OCP\Util::addStyle(application: self::APP_ID, file: 'error-contrast');
        }

        // 4. Custom overrides — admin-defined token overrides, always loaded last.
        $customOverridesSvc = $serverContainer->get(CustomOverridesService::class);
        $customOverridesSvc->ensureExists();
        \OCP\Util::addStyle(application: self::APP_ID, file: 'custom-overrides');

        // 4.5 Custom fonts — admin-uploaded, self-hosted webfonts. Injected as
        // a <link rel="stylesheet"> (not \OCP\Util::addStyle(), because the
        // CSS is generated dynamically by FontController::css(), not a static
        // file under css/) AFTER the token-set styles so the font tokens win
        // the cascade, and only when at least one font is configured, so a
        // themed instance with zero uploaded fonts issues no extra request.
        if ($designSystemId !== 'none') {
            $fontService = $serverContainer->get(FontService::class);
            if ($fontService->hasFonts() === true) {
                $urlGenerator = $serverContainer->get(IURLGenerator::class);
                $cssUrl       = $urlGenerator->linkToRoute('nldesign.font.css').'?v='.$fontService->getRevision();
                \OCP\Util::addHeader(
                    tag: 'link',
                    attributes: [
                        'rel'  => 'stylesheet',
                        'href' => $cssUrl,
                    ]
                );
            }
        }

        // 5. Conditional stylesheets.
        if ($hideSlogan === true) {
            \OCP\Util::addStyle(application: self::APP_ID, file: 'hide-slogan');
        }

        if ($showMenuLabels === true) {
            \OCP\Util::addStyle(application: self::APP_ID, file: 'show-menu-labels');
        }
    }//end injectThemeCSS()

    /**
     * Resolve whether theming must be skipped for the request being rendered.
     *
     * Reads the request path, resolves the app id, and consults the exclusion
     * list. Wrapped in a try/catch so any resolution failure (CLI/occ, cron, an
     * unavailable request) fails open to themed.
     *
     * @param mixed $serverContainer The server container.
     *
     * @return bool True when nldesign style injection must be skipped.
     *
     * @spec openspec/changes/per-app-theming-toggle/tasks.md#task-2.1
     */
    private function isThemingDisabled($serverContainer): bool
    {
        try {
            $appTheming = $serverContainer->get(AppThemingService::class);
            $request    = $serverContainer->get(\OCP\IRequest::class);
            $appId      = $appTheming->resolveAppIdFromPath(pathInfo: $request->getPathInfo());

            return $appTheming->isThemingDisabledFor(appId: $appId);
        } catch (\Throwable $e) {
            // Fail open: presentation, not security — a broken resolve must not
            // strip theming everywhere, nor crash the boot path.
            return false;
        }
    }//end isThemingDisabled()
}//end class
