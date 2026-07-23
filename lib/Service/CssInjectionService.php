<?php

/**
 * NL Design CSS Injection Service.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category  Service
 * @package   OCA\NLDesign
 * @author    Conduction <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://codeberg.org/Conduction/nldesign
 *
 * @spec openspec/specs/css-architecture/spec.md
 * @spec openspec/specs/custom-fonts/spec.md
 */

declare(strict_types=1);

namespace OCA\NLDesign\Service;

use OCA\NLDesign\AppInfo\Application;
use OCP\IConfig;
use OCP\IURLGenerator;

/**
 * Injects the nldesign stylesheet cascade for a themed render context.
 *
 * The body of {@see inject()} is the former `Application::injectThemeCSS()`
 * moved verbatim (css-architecture layers 1-8 + conditionals, including the
 * custom-font stylesheet link added by the custom-font-upload change), now
 * driven by render events instead of `Application::boot()` — see
 * `openspec/changes/render-event-injection`. The only behavior addition is
 * context gating via the `themed_contexts` appconfig key; every stylesheet,
 * its cascade position, and its loading condition are otherwise unchanged.
 *
 * The two `\OCP\Util::addStyle()` / `\OCP\Util::addHeader()` calls are
 * wrapped in the protected `emitStyle()` / `emitFontLink()` seams purely so
 * unit tests can assert the exact stylesheet sequence via a partial mock —
 * the real static calls delegate to the server-private `\OC_Util`, which is
 * not resolvable outside a full Nextcloud bootstrap. Production code always
 * runs through these two one-line wrappers, so no behavior changes.
 *
 * @spec openspec/specs/css-architecture/spec.md
 */
class CssInjectionService
{

    /**
     * The appconfig key holding the JSON render-context allow-list.
     *
     * @var string
     */
    private const THEMED_CONTEXTS_KEY = 'themed_contexts';

    /**
     * The render-context names the `themed_contexts` allow-list recognizes.
     *
     * Any other context value (e.g. an unmapped/future `renderAs`) always
     * fails open to themed — see {@see isContextThemed()}.
     *
     * @var string[]
     */
    private const VALID_CONTEXTS = ['user', 'login', 'guest', 'public', 'error'];

    /**
     * The application configuration service.
     *
     * @var IConfig
     */
    private IConfig $config;

    /**
     * Resolves which design system a token set uses and its stylesheet order.
     *
     * @var DesignSystemService
     */
    private DesignSystemService $designSystemService;

    /**
     * Ensures the custom-overrides.css file exists before it is loaded.
     *
     * @var CustomOverridesService
     */
    private CustomOverridesService $overridesService;

    /**
     * Resolves admin-uploaded custom fonts.
     *
     * @var FontService
     */
    private FontService $fontService;

    /**
     * Builds the URL to the generated custom-fonts stylesheet route.
     *
     * @var IURLGenerator
     */
    private IURLGenerator $urlGenerator;

    /**
     * Resolves the effective token set for the requesting user (per-group
     * mapping, falling back to the instance default).
     *
     * @var GroupThemingService
     */
    private GroupThemingService $groupThemingService;

    /**
     * Constructor.
     *
     * @param IConfig                $config              The config service.
     * @param DesignSystemService    $designSystemService The design system resolver.
     * @param CustomOverridesService $overridesService    The custom overrides file service.
     * @param FontService            $fontService         The custom font resolver.
     * @param IURLGenerator          $urlGenerator        The URL generator.
     * @param GroupThemingService    $groupThemingService The per-group token set resolver.
     */
    public function __construct(
        IConfig $config,
        DesignSystemService $designSystemService,
        CustomOverridesService $overridesService,
        FontService $fontService,
        IURLGenerator $urlGenerator,
        GroupThemingService $groupThemingService
    ) {
        $this->config = $config;
        $this->designSystemService = $designSystemService;
        $this->overridesService    = $overridesService;
        $this->fontService         = $fontService;
        $this->urlGenerator        = $urlGenerator;
        $this->groupThemingService = $groupThemingService;
    }//end __construct()

    /**
     * Inject the full nldesign stylesheet cascade for a render context.
     *
     * A no-op when the context is gated out by the `themed_contexts`
     * appconfig (see {@see isContextThemed()}). Otherwise this is the
     * verbatim former `Application::injectThemeCSS()` body: design-system
     * stylesheets in declared order, token set CSS, icon/error contrast
     * fixes, custom overrides, custom fonts, then conditional
     * hide-slogan/show-menu-labels stylesheets.
     *
     * @param string $context One of `user`/`login`/`guest`/`public`/`error`,
     *                        or any other value (always themed — fail open).
     *
     * @return void
     *
     * @spec openspec/specs/css-architecture/spec.md
     * @spec openspec/specs/custom-fonts/spec.md
     */
    public function inject(string $context): void
    {
        if ($this->isContextThemed(context: $context) === false) {
            return;
        }

        // The active set is the per-group resolution (group mapping → instance
        // default). With no mapping configured this is the plain appconfig
        // value, so behaviour is byte-identical to a single-tenant instance.
        $tokenSet       = $this->groupThemingService->resolveTokenSetForRequest();
        $hideSlogan     = $this->config->getAppValue(Application::APP_ID, 'hide_slogan', '0') === '1';
        $showMenuLabels = $this->config->getAppValue(Application::APP_ID, 'show_menu_labels', '0') === '1';

        // 1. Resolve which design system this token set uses.
        $tokenSetMeta   = $this->designSystemService->getTokenSetMeta(tokenSetId: $tokenSet);
        $designSystemId = $tokenSetMeta['design_system'] ?? 'nldesign';
        $designSystem   = $this->designSystemService->getDesignSystem(id: $designSystemId);

        // 2. Load design system stylesheets in declared order.
        // For "none" (stock Nextcloud) this array is empty — no CSS loads.
        foreach ($designSystem['stylesheets'] as $stylesheet) {
            $this->emitStyle(file: $stylesheet);
        }

        // 3. Load token values (only when a design system reads --nldesign-* vars).
        if ($designSystemId !== 'none') {
            $this->emitStyle(file: 'tokens/'.$tokenSet);
            // Functional contrast fix shared by all design systems: app icons
            // that carry their white fill on <path> vanish on light surfaces
            // in the NC 34 app-management list (see css/icon-contrast.css).
            $this->emitStyle(file: 'icon-contrast');
            // Functional contrast fix shared by all design systems: our error
            // fill is a saturated brand red where Nextcloud's is pale, so the
            // components painting --color-error-text on it lose all contrast
            // (see css/error-contrast.css).
            $this->emitStyle(file: 'error-contrast');
        }

        // 4. Custom overrides — admin-defined token overrides, always loaded last.
        $this->overridesService->ensureExists();
        $this->emitStyle(file: 'custom-overrides');

        // 4.5 Custom fonts — admin-uploaded, self-hosted webfonts. Injected as
        // a <link rel="stylesheet"> (not \OCP\Util::addStyle(), because the
        // CSS is generated dynamically by FontController::css(), not a static
        // file under css/) AFTER the token-set styles so the font tokens win
        // the cascade, and only when at least one font is configured, so a
        // themed instance with zero uploaded fonts issues no extra request.
        if ($designSystemId !== 'none' && $this->fontService->hasFonts() === true) {
            $cssUrl = $this->urlGenerator->linkToRoute('nldesign.font.css').'?v='.$this->fontService->getRevision();
            $this->emitFontLink(url: $cssUrl);
        }

        // 5. Conditional stylesheets.
        if ($hideSlogan === true) {
            $this->emitStyle(file: 'hide-slogan');
        }

        if ($showMenuLabels === true) {
            $this->emitStyle(file: 'show-menu-labels');
        }
    }//end inject()

    /**
     * Whether a render context must receive nldesign CSS.
     *
     * A context outside {@see VALID_CONTEXTS} (an unmapped or future
     * `renderAs` value) is never gated by configuration — it always resolves
     * to themed, so a forward-compatibility gap can never silently strip
     * theming. For a recognized context, an absent, empty, or unparseable
     * `themed_contexts` value themes ALL contexts (byte-identical to the
     * previous boot-time injection); a non-empty valid list themes only the
     * contexts it names.
     *
     * @param string $context The render context to check.
     *
     * @return bool True when the context must receive nldesign CSS.
     *
     * @spec openspec/specs/css-architecture/spec.md
     */
    private function isContextThemed(string $context): bool
    {
        if (in_array($context, self::VALID_CONTEXTS, true) === false) {
            return true;
        }

        $raw     = $this->config->getAppValue(Application::APP_ID, self::THEMED_CONTEXTS_KEY, '[]');
        $decoded = json_decode($raw, true);
        if (is_array($decoded) === false || empty($decoded) === true) {
            return true;
        }

        return in_array($context, $decoded, true);
    }//end isContextThemed()

    /**
     * Emit a static nldesign stylesheet via the Nextcloud style registry.
     *
     * @param string $file The stylesheet path relative to `css/` (no extension).
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess) - \OCP\Util::addStyle() is the Nextcloud API for CSS injection
     *
     * @spec openspec/specs/css-architecture/spec.md
     */
    protected function emitStyle(string $file): void
    {
        \OCP\Util::addStyle(application: Application::APP_ID, file: $file);
    }//end emitStyle()

    /**
     * Emit the dynamically-generated custom-fonts stylesheet as a `<link>`
     * header (not a static `css/` file, so `Util::addStyle()` cannot serve it).
     *
     * @param string $url The absolute URL to the generated fonts CSS route.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess) - \OCP\Util::addHeader() is the Nextcloud API for header injection
     *
     * @spec openspec/specs/custom-fonts/spec.md
     */
    protected function emitFontLink(string $url): void
    {
        \OCP\Util::addHeader(
            tag: 'link',
            attributes: [
                'rel'  => 'stylesheet',
                'href' => $url,
            ]
        );
    }//end emitFontLink()
}//end class
