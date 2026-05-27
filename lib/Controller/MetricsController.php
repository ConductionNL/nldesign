<?php

/**
 * NL Design Metrics Controller.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category Controller
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://github.com/ConductionNL/nldesign
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-4
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-5
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-6
 */

declare(strict_types=1);

namespace OCA\NLDesign\Controller;

use OCA\NLDesign\AppInfo\Application;
use OCA\NLDesign\Service\CustomOverridesService;
use OCA\NLDesign\Service\TokenSetService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\TextPlainResponse;
use OCP\IConfig;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Controller for exposing Prometheus metrics.
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-4
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-5
 * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-6
 */
class MetricsController extends Controller
{
    /**
     * Constructor.
     *
     * @param string                 $appName         The app name.
     * @param IRequest               $request         The request object.
     * @param IConfig                $config          The config service.
     * @param TokenSetService        $tokenSetService The token set service.
     * @param CustomOverridesService $overridesSvc    The custom overrides service.
     * @param LoggerInterface        $logger          Logger for error reporting.
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly IConfig $config,
        private readonly TokenSetService $tokenSetService,
        private readonly CustomOverridesService $overridesSvc,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()

    /**
     * Expose Prometheus metrics.
     *
     * @return TextPlainResponse Plain text response with Prometheus metrics.
     *
     * @NoCSRFRequired
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-4
     */
    #[PublicPage]
    public function index(): TextPlainResponse
    {
        $lines = [];

        $appVersion = $this->config->getAppValue(Application::APP_ID, 'installed_version', '0.0.0');
        $phpVersion = PHP_VERSION;
        $ncVersion  = $this->config->getSystemValueString('version', '0.0.0');

        // Info gauge.
        $lines[] = '# HELP nldesign_info Application information';
        $lines[] = '# TYPE nldesign_info gauge';
        $lines[] = 'nldesign_info{version="'.$appVersion.'",php_version="'.$phpVersion.'",nextcloud_version="'.$ncVersion.'"} 1';

        // Up gauge.
        $lines[] = '# HELP nldesign_up Whether the application is up';
        $lines[] = '# TYPE nldesign_up gauge';
        $lines[] = 'nldesign_up 1';

        // Token sets total.
        $this->collectTokenSetMetrics(lines: $lines);

        // Custom overrides total.
        $this->collectOverrideMetrics(lines: $lines);

        // Theming syncs counter.
        $syncsTotal = (int) $this->config->getAppValue(Application::APP_ID, 'theming_syncs_total', '0');
        $lines[]    = '# HELP nldesign_theming_syncs_total Total theming sync operations';
        $lines[]    = '# TYPE nldesign_theming_syncs_total counter';
        $lines[]    = 'nldesign_theming_syncs_total '.$syncsTotal;

        $body     = implode("\n", $lines)."\n";
        $response = new TextPlainResponse($body);
        $response->addHeader('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');

        return $response;

    }//end index()

    /**
     * Collect token set metrics.
     *
     * @param array $lines Reference to the metrics output lines.
     *
     * @return void
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-5
     */
    private function collectTokenSetMetrics(array &$lines): void
    {
        try {
            $tokenSets     = $this->tokenSetService->getAvailableTokenSets();
            $tokenSetCount = count($tokenSets);

            $lines[] = '# HELP nldesign_token_sets_total Total number of available token sets';
            $lines[] = '# TYPE nldesign_token_sets_total gauge';
            $lines[] = 'nldesign_token_sets_total '.$tokenSetCount;

            // Active token set.
            $activeSet = $this->config->getAppValue(Application::APP_ID, 'token_set', 'rijkshuisstijl');
            $lines[]   = '# HELP nldesign_active_token_set Currently active token set';
            $lines[]   = '# TYPE nldesign_active_token_set gauge';
            $lines[]   = 'nldesign_active_token_set{name="'.$activeSet.'"} 1';
        } catch (\Exception $e) {
            $this->logger->warning('Could not collect token set metrics', ['exception' => $e->getMessage()]);
            $lines[] = '# HELP nldesign_token_sets_total Total number of available token sets';
            $lines[] = '# TYPE nldesign_token_sets_total gauge';
            $lines[] = 'nldesign_token_sets_total 0';
        }

    }//end collectTokenSetMetrics()

    /**
     * Collect custom overrides metrics.
     *
     * @param array $lines Reference to the metrics output lines.
     *
     * @return void
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-nldesign/tasks.md#task-6
     */
    private function collectOverrideMetrics(array &$lines): void
    {
        try {
            $overrides     = $this->overridesSvc->read();
            $overrideCount = count($overrides);

            $lines[] = '# HELP nldesign_custom_overrides_total Total custom CSS overrides';
            $lines[] = '# TYPE nldesign_custom_overrides_total gauge';
            $lines[] = 'nldesign_custom_overrides_total '.$overrideCount;
        } catch (\Exception $e) {
            $this->logger->warning('Could not collect override metrics', ['exception' => $e->getMessage()]);
            $lines[] = '# HELP nldesign_custom_overrides_total Total custom CSS overrides';
            $lines[] = '# TYPE nldesign_custom_overrides_total gauge';
            $lines[] = 'nldesign_custom_overrides_total 0';
        }

    }//end collectOverrideMetrics()
}//end class
