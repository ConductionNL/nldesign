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
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUserSession;

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
     * Gates and reads the freeform custom CSS layer.
     *
     * @var CustomCssService
     */
    private CustomCssService $customCssService;

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
     * Resolves whether the requesting user has an active theme preview.
     *
     * @var ThemePreviewService
     */
    private ThemePreviewService $previewService;

    /**
     * The user session, to resolve the previewing user.
     *
     * @var IUserSession
     */
    private IUserSession $userSession;

    /**
     * Provides the preview banner's initial state to the frontend.
     *
     * @var IInitialState
     */
    private IInitialState $initialState;

    /**
     * Constructor.
     *
     * @param IConfig                $config              The config service.
     * @param DesignSystemService    $designSystemService The design system resolver.
     * @param CustomOverridesService $overridesService    The custom overrides file service.
     * @param CustomCssService       $customCssService    The freeform custom CSS service.
     * @param FontService            $fontService         The custom font resolver.
     * @param IURLGenerator          $urlGenerator        The URL generator.
     * @param GroupThemingService    $groupThemingService The per-group token-set resolver.
     * @param ThemePreviewService    $previewService      The admin theme-preview resolver.
     * @param IUserSession           $userSession         The user session.
     * @param IInitialState          $initialState        Provides the preview banner's initial state.
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList) - Each dependency is one layer of the
     * documented injection cascade (config, design system, overrides, fonts, URLs, per-group
     * resolution, preview). NC's DI container supplies them; a parameter object would only
     * rename the coupling.
     */
    public function __construct(
        IConfig $config,
        DesignSystemService $designSystemService,
        CustomOverridesService $overridesService,
        CustomCssService $customCssService,
        FontService $fontService,
        IURLGenerator $urlGenerator,
        GroupThemingService $groupThemingService,
        ThemePreviewService $previewService,
        IUserSession $userSession,
        IInitialState $initialState
    ) {
        $this->config = $config;
        $this->designSystemService = $designSystemService;
        $this->overridesService    = $overridesService;
        $this->customCssService    = $customCssService;
        $this->fontService         = $fontService;
        $this->urlGenerator        = $urlGenerator;
        $this->groupThemingService = $groupThemingService;
        $this->previewService      = $previewService;
        $this->userSession         = $userSession;
        $this->initialState        = $initialState;
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
     * @spec openspec/specs/marianne-font/spec.md
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

        // 2b. Marianne (French State typeface) — gated, inert-by-default
        // self-hosted font layer, emitted directly after the design-system
        // stylesheets above (which already include the base
        // systems/lasuite/fonts layer) so its real @font-face declarations
        // exist. See injectMarianneStylesheet() for the gate condition.
        $this->injectMarianneStylesheet(designSystemId: $designSystemId);

        // 3. Load token values (only when a design system reads --nldesign-* vars).
        if ($designSystemId !== 'none') {
            $this->emitStyle(file: 'tokens/'.$tokenSet);
            // 3b. Generated dark-mode variant, directly after the light layer
            // so its media-query/attribute-scoped rules override it — only
            // when the toggle is on AND a generated file exists for this set.
            // A disabled toggle or a set without a variant adds nothing.
            $this->injectDarkVariantStyle(tokenSet: $tokenSet);
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

        // 4.1 Freeform custom CSS — admin-authored arbitrary rules. Emitted
        // AFTER custom-overrides so administrator intent wins the cascade, and
        // only when the feature is switched on AND something is actually
        // stored, so an instance that never opts in loads nothing at all.
        if ($this->customCssService->isEnabled() === true
            && $this->customCssService->hasContent() === true
        ) {
            $this->emitStyle(file: 'custom-css');
        }

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

        // 6. Preview banner — ONLY when a theme preview is active for this
        // request's user. Every other user (and every anonymous render) pays
        // nothing: no script, no style, no initial state.
        $this->injectPreviewBanner(tokenSet: $tokenSet, tokenSetMeta: $tokenSetMeta);
    }//end inject()

    /**
     * Load the preview banner assets and provide its initial state when the
     * requesting user has an active theme preview.
     *
     * Fails open (renders nothing) on any error — a broken banner must never
     * break the page it annotates.
     *
     * @param string               $tokenSet     The previewed token set id.
     * @param array<string, mixed> $tokenSetMeta The token set metadata (for its display name).
     *
     * @return void
     *
     * @spec openspec/specs/theme-preview/spec.md#requirement-preview-banner
     */
    protected function injectPreviewBanner(string $tokenSet, array $tokenSetMeta): void
    {
        try {
            $effective = $this->previewService->resolveEffectiveTokenSet(
                userSession: $this->userSession,
                activeTokenSet: $tokenSet
            );

            if ($effective['previewActive'] !== true) {
                return;
            }

            $this->emitPreviewAssets();
            $this->initialState->provideInitialState(
                'preview',
                [
                    'tokenSet'  => $tokenSet,
                    'name'      => ($tokenSetMeta['name'] ?? $tokenSet),
                    'expiresAt' => ($effective['expiresAt'] ?? null),
                ]
            );
        } catch (\Throwable $e) {
            return;
        }//end try
    }//end injectPreviewBanner()

    /**
     * Emit the preview banner's script and stylesheet.
     *
     * Isolated as a seam so unit tests can assert banner injection without a
     * Nextcloud bootstrap (see the emitStyle()/emitFontLink() rationale).
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess) - \OCP\Util is the Nextcloud API for asset injection.
     *
     * @spec openspec/specs/theme-preview/spec.md#requirement-preview-banner
     */
    protected function emitPreviewAssets(): void
    {
        \OCP\Util::addScript(application: Application::APP_ID, file: 'preview-banner');
        \OCP\Util::addStyle(application: Application::APP_ID, file: 'preview-banner');
    }//end emitPreviewAssets()

    /**
     * Add the generated dark-mode stylesheet, when ALL of: the `dark_variants`
     * app config is enabled, and a generated `css/tokens/dark/{set}.css` file
     * exists for the active set. A missing file or a disabled toggle simply
     * adds nothing — never an error.
     *
     * @param string $tokenSet The active token set id.
     *
     * @return void
     *
     * @spec openspec/specs/dark-mode/spec.md
     */
    private function injectDarkVariantStyle(string $tokenSet): void
    {
        $darkVariantsEnabled = ($this->config->getAppValue(Application::APP_ID, 'dark_variants', '1') === '1');
        if ($darkVariantsEnabled === false) {
            return;
        }

        if ($this->designSystemService->hasGeneratedDarkVariant(tokenSetId: $tokenSet) === false) {
            return;
        }

        $this->emitStyle(file: 'tokens/dark/'.$tokenSet);
    }//end injectDarkVariantStyle()

    /**
     * Add the gated, self-hosted Marianne (French State typeface) stylesheet,
     * when BOTH the active design system is `lasuite` AND an admin has
     * acknowledged eligibility via the `marianne_enabled` appconfig flag
     * (default `'0'`). While the flag is off — or the design system is not
     * `lasuite` — this adds nothing, so no `@font-face` `url()` source for
     * Marianne exists and the fonts.css family stack falls through to Inter.
     *
     * @param string $designSystemId The resolved design system id for the active token set.
     *
     * @return void
     *
     * @spec openspec/specs/marianne-font/spec.md
     */
    private function injectMarianneStylesheet(string $designSystemId): void
    {
        if ($designSystemId !== 'lasuite') {
            return;
        }

        if ($this->config->getAppValue(Application::APP_ID, 'marianne_enabled', '0') !== '1') {
            return;
        }

        $this->emitStyle(file: 'systems/lasuite/marianne');
    }//end injectMarianneStylesheet()

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
