<?php

declare(strict_types=1);

define('PHPUNIT_RUN', 1);

require_once __DIR__ . '/../vendor/autoload.php';

// Register the OCP/NCU namespaces from the nextcloud/ocp dev dependency so that
// PHPUnit can mock OCP interfaces (e.g. OCP\IConfig, OCP\App\IAppManager) when
// no full Nextcloud server is present (standalone container runs). The
// nextcloud/ocp package ships no autoload entry of its own, so we register it
// manually — mirroring the fleet-standard bootstrap (decidesk, larpingapp, …).
// Registered as a fallback autoloader (appended) so a real Nextcloud
// environment's own OCP classes always win when present.
spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'OCP\\' => __DIR__ . '/../vendor/nextcloud/ocp/OCP/',
        'NCU\\' => __DIR__ . '/../vendor/nextcloud/ocp/NCU/',
    ];
    foreach ($prefixes as $prefix => $baseDir) {
        if (str_starts_with($class, $prefix) === false) {
            continue;
        }

        $path = $baseDir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path) === true) {
            require_once $path;
        }

        return;
    }
});

if (!defined('OC_CONSOLE')) {
    if (file_exists(__DIR__ . '/../../../lib/base.php')) {
        require_once __DIR__ . '/../../../lib/base.php';
    }

    if (file_exists(__DIR__ . '/../../../tests/autoload.php')) {
        require_once __DIR__ . '/../../../tests/autoload.php';
    }

    if (class_exists('\OC_App')) {
        \OC_App::loadApps();
        \OC_App::loadApp('nldesign');
        OC_Hook::clear();
    }
}
