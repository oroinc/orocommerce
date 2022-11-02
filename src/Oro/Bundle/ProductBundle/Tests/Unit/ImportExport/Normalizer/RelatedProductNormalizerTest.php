<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ScalarFieldDenormalizer;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\ImportExport\Normalizer\RelatedProductNormalizer;

class RelatedProductNormalizerTest extends \PHPUnit\Framework\TestCase
{
    private FieldHelper|\PHPUnit\Framework\MockObject\MockObject $fieldHelper;

    private RelatedProductNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->fieldHelper = $this->createMock(FieldHelper::class);
        $serializer = $this->createMock(Serializer::class);

        $this->normalizer = new RelatedProductNormalizer($this->fieldHelper);
        $this->normalizer->setSerializer($serializer);
        $this->normalizer->setScalarFieldDenormalizer(new ScalarFieldDenormalizer());
    }

    public function testSupportsNormalization(): void
    {
        self::assertFalse($this->normalizer->supportsNormalization(new RelatedProduct()));
    }

    public function testSupportsDenormalization(): void
    {
        self::assertTrue($this->normalizer->supportsDenormalization([], RelatedProduct::class));
    }

    public function testDenormalize(): void
    {
        $expected = new RelatedProduct();

        $this->fieldHelper->expects(self::atLeastOnce())
            ->method('setObjectValue')
            ->withConsecutive(
                [$expected, 'product', ['sku' => 'sku-1']],
                [$expected, 'relatedItem', ['sku' => 'sku-2']]
            );

        $this->fieldHelper->expects(self::any())
            ->method('getEntityFields')
            ->willReturnMap(
                [
                    [
                        RelatedProduct::class,
                        EntityFieldProvider::OPTION_WITH_RELATIONS,
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

        self::assertEquals(
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
