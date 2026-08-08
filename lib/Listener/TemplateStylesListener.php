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
use OCA\NLDesign\Application\Branding\RuntimeStylesheetPlan;
use OCA\NLDesign\Service\TokenSetService;
use OCA\NLDesign\Service\ProfileStateService;
use OCP\AppFramework\Http\Events\BeforeLoginTemplateRenderedEvent;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\EventDispatcher\IEventListener;
use OCP\EventDispatcher\Event;
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
     * @param LoggerInterface       $logger              Application logger.
     */
    public function __construct(
        TokenSetService $tokenSetService,
        ProfileStateService $profileStateService,
        private RuntimeStylesheetPlan $stylesheetPlan,
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
            $profileState = $this->profileStateService->getActiveProfileState();
            $tokenSet     = $profileState['active_profile_id'];
            if ($tokenSet !== null
                && $this->tokenSetService->isValidTokenSet($tokenSet) === true
            ) {
                // The listener is registered for two template events. Keep the
                // global style queue idempotent when both fire in one request.
                $this->stylesAttached = true;
                foreach ($this->stylesheetPlan->build(profileId: $tokenSet) as $stylesheet) {
                    \OCP\Util::addStyle(Application::APP_ID, $stylesheet);
                }
            } else if ($tokenSet !== null) {
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
}//end class
