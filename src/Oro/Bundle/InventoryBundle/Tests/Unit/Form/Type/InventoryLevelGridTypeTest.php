<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\Type\DataChangesetType;
use Oro\Bundle\InventoryBundle\Form\Type\InventoryLevelGridType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class InventoryLevelGridTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var InventoryLevelGridType
     */
    protected $type;

    /**
     * @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $formFactory;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock('Symfony\Component\Form\FormFactoryInterface');
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new InventoryLevelGridType($this->formFactory, $this->doctrineHelper);
        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension([
                $this->type,
                DataChangesetType::class => new DataChangesetType()
            ], [])
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals(DataChangesetType::class, $this->type->getParent());
    }

    /**
     * @param array $options
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $options, $submittedData, $expectedData)
    {
        $form = $this->factory->create(InventoryLevelGridType::class, null, $options);
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider()
    {
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
                    ],
                    '2_12' => [
                        'data' => ['levelQuantity' => null],
                    ]

                ])
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

        $constraintsForm = $this->createMock('Symfony\Component\Form\FormInterface');
        $constraintsForm->expects($this->once())
            ->method('createView')
            ->willReturn($constraintsView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(NumberType::class, null, $this->isType('array'))
            ->willReturn($constraintsForm);

        $view = new FormView();
        /** @var FormInterface $form */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');
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
