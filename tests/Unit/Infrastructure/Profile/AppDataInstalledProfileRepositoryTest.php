<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Infrastructure\Profile;

use OCA\NLDesign\Application\Profile\ProfileCssCompiler;
use OCA\NLDesign\Domain\Profile\ProfileModeValidator;
use OCA\NLDesign\Domain\Profile\ProfilePackValidator;
use OCA\NLDesign\Infrastructure\Profile\AppDataInstalledProfileRepository;
use OCA\NLDesign\Infrastructure\Profile\InstalledProfileRecordCodec;
use OCP\Files\IAppData;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AppDataInstalledProfileRepositoryTest extends TestCase
{
    /** @var array<string, ISimpleFile&MockObject> */
    private array $files = [];

    public function testImmutableVersionRoundTripAndRemoval(): void
    {
        $compiler   = new ProfileCssCompiler();
        $repository = $this->createRepository(compiler: $compiler);
        $profile    = $this->profile();
        $css        = $compiler->compile(profile: $profile);

        $installed = $repository->install(profile: $profile, css: $css, actor: 'admin');
        self::assertSame('ok', $installed['status']);
        self::assertSame('1.0.0', $installed['metadata']['version']);
        self::assertCount(1, $this->files);

        $found = $repository->find(profileId: 'voorbeeld-profiel', profileVersion: '1.0.0');
        self::assertNotNull($found);
        self::assertSame($css, $found['css']);
        self::assertSame([$found], $repository->listRecords());

        self::assertSame(
            'noop',
            $repository->install(profile: $profile, css: $css, actor: 'another-admin')['status']
        );
        self::assertCount(1, $this->files);

        self::assertSame(
            ['status' => 'ok'],
            $repository->remove(profileId: 'voorbeeld-profiel', profileVersion: '1.0.0')
        );
        self::assertSame([], $repository->listRecords());
    }

    public function testSameIdentityWithDifferentContentIsAConflict(): void
    {
        $compiler   = new ProfileCssCompiler();
        $repository = $this->createRepository(compiler: $compiler);
        $profile    = $this->profile();

        self::assertSame(
            'ok',
            $repository->install(
                profile: $profile,
                css: $compiler->compile(profile: $profile),
                actor: 'admin'
            )['status']
        );

        $profile['tokens']['light']['primary'] = '#000000';
        self::assertSame(
            'version_conflict',
            $repository->install(
                profile: $profile,
                css: $compiler->compile(profile: $profile),
                actor: 'admin'
            )['status']
        );
        self::assertCount(1, $this->files);
    }

    public function testInvalidIdentityNeverReachesAppData(): void
    {
        $repository = $this->createRepository(compiler: new ProfileCssCompiler());

        self::assertNull($repository->find(profileId: '../secret', profileVersion: '1.0.0'));
        self::assertSame(
            ['status' => 'invalid_profile'],
            $repository->remove(profileId: 'valid', profileVersion: 'latest')
        );
        self::assertSame([], $this->files);
    }

    public function testFailedReadBackRemovesTheNewRecord(): void
    {
        $compiler   = new ProfileCssCompiler();
        $repository = $this->createRepository(
            compiler: $compiler,
            failNewFileReadBack: true
        );
        $profile = $this->profile();

        self::assertSame(
            'storage_failed',
            $repository->install(
                profile: $profile,
                css: $compiler->compile(profile: $profile),
                actor: 'admin'
            )['status']
        );
        self::assertSame([], $this->files);
    }

    private function createRepository(
        ProfileCssCompiler $compiler,
        bool $failNewFileReadBack=false
    ): AppDataInstalledProfileRepository {
        $folder = $this->createMock(ISimpleFolder::class);
        $folder->method('getDirectoryListing')->willReturnCallback(fn (): array => array_values($this->files));
        $folder->method('fileExists')->willReturnCallback(
            fn (string $name): bool => isset($this->files[$name])
        );
        $folder->method('getFile')->willReturnCallback(
            fn (string $name): ISimpleFile => $this->files[$name]
        );
        $folder->method('newFile')->willReturnCallback(
            function (string $name, mixed $content=null) use ($failNewFileReadBack): ISimpleFile {
                self::assertIsString($content);
                $file = $this->createMock(ISimpleFile::class);
                $file->method('getName')->willReturn($name);
                $file->method('getSize')->willReturnCallback(fn (): int => strlen($content));
                if ($failNewFileReadBack === true) {
                    $file->method('getContent')->willThrowException(new \RuntimeException('read failed'));
                } else {
                    $file->method('getContent')->willReturn($content);
                }
                $file->method('delete')->willReturnCallback(function () use ($name): void {
                    unset($this->files[$name]);
                });
                $this->files[$name] = $file;
                return $file;
            }
        );

        $appData = $this->createMock(IAppData::class);
        $appData->method('getFolder')->willReturn($folder);

        $validator = new ProfilePackValidator(modeValidator: new ProfileModeValidator());
        return new AppDataInstalledProfileRepository(
            appData: $appData,
            validator: $validator,
            codec: new InstalledProfileRecordCodec(
                validator: $validator,
                compiler: $compiler
            ),
            logger: $this->createMock(LoggerInterface::class)
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
                'font_stack' => 'system',
                'light'      => [
                    'primary'       => '#183a37',
                    'primary_text'  => '#faf7f0',
                    'primary_hover' => '#3f5a57',
                ],
            ],
        ];
    }
}
