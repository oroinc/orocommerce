<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Tools;

use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\Repository\FileRepository;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestFile;
use Oro\Bundle\CMSBundle\Tools\DigitalAssetTwigTagsConverter;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Entity\Repository\DigitalAssetRepository;
use Symfony\Component\Yaml\Yaml;

class DigitalAssetTwigTagsConverterTest extends \PHPUnit\Framework\TestCase
{
    private const NEW_UUID = '0e6bffee-15bb-44ec-9a4a-0113ca51452d';

    private static array $fixturesData = [];

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $managerRegistry;

    private FileUrlProviderInterface|\PHPUnit\Framework\MockObject\MockObject $fileUrlProvider;

    private DigitalAssetTwigTagsConverter $converter;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->fileUrlProvider = $this->createMock(FileUrlProviderInterface::class);

        $this->converter = $this->getMockBuilder(DigitalAssetTwigTagsConverter::class)
            ->setConstructorArgs([$this->managerRegistry, $this->fileUrlProvider])
            ->onlyMethods(['generateUuid'])
            ->getMock();

        $this->converter
            ->expects(self::any())
            ->method('generateUuid')
            ->willReturnCallback(static fn () => self::NEW_UUID);
    }

    private static function getFixturesData(string $name): array
    {
        if (!self::$fixturesData) {
            $fixturePath = sprintf(
                '%1$s%2$s..%2$sFixtures%2$sdigital_asset_twig_tags_converter_data.yml',
                __DIR__,
                DIRECTORY_SEPARATOR
            );
            self::$fixturesData = Yaml::parse(file_get_contents($fixturePath));
        }

        return self::$fixturesData[$name] ?? [];
    }

    /**
     * @dataProvider convertToUrlsDataProvider
     */
    public function testConvertToUrls(string $contentWithTwigTags, string $expected): void
    {
        $this->fileUrlProvider
            ->expects(self::any())
            ->method('getFilteredImageUrl')
            ->willReturnCallback(
                function (File $file, string $filterName, string $format) {
                    $this->assertEquals('wysiwyg_original', $filterName);

                    return sprintf(
                        '/media/cache/attachment/resize/wysiwyg_original/%1$s/%2$d/file%2$d.jpg%3$s',
                        md5('filterMd5'),
                        $file->getId(),
                        $format ? '.' . $format : ''
                    );
                }
            );

        $this->fileUrlProvider
            ->expects(self::any())
            ->method('getFileUrl')
            ->willReturnCallback(
                function (File $file, string $actionName) {
                    $this->assertEquals(FileUrlProviderInterface::FILE_ACTION_DOWNLOAD, $actionName);

                    return sprintf('/attachment/download/%1$d/file%1$d.jpg', $file->getId());
                }
            );

        $fileRepository = $this->createMock(FileRepository::class);
        $digitalAssetRepository = $this->createMock(DigitalAssetRepository::class);
        $this->managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    [File::class, null, $fileRepository],
                    [DigitalAsset::class, null, $digitalAssetRepository],
                ]
            );

        $fileRepository
            ->expects(self::any())
            ->method('findBy')
            ->willReturnCallback(
                static function (array $criteria) {
                    $uuid = $criteria['uuid'];

                    if ($uuid === self::NEW_UUID) {
                        // File with such uuid does not exist.
                        return [];
                    }

                    return [(new TestFile())->setId(explode('-', $uuid)[1])];
                }
            );

        $digitalAssetRepository
            ->expects(self::any())
            ->method('findSourceFile')
            ->willReturnCallback(
                static function (int $digitalAssetId) {
                    if ($digitalAssetId < 1000) {
                        // Digital asset with such id does not exist.
                        return null;
                    }

                    return (new TestFile())->setId($digitalAssetId * 10);
                }
            );

        self::assertEquals($expected, $this->converter->convertToUrls($contentWithTwigTags));
    }

    public function convertToUrlsDataProvider(): array
    {
        return self::getFixturesData('convertToUrls');
    }

    public function testConvertToUrlsWhenInvalidUuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID v4: invalid-uuid');

        $this->converter->convertToUrls('{{ wysiwyg_file(42, "invalid-uuid") }}');
    }

    /**
     * @dataProvider convertToUrlsWhenNoFileDataProvider
     */
    public function testConvertToUrlsWhenNoFile(string $contentWithTwigTags, string $expected): void
    {
        $this->fileUrlProvider
            ->expects(self::any())
            ->method('getFilteredImageUrl')
            ->willReturnCallback(
                function (File $file, string $filterName, string $format) {
                    $this->assertEquals('wysiwyg_original', $filterName);

                    return sprintf(
                        '/media/cache/attachment/resize/wysiwyg_original/%1$s/%2$d/file%2$d.jpg%3$s',
                        md5('filterMd5'),
                        $file->getId(),
                        $format ? '.' . $format : ''
                    );
                }
            );

        $this->fileUrlProvider
            ->expects(self::any())
            ->method('getFileUrl')
            ->willReturnCallback(
                function (File $file, string $actionName) {
                    $this->assertEquals(FileUrlProviderInterface::FILE_ACTION_DOWNLOAD, $actionName);

                    return sprintf('/attachment/download/%1$d/file%1$d.jpg', $file->getId());
                }
            );

        $fileRepository = $this->createMock(FileRepository::class);
        $digitalAssetRepository = $this->createMock(DigitalAssetRepository::class);

        $this->managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    [File::class, null, $fileRepository],
                    [DigitalAsset::class, null, $digitalAssetRepository],
                ]
            );

        $fileRepository
            ->expects(self::atLeastOnce())
            ->method('findBy')
            ->willReturn([]);

        $digitalAssetRepository
            ->expects(self::any())
            ->method('findSourceFile')
            ->willReturnCallback(
                static function (int $digitalAssetId) {
                    if ($digitalAssetId < 1000) {
                        // Digital asset with such id does not exist.
                        throw new NoResultException();
                    }

                    return (new TestFile())->setId($digitalAssetId * 10);
                }
            );

        self::assertEquals($expected, $this->converter->convertToUrls($contentWithTwigTags));
    }

    public function convertToUrlsWhenNoFileDataProvider(): array
    {
        return self::getFixturesData('convertToUrlsWhenNoFile');
    }

    /**
     * @dataProvider convertToTwigTagsDataProvider
     */
    public function testConvertToTwigTags(string $contentWithUrls, string $expected): void
    {
        $digitalAssetRepository = $this->createMock(DigitalAssetRepository::class);
        $this->managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->with(DigitalAsset::class)
            ->willReturn($digitalAssetRepository);

        $context = [
            'entityClass' => \stdClass::class,
            'entityId' => 42,
            'fieldName' => 'content',
        ];

        $digitalAssetRepository
            ->expects(self::any())
            ->method('getFileDataForTwigTag')
            ->willReturnCallback(
                static function (int $fileId) use ($context) {
                    if ($fileId < 500) {
                        // File does not exist.
                        return [];
                    }

                    if ($fileId < 1000) {
                        // File is regular - not a digital asset source or child.
                        return [];
                    }

                    if ($fileId < 2000) {
                        // File is a child of a digital asset and belongs to the currently processed entity.
                        return [
                            'parentEntityClass' => $context['entityClass'],
                            'parentEntityId' => $context['entityId'],
                            'parentEntityFieldName' => $context['fieldName'],
                            'uuid' => 'e9ff6eea-' . $fileId . '-4689-ab69-ee2567103cd1',
                            'digitalAssetId' => $fileId / 10,
                        ];
                    }

                    if ($fileId < 3000) {
                        // File is a source file of a digital asset.
                        return [
                            'parentEntityClass' => DigitalAsset::class,
                            'parentEntityId' => $fileId / 10,
                            'parentEntityFieldName' => 'sourceFile',
                            'uuid' => 'ff256712-' . $fileId . '-412c-9140-5068077b60d5',
                            'digitalAssetId' => null,
                        ];
                    }

                    if ($fileId < 4000) {
                        // File is a child of a digital asset but belongs of another entity.
                        return [
                            'parentEntityClass' => \stdClass::class,
                            'parentEntityId' => 4242,
                            'parentEntityFieldName' => 'content',
                            'uuid' => 'db50675d-' . $fileId . '-4441-acc3-6c0ab45caf69',
                            'digitalAssetId' => $fileId / 10,
                        ];
                    }

                    self::fail('File with id #' . $fileId . ' was not expected');
                }
            );

        self::assertEquals($expected, $this->converter->convertToTwigTags($contentWithUrls, $context));
    }

    public function convertToTwigTagsDataProvider(): array
    {
        return self::getFixturesData('convertToTwigTags');
    }
}
