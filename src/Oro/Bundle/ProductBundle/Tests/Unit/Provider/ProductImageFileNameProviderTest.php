<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileNameProviderInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Provider\ProductImageFileNameProvider;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductImageFileNameProviderTest extends \PHPUnit\Framework\TestCase
{
    private const FILTER = 'sample_filter';
    private const FORMAT = 'sample_format';
    private const WIDTH = 42;
    private const HEIGHT = 142;

    private FileNameProviderInterface|\PHPUnit\Framework\MockObject\MockObject $innerProvider;

    private ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager;

    private ProductImageFileNameProvider $provider;

    protected function setUp(): void
    {
        $this->innerProvider = $this->createMock(FileNameProviderInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new ProductImageFileNameProvider(
            $this->innerProvider,
            $this->configManager
        );
    }

    public function testGetFileName(): void
    {
        $file = new File();
        $this->innerProvider->expects(self::once())
            ->method('getFileName')
            ->with($file)
            ->willReturn('filename.jpeg');

        self::assertEquals('filename.jpeg', $this->provider->getFileName($file));
    }

    public function testGetFilteredImageNameNotProductImage(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');
        $file->setParentEntityClass(Category::class);

        $this->configManager->expects(self::never())
            ->method(self::anything());

        $this->innerProvider->expects(self::once())
            ->method('getFilteredImageName')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn('filename.jpeg');

        self::assertEquals('filename.jpeg', $this->provider->getFilteredImageName($file, self::FILTER, self::FORMAT));
    }

    public function testGetFilteredImageNameOriginalFileNamesDisabled(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');
        $file->setParentEntityClass(ProductImage::class);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_product.original_file_names_enabled')
            ->willReturn(false);

        $this->innerProvider->expects(self::once())
            ->method('getFilteredImageName')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn('filename.jpeg');

        self::assertEquals('filename.jpeg', $this->provider->getFilteredImageName($file, self::FILTER, self::FORMAT));
    }

    public function testGetFilteredImageNameOriginalFileNamesEnabled(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename_#123-картинка))).jpeg');
        $file->setExtension('jpeg');
        $file->setParentEntityClass(ProductImage::class);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_product.original_file_names_enabled')
            ->willReturn(true);

        $this->innerProvider->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            'filename-original-filename_-123-картинка.jpeg.' . self::FORMAT,
            $this->provider->getFilteredImageName($file, self::FILTER, self::FORMAT)
        );
    }

    public function testGetFilteredImageNameOriginalFileNamesEnabledAndFormatIsSame(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename_#123-картинка))).jpeg');
        $file->setExtension('jpeg');
        $file->setParentEntityClass(ProductImage::class);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_product.original_file_names_enabled')
            ->willReturn(true);

        $this->innerProvider->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            'filename-original-filename_-123-картинка.jpeg',
            $this->provider->getFilteredImageName($file, self::FILTER, 'jpeg')
        );
    }

    public function testGetFilteredImageNameOriginalFileNamesEnabledNoOriginalFileName(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setExtension('jpeg');
        $file->setParentEntityClass(ProductImage::class);

        $this->configManager->expects(self::never())
            ->method('get')
            ->with('oro_product.original_file_names_enabled');

        $this->innerProvider->expects(self::once())
            ->method('getFilteredImageName')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn('filename.jpeg');

        self::assertEquals('filename.jpeg', $this->provider->getFilteredImageName($file, self::FILTER, self::FORMAT));
    }

    public function testGetResizedImageNameNotProductImage(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');
        $file->setParentEntityClass(Category::class);

        $this->configManager->expects(self::never())
            ->method(self::anything());

        $this->innerProvider->expects(self::once())
            ->method('getResizedImageName')
            ->with($file, self::WIDTH, self::HEIGHT, self::FORMAT)
            ->willReturn('filename.jpeg');

        self::assertEquals(
            'filename.jpeg',
            $this->provider->getResizedImageName($file, self::WIDTH, self::HEIGHT, self::FORMAT)
        );
    }

    public function testGetResizedImageNameOriginalFileNamesDisabled(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');
        $file->setParentEntityClass(ProductImage::class);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_product.original_file_names_enabled')
            ->willReturn(false);

        $this->innerProvider->expects(self::once())
            ->method('getResizedImageName')
            ->with($file, self::WIDTH, self::HEIGHT, self::FORMAT)
            ->willReturn('filename.jpeg');

        self::assertEquals(
            'filename.jpeg',
            $this->provider->getResizedImageName($file, self::WIDTH, self::HEIGHT, self::FORMAT)
        );
    }

    public function testGetResizedImageNameOriginalFileNamesEnabled(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename_#123-картинка))).jpeg');
        $file->setExtension('jpeg');
        $file->setParentEntityClass(ProductImage::class);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_product.original_file_names_enabled')
            ->willReturn(true);

        $this->innerProvider->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            'filename-original-filename_-123-картинка.jpeg.' . self::FORMAT,
            $this->provider->getResizedImageName($file, self::WIDTH, self::HEIGHT, self::FORMAT)
        );
    }

    public function testGetResizedImageNameOriginalFileNamesEnabledAndFormatIsSame(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename_#123-картинка))).jpeg');
        $file->setExtension('jpeg');
        $file->setParentEntityClass(ProductImage::class);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_product.original_file_names_enabled')
            ->willReturn(true);

        $this->innerProvider->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            'filename-original-filename_-123-картинка.jpeg',
            $this->provider->getResizedImageName($file, self::WIDTH, self::HEIGHT, 'jpeg')
        );
    }

    public function testGetResizedImageNameOriginalFileNamesEnabledNoOriginalFileName(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setExtension('jpeg');
        $file->setParentEntityClass(ProductImage::class);

        $this->configManager->expects(self::never())
            ->method('get')
            ->with('oro_product.original_file_names_enabled');

        $this->innerProvider->expects(self::once())
            ->method('getResizedImageName')
            ->with($file, self::WIDTH, self::HEIGHT, self::FORMAT)
            ->willReturn('filename.jpeg');

        self::assertEquals(
            'filename.jpeg',
            $this->provider->getResizedImageName($file, self::WIDTH, self::HEIGHT, self::FORMAT)
        );
    }
}
