<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileNameProviderInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Provider\ProductImageFileNameProvider;

class ProductImageFileNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileNameProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerProvider;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    private ProductImageFileNameProvider $provider;

    protected function setUp(): void
    {
        $this->innerProvider = $this->createMock(FileNameProviderInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->provider = new ProductImageFileNameProvider(
            $this->innerProvider,
            $this->createMock(ConfigManager::class)
        );
        $this->provider->setFeatureChecker($this->featureChecker);
        $this->provider->addFeature('product_original_filenames');
    }

    public function testGetFileNameNotProductImage(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');
        $file->setParentEntityClass(Category::class);

        $this->featureChecker->expects(self::never())
            ->method(self::anything());

        $this->innerProvider->expects(self::once())
            ->method('getFileName')
            ->with($file)
            ->willReturn('filename.jpeg');

        self::assertEquals(
            'filename.jpeg',
            $this->provider->getFileName($file)
        );
    }

    public function testGetFileNameOriginalFileNamesDisabled(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');
        $file->setParentEntityClass(ProductImage::class);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('product_original_filenames')
            ->willReturn(false);

        $this->innerProvider->expects(self::once())
            ->method('getFileName')
            ->with($file)
            ->willReturn('filename.jpeg');

        self::assertEquals(
            'filename.jpeg',
            $this->provider->getFileName($file)
        );
    }

    public function testGetFileNameFilenameSameAsOriginal(): void
    {
        $fileName = 'original-filename_#123-картинка))).jpeg';

        $file = new File();
        $file->setFilename($fileName);
        $file->setOriginalFilename($fileName);
        $file->setParentEntityClass(ProductImage::class);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('product_original_filenames')
            ->willReturn(true);

        $this->innerProvider->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            'original-filename_-123-картинка.jpeg',
            $this->provider->getFileName($file)
        );
    }

    public function testGetFileNameOriginalFileNamesEnabled(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename_#123-картинка))).jpeg');
        $file->setParentEntityClass(ProductImage::class);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('product_original_filenames')
            ->willReturn(true);

        $this->innerProvider->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            'filename-original-filename_-123-картинка.jpeg',
            $this->provider->getFileName($file)
        );
    }

    public function testGetFileNameOriginalFileNamesEnabledNoOriginalFileName(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setParentEntityClass(ProductImage::class);

        $this->featureChecker->expects(self::never())
            ->method(self::anything());

        $this->innerProvider->expects(self::once())
            ->method('getFileName')
            ->with($file)
            ->willReturn('filename.jpeg');

        self::assertEquals(
            'filename.jpeg',
            $this->provider->getFileName($file)
        );
    }

    /**
     * @dataProvider getExtensionDataProvider
     */
    public function testGetFileName(?string $extension): void
    {
        $file = new File();
        $file->setParentEntityClass(ProductImage::class);
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename_#123-картинка))).jpeg');
        $file->setExtension($extension);

        $this->innerProvider->expects(self::never())
            ->method(self::anything());

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('product_original_filenames')
            ->willReturn(true);

        self::assertEquals('filename-original-filename_-123-картинка.jpeg', $this->provider->getFileName($file));
    }

    public function getExtensionDataProvider(): array
    {
        return [
            'no extension' => [
                'extension' => null,
            ],
            'extension' => [
                'extension' => 'jpeg',
            ],
        ];
    }
}
