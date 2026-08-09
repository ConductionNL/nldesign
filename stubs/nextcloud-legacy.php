<?php

/**
 * Static-analysis stub for Nextcloud's legacy global `OC_App`.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * `OC_App::registerAutoloading()` is the ONLY way to pull another app's PSR-4
 * prefix into the current process, and lib/AppInfo/Application.php needs it in
 * `register()` — see ADR-040. It lives in `lib/private/legacy/OC_App.php` in
 * the server, which is not part of the `nextcloud/ocp` public-API package the
 * analysers see, so both phpstan and psalm resolve it to nothing. phpstan is
 * satisfied by `scanDirectories: stubs`; psalm reported
 * `UndefinedClass - Class, interface or enum named OC_App does not exist`.
 *
 * A declaration-only stub is the right tool here rather than an issue
 * suppression: a suppression would hide a genuine typo in the call as
 * readily as it hides this, whereas a stub makes the analysers CHECK the
 * signature. Wired into analysis only — phpstan.neon `scanDirectories` and
 * psalm.xml `<stubs>` — and never autoloaded at runtime, so the server's real
 * class is always the one that executes.
 *
 * Signature mirrors `lib/private/legacy/OC_App.php`; keep it in sync if the
 * server changes it.
 *
 * @psalm-suppress UnrecognizedStatement
 */

declare(strict_types=1);

// phpcs:disable
class OC_App
{
    /**
     * Register an app's composer/PSR-4 autoloading with the current process.
     *
     * @param string $appId The app id whose prefix to register.
     * @param string $path  Absolute path to the app directory.
     * @param bool   $force Re-register even if already done.
     *
     * @return bool
     */
    public static function registerAutoloading(string $appId, string $path, bool $force = false): bool
    {
        return true;
    }
}
