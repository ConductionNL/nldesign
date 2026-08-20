<?php

/**
 * Static-analysis stubs for OpenRegister's federated-config contract.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * OpenRegister is a SOFT / optional runtime dependency of this app: the
 * federated-config listener is registered only behind a `class_exists()` guard
 * in lib/AppInfo/Application.php, so an instance without OpenRegister (or with
 * an older one) still boots. During static analysis OpenRegister is absent, so
 * phpstan/psalm cannot resolve the contract that
 * lib/Service/Config/NlDesignThemeShareableConfigType.php implements and
 * lib/Listener/ShareableConfigTypeListener.php dispatches on.
 *
 * These declaration-only stubs give the analysers that contract WITHOUT
 * blanket-excluding whole files (the coarser tool the app uses for
 * HealthController's `extends unknown class`). They are wired into analysis
 * ONLY — phpstan.neon `scanDirectories` and psalm.xml `<stubs>` — and are
 * never autoloaded at runtime (composer PSR-4 maps only `OCA\NLDesign\` → lib/,
 * and this file is under stubs/, outside the test suite), so the real
 * OCA\OpenRegister classes are used whenever OpenRegister is enabled.
 *
 * Signatures mirror openregister/lib/Service/Config/{IShareableConfigType,
 * RegisterShareableConfigTypesEvent}.php — keep them in sync if that contract
 * changes.
 */

declare(strict_types=1);

namespace OCA\OpenRegister\Service\Config;

use OCP\EventDispatcher\Event;

interface IShareableConfigType
{
    public function getId(): string;

    public function getDisplayName(): string;

    public function getTopic(): string;

    public function serialise(array $selection): array;

    public function deserialise(array $bundle): array;
}

class RegisterShareableConfigTypesEvent extends Event
{
    public function registerType(IShareableConfigType $type): void
    {
    }
}
