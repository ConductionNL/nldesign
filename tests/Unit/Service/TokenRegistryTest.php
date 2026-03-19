<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Service;

use OCA\NLDesign\Service\TokenRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TokenRegistry.
 */
class TokenRegistryTest extends TestCase
{
    /**
     * Test that getTokens returns a non-empty array with correct structure.
     */
    public function testGetTokensReturnsNonEmptyArray(): void
    {
        $tokens = TokenRegistry::getTokens();

        $this->assertNotEmpty($tokens);
        $this->assertIsArray($tokens);
    }

    /**
     * Test that every token entry has the required keys: tab, type, label.
     */
    public function testEveryTokenHasRequiredKeys(): void
    {
        $tokens = TokenRegistry::getTokens();

        foreach ($tokens as $name => $meta) {
            $this->assertArrayHasKey('tab', $meta, "Token {$name} is missing 'tab' key");
            $this->assertArrayHasKey('type', $meta, "Token {$name} is missing 'type' key");
            $this->assertArrayHasKey('label', $meta, "Token {$name} is missing 'label' key");
        }
    }

    /**
     * Test that all token names start with '--'.
     */
    public function testTokenNamesStartWithDoubleDash(): void
    {
        $tokens = TokenRegistry::getTokens();

        foreach (array_keys($tokens) as $name) {
            $this->assertStringStartsWith('--', $name, "Token name '{$name}' should start with '--'");
        }
    }

    /**
     * Test that all token types are either 'color' or 'text'.
     */
    public function testTokenTypesAreValid(): void
    {
        $tokens = TokenRegistry::getTokens();
        $validTypes = ['color', 'text'];

        foreach ($tokens as $name => $meta) {
            $this->assertContains(
                $meta['type'],
                $validTypes,
                "Token {$name} has invalid type '{$meta['type']}'"
            );
        }
    }

    /**
     * Test that all token tabs are among the known set.
     */
    public function testTokenTabsAreValid(): void
    {
        $tokens = TokenRegistry::getTokens();
        $validTabs = ['login', 'content', 'status', 'typography'];

        foreach ($tokens as $name => $meta) {
            $this->assertContains(
                $meta['tab'],
                $validTabs,
                "Token {$name} has invalid tab '{$meta['tab']}'"
            );
        }
    }

    /**
     * Test getTabLabels returns labels for all known tabs.
     */
    public function testGetTabLabelsCoversAllTabs(): void
    {
        $tabLabels = TokenRegistry::getTabLabels();

        $this->assertArrayHasKey('login', $tabLabels);
        $this->assertArrayHasKey('content', $tabLabels);
        $this->assertArrayHasKey('status', $tabLabels);
        $this->assertArrayHasKey('typography', $tabLabels);
        $this->assertCount(4, $tabLabels);
    }

    /**
     * Test getTokenNames returns the same keys as getTokens.
     */
    public function testGetTokenNamesMatchesGetTokensKeys(): void
    {
        $names = TokenRegistry::getTokenNames();
        $tokenKeys = array_keys(TokenRegistry::getTokens());

        $this->assertSame($tokenKeys, $names);
    }

    /**
     * Test isEditable returns true for a known token.
     */
    public function testIsEditableReturnsTrueForKnownToken(): void
    {
        $this->assertTrue(TokenRegistry::isEditable('--color-primary'));
        $this->assertTrue(TokenRegistry::isEditable('--font-face'));
    }

    /**
     * Test isEditable returns false for an unknown token.
     */
    public function testIsEditableReturnsFalseForUnknownToken(): void
    {
        $this->assertFalse(TokenRegistry::isEditable('--nonexistent-token'));
        $this->assertFalse(TokenRegistry::isEditable(''));
        $this->assertFalse(TokenRegistry::isEditable('color-primary'));
    }

    /**
     * Test getTokensByTab groups tokens correctly.
     */
    public function testGetTokensByTabGroupsCorrectly(): void
    {
        $grouped = TokenRegistry::getTokensByTab();

        // Should have exactly 4 tab groups.
        $this->assertCount(4, $grouped);
        $this->assertArrayHasKey('login', $grouped);
        $this->assertArrayHasKey('content', $grouped);
        $this->assertArrayHasKey('status', $grouped);
        $this->assertArrayHasKey('typography', $grouped);

        // Every token within a group should have the matching tab value.
        foreach ($grouped as $tab => $tokens) {
            foreach ($tokens as $name => $meta) {
                $this->assertSame(
                    $tab,
                    $meta['tab'],
                    "Token {$name} is in tab group '{$tab}' but has tab value '{$meta['tab']}'"
                );
            }
        }
    }

    /**
     * Test that the total count of grouped tokens equals the total token count.
     */
    public function testGetTokensByTabPreservesAllTokens(): void
    {
        $grouped = TokenRegistry::getTokensByTab();
        $totalGrouped = 0;
        foreach ($grouped as $tokens) {
            $totalGrouped += count($tokens);
        }

        $this->assertSame(count(TokenRegistry::getTokens()), $totalGrouped);
    }

    /**
     * Test that --color-primary exists and belongs to the login tab.
     */
    public function testColorPrimaryIsInLoginTab(): void
    {
        $tokens = TokenRegistry::getTokens();

        $this->assertArrayHasKey('--color-primary', $tokens);
        $this->assertSame('login', $tokens['--color-primary']['tab']);
        $this->assertSame('color', $tokens['--color-primary']['type']);
    }
}
