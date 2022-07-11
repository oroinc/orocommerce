<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileNamesProviderInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Provider\ProductImageFileNamesProvider;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;

class ProductImageFileNamesProviderTest extends \PHPUnit\Framework\TestCase
{
    private FileNamesProviderInterface|\PHPUnit\Framework\MockObject\MockObject $innerProvider;

    private ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager;

    private FeatureChecker|\PHPUnit\Framework\MockObject\MockObject $featureChecker;

    private ProductImageFileNamesProvider $fileNamesProvider;

    protected function setUp(): void
    {
        $this->innerProvider = $this->createMock(FileNamesProviderInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->fileNamesProvider = new ProductImageFileNamesProvider(
            $this->innerProvider,
            $this->configManager,
            $this->featureChecker
        );
    }

    public function testGetFileNamesForStandaloneFileEntity(): void
    {
        $file = $this->createMock(File::class);
        $file->expects(self::once())
            ->method('getParentEntityClass')
            ->willReturn(null);
        $file->expects(self::never())
            ->method('getOriginalFilename');

        $this->configManager->expects(self::never())
            ->method('get');
        $this->configManager->expects(self::never())
            ->method('set');
        $this->configManager->expects(self::never())
            ->method('flush');

        $this->featureChecker->expects(self::never())
            ->method('resetCache');

        $fileNames = ['attachment/filter/filter1/file.jpg', 'attachment/filter/filter2/file.jpg'];
        $this->innerProvider->expects(self::once())
            ->method('getFileNames')
            ->with($file)
            ->willReturn($fileNames);

        self::assertSame($fileNames, $this->fileNamesProvider->getFileNames($file));
    }

    public function testGetFileNamesForNotProductImageFileEntity(): void
    {
        $file = $this->createMock(File::class);
        $file->expects(self::once())
            ->method('getParentEntityClass')
            ->willReturn('Test\Entity');
        $file->expects(self::never())
            ->method('getOriginalFilename');

        $this->configManager->expects(self::never())
            ->method('get');
        $this->configManager->expects(self::never())
            ->method('set');
        $this->configManager->expects(self::never())
            ->method('flush');

        $this->featureChecker->expects(self::never())
            ->method('resetCache');

        $fileNames = ['attachment/filter/filter1/file.jpg', 'attachment/filter/filter2/file.jpg'];
        $this->innerProvider->expects(self::once())
            ->method('getFileNames')
            ->with($file)
            ->willReturn($fileNames);

        self::assertSame($fileNames, $this->fileNamesProvider->getFileNames($file));
    }

    public function testGetFileNamesForProductImageFileEntityButWithoutOriginalFilename(): void
    {
        $file = $this->createMock(File::class);
        $file->expects(self::once())
            ->method('getParentEntityClass')
            ->willReturn(ProductImage::class);
        $file->expects(self::once())
            ->method('getOriginalFilename')
            ->willReturn(null);

        $this->configManager->expects(self::never())
            ->method('get');
        $this->configManager->expects(self::never())
            ->method('set');
        $this->configManager->expects(self::never())
            ->method('flush');

        $this->featureChecker->expects(self::never())
            ->method('resetCache');

        $fileNames = ['attachment/filter/filter1/file.jpg', 'attachment/filter/filter2/file.jpg'];
        $this->innerProvider->expects(self::once())
            ->method('getFileNames')
            ->with($file)
            ->willReturn($fileNames);

        self::assertSame($fileNames, $this->fileNamesProvider->getFileNames($file));
    }

    public function testGetFileNamesForProductImageFileEntityAndOriginalFileNamesEnabled(): void
    {
        $file = $this->createMock(File::class);
        $file->expects(self::once())
            ->method('getParentEntityClass')
            ->willReturn(ProductImage::class);
        $file->expects(self::once())
            ->method('getOriginalFilename')
            ->willReturn('test.jpg');

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_product.original_file_names_enabled')
            ->willReturn(true);
        $this->configManager->expects(self::exactly(2))
            ->method('set')
            ->withConsecutive(
                ['oro_product.original_file_names_enabled', false],
                ['oro_product.original_file_names_enabled', true]
            );
        $this->configManager->expects(self::never())
            ->method('flush');

        $this->featureChecker->expects(self::exactly(2))
            ->method('resetCache');

        $this->innerProvider->expects(self::exactly(2))
            ->method('getFileNames')
            ->with($file)
            ->willReturnOnConsecutiveCalls(
                [
                    'attachment/filter/filter1/hash-test.jpg',
                    'attachment/filter/filter2/hash-test.jpg',
                    'attachment/resize/1/1/file.jpg'
                ],
                [
                    'attachment/filter/filter1/hash.jpg',
                    'attachment/filter/filter2/hash.jpg',
                    'attachment/resize/1/1/file.jpg'
                ]
            );

        self::assertSame(
            [
                'attachment/filter/filter1/hash-test.jpg',
                'attachment/filter/filter2/hash-test.jpg',
                'attachment/resize/1/1/file.jpg',
                'attachment/filter/filter1/hash.jpg',
                'attachment/filter/filter2/hash.jpg'
            ],
            $this->fileNamesProvider->getFileNames($file)
        );
    }

    public function testGetFileNamesForProductImageFileEntityAndOriginalFileNamesDisabled(): void
    {
        $file = $this->createMock(File::class);
        $file->expects(self::once())
            ->method('getParentEntityClass')
            ->willReturn(ProductImage::class);
        $file->expects(self::once())
            ->method('getOriginalFilename')
            ->willReturn('test.jpg');

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_product.original_file_names_enabled')
            ->willReturn(false);
        $this->configManager->expects(self::exactly(2))
            ->method('set')
            ->withConsecutive(
                ['oro_product.original_file_names_enabled', true],
                ['oro_product.original_file_names_enabled', false]
            );
        $this->configManager->expects(self::never())
            ->method('flush');

        $this->featureChecker->expects(self::exactly(2))
            ->method('resetCache');

        $this->innerProvider->expects(self::exactly(2))
            ->method('getFileNames')
            ->with($file)
            ->willReturnOnConsecutiveCalls(
                [
                    'attachment/filter/filter1/hash.jpg',
                    'attachment/filter/filter2/hash.jpg',
                    'attachment/resize/1/1/file.jpg'
                ],
                [
                    'attachment/filter/filter1/hash-test.jpg',
                    'attachment/filter/filter2/hash-test.jpg',
                    'attachment/resize/1/1/file.jpg'
                ]
            );

        self::assertSame(
            [
                'attachment/filter/filter1/hash.jpg',
                'attachment/filter/filter2/hash.jpg',
                'attachment/resize/1/1/file.jpg',
                'attachment/filter/filter1/hash-test.jpg',
                'attachment/filter/filter2/hash-test.jpg'
            ],
            $this->fileNamesProvider->getFileNames($file)
        );
    }

    public function testGetFileNamesShouldRestoreOriginalFileNamesIdExceptionOccurred(): void
    {
        $file = $this->createMock(File::class);
        $file->expects(self::once())
            ->method('getParentEntityClass')
            ->willReturn(ProductImage::class);
        $file->expects(self::once())
            ->method('getOriginalFilename')
            ->willReturn('test.jpg');

        $exception = new \Exception('some error');

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_product.original_file_names_enabled')
            ->willReturn(false);
        $this->configManager->expects(self::exactly(2))
            ->method('set')
            ->withConsecutive(
                ['oro_product.original_file_names_enabled', true],
                ['oro_product.original_file_names_enabled', false]
            );
        $this->configManager->expects(self::never())
            ->method('flush');

        $this->featureChecker->expects(self::exactly(2))
            ->method('resetCache');

        $this->innerProvider->expects(self::exactly(2))
            ->method('getFileNames')
            ->with(self::identicalTo($file))
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function () {
                    return ['attachment/filter/filter1/hash.jpg'];
                }),
                new ReturnCallback(function () use ($exception) {
                    throw $exception;
                })
            );

        $this->expectException(get_class($exception));
        $this->expectExceptionMessage($exception->getMessage());

        $this->fileNamesProvider->getFileNames($file);
    }
}
