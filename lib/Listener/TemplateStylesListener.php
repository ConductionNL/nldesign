<?php

/**
 * NL Design template listener for stylesheet injection.
 *
 * @category Listener
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Listener;

use OCA\NLDesign\AppInfo\Application;
use OCA\NLDesign\Application\Presentation\RuntimeStylesheetPlan;
use OCA\NLDesign\Service\TokenSetService;
use OCA\NLDesign\Service\ProfileStateService;
use OCP\AppFramework\Http\Events\BeforeLoginTemplateRenderedEvent;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\EventDispatcher\IEventListener;
use OCP\EventDispatcher\Event;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

/**
 * Injects token-set CSS only during template rendering.
 *
 * @implements IEventListener<Event>
 */
final class TemplateStylesListener implements IEventListener
{

    /**
     * Whether this shared request-scoped listener already attached its stack.
     *
     * @var boolean
     */
    private bool $stylesAttached = false;

    /**
     * Token set service.
     *
     * @var TokenSetService
     */
    private TokenSetService $tokenSetService;

    /**
     * Profile state service.
     *
     * @var ProfileStateService
     */
    private ProfileStateService $profileStateService;

    /**
     * Constructor.
     *
     * @param TokenSetService       $tokenSetService     Token set service.
     * @param ProfileStateService   $profileStateService Profile state service.
     * @param RuntimeStylesheetPlan $stylesheetPlan      Stylesheet precedence plan.
     * @param IURLGenerator         $urlGenerator        Installed stylesheet URL builder.
     * @param LoggerInterface       $logger              Application logger.
     */
    public function __construct(
        TokenSetService $tokenSetService,
        ProfileStateService $profileStateService,
        private RuntimeStylesheetPlan $stylesheetPlan,
        private IURLGenerator $urlGenerator,
        private LoggerInterface $logger
    ) {
        $this->tokenSetService     = $tokenSetService;
        $this->profileStateService = $profileStateService;
    }//end __construct()

    /**
     * Inject NL Design styles during template rendering.
     *
     * @param Event $event The rendering event.
     *
     * @return void
     */
    public function handle(Event $event): void
    {
        // Defensive: the service is registered for both template events,
        // but this keeps behavior explicit during review.
        if (($event instanceof BeforeTemplateRenderedEvent) === false
            && ($event instanceof BeforeLoginTemplateRenderedEvent) === false
        ) {
            return;
        }

        if ($this->stylesAttached === true) {
            return;
        }

        try {
            $profileState   = $this->profileStateService->getActiveProfileState();
            $tokenSet       = $profileState['active_profile_id'];
            $profileVersion = $profileState['active_profile_version'];
            if ($tokenSet === null) {
                return;
            }

            if (is_string($profileVersion) === false) {
                $this->logger->debug(
                    'NL Design skipped an unavailable active profile.',
                    ['profile_id' => $tokenSet]
                );
                return;
            }

            $runtimeStylesheet = $this->tokenSetService->getRuntimeStylesheet(
                profileId: $tokenSet,
                profileVersion: $profileVersion
            );
            $stylesheetPlan    = $this->stylesheetPlan->build();
            if ($stylesheetPlan['supported'] === false) {
                $this->logger->warning(
                    'NL Design skipped an unsupported Nextcloud presentation runtime.',
                    [
                        'nextcloud_version' => $stylesheetPlan['runtime_version'],
                        'reason'            => $stylesheetPlan['reason'],
                    ]
                );
                return;
            }

            $attached = $this->attachRuntimeStylesheet(
                runtimeStylesheet: $runtimeStylesheet,
                tokenSet: $tokenSet,
                profileVersion: $profileVersion,
                beforeProfile: $stylesheetPlan['before_profile'],
                afterProfile: $stylesheetPlan['after_profile']
            );
            if ($attached === false) {
                $this->logger->debug(
                    'NL Design skipped an unavailable active profile.',
                    ['profile_id' => $tokenSet]
                );
            }
        } catch (\Throwable $exception) {
            // Fail open for admin safety: keep rendering even when profile
            // state, catalogue, or style planning fails.
            $this->logger->warning(
                'NL Design styles could not be attached to the template.',
                ['exception' => $exception]
            );
        }//end try
    }//end handle()

    /**
     * Attach one validated packaged or installed profile stack.
     *
     * @param array<string, string>|null $runtimeStylesheet Runtime descriptor.
     * @param string                     $tokenSet          Profile identifier.
     * @param string                     $profileVersion    Exact profile version.
     * @param array<int, string>         $beforeProfile     Styles before profile.
     * @param array<int, string>         $afterProfile      Styles after profile.
     *
     * @return bool Whether a complete stack was attached.
     */
    private function attachRuntimeStylesheet(
        ?array $runtimeStylesheet,
        string $tokenSet,
        string $profileVersion,
        array $beforeProfile,
        array $afterProfile
    ): bool {
        $type = $runtimeStylesheet['type'] ?? null;
        $path = $runtimeStylesheet['path'] ?? null;
        if ($type === 'packaged' && is_string($path) === true) {
            $this->stylesAttached = true;
            $this->addStyles(stylesheets: $beforeProfile);
            \OCP\Util::addStyle(Application::APP_ID, $path);
            $this->addStyles(stylesheets: $afterProfile);
            return true;
        }

        $contentHash = $runtimeStylesheet['content_hash'] ?? null;
        if ($type !== 'installed' || is_string($contentHash) === false) {
            return false;
        }

        $this->stylesAttached = true;
        $this->addStyles(stylesheets: $beforeProfile);
        \OCP\Util::addHeader(
            'link',
            [
                'rel'  => 'stylesheet',
                'href' => $this->urlGenerator->linkToRoute(
                    'nldesign.stylesheet.getProfile',
                    [
                        'profileId'      => $tokenSet,
                        'profileVersion' => $profileVersion,
                        'contentHash'    => $contentHash,
                    ]
                ),
            ]
        );
        $this->addStyles(stylesheets: $afterProfile);
        return true;
    }//end attachRuntimeStylesheet()

    /**
     * Add an ordered group of packaged stylesheets.
     *
     * @param array<int, string> $stylesheets App-relative stylesheet names.
     *
     * @return void
     */
    private function addStyles(array $stylesheets): void
    {
        foreach ($stylesheets as $stylesheet) {
            \OCP\Util::addStyle(Application::APP_ID, $stylesheet);
        }
    }//end addStyles()
}//end class
