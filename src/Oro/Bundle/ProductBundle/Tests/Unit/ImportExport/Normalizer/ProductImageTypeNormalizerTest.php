<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\ImportExport\Normalizer\ProductImageTypeNormalizer;

class ProductImageTypeNormalizerTest extends \PHPUnit\Framework\TestCase
{
    private FieldHelper|\PHPUnit\Framework\MockObject\MockObject $fieldHelper;

    private ProductImageTypeNormalizer $productImageTypeNormalizer;

    protected function setUp(): void
    {
        $this->fieldHelper = $this->createMock(FieldHelper::class);

        $this->productImageTypeNormalizer = new ProductImageTypeNormalizer($this->fieldHelper);
        $this->productImageTypeNormalizer->setProductImageTypeClass(ProductImageType::class);
    }

    public function testDenormalize()
    {
        $result =  $this->productImageTypeNormalizer->denormalize(
            ProductImageType::TYPE_MAIN,
            ProductImageType::class,
            null,
            []
        );

        $this->assertInstanceOf(ProductImageType::class, $result);
        $this->assertEquals(ProductImageType::TYPE_MAIN, $result->getType());
    }

    public function testSupportsDenormalization()
    {
        $result = $this->productImageTypeNormalizer->supportsDenormalization(
            [],
            ProductImageType::class,
            null,
            []
        );

        $this->assertTrue($result);
    }
}
