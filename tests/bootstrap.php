<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;

/** @var ClassLoader $autoloader */
$autoloader = require dirname(__DIR__).'/vendor/autoload.php';
$autoloader->addPsr4('OCP\\', dirname(__DIR__).'/vendor/nextcloud/ocp/OCP/');
$autoloader->addPsr4('NCU\\', dirname(__DIR__).'/vendor/nextcloud/ocp/NCU/');

if (class_exists('OC_Util') === false) {
    require dirname(__DIR__).'/stubs/oc-util.php';
}
