<?php

/**
 * NL Design themes as a shareable configuration type.
 *
 * This is the case that proves the federated-config standard is genuinely
 * storage-agnostic. Every other shareable type so far stores its configuration
 * as OpenRegister objects; NL Design's theme configuration lives in Nextcloud's
 * own `IConfig`, exported and imported by this app's `ConfigBundleService`. Yet
 * a theme shares exactly the same way a flow or a register does — because a type
 * owns its own (de)serialisation, and OpenRegister owns only the engine.
 *
 * So an admin can publish their NL Design theme (token sets, overrides, footer
 * and email theming, custom fonts) to GitHub and another instance can install
 * it, over the one fleet mechanism, with no migration onto OpenRegister objects.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Service
 * @package  OCA\NLDesign\Service\Config
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/specs/federated-config-sharing/spec.md
 */

declare(strict_types=1);

namespace OCA\NLDesign\Service\Config;

use OCA\NLDesign\Service\ConfigBundleService;
use OCA\OpenRegister\Service\Config\IShareableConfigType;
use Psr\Container\ContainerInterface;

/**
 * Shares an NL Design theme through the federated-config engine.
 *
 * @spec openspec/specs/federated-config-sharing/spec.md
 */
class NlDesignThemeShareableConfigType implements IShareableConfigType
{
    /**
     * Constructor.
     *
     * The `ConfigBundleService` is resolved lazily rather than injected: this
     * type is constructed whenever the shareable-type catalogue is read (which a
     * cross-app request does), but the bundle service — with its deep theming
     * dependency chain — is only needed when a theme is actually serialised or
     * installed. Resolving it eagerly would drag that whole chain into every
     * catalogue read, in a container context that may not autowire it.
     *
     * @param ContainerInterface $container Resolves the bundle service on demand.
     */
    public function __construct(private readonly ContainerInterface $container)
    {

    }//end __construct()

    /**
     * The theme export/import service, resolved on first use.
     *
     * @return ConfigBundleService The bundle service.
     */
    private function bundle(): ConfigBundleService
    {
        return $this->container->get(ConfigBundleService::class);

    }//end bundle()

    /**
     * The type id.
     *
     * @return string The id.
     *
     * @spec openspec/specs/federated-config-sharing/spec.md
     */
    public function getId(): string
    {
        return 'nldesign.theme';

    }//end getId()

    /**
     * The display name.
     *
     * @return string The name.
     *
     * @spec openspec/specs/federated-config-sharing/spec.md
     */
    public function getDisplayName(): string
    {
        return 'NL Design theme';

    }//end getDisplayName()

    /**
     * The discovery topic.
     *
     * @return string The topic.
     *
     * @spec openspec/specs/federated-config-sharing/spec.md
     */
    public function getTopic(): string
    {
        return 'nldesign-theme';

    }//end getTopic()

    /**
     * Package the instance's NL Design theme into a portable bundle.
     *
     * The selection is ignored: a theme is the instance's one theme
     * configuration, which `ConfigBundleService` already exports as a
     * self-describing, portable bundle (no secrets — theming carries none).
     *
     * @param array $selection Unused for themes.
     *
     * @return array `{type, version, bundle}`.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) - $selection is required by
     * the IShareableConfigType::serialise() contract; a theme has no selection.
     *
     * @spec openspec/specs/federated-config-sharing/spec.md
     */
    public function serialise(array $selection): array
    {
        return [
            'type'    => $this->getId(),
            'version' => '1.0',
            'bundle'  => $this->bundle()->export(),
        ];

    }//end serialise()

    /**
     * Apply a shared NL Design theme to this instance.
     *
     * @param array $bundle A bundle produced by this type.
     *
     * @return array `{installed: ['nldesign-theme'], import: {...}}`.
     *
     * @spec openspec/specs/federated-config-sharing/spec.md
     */
    public function deserialise(array $bundle): array
    {
        // `(array) $scalar` WRAPS rather than empties: `(array) 'x'` is `['x']`,
        // not `[]`. So a peer sending `{"bundle": "x"}` — or any scalar — reached
        // the importer as a one-element list instead of the empty default the
        // `?? []` was written to provide. The import path rejects it either way,
        // so this was never exploitable, but "malformed becomes empty" is what
        // this line is supposed to say and `(array)` does not say it.
        $payload = ($bundle['bundle'] ?? null);
        if (is_array($payload) === false) {
            $payload = [];
        }

        $result = $this->bundle()->import($payload, false);

        return [
            'installed' => ['nldesign-theme'],
            'import'    => $result,
        ];

    }//end deserialise()
}//end class
