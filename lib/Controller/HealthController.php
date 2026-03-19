<?php

/**
 * NL Design Health Controller.
 *
 * @category Controller
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0-or-later
 * @link     https://github.com/ConductionNL/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Controller for health check endpoint.
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
            if ($tokenSet !== '') {
                $checks['configuration'] = 'ok';
            } else {
                $checks['configuration'] = 'degraded';
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
