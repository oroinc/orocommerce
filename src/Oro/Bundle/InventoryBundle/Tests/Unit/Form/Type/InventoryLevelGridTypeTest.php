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

    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var InventoryLevelGridType */
    private $type;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->type = new InventoryLevelGridType($this->formFactory, $this->doctrineHelper);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
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
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $options, mixed $submittedData, mixed $expectedData)
    {
        $form = $this->factory->create(InventoryLevelGridType::class, null, $options);
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        $firstPrecision = $this->getEntity(ProductUnitPrecision::class, ['id' => 11]);
        $secondPrecision = $this->getEntity(ProductUnitPrecision::class, ['id' => 12]);

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
                ], JSON_THROW_ON_ERROR),
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
        $product = $this->getEntity(Product::class, ['id' => $productId]);
        $product->addUnitPrecision($kgPrecision)->addUnitPrecision($itemPrecision);

        $constraints = ['some' => 'constraints'];
        $constraintsView = new FormView();
        $constraintsView->vars['attr']['data-validation'] = json_encode($constraints, JSON_THROW_ON_ERROR);

        $constraintsForm = $this->createMock(FormInterface::class);
        $constraintsForm->expects($this->once())
            ->method('createView')
            ->willReturn($constraintsView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(NumberType::class, null, $this->isType('array'))
            ->willReturn($constraintsForm);

        $view = new FormView();
        $form = $this->createMock(FormInterface::class);
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
