<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Type\LineItemType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LineItemTypeTest extends AbstractFormIntegrationTestCase
{
    use QuantityTypeTrait;

    const DATA_CLASS = 'Oro\Bundle\ShoppingListBundle\Entity\LineItem';
    const PRODUCT_CLASS = 'Oro\Bundle\ProductBundle\Entity\Product';

    /**
     * @var LineItemType
     */
    protected $type;

    /**
     * @var array
     */
    protected $units = [
        'item',
        'kg'
    ];

    protected function setUp(): void
    {
        $this->type = new LineItemType();
        $this->type->setDataClass(self::DATA_CLASS);
        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityTypeStub(
            [
                1 => $this->getProductEntityWithPrecision(1, 'kg', 3),
                2 => $this->getProductEntityWithPrecision(2, 'kg', 3)
            ]
        );

        $productUnitSelection = new ProductUnitSelectionTypeStub($this->prepareProductUnitSelectionChoices());
        $productSelectType = new ProductSelectTypeStub();

        return [
            new PreloadedExtension(
                [
                    LineItemType::class => $this->type,
                    EntityType::class => $entityType,
                    ProductSelectType::class => $productSelectType,
                    ProductUnitSelectionType::class => $productUnitSelection,
                    QuantityType::class => $this->getQuantityType(),
                ],
                []
            )
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(LineItemType::class);

        $this->assertTrue($form->has('product'));
        $this->assertTrue($form->has('quantity'));
        $this->assertTrue($form->has('unit'));
        $this->assertTrue($form->has('notes'));
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param mixed $defaultData
     * @param mixed $submittedData
     * @param mixed $expectedData
     */
    public function testSubmit($defaultData, $submittedData, $expectedData)
    {
        $form = $this->factory->create(LineItemType::class, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        $this->assertEmpty($form->getErrors(true)->count());
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $shoppingList = new ShoppingList();

        /** @var Product $expectedProduct */
        $expectedProduct = $this->getProductEntityWithPrecision(1, 'kg', 3);

        $defaultLineItem = new LineItem();
        $defaultLineItem->setShoppingList($shoppingList);

        $expectedLineItem = clone $defaultLineItem;
        $expectedLineItem
            ->setProduct($expectedProduct)
            ->setQuantity('10')
            ->setUnit($expectedProduct->getUnitPrecision('kg')->getUnit())
            ->setNotes('my note');

        $existingLineItem = $this->getEntity('Oro\Bundle\ShoppingListBundle\Entity\LineItem', 2);
        $existingLineItem
            ->setShoppingList($shoppingList)
            ->setProduct($expectedProduct)
            ->setQuantity(5)
            ->setUnit($expectedProduct->getUnitPrecision('kg')->getUnit())
            ->setNotes('my note2');

        $expectedLineItem2 = clone $existingLineItem;
        $expectedLineItem2
            ->setQuantity(15.112)
            ->setUnit($expectedProduct->getUnitPrecision('kg')->getUnit())
            ->setNotes('note1');

        $expectedLineItem3 = clone $existingLineItem;
        $expectedLineItem3
            ->setQuantity(15.112)
            ->setUnit($expectedProduct->getUnitPrecision('kg')->getUnit())
            ->setNotes(null);

        return [
            'new line item'      => [
                'defaultData'   => $defaultLineItem,
                'submittedData' => [
                    'product'  => 1,
                    'quantity' => 10,
                    'unit'     => 'kg',
                    'notes'    => 'my note',
                ],
                'expectedData'  => $expectedLineItem,
                'isExisting'    => false,
            ],
            'existing line item' => [
                'defaultData'   => $existingLineItem,
                'submittedData' => [
                    'product'  => 2,
                    'quantity' => 15.112,
                    'unit'     => 'kg',
                    'notes'    => 'note1',
                ],
                'expectedData'  => $expectedLineItem2,
                'isExisting'    => true,
            ],
            'missing product' => [
                'defaultData'   => $existingLineItem,
                'submittedData' => [
                    'unit'     => 'kg',
                    'quantity' => 15.112,
                ],
                'expectedData'  => $expectedLineItem3,
                'isExisting'    => true,
            ],
        ];
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $this->type->configureOptions($resolver);
        $resolvedOptions = $resolver->resolve();

        $lineItem = new LineItem();
        /** @var LineItem $lineItem2 */
        $lineItem2 = $this->getEntity('Oro\Bundle\ShoppingListBundle\Entity\LineItem', 1);

        $this->assertEquals(self::DATA_CLASS, $resolvedOptions['data_class']);
        $this->assertEquals(['create'], $resolvedOptions['validation_groups']($this->getForm($lineItem)));
        $this->assertEquals(['update'], $resolvedOptions['validation_groups']($this->getForm($lineItem2)));
    }

    /**
     * @param LineItem $lineItem
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|FormInterface
     */
    protected function getForm(LineItem $lineItem)
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface $form */
        $form = $this->getMockBuilder('Symfony\Component\Form\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($lineItem));

        return $form;
    }

    /**
     * @return array
     */
    protected function prepareProductUnitSelectionChoices()
    {
        $choices = [];
        foreach ($this->units as $unitCode) {
            $unit = new ProductUnit();
            $unit->setCode($unitCode);
            $choices[$unitCode] = $unit;
        }

        return $choices;
    }
}
