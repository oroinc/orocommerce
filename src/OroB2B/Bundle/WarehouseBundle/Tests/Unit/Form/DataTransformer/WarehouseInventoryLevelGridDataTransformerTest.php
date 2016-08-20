<?php

namespace Oro\Bundle\WarehouseBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\WarehouseBundle\Form\DataTransformer\WarehouseInventoryLevelGridDataTransformer;

class WarehouseInventoryLevelGridDataTransformerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
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

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->product = new Product();

        $this->transformer = new WarehouseInventoryLevelGridDataTransformer(
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
     * @param $value
     * @param $expected
     * @param DoctrineHelper|null $doctrineHelper
     * @param Product|null $product
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

        $transformer = new WarehouseInventoryLevelGridDataTransformer($doctrineHelper, $product);
        $this->assertEquals($expected, $transformer->reverseTransform($value));
    }

    /**
     * @return array
     */
    public function reverseTransformDataProvider()
    {
        $firstWarehouse = $this->getEntity('Oro\Bundle\WarehouseBundle\Entity\Warehouse', ['id' => 1]);
        $secondWarehouse = $this->getEntity('Oro\Bundle\WarehouseBundle\Entity\Warehouse', ['id' => 2]);

        $warehouseClass = 'OroWarehouseBundle:Warehouse';
        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->willReturnMap([
                [$warehouseClass, 1, $firstWarehouse],
                [$warehouseClass, 2, $secondWarehouse],
            ]);

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
                    '1_11' => ['data' => ['levelQuantity' => '42']],
                    '2_12' => ['data' => ['levelQuantity' => null]],
                    '1_13' => ['data' => ['levelQuantity' => '1']],
                    '3_11' => ['data' => ['levelQuantity' => '2']],
                ]),
                'expected' => new ArrayCollection([
                    '1_11' => [
                        'data' => ['levelQuantity' => '42'],
                        'warehouse' => $firstWarehouse,
                        'precision' => $firstPrecision,
                    ],
                    '2_12' => [
                        'data' => ['levelQuantity' => null],
                        'warehouse' => $secondWarehouse,
                        'precision' => $secondPrecision,
                    ]
                ]),
                'doctrineHelper' => $doctrineHelper,
                'product' => $product,
            ]
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "array", "string" given
     */
    public function testReverseTransformException()
    {
        $this->transformer->reverseTransform('test');
    }
}
