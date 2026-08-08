<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Infrastructure\Profile;

use OCA\NLDesign\Application\Profile\ProfileCssCompiler;
use OCA\NLDesign\Domain\Profile\ProfileModeValidator;
use OCA\NLDesign\Domain\Profile\ProfilePackValidator;
use OCA\NLDesign\Infrastructure\Profile\InstalledProfileRecordCodec;
use PHPUnit\Framework\TestCase;

class InstalledProfileRecordCodecTest extends TestCase
{
    public function testEncodedRecordRoundTripsToRuntimeMetadata(): void
    {
        $compiler = new ProfileCssCompiler();
        $codec    = $this->createCodec(compiler: $compiler);
        $profile  = $this->profile();

        $encoded = $codec->encode(
            profile: $profile,
            css: $compiler->compile(profile: $profile),
            actor: 'admin'
        );

        self::assertNotNull($encoded);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $encoded['content_hash']);

        $decoded = $codec->decode(content: $encoded['content']);
        self::assertNotNull($decoded);
        self::assertSame('installed', $decoded['metadata']['origin']);
        self::assertTrue($decoded['metadata']['installed']);
        self::assertSame('#183a37', $decoded['metadata']['theming']['primary_color']);
        self::assertSame($profile['tokens']['dark'], $decoded['metadata']['preview']['dark']);
        self::assertSame($compiler->compile(profile: $profile), $decoded['css']);
    }

    public function testRejectsCssTamperingEvenWhenTheEnvelopeIsValidJson(): void
    {
        $compiler = new ProfileCssCompiler();
        $codec    = $this->createCodec(compiler: $compiler);
        $encoded  = $codec->encode(
            profile: $this->profile(),
            css: $compiler->compile(profile: $this->profile()),
            actor: 'admin'
        );
        self::assertNotNull($encoded);

        $record = json_decode($encoded['content'], true, 512, JSON_THROW_ON_ERROR);
        $record['css'] .= "\n@import url(https://example.invalid/evil.css);";

        self::assertNull($codec->decode(content: json_encode($record, JSON_THROW_ON_ERROR)));
    }

    public function testRejectsDigestAndEnvelopeFieldTampering(): void
    {
        $compiler = new ProfileCssCompiler();
        $codec    = $this->createCodec(compiler: $compiler);
        $encoded  = $codec->encode(
            profile: $this->profile(),
            css: $compiler->compile(profile: $this->profile()),
            actor: 'admin'
        );
        self::assertNotNull($encoded);

        $record = json_decode($encoded['content'], true, 512, JSON_THROW_ON_ERROR);
        $record['content_hash'] = str_repeat('0', 64);
        self::assertNull($codec->decode(content: json_encode($record, JSON_THROW_ON_ERROR)));

        $record = json_decode($encoded['content'], true, 512, JSON_THROW_ON_ERROR);
        $record['unexpected'] = true;
        self::assertNull($codec->decode(content: json_encode($record, JSON_THROW_ON_ERROR)));
    }

    public function testUntrustedActorMetadataFallsBackToSystem(): void
    {
        $compiler = new ProfileCssCompiler();
        $codec    = $this->createCodec(compiler: $compiler);
        $encoded  = $codec->encode(
            profile: $this->profile(),
            css: $compiler->compile(profile: $this->profile()),
            actor: "admin\nforged"
        );
        self::assertNotNull($encoded);

        $decoded = $codec->decode(content: $encoded['content']);
        self::assertNotNull($decoded);
        self::assertSame('system', $decoded['metadata']['installed_by']);
    }

    private function createCodec(ProfileCssCompiler $compiler): InstalledProfileRecordCodec
    {
        return new InstalledProfileRecordCodec(
            validator: new ProfilePackValidator(modeValidator: new ProfileModeValidator()),
            compiler: $compiler
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(): array
    {
        return [
            'id'              => 'voorbeeld-profiel',
            'version'         => '1.0.0',
            'name'            => 'Voorbeeldprofiel',
            'description'     => 'Installeerbaar profiel',
            'publisher'       => 'Lokale beheerder',
            'license'         => 'CC0-1.0',
            'source'          => 'unit-test',
            'source_revision' => 'fixture-1',
            'projection'      => 'nextcloud-core-v1',
            'tokens'          => [
                'font_stack' => 'fira-sans',
                'light'      => [
                    'primary'       => '#183a37',
                    'primary_text'  => '#faf7f0',
                    'primary_hover' => '#3f5a57',
                ],
                'dark'       => [
                    'primary'       => '#efd6ac',
                    'primary_text'  => '#04151f',
                    'primary_hover' => '#e7ece8',
                ],
            ],
        ];
    }
}
