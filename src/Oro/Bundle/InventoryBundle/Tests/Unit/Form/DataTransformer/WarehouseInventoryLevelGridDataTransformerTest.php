<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Form\DataTransformer\InventoryLevelGridDataTransformer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Component\Testing\Unit\EntityTrait;

class WarehouseInventoryLevelGridDataTransformerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var WarehouseInventoryLevelGridDataTransformer
     */
    protected $transformer;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
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
        $value,
        $expected,
        DoctrineHelper $doctrineHelper = null,
        Product $product = null
    ) {
        $doctrineHelper = $doctrineHelper ?: $this->doctrineHelper;
        $product = $product ?: $this->product;

        $transformer = new InventoryLevelGridDataTransformer($doctrineHelper, $product);
        $this->assertEquals($expected, $transformer->reverseTransform($value));
    }

    /**
     * @return array
     */
    public function reverseTransformDataProvider()
    {
        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ProductUnitPrecision $firstPrecision */
        $firstPrecision = $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision', ['id' => 11]);
        /** @var ProductUnitPrecision $secondPrecision */
        $secondPrecision = $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision', ['id' => 12]);

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
        $this->expectException(\Symfony\Component\Form\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "array", "string" given');

        $this->transformer->reverseTransform('test');
    }
}
