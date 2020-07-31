<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\ImportExport\FileNormalizer;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\ImportExport\Normalizer\ProductImageAwareFileNormalizerDecorator;

class ProductImageAwareFileNormalizerDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileNormalizer|\PHPUnit\Framework\MockObject\MockObject */
    private $fileNormalizer;

    /** @var ProductImageAwareFileNormalizerDecorator */
    private $decorator;

    protected function setUp(): void
    {
        $this->fileNormalizer = $this->createMock(FileNormalizer::class);
        $this->decorator = new ProductImageAwareFileNormalizerDecorator($this->fileNormalizer);
    }

    public function testSupportsDenormalization(): void
    {
        $data = ['sampleData'];
        $class = 'SampleClass';
        $format = 'sampleFormat';
        $context = ['sampleContext'];

        $this->fileNormalizer
            ->expects($this->once())
            ->method('supportsDenormalization')
            ->with($data, $class, $format, $context)
            ->willReturn(true);

        $this->assertTrue($this->decorator->supportsDenormalization($data, $class, $format, $context));
    }

    public function testSupportsNormalization(): void
    {
        $data = new \stdClass();
        $format = 'sampleFormat';
        $context = ['sampleContext'];

        $this->fileNormalizer
            ->expects($this->once())
            ->method('supportsNormalization')
            ->with($data, $format, $context)
            ->willReturn(true);

        $this->assertTrue($this->decorator->supportsNormalization($data, $format, $context));
    }

    public function testDenormalizeWhenProductImage(): void
    {
        $data = 'sampleData';
        $class = 'SampleClass';
        $format = 'sampleFormat';
        $context = ['entityName' => ProductImage::class];
        $file = new File();

        $this->fileNormalizer
            ->expects($this->once())
            ->method('denormalize')
            ->with(['uri' => $data, 'uuid' => null], $class, $format, $context)
            ->willReturn($file);

        $this->assertSame($file, $this->decorator->denormalize($data, $class, $format, $context));
    }

    public function testDenormalize(): void
    {
        $data = ['sampleData'];
        $class = 'SampleClass';
        $format = 'sampleFormat';
        $context = [];
        $file = new File();

        $this->fileNormalizer
            ->expects($this->once())
            ->method('denormalize')
            ->with($data, $class, $format, $context)
            ->willReturn($file);

        $this->assertSame($file, $this->decorator->denormalize($data, $class, $format, $context));
    }

    public function testNormalizeWhenProductImage(): void
    {
        $data = ['sampleData'];
        $format = 'sampleFormat';
        $context = ['entityName' => ProductImage::class];
        $sampleUrl = '/sample/url';

        $this->fileNormalizer
            ->expects($this->once())
            ->method('normalize')
            ->with($data, $format, $context)
            ->willReturn(['uri' => $sampleUrl, 'uuid' => '']);

        $this->assertSame($sampleUrl, $this->decorator->normalize($data, $format, $context));
    }

    public function testNormalize(): void
    {
        $data = ['sampleData'];
        $format = 'sampleFormat';
        $context = [];
        $result = ['uri' => '/sample/url', 'uuid' => 'sample-uuid'];

        $this->fileNormalizer
            ->expects($this->once())
            ->method('normalize')
            ->with($data, $format, $context)
            ->willReturn($result);

        $this->assertSame($result, $this->decorator->normalize($data, $format, $context));
    }
}
