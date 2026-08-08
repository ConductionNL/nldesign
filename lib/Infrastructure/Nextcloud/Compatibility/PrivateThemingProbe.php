<?php

/**
 * Read-only probe for private Nextcloud Theming implementation shapes.
 *
 * @category Infrastructure
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/ConductionNL/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Infrastructure\Nextcloud\Compatibility;

use OCA\Theming\ImageManager;
use OCA\Theming\ThemingDefaults;

/**
 * Inspect private lifecycle methods without resolving services or mutating
 * Theming state.
 *
 * @internal This prototype is intentionally absent from application wiring.
 */
final class PrivateThemingProbe
{
    /**
     * Inspect the minimum private method shape researched for a future driver.
     *
     * Method presence is not compatibility evidence. A future adapter still
     * needs reflected signatures and packaged lifecycle tests for an exact
     * Nextcloud/Theming build before it may expose any write capability.
     *
     * @return array{
     *     structurally_available: bool,
     *     requirements: array<string, bool>
     * }
     */
    public function inspect(): array
    {
        $requirements = [
            'ThemingDefaults::set'      => $this->hasMethod(
                className: ThemingDefaults::class,
                method: 'set'
            ),
            'ThemingDefaults::undo'     => $this->hasMethod(
                className: ThemingDefaults::class,
                method: 'undo'
            ),
            'ImageManager::updateImage' => $this->hasMethod(
                className: ImageManager::class,
                method: 'updateImage'
            ),
            'ImageManager::hasImage'    => $this->hasMethod(
                className: ImageManager::class,
                method: 'hasImage'
            ),
        ];

        return [
            'structurally_available' => in_array(false, $requirements, true) === false,
            'requirements'           => $requirements,
        ];
    }//end inspect()

    /**
     * Check one private class/method without constructing the service.
     *
     * @param class-string $className Private implementation class.
     * @param string       $method    Required method.
     *
     * @return bool Whether the runtime exposes the method.
     */
    private function hasMethod(string $className, string $method): bool
    {
        return class_exists($className) === true
            && method_exists($className, $method) === true;
    }//end hasMethod()
}//end class
