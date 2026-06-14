<?php

/**
 * Unit tests for DesignTokensMapper.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @spec openspec/changes/custom-token-set-upload/tasks.md#task-5.2
 */

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Service;

use OCA\NLDesign\Service\DesignTokensMapper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the W3C Design Tokens (DTCG) mapper.
 *
 * Covers tasks.md#task-5.2: mapped tokens, unmapped → skipped, and the
 * longest-suffix-match preference. Malformed JSON is handled by the controller
 * (json_decode), not the mapper, so that path is covered in the controller/Newman.
 */
class DesignTokensMapperTest extends TestCase
{

    /**
     * The mapper under test.
     *
     * @var DesignTokensMapper
     */
    private DesignTokensMapper $mapper;

    /**
     * Set up the mapper before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new DesignTokensMapper();
    }//end setUp()

    /**
     * DTCG color tokens map onto the --nldesign-* vocabulary.
     */
    public function testColorTokensMapToNldesignVocabulary(): void
    {
        $document = [
            'color' => [
                'primary'    => ['$type' => 'color', '$value' => '#154273'],
                'on-primary' => ['$type' => 'color', '$value' => '#ffffff'],
            ],
        ];

        $result = $this->mapper->map(document: $document);

        $this->assertSame('#154273', $result['declarations']['--nldesign-color-primary']);
        $this->assertSame('#ffffff', $result['declarations']['--nldesign-color-primary-text']);
        $this->assertSame(2, $result['imported']);
        $this->assertSame([], $result['skipped']);
    }//end testColorTokensMapToNldesignVocabulary()

    /**
     * Unmapped tokens degrade to skipped counts, never errors.
     */
    public function testUnmappedTokensAreSkipped(): void
    {
        $document = [
            'color'  => ['primary' => ['$type' => 'color', '$value' => '#154273']],
            'shadow' => ['elevation-1' => ['$type' => 'shadow', '$value' => '0 1px 2px']],
        ];

        $result = $this->mapper->map(document: $document);

        $this->assertSame(1, $result['imported']);
        $this->assertContains('shadow.elevation-1', $result['skipped']);
    }//end testUnmappedTokensAreSkipped()

    /**
     * The longest matching suffix wins (primary-text over primary).
     */
    public function testLongestSuffixWins(): void
    {
        $document = [
            'theme' => [
                'color' => [
                    'primary'      => ['$type' => 'color', '$value' => '#111111'],
                    'primary-text' => ['$type' => 'color', '$value' => '#eeeeee'],
                ],
            ],
        ];

        $result = $this->mapper->map(document: $document);

        $this->assertSame('#111111', $result['declarations']['--nldesign-color-primary']);
        $this->assertSame('#eeeeee', $result['declarations']['--nldesign-color-primary-text']);
    }//end testLongestSuffixWins()

    /**
     * Nested groups and $-prefixed metadata keys are handled.
     */
    public function testNestedGroupsAndMetadataKeys(): void
    {
        $document = [
            '$description' => 'My theme',
            'brand'        => [
                '$type'   => 'color',
                'primary' => ['$value' => '#007bc7'],
            ],
            'typography'   => [
                'font-family' => ['$type' => 'fontFamily', '$value' => 'Inter, sans-serif'],
            ],
        ];

        $result = $this->mapper->map(document: $document);

        $this->assertSame('#007bc7', $result['declarations']['--nldesign-color-primary']);
        $this->assertSame('Inter, sans-serif', $result['declarations']['--nldesign-font-family']);
    }//end testNestedGroupsAndMetadataKeys()

    /**
     * The published mapping table is exposed for transparency.
     */
    public function testMappingTableIsExposed(): void
    {
        $table = $this->mapper->getMappingTable();
        $this->assertArrayHasKey('color.primary', $table);
        $this->assertSame('--nldesign-color-primary', $table['color.primary']);
    }//end testMappingTableIsExposed()
}//end class
