<?php

/**
 * NL Design Health Controller.
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
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-3
 */

declare(strict_types=1);

namespace OCA\NLDesign\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Controller for health check endpoint.
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-3
 */
class HealthController extends Controller
{
    /**
     * Constructor.
     *
     * @param string          $appName The app name.
     * @param IRequest        $request The request object.
     * @param LoggerInterface $logger  Logger for error reporting.
     * @param IConfig         $config  The Nextcloud config service.
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly LoggerInterface $logger,
        private readonly IConfig $config
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()

    /**
     * Return health check status.
     *
     * @return JSONResponse JSON response with health status and checks.
     *
     * @NoCSRFRequired
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-3
     */
    #[PublicPage]
    public function index(): JSONResponse
    {
        $checks = [];
        $status = 'ok';

        // NL Design is CSS-only, no database tables.
        // Check that the token set config is accessible.
        try {
            $tokenSet = $this->config->getAppValue('nldesign', 'token_set', 'rijkshuisstijl');
            $checks['configuration'] = 'degraded';
            if ($tokenSet !== '') {
                $checks['configuration'] = 'ok';
            }
        } catch (\Exception $e) {
            $checks['configuration'] = 'error';
            $status = 'error';
            $this->logger->error('Health check: configuration failed', ['exception' => $e->getMessage()]);
        }

        return new JSONResponse(
            [
                'status' => $status,
                'checks' => $checks,
            ]
        );

    }//end index()
}//end class
