<?php

/**
 * NL Design Theme Preview Banner Service.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category  Service
 * @package   OCA\Thematiq
 * @author    Conduction <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://github.com/ConductionNL/thematiq
 *
 * @spec openspec/specs/theme-preview/spec.md
 */

declare(strict_types=1);

namespace OCA\Thematiq\Service;

use OCA\Thematiq\AppInfo\Application;
use OCP\AppFramework\Services\IInitialState;
use OCP\IUserSession;

/**
 * Injects the theme-preview banner for a user with an active preview.
 *
 * Split out of {@see CssInjectionService} so the banner's three collaborators
 * (preview resolution, the user session, and the initial-state channel) are not
 * carried by the stylesheet cascade, which needs none of them. The behaviour is
 * the former `CssInjectionService::injectPreviewBanner()` moved verbatim: same
 * fail-open contract, same initial-state payload, same asset pair.
 *
 * @spec openspec/specs/theme-preview/spec.md#requirement-preview-banner
 */
class ThemePreviewBannerService {

	/**
	 * Resolves whether the requesting user has an active theme preview.
	 *
	 * @var ThemePreviewService
	 */
	private ThemePreviewService $previewService;

	/**
	 * The user session, to resolve the previewing user.
	 *
	 * @var IUserSession
	 */
	private IUserSession $userSession;

	/**
	 * Provides the preview banner's initial state to the frontend.
	 *
	 * @var IInitialState
	 */
	private IInitialState $initialState;

	/**
	 * Constructor.
	 *
	 * @param ThemePreviewService $previewService The admin theme-preview resolver.
	 * @param IUserSession $userSession The user session.
	 * @param IInitialState $initialState Provides the banner's initial state.
	 */
	public function __construct(
		ThemePreviewService $previewService,
		IUserSession $userSession,
		IInitialState $initialState,
	) {
		$this->previewService = $previewService;
		$this->userSession = $userSession;
		$this->initialState = $initialState;
	}//end __construct()

	/**
	 * Load the preview banner assets and provide its initial state when the
	 * requesting user has an active theme preview.
	 *
	 * Fails open (renders nothing) on any error — a broken banner must never
	 * break the page it annotates.
	 *
	 * @param string $tokenSet The previewed token set id.
	 * @param array<string, mixed> $tokenSetMeta The token set metadata (for its display name).
	 *
	 * @return void
	 *
	 * @spec openspec/specs/theme-preview/spec.md#requirement-preview-banner
	 */
	public function inject(string $tokenSet, array $tokenSetMeta): void {
		try {
			$effective = $this->previewService->resolveEffectiveTokenSet(
				userSession: $this->userSession,
				activeTokenSet: $tokenSet
			);

			if ($effective['previewActive'] !== true) {
				return;
			}

			$this->emitPreviewAssets();
			$this->initialState->provideInitialState(
				'preview',
				[
					'tokenSet' => $tokenSet,
					'name' => ($tokenSetMeta['name'] ?? $tokenSet),
					'expiresAt' => ($effective['expiresAt'] ?? null),
				]
			);
		} catch (\Throwable $e) {
			return;
		}//end try
	}//end inject()

	/**
	 * Emit the preview banner's script and stylesheet.
	 *
	 * Isolated as a seam so unit tests can assert banner injection without a
	 * Nextcloud bootstrap (see the CssInjectionService emitStyle() rationale).
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) - \OCP\Util is the Nextcloud API for asset injection.
	 *
	 * @spec openspec/specs/theme-preview/spec.md#requirement-preview-banner
	 */
	protected function emitPreviewAssets(): void {
		\OCP\Util::addScript(application: Application::APP_ID, file: 'preview-banner');
		\OCP\Util::addStyle(application: Application::APP_ID, file: 'preview-banner');
	}//end emitPreviewAssets()
}//end class
