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
 * @spec openspec/changes/render-event-injection/tasks.md#task-3.1
 */

declare(strict_types=1);

namespace OCA\NLDesign\AppInfo;

use OCA\NLDesign\Capabilities;
use OCA\NLDesign\Listener\ThemeInjectionListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeLoginTemplateRenderedEvent;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;

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
     * `ThemeInjectionListener` is registered for both `BeforeTemplateRenderedEvent`
     * and `BeforeLoginTemplateRenderedEvent` — style injection is event-driven,
     * not boot-driven (see `openspec/changes/render-event-injection`).
     * `registerEventListener()` registers a lazy service: the listener (and its
     * whole service graph — config, design system, custom overrides, fonts) is
     * only instantiated when one of these two events actually fires, so
     * requests that render no template (WebDAV, OCS/API, cron) never pay for
     * it.
     *
     * @param IRegistrationContext $context The registration context.
     *
     * @return void
     *
     * @spec openspec/changes/adopt-apphost-2026-06-16/tasks.md#task-2
     * @spec openspec/changes/render-event-injection/tasks.md#task-3.1
     * @spec openspec/specs/theming-capability/spec.md
     */
    public function register(IRegistrationContext $context): void
    {
        // Load the app's composer autoloader so this app's classes are resolvable
        // process-wide, not only inside this app's own container — required so a
        // cross-app event listener (e.g. the OpenRegister federated-config type)
        // can be constructed by another app's dispatcher. Mirrors hermiq.
        include_once __DIR__.'/../../vendor/autoload.php';

        // Health endpoint served by the thin Controller\HealthController
        // subclass of the AppHost engine — no explicit registration needed.
        // Public huisstijl capability — see lib/Capabilities.php.
        $context->registerCapability(Capabilities::class);

        // Event-driven CSS injection — see lib/Listener/ThemeInjectionListener.php.
        $context->registerEventListener(BeforeTemplateRenderedEvent::class, ThemeInjectionListener::class);
        $context->registerEventListener(BeforeLoginTemplateRenderedEvent::class, ThemeInjectionListener::class);

        // Federated configuration sharing (openregister): contribute the NL Design
        // theme as a shareable config type so a theme can be published to and
        // installed from GitHub over OpenRegister's one fleet mechanism. Guarded
        // on the event class so an instance without OpenRegister still boots.
        if (class_exists(\OCA\OpenRegister\Service\Config\RegisterShareableConfigTypesEvent::class) === true) {
            $context->registerEventListener(
                \OCA\OpenRegister\Service\Config\RegisterShareableConfigTypesEvent::class,
                \OCA\NLDesign\Listener\ShareableConfigTypeListener::class
            );
        }
    }//end register()

    /**
     * Boot the application.
     *
     * Intentionally a no-op: `IBootstrap` requires the method, but style
     * injection is entirely event-driven since
     * `openspec/changes/render-event-injection` — see
     * `lib/Listener/ThemeInjectionListener.php` and
     * `lib/Service/CssInjectionService.php`.
     *
     * @param IBootContext $context The boot context (unused — kept only to satisfy the IBootstrap signature).
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) - IBootstrap::boot() mandates this exact signature
     *
     * @spec openspec/changes/render-event-injection/tasks.md#task-3.2
     */
    public function boot(IBootContext $context): void
    {
    }//end boot()
}//end class
