<?php

/**
 * Installed profile stylesheet controller.
 *
 * @category Controller
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Controller;

use OCA\NLDesign\Service\TokenSetService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\IRequest;

/**
 * Serve only digest-addressed CSS regenerated and verified from app data.
 */
final class StylesheetController extends Controller
{
    private const HASH_PATTERN = '/^[a-f0-9]{64}$/';

    /**
     * Constructor.
     *
     * @param string          $appName  App id.
     * @param IRequest        $request  Request object.
     * @param TokenSetService $profiles Composite profile catalogue.
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private TokenSetService $profiles
    ) {
        parent::__construct(appName: $appName, request: $request);
    }//end __construct()

    /**
     * Return one immutable installed profile stylesheet.
     *
     * @param string $profileId      Stable profile identifier.
     * @param string $profileVersion Exact profile version.
     * @param string $contentHash    Expected content digest.
     *
     * @return DataDisplayResponse<Http::STATUS_*, array<string, mixed>> Stylesheet response.
     */
    #[PublicPage]
    #[NoCSRFRequired]
    public function getProfile(
        string $profileId,
        string $profileVersion,
        string $contentHash
    ): DataDisplayResponse {
        $css = null;
        if (preg_match(self::HASH_PATTERN, $contentHash) === 1) {
            $css = $this->profiles->getInstalledStylesheet(
                profileId: $profileId,
                profileVersion: $profileVersion,
                contentHash: $contentHash
            );
        }

        if ($css === null) {
            return $this->createResponse(
                data: '',
                statusCode: Http::STATUS_NOT_FOUND,
                headers: [
                    'Content-Type'           => 'text/css; charset=utf-8',
                    'X-Content-Type-Options' => 'nosniff',
                ]
            );
        }

        return $this->createResponse(
            data: $css,
            statusCode: Http::STATUS_OK,
            headers: [
                'Content-Type'           => 'text/css; charset=utf-8',
                'Cache-Control'          => 'public, max-age=31536000, immutable',
                'ETag'                   => '"'.$contentHash.'"',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }//end getProfile()

    /**
     * Keep Nextcloud's invariant response templates broad at this boundary.
     *
     * @param string               $data       Stylesheet body.
     * @param Http::STATUS_*       $statusCode HTTP status.
     * @param array<string, mixed> $headers    Response headers.
     *
     * @return DataDisplayResponse<Http::STATUS_*, array<string, mixed>> Response.
     */
    private function createResponse(string $data, int $statusCode, array $headers): DataDisplayResponse
    {
        return new DataDisplayResponse(
            data: $data,
            statusCode: $statusCode,
            headers: $headers
        );
    }//end createResponse()
}//end class
