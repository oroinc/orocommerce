<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ScalarFieldDenormalizer;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\ImportExport\Normalizer\RelatedProductNormalizer;

class RelatedProductNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FieldHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldHelper;

    /** @var Serializer|\PHPUnit\Framework\MockObject\MockObject */
    private $serializer;

    /** @var RelatedProductNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->fieldHelper = $this->createMock(FieldHelper::class);
        $this->serializer = $this->createMock(Serializer::class);

        $this->normalizer = new RelatedProductNormalizer($this->fieldHelper);
        $this->normalizer->setSerializer($this->serializer);
        $this->normalizer->setScalarFieldDenormalizer(new ScalarFieldDenormalizer());
    }

    public function testSupportsNormalization(): void
    {
        $this->assertFalse($this->normalizer->supportsNormalization(new RelatedProduct()));
    }

    public function testSupportsDenormalization(): void
    {
        $this->assertTrue($this->normalizer->supportsDenormalization([], RelatedProduct::class));
    }

    public function testDenormalize(): void
    {
        $expected = new RelatedProduct();

        $this->fieldHelper->expects($this->atLeastOnce())
            ->method('setObjectValue')
            ->withConsecutive(
                [$expected, 'product', ['sku' => 'sku-1']],
                [$expected, 'relatedItem', ['sku' => 'sku-2']]
            );

        $this->fieldHelper->expects($this->any())
            ->method('getFields')
            ->willReturnMap(
                [
                    [
                        RelatedProduct::class,
                        true,
                        false,
                        false,
                        false,
                        false,
                        true,
                        [
                            'product' => [
                                'name' => 'product',
                                'type' => 'test-type',
                            ],
                            'relatedItem' => [
                                'name' => 'relatedItem',
                                'type' => 'test-type',
                            ],
                        ]
                    ]
                ]
            );

        $this->assertEquals(
            $expected,
            $this->normalizer->denormalize(
                [
                    'sku' => 'sku-1',
                    'relatedItem' => 'sku-2',
                ],
                RelatedProduct::class,
                null,
                []
            )
        );
    }
}
