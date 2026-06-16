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

use OCA\NLDesign\Service\AppThemingService;
use OCA\NLDesign\Service\CustomOverridesService;
use OCA\NLDesign\Service\DesignSystemService;
use OCA\NLDesign\Themes\NLDesignTheme;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use Psr\Container\ContainerInterface;

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
     * Fully-qualified OpenRegister AppHost engine class names, kept as plain
     * strings so referencing this map never autoloads an OpenRegister class.
     * They are resolved through the container only inside the health-controller
     * closure below, i.e. when the `/api/health` route is actually dispatched.
     */
    private const GENERIC_HEALTH_CONTROLLER     = 'OCA\\OpenRegister\\AppHost\\Controller\\GenericHealthController';
    private const OBSERVABILITY_MANIFEST_LOADER = 'OCA\\OpenRegister\\AppHost\\Observability\\ManifestLoader';
    private const OBSERVABILITY_EXECUTOR        = 'OCA\\OpenRegister\\AppHost\\Observability\\HealthCheckExecutor';

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
     * Adopts the OpenRegister AppHost observability engine (ADR-040) for the
     * `/api/health` endpoint only. nldesign is a pure NL Design theme app with
     * NO OpenRegister dependency, so OpenRegister is a SOFT/optional dependency
     * here: the engine class names are referenced only as strings inside the
     * closure, which the leaf DI container executes lazily on route dispatch.
     * Therefore, when OpenRegister is disabled or absent, this method (and
     * Nextcloud bootstrap) complete without loading a single OpenRegister class;
     * the first request to `/api/health` would surface a degraded 5xx instead
     * of fatalling the app. The declarative checks live in `src/manifest.json`
     * and use only the OR-independent primitives (database, filesystem,
     * appEnabled) — never orAvailable, and no OR-object metrics.
     *
     * @param IRegistrationContext $context The registration context.
     *
     * @return void
     *
     * @spec openspec/changes/adopt-apphost-2026-06-16/tasks.md#task-2
     */
    public function register(IRegistrationContext $context): void
    {
        // Lazily alias nldesign's HealthController service name to the AppHost
        // GenericHealthController. The route name in appinfo/routes.php
        // (health#index) and the URL (/api/health) are unchanged; only the
        // resolved class changes. Auth posture (#[PublicPage]) and the ADR-006
        // {status, app, version, checks} contract are owned by the engine.
        $context->registerService(
            'OCA\\NLDesign\\Controller\\HealthController',
            static function (ContainerInterface $c): object {
                $class = self::GENERIC_HEALTH_CONTROLLER;
                return new $class(
                    appName: self::APP_ID,
                    request: $c->get('OCP\\IRequest'),
                    manifestLoader: $c->get(self::OBSERVABILITY_MANIFEST_LOADER),
                    executor: $c->get(self::OBSERVABILITY_EXECUTOR)
                );
            }
        );
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

        $config         = $serverContainer->getConfig();
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
        }

        // 4. Custom overrides — admin-defined token overrides, always loaded last.
        $customOverridesSvc = $serverContainer->get(CustomOverridesService::class);
        $customOverridesSvc->ensureExists();
        \OCP\Util::addStyle(application: self::APP_ID, file: 'custom-overrides');

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
