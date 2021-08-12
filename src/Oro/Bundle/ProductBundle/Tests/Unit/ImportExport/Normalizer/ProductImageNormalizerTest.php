<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\ImportExport\Normalizer\ProductImageNormalizer;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProductImage;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductImageNormalizerTest extends \PHPUnit\Framework\TestCase
{
    private FieldHelper|\PHPUnit\Framework\MockObject\MockObject $fieldHelper;

    private ProductImageNormalizer $productImageNormalizer;

    private FileLocator|\PHPUnit\Framework\MockObject\MockObject $fileLocator;

    private ImageTypeProvider|\PHPUnit\Framework\MockObject\MockObject $imageTypeProvider;

    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    protected function setUp(): void
    {
        $this->imageTypeProvider = $this->createMock(ImageTypeProvider::class);
        $this->fileLocator = $this->createMock(FileLocator::class);
        $this->fieldHelper = $this->createMock(FieldHelper::class);

        $this->imageTypeProvider->expects(self::any())
            ->method('getImageTypes')
            ->willReturn(
                [
                    ProductImageType::TYPE_MAIN => true
                ]
            );

        $this->fieldHelper->expects(self::any())
            ->method('getEntityFields')
            ->willReturn(
                [
                    [
                        'name' => 'types'
                    ],
                ]
            );

        $this->productImageNormalizer = new ProductImageNormalizer($this->fieldHelper);
        $this->productImageNormalizer->setProductImageClass(ProductImage::class);
        $this->productImageNormalizer->setImageTypeProvider($this->imageTypeProvider);
        $this->productImageNormalizer->setFileLocator($this->fileLocator);
    }

    /**
     * @param StubProductImage $productImage
     *
     * @dataProvider normalizationDataProvider
     */
    public function testNormalize($productImage): void
    {
        $this->fieldHelper->expects(self::once())
            ->method('getObjectValue')
            ->willReturn(
                [
                    'types' => ['type' => ProductImageType::TYPE_MAIN]
                ]
            );

        $result = $this->productImageNormalizer->normalize(
            $productImage,
            null
        );

        self::assertArrayHasKey('types', $result);
        self::assertArrayHasKey('image', $result);
    }

    public function testDenormalize(): void
    {
        $productImageData = [
            'types' => [
                ProductImageType::TYPE_MAIN => true
            ],
            'image' => ['name' => 'imageName'],
        ];

        $this->fieldHelper->expects(self::once())
            ->method('setObjectValue')
            ->willReturnCallback(
                function (ProductImage $result, $fieldName, $value) {
                    return $result->{'add' . ucfirst(substr($fieldName, 0, -1))}(reset($value));
                }
            );

        $productImage = $this->productImageNormalizer->denormalize(
            $productImageData,
            ProductImage::class,
            null
        );

        self::assertArrayHasKey(ProductImageType::TYPE_MAIN, $productImage->getTypes());
    }

    public function testSupportsNormalization(): void
    {
        $result = $this->productImageNormalizer->supportsNormalization(
            new StubProductImage(),
            null
        );

        self::assertTrue($result);
    }

    public function testSupportsDenormalization(): void
    {
        $result = $this->productImageNormalizer->supportsDenormalization(
            [],
            StubProductImage::class,
            null
        );

        self::assertTrue($result);
    }

    /**
     * @return array
     */
    public function normalizationDataProvider(): array
    {
        $productImage = new StubProductImage();

        $file = new File();
        $file->setOriginalFilename('sku_001_1.jpg');

        $product = new Product();
        $product->setSku('sku_001');

        $productImage->setImage($file);
        $productImage->addType(new ProductImageType(ProductImageType::TYPE_MAIN));
        $productImage->setProduct($product);

        return [
            [$productImage]
        ];
    }
}
