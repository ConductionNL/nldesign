<?php

declare(strict_types=1);

define('PHPUNIT_RUN', 1);

require_once __DIR__ . '/../vendor/autoload.php';

// vendor/ is a symlink into a shared checkout (e.g. a git worktree's vendor/
// points at the main checkout so `composer install` never has to run twice).
// Composer's generated autoload_psr4.php computes its base directory from
// __DIR__, which PHP resolves through that symlink — so without this fix the
// OCA\NLDesign\ prefix would silently load classes from the SHARED checkout
// instead of THIS checkout, masking any change made only here (new files
// would 404 as "class not found"; edited files would test stale code). Force
// the prefix back onto this checkout's own lib/ so tests always exercise the
// code actually present on disk here.
//
// `require_once` above may return `true` rather than the loader (PHPUnit's own
// bootstrap already required this exact file once), so the already-registered
// ClassLoader singleton is located via spl_autoload_functions() instead of
// trusting the require_once return value.
//
// Fixing the PSR-4 prefix alone is not enough: `optimize-autoloader` (see
// composer.json) also bakes every already-known OCA\NLDesign\ class into a
// classMap with an absolute path into the SHARED checkout, and the classMap
// lookup wins over PSR-4 in Composer's ClassLoader::findFile(). Strip those
// entries via reflection so every nldesign class — new AND edited — falls
// through to the (now-corrected) PSR-4 rule and loads from THIS checkout.
foreach (spl_autoload_functions() as $autoloadFunction) {
    if (is_array($autoloadFunction) === false || ($autoloadFunction[0] instanceof \Composer\Autoload\ClassLoader) === false) {
        continue;
    }

    $loader = $autoloadFunction[0];
    $loader->setPsr4('OCA\\NLDesign\\', [__DIR__ . '/../lib']);

    $classMapProperty = (new \ReflectionClass($loader))->getProperty('classMap');
    $classMapProperty->setAccessible(true);
    $classMap = $classMapProperty->getValue($loader);
    foreach (array_keys($classMap) as $mappedClass) {
        if (str_starts_with($mappedClass, 'OCA\\NLDesign\\') === true) {
            unset($classMap[$mappedClass]);
        }
    }

    $classMapProperty->setValue($loader, $classMap);
    break;
}

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

// Server-internal `OC\` classes (e.g. OC\Mail\EMailTemplate, extended by
// lib/Mail/NLDesignEMailTemplate.php) are not part of the nextcloud/ocp dev
// dependency — there is no public stub package for private-namespace code.
// When the full Nextcloud server IS mounted (real environment, or a
// standalone container with the server's lib/private/ bind-mounted at
// /var/www/html/lib/private per the fleet-standard phpunit invocation), fall
// back to loading the class directly from there. Absent that mount this is a
// silent no-op — mirrors the OCP/NCU registration above, and any test that
// actually needs the class simply fails with a clear "class not found"
// rather than the bootstrap itself failing.
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'OC\\') === false) {
        return;
    }

    $path = '/var/www/html/lib/private/' . str_replace('\\', '/', substr($class, strlen('OC\\'))) . '.php';
    if (is_file($path) === true) {
        require_once $path;
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
