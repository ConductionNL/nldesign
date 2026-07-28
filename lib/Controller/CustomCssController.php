<?php

/**
 * NL Design Freeform Custom CSS Controller.
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
 * @spec openspec/specs/custom-css-freeform/spec.md
 * @spec openspec/specs/theming-audit/spec.md#requirement-complete-call-site-coverage
 */

declare(strict_types=1);

namespace OCA\NLDesign\Controller;

use OCA\NLDesign\Service\CustomCssService;
use OCA\NLDesign\Service\ThemingAuditService;
use OCA\NLDesign\Settings\Admin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use RuntimeException;

/**
 * Read and write the admin-authored freeform CSS layer.
 *
 * Kept separate from OverridesController on purpose: the two capabilities
 * have different trust profiles and different audit actions, and keeping the
 * write paths apart means a change to one cannot silently widen the other.
 *
 * Every method carries #[AuthorizedAdminSetting(Admin::class)] — the same
 * posture as the token-override endpoints. Note that Admin implements
 * IDelegatedSettings, so a DELEGATED admin can reach these; that is precisely
 * why the payload is sanitised rather than trusted, and why every write is
 * audit-logged.
 *
 * @spec openspec/specs/custom-css-freeform/spec.md
 */
class CustomCssController extends Controller
{

    /**
     * The freeform CSS persistence service.
     *
     * @var CustomCssService
     */
    private CustomCssService $customCssService;

    /**
     * The theming audit log.
     *
     * @var ThemingAuditService
     */
    private ThemingAuditService $auditService;

    /**
     * Constructor.
     *
     * @param string              $appName          The app name.
     * @param IRequest            $request          The request.
     * @param CustomCssService    $customCssService The freeform CSS service.
     * @param ThemingAuditService $auditService     The theming audit log.
     */
    public function __construct(
        string $appName,
        IRequest $request,
        CustomCssService $customCssService,
        ThemingAuditService $auditService
    ) {
        parent::__construct(appName: $appName, request: $request);
        $this->customCssService = $customCssService;
        $this->auditService     = $auditService;

    }//end __construct()

    /**
     * Return the stored freeform CSS and whether the layer is enabled.
     *
     * @return JSONResponse The current state.
     *
     * @spec openspec/specs/custom-css-freeform/spec.md
     */
    #[AuthorizedAdminSetting(Admin::class)]
    public function getCustomCss(): JSONResponse
    {
        return new JSONResponse(
            [
                'enabled'  => $this->customCssService->isEnabled(),
                'css'      => $this->customCssService->read(),
                'maxBytes' => \OCA\NLDesign\Service\CustomCssValidator::MAX_BYTES,
            ]
        );

    }//end getCustomCss()

    /**
     * Validate and persist freeform CSS, and set the enable flag.
     *
     * Validation is fail-closed: if ANY rule is violated nothing is written
     * and every reason is returned, so the administrator can see and fix the
     * whole list rather than discovering problems one at a time.
     *
     * @return JSONResponse Status, or 422 with the list of validation errors.
     *
     * @spec openspec/specs/custom-css-freeform/spec.md
     * @spec openspec/specs/theming-audit/spec.md#requirement-complete-call-site-coverage
     */
    #[AuthorizedAdminSetting(Admin::class)]
    public function setCustomCss(): JSONResponse
    {
        $params = $this->request->getParams();
        $css    = ($params['css'] ?? '');

        if (is_string($css) === false) {
            return new JSONResponse(['error' => 'css must be a string'], 400);
        }

        $before = $this->customCssService->read();

        try {
            $errors = $this->customCssService->write(css: $css);
        } catch (RuntimeException $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }

        if (empty($errors) === false) {
            // Nothing was written — report every reason at once.
            $this->auditService->log(
                action: 'custom_css_rejected',
                context: ['errors' => $errors]
            );

            return new JSONResponse(['errors' => $errors], 422);
        }

        if (array_key_exists('enabled', $params) === true) {
            $this->customCssService->setEnabled(enabled: ((bool) $params['enabled']));
        }

        $this->auditService->log(
            action: 'custom_css_written',
            context: [
                'oldBytes' => strlen($before),
                'newBytes' => strlen($css),
                'enabled'  => $this->customCssService->isEnabled(),
            ]
        );

        return new JSONResponse(
            [
                'status'  => 'ok',
                'bytes'   => strlen($css),
                'enabled' => $this->customCssService->isEnabled(),
            ]
        );

    }//end setCustomCss()
}//end class
