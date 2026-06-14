<?php

/**
 * NL Design Custom Token Set Controller.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category  Controller
 * @package   OCA\NLDesign
 * @author    Conduction <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://codeberg.org/Conduction/nldesign
 *
 * @spec openspec/changes/custom-token-set-upload/tasks.md#task-3.1
 * @spec openspec/changes/custom-token-set-upload/tasks.md#task-3.2
 * @spec openspec/changes/custom-token-set-upload/tasks.md#task-3.3
 */

declare(strict_types=1);

namespace OCA\NLDesign\Controller;

use OCA\NLDesign\Service\CssParserService;
use OCA\NLDesign\Service\CustomTokenSetService;
use OCA\NLDesign\Service\CustomTokenSetValidator;
use OCA\NLDesign\Service\DesignTokensMapper;
use OCA\NLDesign\Settings\Admin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use RuntimeException;

/**
 * Admin-only controller for the custom token set upload lifecycle.
 *
 * Every method is restricted to delegated theming admins via
 * AuthorizedAdminSetting and CSRF-protected (no NoCSRFRequired). The upload
 * output is CSS served to every user, so the validation pipeline is strict and
 * the served file is always re-serialised from parsed declarations.
 *
 * @spec openspec/changes/custom-token-set-upload/tasks.md#task-3.1
 */
class CustomTokenSetController extends Controller
{

    /**
     * The custom token set storage/lifecycle service.
     *
     * @var CustomTokenSetService
     */
    private CustomTokenSetService $service;

    /**
     * The CSS upload validator / re-serialiser.
     *
     * @var CustomTokenSetValidator
     */
    private CustomTokenSetValidator $validator;

    /**
     * The CSS parser service.
     *
     * @var CssParserService
     */
    private CssParserService $cssParser;

    /**
     * The W3C Design Tokens mapper.
     *
     * @var DesignTokensMapper
     */
    private DesignTokensMapper $mapper;

    /**
     * The localization service.
     *
     * @var IL10N
     */
    private IL10N $l;

    /**
     * Constructor.
     *
     * @param string                  $appName   The app name.
     * @param IRequest                $request   The request object.
     * @param CustomTokenSetService   $service   The storage/lifecycle service.
     * @param CustomTokenSetValidator $validator The CSS validator.
     * @param CssParserService        $cssParser The CSS parser service.
     * @param DesignTokensMapper      $mapper    The DTCG mapper.
     * @param IL10N                   $l         The localization service.
     */
    public function __construct(
        string $appName,
        IRequest $request,
        CustomTokenSetService $service,
        CustomTokenSetValidator $validator,
        CssParserService $cssParser,
        DesignTokensMapper $mapper,
        IL10N $l
    ) {
        parent::__construct(appName: $appName, request: $request);
        $this->service   = $service;
        $this->validator = $validator;
        $this->cssParser = $cssParser;
        $this->mapper    = $mapper;
        $this->l         = $l;
    }//end __construct()

    /**
     * Upload a custom token set (CSS or W3C Design Tokens JSON).
     *
     * Accepts a multipart upload with a `file` field and a `name` field. The
     * file is validated, mapped (JSON), whitelisted, re-serialised, stored as
     * css/tokens/custom-{slug}.css, and its contrast warnings are computed.
     *
     * @return JSONResponse `{ id, imported, skipped, warnings }` or an error.
     *
     * @spec openspec/changes/custom-token-set-upload/tasks.md#task-3.3
     */
    #[AuthorizedAdminSetting(Admin::class)]
    public function upload(): JSONResponse
    {
        $name = trim((string) ($this->request->getParam('name', '')));
        if ($name === '') {
            return new JSONResponse(['error' => $this->l->t('A token set name is required.')], 400);
        }

        $file = $this->request->getUploadedFile(key: 'file');
        if (empty($file) === true || isset($file['tmp_name']) === false) {
            return new JSONResponse(['error' => $this->l->t('No file uploaded.')], 400);
        }

        if (($file['size'] ?? 0) > CustomTokenSetValidator::MAX_SIZE) {
            return new JSONResponse(['error' => $this->l->t('File exceeds the 512 KB size limit.')], 413);
        }

        $content = file_get_contents($file['tmp_name']);
        if ($content === false) {
            return new JSONResponse(['error' => $this->l->t('Could not read the uploaded file.')], 400);
        }

        $extension = strtolower(pathinfo(($file['name'] ?? ''), PATHINFO_EXTENSION));
        $slug      = $this->service->slugify(name: $name);
        if ($slug === '') {
            return new JSONResponse(['error' => $this->l->t('A token set name must contain at least one letter or digit.')], 422);
        }

        if ($extension === 'json' || str_ends_with(strtolower((string) ($file['name'] ?? '')), '.tokens.json') === true) {
            $parsed = $this->mapFromJson(content: $content);
        } else {
            $parsed = $this->mapFromCss(content: $content, slug: $slug);
        }

        if ($parsed instanceof JSONResponse) {
            return $parsed;
        }

        return $this->persist(name: $name, parsed: $parsed);
    }//end upload()

    /**
     * Parse and map a CSS upload into the accepted/skipped split.
     *
     * @param string $content The raw CSS upload.
     * @param string $slug    The derived slug (for `--{slug}-*` extras).
     *
     * @return array{accepted: array<string, string>, skipped: string[]}|JSONResponse
     *
     * @spec openspec/changes/custom-token-set-upload/tasks.md#task-3.3
     */
    private function mapFromCss(string $content, string $slug)
    {
        if ($this->validator->hasDisallowedSelector(css: $content) === true) {
            return new JSONResponse(
                ['error' => $this->l->t('The CSS contains a selector or at-rule other than :root, which is not allowed in a token set.')],
                422
            );
        }

        $declarations = $this->cssParser->parseRootBlock(css: $content);
        $split        = $this->validator->validateDeclarations(declarations: $declarations, slug: $slug);
        if ($split === null) {
            $error = $this->validator->getLastError();

            return new JSONResponse(
                ['error' => ($error['message'] ?? $this->l->t('The uploaded CSS could not be validated.'))],
                ($error['status'] ?? 422)
            );
        }

        return $split;
    }//end mapFromCss()

    /**
     * Parse and map a W3C Design Tokens JSON upload into the accepted/skipped split.
     *
     * @param string $content The raw JSON upload.
     *
     * @return array{accepted: array<string, string>, skipped: string[]}|JSONResponse
     *
     * @spec openspec/changes/custom-token-set-upload/tasks.md#task-3.3
     */
    private function mapFromJson(string $content)
    {
        $document = json_decode($content, true);
        if (is_array($document) === false) {
            return new JSONResponse(['error' => $this->l->t('The uploaded file is not valid JSON.')], 422);
        }

        $mapped = $this->mapper->map(document: $document);

        // The mapped declarations are already --nldesign-* targets, but pass
        // them through the value blacklist so JSON cannot smuggle a forbidden
        // value into the served CSS.
        $accepted = [];
        foreach ($mapped['declarations'] as $name => $value) {
            if ($this->validator->isForbiddenValue(value: (string) $value) === true) {
                return new JSONResponse(
                    ['error' => $this->l->t('Mapped token %s contains a forbidden value.', [$name])],
                    422
                );
            }

            $accepted[$name] = (string) $value;
        }

        if (empty($accepted) === true) {
            return new JSONResponse(
                ['error' => $this->l->t('No recognized design tokens were found in the uploaded file.')],
                422
            );
        }

        return [
            'accepted' => $accepted,
            'skipped'  => $mapped['skipped'],
        ];
    }//end mapFromJson()

    /**
     * Store the accepted declarations and build the upload response.
     *
     * @param string                                                    $name   The display name.
     * @param array{accepted: array<string, string>, skipped: string[]} $parsed The validated split.
     *
     * @return JSONResponse The upload result or a collision/storage error.
     *
     * @spec openspec/changes/custom-token-set-upload/tasks.md#task-3.3
     */
    private function persist(string $name, array $parsed): JSONResponse
    {
        try {
            $result = $this->service->store(
                displayName: $name,
                description: trim((string) ($this->request->getParam('description', ''))),
                declarations: $parsed['accepted']
            );
        } catch (RuntimeException $e) {
            $code = $e->getCode();
            if ($code < 400 || $code > 599) {
                $code = 500;
            }

            return new JSONResponse(['error' => $e->getMessage()], $code);
        }

        return new JSONResponse(
            [
                'id'       => $result['id'],
                'imported' => count($parsed['accepted']),
                'skipped'  => $parsed['skipped'],
                'warnings' => $result['warnings'],
            ]
        );
    }//end persist()

    /**
     * List stored custom token sets with their metadata and contrast warnings.
     *
     * @return JSONResponse The list of custom sets.
     *
     * @spec openspec/changes/custom-token-set-upload/tasks.md#task-3.1
     */
    #[AuthorizedAdminSetting(Admin::class)]
    public function list(): JSONResponse
    {
        return new JSONResponse(['sets' => $this->service->list()]);
    }//end list()

    /**
     * Export (download) the served CSS of a custom token set.
     *
     * @param string $id The custom set id.
     *
     * @return DataDownloadResponse|JSONResponse The CSS download or a 404.
     *
     * @spec openspec/changes/custom-token-set-upload/tasks.md#task-3.1
     */
    #[AuthorizedAdminSetting(Admin::class)]
    public function export(string $id)
    {
        $content = $this->service->getRawContent(id: $id);
        if ($content === null) {
            return new JSONResponse(['error' => $this->l->t('Token set not found.')], 404);
        }

        return new DataDownloadResponse(
            data: $content,
            filename: $id.'.css',
            contentType: 'text/css'
        );
    }//end export()

    /**
     * Delete a custom token set (file + manifest), resetting the active set if needed.
     *
     * @param string $id The custom set id.
     *
     * @return JSONResponse The deletion result.
     *
     * @spec openspec/changes/custom-token-set-upload/tasks.md#task-3.1
     */
    #[AuthorizedAdminSetting(Admin::class)]
    public function delete(string $id): JSONResponse
    {
        if ($this->service->isCustomId(id: $id) === false) {
            return new JSONResponse(['error' => $this->l->t('Only custom token sets can be deleted.')], 400);
        }

        if ($this->service->delete(id: $id) === false) {
            return new JSONResponse(['error' => $this->l->t('Token set not found.')], 404);
        }

        return new JSONResponse(['status' => 'ok']);
    }//end delete()
}//end class
