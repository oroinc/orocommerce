<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileNameProviderInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Provider\ProductImageFileNameProvider;

class ProductImageFileNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileNameProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ProductImageFileNameProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->innerProvider = $this->createMock(FileNameProviderInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new ProductImageFileNameProvider(
            $this->innerProvider,
            $this->configManager
        );
    }

    public function testGetFileNameNotProductImage()
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');
        $file->setParentEntityClass(Category::class);

        $this->configManager->expects($this->never())
            ->method($this->anything());

        $this->innerProvider->expects($this->once())
            ->method('getFileName')
            ->with($file)
            ->willReturn('filename.jpeg');

        $this->assertEquals('filename.jpeg', $this->provider->getFileName($file));
    }

    public function testGetFileNameOriginalFileNamesDisabled()
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');
        $file->setParentEntityClass(ProductImage::class);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_product.original_file_names_enabled')
            ->willReturn(false);

        $this->innerProvider->expects($this->once())
            ->method('getFileName')
            ->with($file)
            ->willReturn('filename.jpeg');

        $this->assertEquals('filename.jpeg', $this->provider->getFileName($file));
    }

    public function testGetFileNameOriginalFileNamesEnabled()
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename_#123-картинка))).jpeg');
        $file->setExtension('jpeg');
        $file->setParentEntityClass(ProductImage::class);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_product.original_file_names_enabled')
            ->willReturn(true);

        $this->innerProvider->expects($this->never())
            ->method($this->anything());

        $this->assertEquals('filename-original-filename_-123-картинка.jpeg', $this->provider->getFileName($file));
    }

    public function testGetFileNameOriginalFileNamesEnabledNoOriginalFileName()
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setExtension('jpeg');
        $file->setParentEntityClass(ProductImage::class);

        $this->configManager->expects($this->never())
            ->method('get')
            ->with('oro_product.original_file_names_enabled');

        $this->innerProvider->expects($this->once())
            ->method('getFileName')
            ->with($file)
            ->willReturn('filename.jpeg');

        $this->assertEquals('filename.jpeg', $this->provider->getFileName($file));
    }
}
