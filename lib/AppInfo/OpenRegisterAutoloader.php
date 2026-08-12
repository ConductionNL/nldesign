<?php

/**
 * The ADR-040 OpenRegister autoload prelude, as one named, testable unit.
 *
 * @category  AppInfo
 * @package   OCA\NLDesign\AppInfo
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://github.com/ConductionNL/nldesign
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 */

declare(strict_types=1);

namespace OCA\NLDesign\AppInfo;

use OCP\App\IAppManager;
use OCP\Server;
use Throwable;

/**
 * Makes `OCA\OpenRegister\*` resolvable during this app's `register()`.
 *
 * WHY THIS EXISTS
 * ---------------
 * `Coordinator::registerApps()` walks the SORTED app list, calling
 * `OC_App::registerAutoloading()` and then `register()` for one app at a time.
 * `nldesign` sorts before `openregister`, so `Application::register()` runs
 * while the `OCA\OpenRegister\` PSR-4 prefix does not yet exist — on a
 * completely healthy instance with OpenRegister installed and enabled.
 *
 * Any `class_exists()` probe on an OpenRegister class in that window answers
 * FALSE and the branch it guards silently takes the wrong path, permanently
 * and with no error anywhere. See ADR-040.
 *
 * WHY IT IS A CLASS AND NOT FOUR INLINE LINES
 * -------------------------------------------
 * Three reasons, all practical. It is independently unit-testable, which four
 * lines inside `register()` are not — `Application` cannot be constructed in a
 * unit test without a server container. It confines the one unavoidable
 * static call to a single method instead of spreading it through bootstrap
 * code. And `ensure()` is deliberately an INSTANCE method so the call site in
 * `register()` is an ordinary method call: the PHPMD StaticAccess suppression
 * below then covers exactly one line in one class, and the rule keeps its
 * teeth in the bootstrap and everywhere else.
 *
 * @SuppressWarnings(PHPMD.StaticAccess) - `OC_App::registerAutoloading()` is
 * the ONLY way to pull another app's PSR-4 prefix into the running process and
 * Nextcloud exposes no instance API for it; ADR-040 prescribes this exact
 * call.
 *
 * @spec openspec/specs/federated-config-sharing/spec.md
 */
final class OpenRegisterAutoloader {

	/**
	 * The app whose autoloading this prelude pulls in.
	 *
	 * @var string
	 */
	public const OPENREGISTER_APP_ID = 'openregister';

	/**
	 * Register OpenRegister's PSR-4 prefix with the running process.
	 *
	 * `registerAutoloading()` touches only the autoloader and is idempotent,
	 * so calling it is safe regardless of which apps registered before this
	 * one. Every failure is swallowed on purpose: OpenRegister is a SOFT
	 * dependency, and "absent or disabled" is a supported state in which the
	 * caller's `class_exists()` guard then answers FALSE truthfully.
	 *
	 * The path is resolved into a local BEFORE the static call, deliberately.
	 * Written as one expression, PHP resolves `OC_App` first and raises its
	 * Error before `getAppPath()` is ever evaluated — so in any environment
	 * without the legacy class (a unit test, for one) the prelude would never
	 * even ask which path it wanted, and a test asserting that it does would
	 * fail for a reason unrelated to what it meant to check.
	 *
	 * @param IAppManager|null $appManager Injected for tests; resolved from the
	 *                                     server container when omitted.
	 *
	 * @return bool True when the prefix was registered, false when OpenRegister
	 *              is absent, disabled, or otherwise unreachable.
	 *
	 * @spec openspec/specs/federated-config-sharing/spec.md
	 */
	public function ensure(?IAppManager $appManager = null): bool {
		try {
			$manager = ($appManager ?? Server::get(IAppManager::class));
			$path = $manager->getAppPath(self::OPENREGISTER_APP_ID);
			\OC_App::registerAutoloading(self::OPENREGISTER_APP_ID, $path);
			return true;
		} catch (Throwable) {
			// OpenRegister absent, disabled, or the server container is not
			// available (unit tests). The caller degrades as designed.
			return false;
		}
	}//end ensure()
}//end class
