<?php

/**
 * NL Design Health Controller.
 *
 * Thin subclass of the OpenRegister AppHost engine's GenericHealthController
 * (ADR-040). nldesign is a pure NL Design theme app with NO OpenRegister
 * dependency, so the engine is a SOFT/optional dependency wired here ONLY for
 * the health endpoint: this class is autoloaded solely when the `/api/health`
 * route is dispatched, never at Nextcloud bootstrap, so Nextcloud still boots
 * and nldesign still themes when OpenRegister is absent (a request to
 * /api/health would then surface a degraded 5xx instead of fatalling the app).
 *
 * All logic — executing the declarative `observability.health` checks from
 * src/manifest.json and rendering the ADR-006 {status, app, version, checks}
 * envelope — lives in the engine. The checks declared in the manifest use only
 * the OR-independent primitives (database, filesystem, appEnabled); this app
 * never declares orAvailable or OR-object metrics. The public auth posture
 * (#[PublicPage] + #[NoCSRFRequired]) and the response contract are owned by
 * the engine's index() method and cannot drift here.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category  Controller
 * @package   OCA\NLDesign
 * @author    Conduction <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://codeberg.org/Conduction/nldesign
 *
 * @spec openspec/changes/adopt-apphost-2026-06-16/tasks.md#task-3
 */

declare(strict_types=1);

namespace OCA\NLDesign\Controller;

use OCA\OpenRegister\AppHost\Controller\GenericHealthController;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;

/**
 * NlDesign health endpoint — engine-owned, declarative.
 *
 * @psalm-suppress UndefinedClass — OpenRegister is a soft dependency; the
 *   parent class autoloads only on route dispatch, not at bootstrap.
 *
 * @spec openspec/changes/adopt-apphost-2026-06-16/tasks.md#task-3
 */
class HealthController extends GenericHealthController
{
    /**
     * GET /api/health — declarative health check (ADR-006).
     *
     * Delegates entirely to the engine. The auth posture is re-declared here
     * so it is statically visible at nldesign's route, but it matches and
     * cannot widen the engine contract.
     *
     * @return JSONResponse `{status, app, version, checks}`.
     *
     * @spec openspec/changes/adopt-apphost-2026-06-16/specs/prometheus-metrics/spec.md — Requirement: Health Check Endpoint
     */
    #[PublicPage]
    #[NoCSRFRequired]
    public function index(): JSONResponse
    {
        return parent::index();
    }//end index()
}//end class
