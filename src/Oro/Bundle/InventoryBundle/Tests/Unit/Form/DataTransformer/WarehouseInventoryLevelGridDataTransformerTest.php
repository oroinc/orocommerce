<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Form\DataTransformer\InventoryLevelGridDataTransformer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class WarehouseInventoryLevelGridDataTransformerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var Product */
    private $product;

    /** @var InventoryLevelGridDataTransformer */
    private $transformer;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->product = new Product();

        $this->transformer = new InventoryLevelGridDataTransformer(
            $this->doctrineHelper,
            $this->product
        );
    }

    public function testTransform()
    {
        $data = ['some random data'];
        $this->assertEquals($data, $this->transformer->transform($data));
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(
        ArrayCollection|array|null $value,
        ArrayCollection $expected,
        DoctrineHelper $doctrineHelper = null,
        Product $product = null
    ) {
        $doctrineHelper = $doctrineHelper ?: $this->doctrineHelper;
        $product = $product ?: $this->product;

        $transformer = new InventoryLevelGridDataTransformer($doctrineHelper, $product);
        $this->assertEquals($expected, $transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider(): array
    {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);

        $firstPrecision = $this->getEntity(ProductUnitPrecision::class, ['id' => 11]);
        $secondPrecision = $this->getEntity(ProductUnitPrecision::class, ['id' => 12]);

        $product = new Product();
        $product->addUnitPrecision($firstPrecision)
            ->addUnitPrecision($secondPrecision);

        return [
            [
                'value' => null,
                'expected' => new ArrayCollection([]),
            ],
            [
                'value' => [],
                'expected' => new ArrayCollection([]),
            ],
            [
                'value' => new ArrayCollection([
                    '11' => ['data' => ['levelQuantity' => '42']],
                    '12' => ['data' => ['levelQuantity' => null]],
                ]),
                'expected' => new ArrayCollection([
                    '11' => [
                        'data' => ['levelQuantity' => '42'],
                        'precision' => $firstPrecision,
                    ],
                    '12' => [
                        'data' => ['levelQuantity' => null],
                        'precision' => $secondPrecision,
                    ]
                ]),
                'doctrineHelper' => $doctrineHelper,
                'product' => $product,
            ]
        ];
    }

    public function testReverseTransformException()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "array", "string" given');

        $this->transformer->reverseTransform('test');
    }
}
