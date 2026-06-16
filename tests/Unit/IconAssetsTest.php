<?php

/**
 * Inventory regression test for the Amsterdam Design System icon and logo assets.
 *
 * Guards the icon-assets capability contract: the on-disk SVG set under img/icons/
 * and img/logos/ must match the inventory documented in img/ICONS.md (counts and
 * sampled names), Fill variants must pair with their base icon, assets must be safe
 * standalone SVG (no <script>/event handlers), the MPL-2.0 attribution must remain
 * co-located with the assets, and the icon/logo counts must agree across README.md,
 * docs/reference/icons.md and img/ICONS.md. A rename or removal of any asset, or a
 * drift between docs and filesystem, fails this test.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @spec openspec/changes/icon-assets/tasks.md#task-2.1
 * @spec openspec/changes/icon-assets/tasks.md#task-2.2
 * @spec openspec/changes/icon-assets/tasks.md#task-2.3
 * @spec openspec/changes/icon-assets/tasks.md#task-2.4
 */

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Static-asset inventory regression test (no Nextcloud runtime required).
 */
class IconAssetsTest extends TestCase
{
    /**
     * Number of icon SVG files the inventory is expected to contain.
     */
    private const EXPECTED_ICON_COUNT = 344;

    /**
     * Repository root, derived from this test file's location.
     */
    private function repoRoot(): string
    {
        return \dirname(__DIR__, 2);
    }

    /**
     * Absolute path to the icons directory.
     */
    private function iconsDir(): string
    {
        return $this->repoRoot() . '/img/icons';
    }

    /**
     * Absolute path to the logos directory.
     */
    private function logosDir(): string
    {
        return $this->repoRoot() . '/img/logos';
    }

    /**
     * List of icon base names (without the .svg suffix) found on disk.
     *
     * @return array<string>
     */
    private function diskIconNames(): array
    {
        $files = glob($this->iconsDir() . '/*.svg') ?: [];
        return array_map(
            static fn (string $p): string => basename($p, '.svg'),
            $files
        );
    }

    /**
     * List of logo base names (without the .svg suffix) found on disk.
     *
     * @return array<string>
     */
    private function diskLogoNames(): array
    {
        $files = glob($this->logosDir() . '/*.svg') ?: [];
        return array_map(
            static fn (string $p): string => basename($p, '.svg'),
            $files
        );
    }

    /**
     * Read a documentation file from the repository root.
     */
    private function readDoc(string $relativePath): string
    {
        $path = $this->repoRoot() . '/' . $relativePath;
        $this->assertFileExists($path, "Expected documentation file to exist: {$relativePath}");
        $contents = file_get_contents($path);
        $this->assertIsString($contents, "Could not read {$relativePath}");
        return $contents;
    }

    /**
     * The icon directory holds exactly the documented number of SVG files.
     */
    public function testIconCountMatchesDocumentedTotal(): void
    {
        $this->assertDirectoryExists($this->iconsDir());
        $this->assertCount(
            self::EXPECTED_ICON_COUNT,
            $this->diskIconNames(),
            'On-disk icon count must equal the documented inventory total (' . self::EXPECTED_ICON_COUNT . ').'
        );
    }

    /**
     * Every icon name listed in img/ICONS.md resolves to a real file on disk.
     *
     * ICONS.md lists a representative sample (the first N icons then "... and X more"),
     * so this checks that every name actually enumerated in the document exists.
     */
    public function testDocumentedIconSampleResolvesOnDisk(): void
    {
        $icons = $this->readDoc('img/ICONS.md');

        // Capture the "## Icons" block up to the next "##" heading.
        $this->assertMatchesRegularExpression('/##\s*Icons/i', $icons, 'ICONS.md must contain an Icons section.');
        $iconsSection = $this->sectionBetween($icons, '## Icons', '## ');

        $documented = $this->bulletNames($iconsSection);
        $this->assertNotEmpty($documented, 'ICONS.md must enumerate at least one icon name.');

        $onDisk = array_flip($this->diskIconNames());
        foreach ($documented as $name) {
            $this->assertArrayHasKey(
                $name,
                $onDisk,
                "Icon documented in img/ICONS.md has no file img/icons/{$name}.svg"
            );
        }
    }

    /**
     * The logo directory count matches the count stated in img/ICONS.md.
     */
    public function testLogoCountMatchesDocumentedTotal(): void
    {
        $this->assertDirectoryExists($this->logosDir());

        $icons = $this->readDoc('img/ICONS.md');
        $this->assertMatchesRegularExpression(
            '/##\s*Logos\s*\((\d+)\s*total\)/i',
            $icons,
            'ICONS.md must state a logo total.'
        );
        preg_match('/##\s*Logos\s*\((\d+)\s*total\)/i', $icons, $m);
        $documentedLogoTotal = (int) $m[1];

        $this->assertSame(
            $documentedLogoTotal,
            \count($this->diskLogoNames()),
            'On-disk logo count must equal the total documented in img/ICONS.md.'
        );
    }

    /**
     * Every logo name enumerated in img/ICONS.md resolves to a real file on disk.
     */
    public function testDocumentedLogoSampleResolvesOnDisk(): void
    {
        $icons = $this->readDoc('img/ICONS.md');
        $logosSection = $this->sectionBetween($icons, '## Logos', '## ');

        $documented = $this->bulletNames($logosSection);
        $this->assertNotEmpty($documented, 'ICONS.md must enumerate at least one logo name.');

        $onDisk = array_flip($this->diskLogoNames());
        foreach ($documented as $name) {
            $this->assertArrayHasKey(
                $name,
                $onDisk,
                "Logo documented in img/ICONS.md has no file img/logos/{$name}.svg"
            );
        }
    }

    /**
     * Every *Fill.svg icon has its base-variant counterpart (naming convention).
     */
    public function testFillVariantsPairWithBaseIcon(): void
    {
        $names = array_flip($this->diskIconNames());
        $orphans = [];
        foreach (array_keys($names) as $name) {
            if (str_ends_with($name, 'Fill')) {
                $base = substr($name, 0, -\strlen('Fill'));
                if ($base !== '' && !isset($names[$base])) {
                    $orphans[] = $name;
                }
            }
        }

        $this->assertSame(
            [],
            $orphans,
            'These Fill icons have no base counterpart: ' . implode(', ', $orphans)
        );
    }

    /**
     * A sample of assets are well-formed standalone SVG with no scriptable content.
     */
    public function testSampledAssetsAreSafeStandaloneSvg(): void
    {
        $icons = glob($this->iconsDir() . '/*.svg') ?: [];
        $logos = glob($this->logosDir() . '/*.svg') ?: [];
        $this->assertNotEmpty($icons, 'No icon SVG files found to sample.');

        // Deterministic sample: sort and take a spread across the set, plus all logos.
        sort($icons);
        $sample = [];
        $step = (int) max(1, floor(\count($icons) / 40));
        for ($i = 0; $i < \count($icons); $i += $step) {
            $sample[] = $icons[$i];
        }
        $sample = array_merge($sample, $logos);

        foreach ($sample as $file) {
            $svg = file_get_contents($file);
            $this->assertIsString($svg, "Could not read {$file}");
            $rel = basename($file);

            $this->assertMatchesRegularExpression(
                '/<svg[\s>]/i',
                $svg,
                "{$rel} must contain an <svg> root element."
            );
            $this->assertDoesNotMatchRegularExpression(
                '/<script[\s>]/i',
                $svg,
                "{$rel} must not contain a <script> element."
            );
            $this->assertDoesNotMatchRegularExpression(
                '/\son[a-z]+\s*=/i',
                $svg,
                "{$rel} must not contain inline event-handler attributes."
            );

            // Well-formedness: the SVG must parse as XML.
            $previous = libxml_use_internal_errors(true);
            $doc = simplexml_load_string($svg);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            $this->assertNotFalse($doc, "{$rel} must be well-formed XML/SVG.");
        }
    }

    /**
     * img/ICONS.md carries the @amsterdam/design-system-assets MPL-2.0 attribution.
     */
    public function testLicenseNoticeTravelsWithAssets(): void
    {
        $icons = $this->readDoc('img/ICONS.md');

        $this->assertStringContainsString(
            '@amsterdam/design-system-assets',
            $icons,
            'img/ICONS.md must attribute the icons to @amsterdam/design-system-assets.'
        );
        $this->assertMatchesRegularExpression(
            '/Mozilla Public License 2\.0|MPL[\s-]?2\.0/i',
            $icons,
            'img/ICONS.md must name the Mozilla Public License 2.0.'
        );
    }

    /**
     * The icon and logo counts agree across README.md, docs/reference/icons.md and img/ICONS.md.
     */
    public function testCountsAgreeAcrossDocuments(): void
    {
        $iconCount = \count($this->diskIconNames());
        $logoCount = \count($this->diskLogoNames());

        $readme = $this->readDoc('README.md');
        $docs = $this->readDoc('docs/reference/icons.md');

        // README and docs must state the real icon count and never the wrong one.
        $this->assertStringContainsString(
            (string) $iconCount,
            $readme,
            'README.md must state the actual icon count.'
        );
        $this->assertStringContainsString(
            (string) $logoCount . ' logos',
            $readme,
            'README.md must state the actual logo count.'
        );
        $this->assertStringContainsString(
            (string) $logoCount . ' SVG files',
            $docs,
            'docs/reference/icons.md must state the actual logo file count.'
        );
        $this->assertStringContainsString(
            (string) $logoCount . ' logos',
            $docs,
            'docs/reference/icons.md must state the actual logo count.'
        );
    }

    /**
     * Every icon/logo filename used in a consumption example across the docs
     * resolves to a real asset on disk.
     *
     * This guards against documentation referencing icons that do not exist
     * (e.g. an upstream rename of MagnifyingGlass -> Search): a consumer copying
     * the snippet would otherwise get a broken image.
     */
    public function testDocumentedConsumptionExamplesResolveOnDisk(): void
    {
        $docs = [
            'README.md',
            'docs/reference/icons.md',
            'img/ICONS.md',
        ];

        $haveIcon = array_flip($this->diskIconNames());
        $haveLogo = array_flip($this->diskLogoNames());

        foreach ($docs as $doc) {
            $contents = $this->readDoc($doc);

            preg_match_all('#icons/([A-Za-z0-9_-]+)\.svg#', $contents, $iconRefs);
            foreach (array_unique($iconRefs[1]) as $name) {
                $this->assertArrayHasKey(
                    $name,
                    $haveIcon,
                    "{$doc} references a nonexistent icon img/icons/{$name}.svg"
                );
            }

            preg_match_all('#logos/([A-Za-z0-9_-]+)\.svg#', $contents, $logoRefs);
            foreach (array_unique($logoRefs[1]) as $name) {
                $this->assertArrayHasKey(
                    $name,
                    $haveLogo,
                    "{$doc} references a nonexistent logo img/logos/{$name}.svg"
                );
            }
        }
    }

    /**
     * The README "View Icon Documentation" link resolves to an existing file.
     */
    public function testReadmeIconDocumentationLinkResolves(): void
    {
        $readme = $this->readDoc('README.md');

        $this->assertMatchesRegularExpression(
            '/\[View Icon Documentation[^\]]*\]\(([^)]+)\)/',
            $readme,
            'README.md must contain a "View Icon Documentation" link.'
        );
        preg_match('/\[View Icon Documentation[^\]]*\]\(([^)]+)\)/', $readme, $m);
        $target = $m[1];

        $this->assertSame(
            'img/ICONS.md',
            $target,
            'The README icon-documentation link must point at img/ICONS.md.'
        );
        $this->assertFileExists(
            $this->repoRoot() . '/' . $target,
            "README icon-documentation link target does not exist: {$target}"
        );
    }

    /**
     * Extract the section of a markdown document starting at a heading and ending
     * before the next occurrence of $stopMarker.
     */
    private function sectionBetween(string $doc, string $startHeading, string $stopMarker): string
    {
        $start = strpos($doc, $startHeading);
        if ($start === false) {
            return '';
        }
        $afterHeading = $start + \strlen($startHeading);
        $next = strpos($doc, $stopMarker, $afterHeading);
        if ($next === false) {
            return substr($doc, $afterHeading);
        }
        return substr($doc, $afterHeading, $next - $afterHeading);
    }

    /**
     * Extract bullet-list item names ("- Name") from a markdown section,
     * skipping the "... and N more" continuation line.
     *
     * @return array<string>
     */
    private function bulletNames(string $section): array
    {
        $names = [];
        foreach (preg_split('/\r?\n/', $section) ?: [] as $line) {
            if (preg_match('/^-\s+(.+?)\s*$/', $line, $m) === 1) {
                $name = $m[1];
                if (stripos($name, 'and ') === 0 || stripos($name, '...') === 0) {
                    continue;
                }
                $names[] = $name;
            }
        }
        return $names;
    }
}
