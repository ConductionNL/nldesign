<?php

/**
 * NL Design Health Controller.
 *
 * @category Controller
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0-or-later
 * @link     https://github.com/ConductionNL/nldesign
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-3
 */

declare(strict_types=1);

namespace OCA\NLDesign\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
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
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);

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
    public function index(): JSONResponse
    {
        $checks = [];
        $status = 'ok';

        // NL Design is CSS-only, no database tables.
        // Check that the token set config is accessible.
        try {
            $config   = \OCP\Server::get(\OCP\IConfig::class);
            $tokenSet = $config->getAppValue('nldesign', 'token_set', 'rijkshuisstijl');
            $checks['configuration'] = ($tokenSet !== '') ? 'ok' : 'degraded';
        } catch (\Exception $e) {
            $checks['configuration'] = 'error';
            $status                   = 'error';
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
