<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Domain\Profile;

use JsonException;
use OCA\NLDesign\Domain\Profile\ProfileModeValidator;
use OCA\NLDesign\Domain\Profile\ProfilePackValidator;
use PHPUnit\Framework\TestCase;

class ProfilePackValidatorTest extends TestCase
{
    public function testAcceptsAndCanonicalizesTheDocumentedExample(): void
    {
        $pack = $this->readExamplePack();
        $pack['profile']['tokens']['light']['primary'] = '#183A37';

        $result = $this->createValidator()->decode(content: $this->encode(value: $pack));

        self::assertSame('ok', $result['status']);
        self::assertSame('voorbeeld-profiel', $result['profile']['id']);
        self::assertSame('1.0.0', $result['profile']['version']);
        self::assertSame('#183a37', $result['profile']['tokens']['light']['primary']);
        self::assertSame('nextcloud-core-v1', $result['profile']['projection']);
    }

    public function testRejectsUnknownTokenFieldsInsteadOfAcceptingCss(): void
    {
        $pack = $this->readExamplePack();
        $pack['profile']['tokens']['css'] = '@import url(https://example.invalid/evil.css);';

        $result = $this->createValidator()->decode(content: $this->encode(value: $pack));

        self::assertSame('invalid_pack', $result['status']);
        self::assertSame('invalid_profile', $result['code']);
    }

    public function testRejectsColoursWithoutMinimumTextContrast(): void
    {
        $pack = $this->readExamplePack();
        $pack['profile']['tokens']['light'] = [
            'primary'       => '#ffffff',
            'primary_text'  => '#ffffff',
            'primary_hover' => '#eeeeee',
        ];

        $result = $this->createValidator()->decode(content: $this->encode(value: $pack));

        self::assertSame('invalid_pack', $result['status']);
        self::assertSame('invalid_profile', $result['code']);
    }

    public function testRejectsMutableOrIncompleteVersionIdentifiers(): void
    {
        foreach (['1', '1.0', 'latest', 'v1.0.0', '01.0.0', '1.0.0-01', '1.0.0+build'] as $version) {
            $pack = $this->readExamplePack();
            $pack['profile']['version'] = $version;

            $result = $this->createValidator()->decode(content: $this->encode(value: $pack));

            self::assertSame('invalid_pack', $result['status']);
        }
    }

    public function testAcceptsSupportedSemanticPrereleaseVersions(): void
    {
        foreach (['0.0.0', '1.2.3-rc.1', '10.20.30-alpha-1'] as $version) {
            $pack = $this->readExamplePack();
            $pack['profile']['version'] = $version;

            $result = $this->createValidator()->decode(content: $this->encode(value: $pack));

            self::assertSame('ok', $result['status']);
            self::assertSame($version, $result['profile']['version']);
        }
    }

    public function testRejectsOversizedAndMalformedEnvelopes(): void
    {
        $validator = $this->createValidator();

        self::assertSame(
            'invalid_pack_size',
            $validator->decode(content: str_repeat('x', 65537))['code']
        );
        self::assertSame(
            'invalid_json',
            $validator->decode(content: '{')['code']
        );

        $pack = $this->readExamplePack();
        $pack['unexpected'] = true;
        self::assertSame(
            'invalid_envelope',
            $validator->decode(content: $this->encode(value: $pack))['code']
        );
    }

    private function createValidator(): ProfilePackValidator
    {
        return new ProfilePackValidator(modeValidator: new ProfileModeValidator());
    }

    /**
     * @return array<string, mixed>
     */
    private function readExamplePack(): array
    {
        $content = file_get_contents(dirname(__DIR__, 4).'/examples/profile-pack.v1.json');
        self::assertIsString($content);

        $decoded = json_decode(json: $content, associative: true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        return $decoded;
    }

    /**
     * @param array<string, mixed> $value
     *
     * @throws JsonException
     */
    private function encode(array $value): string
    {
        return json_encode(value: $value, flags: JSON_THROW_ON_ERROR);
    }
}
