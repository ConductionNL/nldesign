<?php

/**
 * NL Design Settings Controller.
 *
 * @category Controller
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0-or-later
 * @link     https://github.com/ConductionNL/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Controller;

use OCA\NLDesign\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;

/**
 * Settings controller for NL Design app.
 *
 * Handles API requests for managing NL Design theme settings.
 */
class SettingsController extends Controller
{

    /**
     * The application configuration service.
     *
     * @var IConfig
     */
    private IConfig $config;

    /**
     * Constructor.
     *
     * @param string   $appName The app name.
     * @param IRequest $request The request object.
     * @param IConfig  $config  The config service.
     */
    public function __construct(
        string $appName,
        IRequest $request,
        IConfig $config
    ) {
        parent::__construct(appName: $appName, request: $request);
        $this->config = $config;
    }//end __construct()

    /**
     * Set the active design token set.
     *
     * @param string $tokenSet The token set name.
     *
     * @return JSONResponse The response with status and selected token set.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     */
    public function setTokenSet(string $tokenSet): JSONResponse
    {
        $validSets = ['rijkshuisstijl', 'utrecht', 'amsterdam', 'denhaag', 'rotterdam'];

        if (in_array($tokenSet, $validSets) === false) {
            return new JSONResponse(['error' => 'Invalid token set'], 400);
        }

        $this->config->setAppValue(Application::APP_ID, 'token_set', $tokenSet);

        return new JSONResponse(['status' => 'ok', 'tokenSet' => $tokenSet]);
    }//end setTokenSet()

    /**
     * Get the currently active design token set.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     *
     * @return JSONResponse The response with the current token set.
     */
    public function getTokenSet(): JSONResponse
    {
        $tokenSet = $this->config->getAppValue(
            Application::APP_ID,
            'token_set',
            'nextcloud'
        );

        return new JSONResponse(['tokenSet' => $tokenSet]);
    }//end getTokenSet()

    /**
     * Set the hide slogan setting.
     *
     * @param bool $hideSlogan Whether to hide the slogan on login page.
     *
     * @return JSONResponse The response with the status.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     */
    public function setSloganSetting(bool $hideSlogan): JSONResponse
    {
        $value = '0';
        if ($hideSlogan === true) {
            $value = '1';
        }

        $this->config->setAppValue(
            Application::APP_ID,
            'hide_slogan',
            $value
        );

        return new JSONResponse(['status' => 'ok', 'hideSlogan' => $hideSlogan]);
    }//end setSloganSetting()

    /**
     * Set the show menu labels setting.
     *
     * @param bool $showMenuLabels Whether to show text labels in app menu.
     *
     * @return JSONResponse The response with the status.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     */
    public function setMenuLabelsSetting(bool $showMenuLabels): JSONResponse
    {
        $value = '0';
        if ($showMenuLabels === true) {
            $value = '1';
        }

        $this->config->setAppValue(
            Application::APP_ID,
            'show_menu_labels',
            $value
        );

        return new JSONResponse(['status' => 'ok', 'showMenuLabels' => $showMenuLabels]);
    }//end setMenuLabelsSetting()
}//end class
