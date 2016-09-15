<?php

namespace Oro\Bundle\WarehouseBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\FormBundle\Form\Type\DataChangesetType;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\WarehouseBundle\Form\Type\WarehouseInventoryLevelGridType;

class WarehouseInventoryLevelGridTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var WarehouseInventoryLevelGridType
     */
    protected $type;

    /**
     * @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $formFactory;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    protected function setUp()
    {
        parent::setUp();

        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new WarehouseInventoryLevelGridType($this->formFactory, $this->doctrineHelper);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension([DataChangesetType::NAME => new DataChangesetType()], [])
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(WarehouseInventoryLevelGridType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(DataChangesetType::NAME, $this->type->getParent());
    }

    /**
     * @param array $options
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @param DoctrineHelper $doctrineHelper
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $options, $submittedData, $expectedData, DoctrineHelper $doctrineHelper = null)
    {
        $type = $doctrineHelper
            ? new WarehouseInventoryLevelGridType($this->formFactory, $doctrineHelper)
            : $this->type;

        $form = $this->factory->create($type, null, $options);
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider()
    {
        $firstWarehouse = $this->getEntity('Oro\Bundle\WarehouseProBundle\Entity\Warehouse', ['id' => 1]);
        $secondWarehouse = $this->getEntity('Oro\Bundle\WarehouseProBundle\Entity\Warehouse', ['id' => 2]);

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
            'no data' => [
                'options' => ['product' => $product],
                'submittedData' => null,
                'expectedData' => new ArrayCollection([]),
            ],
            'empty data' => [
                'options' => ['product' => $product],
                'submittedData' => '',
                'expectedData' => new ArrayCollection([]),
            ],
            'valid data' => [
                'options' => ['product' => $product],
                'submittedData' => json_encode([
                    '1_11' => ['levelQuantity' => '42'],
                    '2_12' => ['levelQuantity' => null],
                ]),
                'expectedData' => new ArrayCollection([
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
            ]
        ];
    }

    public function testFinishView()
    {
        $kgUnit = new ProductUnit();
        $kgUnit->setCode('kg')->setDefaultPrecision(3);
        $itemUnit = new ProductUnit();
        $itemUnit->setCode('item')->setDefaultPrecision(0);

        $kgPrecision = new ProductUnitPrecision();
        $kgPrecision->setUnit($kgUnit)->setPrecision(1);
        $itemPrecision = new ProductUnitPrecision();
        $itemPrecision->setUnit($itemUnit)->setPrecision(0);

        $productId = 42;
        /** @var Product $product */
        $product = $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => $productId]);
        $product->addUnitPrecision($kgPrecision)->addUnitPrecision($itemPrecision);

        $constraints = ['some' => 'constraints'];
        $constraintsView = new FormView();
        $constraintsView->vars['attr']['data-validation'] = json_encode($constraints);

        $constraintsForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $constraintsForm->expects($this->once())
            ->method('createView')
            ->willReturn($constraintsView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with('number', null, $this->isType('array'))
            ->willReturn($constraintsForm);

        $view = new FormView();
        /** @var FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $options = ['product' => $product];

        $this->type->finishView($view, $form, $options);
        $this->assertArrayHasKey('product', $view->vars);
        $this->assertArrayHasKey('unitPrecisions', $view->vars);
        $this->assertArrayHasKey('quantityConstraints', $view->vars);
        $this->assertEquals($product, $view->vars['product']);
        $this->assertEquals(['kg' => 1, 'item' => 0], $view->vars['unitPrecisions']);
        $this->assertEquals($constraints, $view->vars['quantityConstraints']);
    }
}
