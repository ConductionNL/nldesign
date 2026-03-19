<?php

/**
 * NL Design Overrides Controller.
 *
 * @category Controller
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0-or-later
 * @link     https://github.com/ConductionNL/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Controller;

use OCA\NLDesign\Service\CustomOverridesService;
use OCA\NLDesign\Service\TokenRegistry;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Controller for managing custom CSS token overrides.
 *
 * Handles CRUD, import, and export of custom-overrides.css.
 */
class OverridesController extends Controller
{

    /**
     * The custom overrides service.
     *
     * @var CustomOverridesService
     */
    private CustomOverridesService $overridesService;

    /**
     * Constructor.
     *
     * @param string                 $appName          The app name.
     * @param IRequest               $request          The request object.
     * @param CustomOverridesService $overridesService The custom overrides service.
     */
    public function __construct(
        string $appName,
        IRequest $request,
        CustomOverridesService $overridesService
    ) {
        parent::__construct($appName, $request);
        $this->overridesService = $overridesService;
    }//end __construct()

    /**
     * Get the current custom token overrides.
     *
     * Returns only tokens explicitly set in custom-overrides.css,
     * plus the full editable token registry for the UI.
     *
     * @return JSONResponse The overrides and token registry.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     *
     * @SuppressWarnings(PHPMD.StaticAccess) - TokenRegistry uses static methods by design
     */
    public function getOverrides(): JSONResponse
    {
        $overrides = $this->overridesService->read();
        $registry  = TokenRegistry::getTokens();
        $tabs      = TokenRegistry::getTabLabels();

        return new JSONResponse(
            [
                'overrides' => $overrides,
                'registry'  => $registry,
                'tabs'      => $tabs,
            ]
        );
    }//end getOverrides()

    /**
     * Write new custom token overrides to custom-overrides.css.
     *
     * Accepts a JSON body with an 'overrides' key containing token name => value pairs.
     * Only tokens in the TokenRegistry are accepted; others are silently ignored.
     *
     * @return JSONResponse Status and count of written tokens.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     */
    public function setOverrides(): JSONResponse
    {
        $params    = $this->request->getParams();
        $overrides = $params['overrides'] ?? [];

        if (is_array($overrides) === false) {
            return new JSONResponse(['error' => 'overrides must be an object'], 400);
        }

        try {
            $this->overridesService->write(tokens: $overrides);
        } catch (\RuntimeException $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }

        return new JSONResponse(['status' => 'ok', 'written' => count($overrides)]);
    }//end setOverrides()

    /**
     * Download custom-overrides.css as a file.
     *
     * @return DataDownloadResponse The CSS file as a download.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     */
    public function exportOverrides(): DataDownloadResponse
    {
        $content = $this->overridesService->getRawContent();

        return new DataDownloadResponse(
            data: $content,
            filename: 'custom-overrides.css',
            contentType: 'text/css'
        );
    }//end exportOverrides()

    /**
     * Import custom token overrides from an uploaded CSS file.
     *
     * Accepts a multipart/form-data upload with a 'file' field.
     * Only recognized editable tokens are imported; unknown tokens are silently skipped.
     * The import fully replaces the existing custom-overrides.css.
     *
     * @return JSONResponse Import result with 'imported' and 'skipped' counts.
     *
     * @AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)
     */
    public function importOverrides(): JSONResponse
    {
        $validationError = $this->validateUploadedFile();
        if ($validationError !== null) {
            return $validationError;
        }

        $content = $this->readUploadedContent();
        if ($content === null) {
            return new JSONResponse(['error' => 'Could not read uploaded file'], 400);
        }

        $parsed = $this->parseCssDeclarations($content);
        if ($parsed === null) {
            return new JSONResponse(
                ['error' => 'No CSS custom property declarations found in the uploaded file'],
                400
            );
        }

        return $this->writeImportedTokens($parsed);
    }//end importOverrides()

    /**
     * Validate the uploaded file for the import endpoint.
     *
     * @return JSONResponse|null An error response, or null if the file is valid.
     */
    private function validateUploadedFile(): ?JSONResponse
    {
        $file = $this->request->getUploadedFile(key: 'file');

        if (empty($file) === true || isset($file['tmp_name']) === false) {
            return new JSONResponse(['error' => 'No file uploaded'], 400);
        }

        $maxSize = (256 * 1024);
        if ($file['size'] > $maxSize) {
            return new JSONResponse(['error' => 'File exceeds the 256 KB size limit'], 413);
        }

        return null;
    }//end validateUploadedFile()

    /**
     * Read the content of the uploaded file.
     *
     * @return string|null The file content, or null on failure.
     */
    private function readUploadedContent(): ?string
    {
        $file    = $this->request->getUploadedFile(key: 'file');
        $content = file_get_contents($file['tmp_name']);

        if ($content === false) {
            return null;
        }

        return $content;
    }//end readUploadedContent()

    /**
     * Parse CSS custom property declarations from a raw CSS string.
     *
     * @param string $content The raw CSS content.
     *
     * @return array<string, string>|null Parsed token map, or null if none found.
     */
    private function parseCssDeclarations(string $content): ?array
    {
        preg_match_all('/^\s*(--[\w-]+)\s*:\s*([^;]+);/m', $content, $matches, PREG_SET_ORDER);

        if (empty($matches) === true) {
            return null;
        }

        $parsed = [];
        foreach ($matches as $match) {
            $parsed[trim($match[1])] = trim($match[2]);
        }

        return $parsed;
    }//end parseCssDeclarations()

    /**
     * Filter parsed tokens and write editable ones to the overrides file.
     *
     * @param array<string, string> $parsed The parsed CSS tokens.
     *
     * @return JSONResponse The import result response.
     *
     * @SuppressWarnings(PHPMD.StaticAccess) - TokenRegistry uses static methods by design
     */
    private function writeImportedTokens(array $parsed): JSONResponse
    {
        $toImport = [];
        $skipped  = 0;
        foreach ($parsed as $name => $value) {
            if (TokenRegistry::isEditable(tokenName: $name) === false) {
                $skipped++;
                continue;
            }

            $toImport[$name] = $value;
        }

        try {
            $this->overridesService->write(tokens: $toImport);
        } catch (\RuntimeException $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }

        return new JSONResponse(
            [
                'status'   => 'ok',
                'imported' => count($toImport),
                'skipped'  => $skipped,
            ]
        );
    }//end writeImportedTokens()
}//end class
