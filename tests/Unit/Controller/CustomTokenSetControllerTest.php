<?php

/**
 * Unit tests for CustomTokenSetController.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @author  Conduction <info@conduction.nl>
 * @license EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @spec openspec/changes/harden-custom-token-set-value-validation/tasks.md#task-2.4
 */

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Controller;

use OCA\NLDesign\Controller\CustomTokenSetController;
use OCA\NLDesign\Service\CssParserService;
use OCA\NLDesign\Service\CustomTokenSetService;
use OCA\NLDesign\Service\CustomTokenSetValidator;
use OCA\NLDesign\Service\DesignTokensMapper;
use OCA\NLDesign\Service\ThemingAuditService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the upload controller's W3C Design Tokens JSON path.
 *
 * Covers harden-custom-token-set-value-validation/tasks.md#task-2.4: proves
 * the same isForbiddenValue() gate that blocks semicolon/comment-marker
 * smuggling on the CSS upload path also blocks it when the value arrives via
 * a mapped DTCG JSON token — mapFromJson() is private, so it is exercised
 * indirectly through the public upload() entry point, wired with the real
 * (unmocked) CustomTokenSetValidator and DesignTokensMapper so the gate
 * itself is under test, not a stand-in.
 */
class CustomTokenSetControllerTest extends TestCase
{

    /**
     * Temp upload files created during a test, cleaned up in tearDown().
     *
     * @var string[]
     */
    private array $tempFiles = [];

    /**
     * Remove any temp upload files created by a test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (file_exists($path) === true) {
                unlink($path);
            }
        }

        $this->tempFiles = [];
        parent::tearDown();
    }//end tearDown()

    /**
     * Build a controller wired with the real validator/mapper/CSS parser and
     * a request mock that serves the given JSON body as an uploaded
     * `.tokens.json` file.
     *
     * @param string                        $jsonBody The raw JSON upload content.
     * @param CustomTokenSetService|null    $service  A pre-configured service mock, or null for a bare one.
     *
     * @return CustomTokenSetController The wired controller.
     */
    private function buildControllerWithJsonUpload(string $jsonBody, ?CustomTokenSetService $service = null): CustomTokenSetController
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'nldesign-test-');
        file_put_contents($tmpFile, $jsonBody);
        $this->tempFiles[] = $tmpFile;

        $request = $this->createMock(IRequest::class);
        $request->method('getParam')->willReturnMap(
            [
                ['name', '', 'Gemeente Voorbeeld'],
                ['description', '', ''],
            ]
        );
        $request->method('getUploadedFile')->with('file')->willReturn(
            [
                'tmp_name' => $tmpFile,
                'size'     => strlen($jsonBody),
                'name'     => 'upload.tokens.json',
            ]
        );

        if ($service === null) {
            $service = $this->createMock(CustomTokenSetService::class);
            $service->method('slugify')->willReturn('gemeente-voorbeeld');
        }

        $l = $this->createMock(IL10N::class);
        $l->method('t')->willReturnCallback(
            static function (string $text, $parameters = []): string {
                return (empty($parameters) === true) ? $text : vsprintf($text, (array) $parameters);
            }
        );

        return new CustomTokenSetController(
            appName: 'nldesign',
            request: $request,
            service: $service,
            validator: new CustomTokenSetValidator(),
            cssParser: new CssParserService(),
            mapper: new DesignTokensMapper(),
            l: $l,
            auditService: $this->createMock(ThemingAuditService::class),
            config: $this->createMock(IConfig::class)
        );
    }//end buildControllerWithJsonUpload()

    /**
     * A semicolon-smuggled value mapped from a DTCG JSON token is rejected by
     * the same isForbiddenValue() gate the CSS path uses — the injection is
     * not limited to the CSS upload format.
     *
     * @return void
     */
    public function testJsonUploadWithSemicolonSmugglingIsRejected(): void
    {
        $json = json_encode(
            [
                'color' => [
                    'primary' => [
                        '$type'  => 'color',
                        '$value' => 'red; --nldesign-evil: url(javascript:alert(1))',
                    ],
                ],
            ]
        );

        $controller = $this->buildControllerWithJsonUpload(jsonBody: $json);
        $response   = $controller->upload();

        $this->assertSame(expected: 422, actual: $response->getStatus());
        $this->assertArrayHasKey(key: 'error', array: $response->getData());
        $this->assertStringContainsString(
            needle: 'forbidden value',
            haystack: $response->getData()['error']
        );
    }//end testJsonUploadWithSemicolonSmugglingIsRejected()

    /**
     * A comment-marker-smuggled value mapped from a DTCG JSON token is
     * rejected the same way.
     *
     * @return void
     */
    public function testJsonUploadWithCommentMarkerSmugglingIsRejected(): void
    {
        $json = json_encode(
            [
                'color' => [
                    'primary' => [
                        '$type'  => 'color',
                        '$value' => 'red */ .evil { color: red',
                    ],
                ],
            ]
        );

        $controller = $this->buildControllerWithJsonUpload(jsonBody: $json);
        $response   = $controller->upload();

        $this->assertSame(expected: 422, actual: $response->getStatus());
        $this->assertArrayHasKey(key: 'error', array: $response->getData());
    }//end testJsonUploadWithCommentMarkerSmugglingIsRejected()

    /**
     * A legitimate mapped value (no injection characters) is accepted and
     * stored — no regression for benign W3C Design Tokens uploads, matching
     * the spec delta's "Legitimate values ... still succeed" scenario.
     *
     * @return void
     */
    public function testJsonUploadWithCleanValueIsAccepted(): void
    {
        $json = json_encode(
            [
                'color' => [
                    'primary' => [
                        '$type'  => 'color',
                        '$value' => '#154273',
                    ],
                ],
            ]
        );

        $service = $this->createMock(CustomTokenSetService::class);
        $service->method('slugify')->willReturn('gemeente-voorbeeld');
        $service->expects($this->once())
            ->method('store')
            ->with(
                $this->equalTo('Gemeente Voorbeeld'),
                $this->equalTo(''),
                $this->equalTo(['--nldesign-color-primary' => '#154273'])
            )
            ->willReturn(
                [
                    'id'       => 'custom-gemeente-voorbeeld',
                    'warnings' => [],
                ]
            );

        $controller = $this->buildControllerWithJsonUpload(jsonBody: $json, service: $service);
        $response   = $controller->upload();

        $this->assertSame(expected: 200, actual: $response->getStatus());
        $this->assertSame(expected: 'custom-gemeente-voorbeeld', actual: $response->getData()['id']);
        $this->assertSame(expected: 1, actual: $response->getData()['imported']);
    }//end testJsonUploadWithCleanValueIsAccepted()
}//end class
