<?php

/**
 * Unit tests for the ADR-040 OpenRegister autoload prelude.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @spec openspec/specs/federated-config-sharing/spec.md
 */

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\AppInfo;

use OCA\NLDesign\AppInfo\OpenRegisterAutoloader;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * `Application::register()` runs before OpenRegister's PSR-4 prefix exists,
 * because the app coordinator walks a SORTED list and `nldesign` sorts before
 * `openregister`. Everything downstream of that — the `class_exists()` guard,
 * the federated-config listener, whether `nldesign.theme` reaches the
 * catalogue at all — depends on this prelude running and, crucially, on it
 * NEVER THROWING when OpenRegister is absent.
 *
 * That second half is what these tests pin. A prelude that throws would abort
 * `register()`, and the coordinator catches the Throwable, logs an emergency
 * and continues — leaving the app enabled with half its wiring missing and
 * nothing in the UI to say so.
 */
class OpenRegisterAutoloaderTest extends TestCase {

	/**
	 * The app id must be the real one, not a near-miss. `getAppPath()` throws
	 * for an unknown id, which this class swallows — so a typo here would
	 * degrade silently and forever, which is the exact failure mode the class
	 * exists to prevent.
	 */
	public function testAppIdIsOpenregister(): void {
		$this->assertSame('openregister', OpenRegisterAutoloader::OPENREGISTER_APP_ID);
	}//end testAppIdIsOpenregister()

	/**
	 * An absent or disabled OpenRegister must produce `false`, not an
	 * exception. `getAppPath()` throwing is the normal, supported signal that
	 * the app is not installed.
	 */
	public function testAbsentOpenRegisterDegradesToFalseWithoutThrowing(): void {
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getAppPath')
			->willThrowException(new RuntimeException('App openregister not found'));

		$this->assertFalse((new OpenRegisterAutoloader())->ensure($appManager));
	}//end testAbsentOpenRegisterDegradesToFalseWithoutThrowing()

	/**
	 * The prelude asks for the path of `openregister` specifically — the whole
	 * point is which app's autoloader gets pulled in.
	 */
	public function testAsksTheAppManagerForOpenregistersPath(): void {
		$appManager = $this->createMock(IAppManager::class);
		$appManager->expects($this->once())
			->method('getAppPath')
			->with('openregister')
			->willThrowException(new RuntimeException('not installed here'));

		(new OpenRegisterAutoloader())->ensure($appManager);
	}//end testAsksTheAppManagerForOpenregistersPath()

	/**
	 * With no argument the prelude resolves the app manager from the server
	 * container. Under PHPUnit there is no container, so this exercises the
	 * same swallow-and-degrade path a real absent OpenRegister takes — and
	 * proves the no-argument call used by `Application::register()` cannot
	 * throw either.
	 */
	public function testNoArgumentCallNeverThrows(): void {
		$this->assertIsBool((new OpenRegisterAutoloader())->ensure());
	}//end testNoArgumentCallNeverThrows()
}//end class
