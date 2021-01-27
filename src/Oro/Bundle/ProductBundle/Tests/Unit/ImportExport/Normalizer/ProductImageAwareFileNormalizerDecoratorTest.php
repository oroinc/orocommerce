<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\ImportExport\FileNormalizer;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\ImportExport\Normalizer\ProductImageAwareFileNormalizerDecorator;

class ProductImageAwareFileNormalizerDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileNormalizer|\PHPUnit\Framework\MockObject\MockObject */
    private $fileNormalizer;

    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var ProductImageAwareFileNormalizerDecorator */
    private $decorator;

    protected function setUp(): void
    {
        $this->fileNormalizer = $this->createMock(FileNormalizer::class);
        $this->fileManager = $this->createMock(FileManager::class);
        $this->decorator = new ProductImageAwareFileNormalizerDecorator($this->fileNormalizer, $this->fileManager);
    }

    public function testSupportsDenormalization(): void
    {
        $data = ['sampleData'];
        $class = 'SampleClass';
        $format = 'sampleFormat';
        $context = ['sampleContext'];

        $this->fileNormalizer->expects(self::once())
            ->method('supportsDenormalization')
            ->with($data, $class, $format, $context)
            ->willReturn(true);

        self::assertTrue($this->decorator->supportsDenormalization($data, $class, $format, $context));
    }

    public function testSupportsNormalization(): void
    {
        $data = new \stdClass();
        $format = 'sampleFormat';
        $context = ['sampleContext'];

        $this->fileNormalizer->expects(self::once())
            ->method('supportsNormalization')
            ->with($data, $format, $context)
            ->willReturn(true);

        self::assertTrue($this->decorator->supportsNormalization($data, $format, $context));
    }

    public function testDenormalizeWhenProductImagePathIsFullUrl(): void
    {
        $data = 'http://domain.com/sampleData.jpg';
        $class = 'SampleClass';
        $format = 'sampleFormat';
        $context = ['entityName' => ProductImage::class];
        $file = new File();

        $this->fileManager->expects(self::never())
            ->method('getFilePath');

        $this->fileNormalizer->expects(self::once())
            ->method('denormalize')
            ->with(['uri' => $data, 'uuid' => null], $class, $format, $context)
            ->willReturn($file);

        self::assertSame($file, $this->decorator->denormalize($data, $class, $format, $context));
    }

    public function testDenormalizeWhenProductImagePathIsFullLocalPath(): void
    {
        $data = __FILE__;
        $class = 'SampleClass';
        $format = 'sampleFormat';
        $context = ['entityName' => ProductImage::class];

        $this->fileManager->expects(self::never())
            ->method('getFilePath');

        $file = new File();
        $this->fileNormalizer->expects(self::once())
            ->method('denormalize')
            ->with(['uri' => $data, 'uuid' => null], $class, $format, $context)
            ->willReturn($file);

        self::assertSame($file, $this->decorator->denormalize($data, $class, $format, $context));
    }

    public function testDenormalizeWhenProductImagePathIsRelativePath(): void
    {
        $data = 'sampleData.jpg';
        $class = 'SampleClass';
        $format = 'sampleFormat';
        $context = ['entityName' => ProductImage::class];

        $expectedPath = 'gaufrette-readonly://import_product_images/sampleData.jpg';
        $this->fileManager->expects(self::once())
            ->method('getReadonlyFilePath')
            ->with('sampleData.jpg')
            ->willReturn($expectedPath);

        $file = new File();
        $this->fileNormalizer->expects(self::once())
            ->method('denormalize')
            ->with(['uri' => $expectedPath, 'uuid' => null], $class, $format, $context)
            ->willReturn($file);

        self::assertSame($file, $this->decorator->denormalize($data, $class, $format, $context));
    }

    public function testDenormalizeWithNonProductImageData(): void
    {
        $data = ['sampleData'];
        $class = 'SampleClass';
        $format = 'sampleFormat';
        $context = [];
        $file = new File();

        $this->fileManager->expects(self::never())
            ->method('getFilePath');

        $this->fileNormalizer->expects(self::once())
            ->method('denormalize')
            ->with($data, $class, $format, $context)
            ->willReturn($file);

        self::assertSame($file, $this->decorator->denormalize($data, $class, $format, $context));
    }

    public function testDenormalizeWithNonProductImageDataWhenProductImagePathIsRelativePath(): void
    {
        $data = 'sampleData.jpg';
        $class = 'SampleClass';
        $format = 'sampleFormat';
        $context = [];

        $this->fileManager->expects(self::never())
            ->method('getFilePath');

        $file = new File();
        $this->fileNormalizer->expects(self::once())
            ->method('denormalize')
            ->with($data, $class, $format, $context)
            ->willReturn($file);

        self::assertSame($file, $this->decorator->denormalize($data, $class, $format, $context));
    }

    public function testNormalizeWhenProductImage(): void
    {
        $data = ['sampleData'];
        $format = 'sampleFormat';
        $context = ['entityName' => ProductImage::class];
        $sampleUrl = '/sample/url';

        $this->fileNormalizer->expects(self::once())
            ->method('normalize')
            ->with($data, $format, $context)
            ->willReturn(['uri' => $sampleUrl, 'uuid' => '']);

        self::assertSame($sampleUrl, $this->decorator->normalize($data, $format, $context));
    }

    public function testNormalize(): void
    {
        $data = ['sampleData'];
        $format = 'sampleFormat';
        $context = [];
        $result = ['uri' => '/sample/url', 'uuid' => 'sample-uuid'];

        $this->fileNormalizer->expects(self::once())
            ->method('normalize')
            ->with($data, $format, $context)
            ->willReturn($result);

        self::assertSame($result, $this->decorator->normalize($data, $format, $context));
    }
}
