<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileNameProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\FilenameExtensionHelper;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
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

    private FeatureChecker|\PHPUnit\Framework\MockObject\MockObject $featureChecker;

    private ProductImageFileNameProvider $provider;

    protected function setUp(): void
    {
        $this->innerProvider = $this->createMock(FileNameProviderInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $filenameExtensionHelper = new FilenameExtensionHelper(['image/svg']);

        $this->provider = new ProductImageFileNameProvider(
            $this->innerProvider,
            $filenameExtensionHelper
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

    public function testGetFilteredImageNameNotProductImage(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');
        $file->setParentEntityClass(Category::class);

        $this->featureChecker->expects(self::never())
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

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('product_original_filenames')
            ->willReturn(false);

        $this->innerProvider->expects(self::once())
            ->method('getFilteredImageName')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn('filename.jpeg');

        self::assertEquals('filename.jpeg', $this->provider->getFilteredImageName($file, self::FILTER, self::FORMAT));
    }

    public function testGetFilteredImageNameFilenameSameAsOriginal(): void
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
            'original-filename_-123-картинка.jpeg.' . self::FORMAT,
            $this->provider->getFilteredImageName($file, self::FILTER, self::FORMAT)
        );
    }

    public function testGetFilteredImageNameUnsupportedMimeType(): void
    {
        $fileName = 'original-filename_#123-картинка))).svg';

        $file = new File();
        $file->setFilename($fileName);
        $file->setOriginalFilename($fileName);
        $file->setParentEntityClass(ProductImage::class);
        $file->setMimeType('image/svg');

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('product_original_filenames')
            ->willReturn(true);

        $this->innerProvider->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            'original-filename_-123-картинка.svg',
            $this->provider->getFilteredImageName($file, self::FILTER, self::FORMAT)
        );
    }

    /**
     * @dataProvider getExtensionDataProvider
     */
    public function testGetFilteredImageNameOriginalFileNamesEnabled(?string $extension): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename_#123-картинка))).jpeg');
        $file->setExtension($extension);
        $file->setParentEntityClass(ProductImage::class);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('product_original_filenames')
            ->willReturn(true);

        $this->innerProvider->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            'filename-original-filename_-123-картинка.jpeg.' . self::FORMAT,
            $this->provider->getFilteredImageName($file, self::FILTER, self::FORMAT)
        );
    }

    /**
     * @dataProvider getExtensionDataProvider
     */
    public function testGetFilteredImageNameOriginalFileNamesEnabledAndFormatIsSame(?string $extension): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename_#123-картинка))).jpeg');
        $file->setExtension($extension);
        $file->setParentEntityClass(ProductImage::class);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('product_original_filenames')
            ->willReturn(true);

        $this->innerProvider->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            'filename-original-filename_-123-картинка.jpeg',
            $this->provider->getFilteredImageName($file, self::FILTER, 'jpeg')
        );
    }

    /**
     * @dataProvider getExtensionDataProvider
     */
    public function testGetFilteredImageNameOriginalFileNamesEnabledNoOriginalFileName(?string $extension): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setExtension($extension);
        $file->setParentEntityClass(ProductImage::class);

        $this->featureChecker->expects(self::never())
            ->method(self::anything());

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

        $this->featureChecker->expects(self::never())
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

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('product_original_filenames')
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

    public function testGetResizedImageNameFilenameSameAsOriginal(): void
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
            'original-filename_-123-картинка.jpeg.' . self::FORMAT,
            $this->provider->getResizedImageName($file, self::WIDTH, self::HEIGHT, self::FORMAT)
        );
    }

    public function testGetResizedImageNameUnsupportedMimeType(): void
    {
        $fileName = 'original-filename_#123-картинка))).svg';

        $file = new File();
        $file->setFilename($fileName);
        $file->setOriginalFilename($fileName);
        $file->setParentEntityClass(ProductImage::class);
        $file->setMimeType('image/svg');

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('product_original_filenames')
            ->willReturn(true);

        $this->innerProvider->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            'original-filename_-123-картинка.svg',
            $this->provider->getResizedImageName($file, self::WIDTH, self::HEIGHT, self::FORMAT)
        );
    }

    /**
     * @dataProvider getExtensionDataProvider
     */
    public function testGetResizedImageNameOriginalFileNamesEnabled(?string $extension): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename_#123-картинка))).jpeg');
        $file->setExtension($extension);
        $file->setParentEntityClass(ProductImage::class);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('product_original_filenames')
            ->willReturn(true);

        $this->innerProvider->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            'filename-original-filename_-123-картинка.jpeg.' . self::FORMAT,
            $this->provider->getResizedImageName($file, self::WIDTH, self::HEIGHT, self::FORMAT)
        );
    }

    /**
     * @dataProvider getExtensionDataProvider
     */
    public function testGetResizedImageNameOriginalFileNamesEnabledAndFormatIsSame(?string $extension): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename_#123-картинка))).jpeg');
        $file->setExtension($extension);
        $file->setParentEntityClass(ProductImage::class);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('product_original_filenames')
            ->willReturn(true);

        $this->innerProvider->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            'filename-original-filename_-123-картинка.jpeg',
            $this->provider->getResizedImageName($file, self::WIDTH, self::HEIGHT, 'jpeg')
        );
    }

    /**
     * @dataProvider getExtensionDataProvider
     */
    public function testGetResizedImageNameOriginalFileNamesEnabledNoOriginalFileName(?string $extension): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setExtension($extension);
        $file->setParentEntityClass(ProductImage::class);

        $this->featureChecker->expects(self::never())
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
