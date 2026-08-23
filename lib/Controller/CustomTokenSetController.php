<?php

/**
 * NL Design Custom Token Set Controller.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category  Controller
 * @package   OCA\Thematiq
 * @author    Conduction <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://codeberg.org/Conduction/nldesign
 *
 * @spec openspec/changes/custom-token-set-upload/tasks.md#task-3.1
 * @spec openspec/changes/custom-token-set-upload/tasks.md#task-3.2
 * @spec openspec/changes/custom-token-set-upload/tasks.md#task-3.3
 * @spec openspec/specs/custom-token-sets/spec.md
 * @spec openspec/specs/theming-audit/spec.md#requirement-complete-call-site-coverage
 */

declare(strict_types=1);

namespace OCA\Thematiq\Controller;

use OCA\Thematiq\AppInfo\Application;
use OCA\Thematiq\Service\CssParserService;
use OCA\Thematiq\Service\CustomTokenSetService;
use OCA\Thematiq\Service\CustomTokenSetValidator;
use OCA\Thematiq\Service\DesignTokensMapper;
use OCA\Thematiq\Service\ThemingAuditService;
use OCA\Thematiq\Settings\Admin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
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
 * @spec openspec/specs/theming-audit/spec.md#requirement-complete-call-site-coverage
 */
class CustomTokenSetController extends Controller {

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
	 * The theming audit trail service.
	 *
	 * @var ThemingAuditService
	 */
	private ThemingAuditService $auditService;

	/**
	 * The application configuration service (active token set lookup, for
	 * detecting whether a delete resets the active set).
	 *
	 * @var IConfig
	 */
	private IConfig $config;

	/**
	 * Constructor.
	 *
	 * @param string $appName The app name.
	 * @param IRequest $request The request object.
	 * @param CustomTokenSetService $service The storage/lifecycle service.
	 * @param CustomTokenSetValidator $validator The CSS validator.
	 * @param CssParserService $cssParser The CSS parser service.
	 * @param DesignTokensMapper $mapper The DTCG mapper.
	 * @param IL10N $l The localization service.
	 * @param ThemingAuditService $auditService The theming audit trail service.
	 * @param IConfig $config The config service.
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		CustomTokenSetService $service,
		CustomTokenSetValidator $validator,
		CssParserService $cssParser,
		DesignTokensMapper $mapper,
		IL10N $l,
		ThemingAuditService $auditService,
		IConfig $config,
	) {
		parent::__construct(appName: $appName, request: $request);
		$this->service = $service;
		$this->validator = $validator;
		$this->cssParser = $cssParser;
		$this->mapper = $mapper;
		$this->l = $l;
		$this->auditService = $auditService;
		$this->config = $config;
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
	public function upload(): JSONResponse {
		$name = trim((string)($this->request->getParam('name', '')));
		if ($name === '') {
			return new JSONResponse(['error' => $this->l->t('A token set name is required.')], 400);
		}

		$slug = $this->service->slugify(name: $name);
		if ($slug === '') {
			return new JSONResponse(['error' => $this->l->t('A token set name must contain at least one letter or digit.')], 422);
		}

		$file = $this->request->getUploadedFile(key: 'file');
		$content = $this->readUpload(file: $file);
		if ($content instanceof JSONResponse) {
			return $content;
		}

		$parsed = $this->mapUpload(fileName: (string)($file['name'] ?? ''), content: $content, slug: $slug);
		if ($parsed instanceof JSONResponse) {
			return $parsed;
		}

		return $this->persist(name: $name, parsed: $parsed);
	}//end upload()

	/**
	 * Validate the uploaded file envelope and read its content.
	 *
	 * @param array<string, mixed>|null $file The uploaded file array from the request.
	 *
	 * @return string|JSONResponse The raw content, or the error response.
	 *
	 * @spec openspec/specs/custom-token-sets/spec.md
	 */
	private function readUpload(?array $file) {
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

		return $content;
	}//end readUpload()

	/**
	 * Route the upload to the JSON or CSS mapper based on its file name.
	 *
	 * @param string $fileName The uploaded file name.
	 * @param string $content The raw upload content.
	 * @param string $slug The derived slug (for `--{slug}-*` extras).
	 *
	 * @return array{accepted: array<string, string>, skipped: string[]}|JSONResponse
	 *
	 * @spec openspec/specs/custom-token-sets/spec.md
	 */
	private function mapUpload(string $fileName, string $content, string $slug) {
		$lower = strtolower($fileName);
		$extension = pathinfo($lower, PATHINFO_EXTENSION);
		if ($extension === 'json' || str_ends_with($lower, '.tokens.json') === true) {
			return $this->mapFromJson(content: $content);
		}

		return $this->mapFromCss(content: $content, slug: $slug);
	}//end mapUpload()

	/**
	 * Parse and map a CSS upload into the accepted/skipped split.
	 *
	 * @param string $content The raw CSS upload.
	 * @param string $slug The derived slug (for `--{slug}-*` extras).
	 *
	 * @return array{accepted: array<string, string>, skipped: string[]}|JSONResponse
	 *
	 * @spec openspec/changes/custom-token-set-upload/tasks.md#task-3.3
	 */
	private function mapFromCss(string $content, string $slug) {
		if ($this->validator->hasDisallowedSelector(css: $content) === true) {
			return new JSONResponse(
				['error' => $this->l->t('The CSS contains a selector or at-rule other than :root, which is not allowed in a token set.')],
				422
			);
		}

		$declarations = $this->cssParser->parseRootBlock(css: $content);
		$split = $this->validator->validateDeclarations(declarations: $declarations, slug: $slug);
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
	 * Parse and map a W3C Design Tokens JSON upload into the accepted/skipped
	 * split, plus the full DTCG diagnostics (structured skips, errors,
	 * deprecation warnings, declared package version).
	 *
	 * @param string $content The raw JSON upload.
	 *
	 * @return array{
	 *     accepted: array<string, string>,
	 *     skipped: array<int, array{path: string, reason: string, detail?: string}>,
	 *     errors: array<int, array{path: string, reason: string, detail?: string}>,
	 *     importWarnings: array<int, array{path: string, message: string|null}>,
	 *     version: string|null
	 * }|JSONResponse The split plus diagnostics, or a hard-failure error response.
	 *
	 * @spec openspec/changes/custom-token-set-upload/tasks.md#task-3.3
	 * @spec openspec/specs/custom-token-sets/spec.md
	 */
	private function mapFromJson(string $content) {
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
			if ($this->validator->isForbiddenValue(value: (string)$value) === true) {
				return new JSONResponse(
					['error' => $this->l->t('Mapped token %s contains a forbidden value.', [$name])],
					422
				);
			}

			$accepted[$name] = (string)$value;
		}

		if (empty($accepted) === true) {
			// Zero-yield: reject actionably, carrying the full structured
			// diagnostics so the admin can see why nothing mapped.
			return new JSONResponse(
				[
					'error' => $this->l->t('No recognized design tokens were found in the uploaded file.'),
					'imported' => 0,
					'skipped' => $mapped['skipped'],
					'errors' => $mapped['errors'],
					'importWarnings' => $mapped['warnings'],
				],
				422
			);
		}

		return [
			'accepted' => $accepted,
			'skipped' => $mapped['skipped'],
			'errors' => $mapped['errors'],
			'importWarnings' => $mapped['warnings'],
			'version' => $mapped['packageVersion'],
		];
	}//end mapFromJson()

	/**
	 * Store the accepted declarations and build the upload response.
	 *
	 * The response's `warnings` key is always the WCAG contrast warnings
	 * (pre-existing behaviour, unchanged). A DTCG (JSON) upload additionally
	 * carries `errors` (structured, non-recoverable per-token diagnostics),
	 * `importWarnings` (structured `$deprecated` notices — deliberately not
	 * named `warnings` to avoid colliding with the contrast warnings above)
	 * and `version` (the declared package version, when present). A CSS
	 * upload carries none of those three — `$parsed` simply omits them.
	 *
	 * @param string $name The display name.
	 * @param array<string, mixed> $parsed The validated split (`accepted`, `skipped`), plus — for a
	 *                                     DTCG upload — `errors`, `importWarnings` and `version` (see the description above).
	 *
	 * @return JSONResponse The upload result or a collision/storage error.
	 *
	 * @spec openspec/changes/custom-token-set-upload/tasks.md#task-3.3
	 * @spec openspec/specs/custom-token-sets/spec.md
	 * @spec openspec/specs/theming-audit/spec.md#requirement-complete-call-site-coverage
	 */
	private function persist(string $name, array $parsed): JSONResponse {
		try {
			$result = $this->service->store(
				displayName: $name,
				description: trim((string)($this->request->getParam('description', ''))),
				declarations: $parsed['accepted'],
				version: ($parsed['version'] ?? null),
				importWarnings: ($parsed['importWarnings'] ?? [])
			);
		} catch (RuntimeException $e) {
			$code = $e->getCode();
			if ($code < 400 || $code > 599) {
				$code = 500;
			}

			return new JSONResponse(['error' => $e->getMessage()], $code);
		}

		$servedCss = $this->service->getRawContent(id: $result['id']);
		$contentHash = null;
		if ($servedCss !== null) {
			$contentHash = 'sha256:' . substr(hash(algo: 'sha256', data: $servedCss), 0, 12);
		}

		$this->auditService->log(
			action: 'custom_set_uploaded',
			context: [
				'id' => $result['id'],
				'name' => $name,
				'declarationCount' => count($parsed['accepted']),
				'contentHash' => $contentHash,
			]
		);

		$response = [
			'id' => $result['id'],
			'imported' => count($parsed['accepted']),
			'skipped' => $parsed['skipped'],
			'warnings' => $result['warnings'],
		];

		if (isset($parsed['errors']) === true) {
			$response['errors'] = $parsed['errors'];
		}

		if (isset($parsed['importWarnings']) === true) {
			$response['importWarnings'] = $parsed['importWarnings'];
		}

		if (array_key_exists('version', $parsed) === true) {
			$response['version'] = $parsed['version'];
		}

		return new JSONResponse($response);
	}//end persist()

	/**
	 * List stored custom token sets with their metadata and contrast warnings.
	 *
	 * @return JSONResponse The list of custom sets.
	 *
	 * @spec openspec/changes/custom-token-set-upload/tasks.md#task-3.1
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	public function list(): JSONResponse {
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
	public function export(string $id) {
		$content = $this->service->getRawContent(id: $id);
		if ($content === null) {
			return new JSONResponse(['error' => $this->l->t('Token set not found.')], 404);
		}

		return new DataDownloadResponse(
			data: $content,
			filename: $id . '.css',
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
	 * @spec openspec/specs/theming-audit/spec.md#requirement-complete-call-site-coverage
	 */
	#[AuthorizedAdminSetting(Admin::class)]
	public function delete(string $id): JSONResponse {
		if ($this->service->isCustomId(id: $id) === false) {
			return new JSONResponse(['error' => $this->l->t('Only custom token sets can be deleted.')], 400);
		}

		$activeBefore = $this->config->getAppValue(Application::APP_ID, 'token_set', 'nextcloud');
		$servedCss = $this->service->getRawContent(id: $id);

		if ($this->service->delete(id: $id) === false) {
			return new JSONResponse(['error' => $this->l->t('Token set not found.')], 404);
		}

		$contentHash = null;
		if ($servedCss !== null) {
			$contentHash = 'sha256:' . substr(hash(algo: 'sha256', data: $servedCss), 0, 12);
		}

		$this->auditService->log(
			action: 'custom_set_deleted',
			context: [
				'id' => $id,
				'activeReset' => ($activeBefore === $id),
				'contentHash' => $contentHash,
			]
		);

		return new JSONResponse(['status' => 'ok']);
	}//end delete()
}//end class
