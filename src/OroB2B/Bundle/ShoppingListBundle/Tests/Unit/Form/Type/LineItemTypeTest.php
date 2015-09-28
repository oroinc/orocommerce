<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\LineItemType;
use OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;

class LineItemTypeTest extends AbstractFormIntegrationTestCase
{
    use QuantityTypeTrait;

    const DATA_CLASS = 'OroB2B\Bundle\ShoppingListBundle\Entity\LineItem';
    const PRODUCT_CLASS = 'OroB2B\Bundle\ProductBundle\Entity\Product';

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

    protected function setUp()
    {
        parent::setUp();

        $this->type = new LineItemType();
        $this->type->setDataClass(self::DATA_CLASS);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType(
            [
                1 => $this->getProductEntityWithPrecision(1, 'kg', 3),
                2 => $this->getProductEntityWithPrecision(2, 'kg', 3)
            ]
        );

        $productUnitSelection = new EntityType(
            $this->prepareProductUnitSelectionChoices(),
            ProductUnitSelectionType::NAME
        );
        $productSelectType = new ProductSelectTypeStub();

        return [
            new PreloadedExtension(
                [
                    $entityType->getName()         => $entityType,
                    $productSelectType->getName()  => $productSelectType,
                    ProductUnitSelectionType::NAME => $productUnitSelection,
                    QuantityTypeTrait::$name       => $this->getQuantityType(),
                ],
                []
            )
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type);

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
     * @param bool $isExisting
     */
    public function testSubmit($defaultData, $submittedData, $expectedData, $isExisting)
    {
        $form = $this->factory->create($this->type, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $this->addRoundingServiceExpect();

        $form->submit($submittedData);

        if ($isExisting) {
            $repo = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository')
                ->disableOriginalConstructor()
                ->getMock();
            $repo->expects($this->once())
                ->method('getProductUnitsQueryBuilder')
                ->will($this->returnValue(null));

            $closure = $form->get('unit')->getConfig()->getOptions()['query_builder'];
            $this->assertNotEmpty($closure);
            $this->assertNull($closure($repo));
        }

        $this->assertEmpty($form->getErrors(true)->count());
        $this->assertTrue($form->isValid());
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

        $existingLineItem = $this->getEntity('OroB2B\Bundle\ShoppingListBundle\Entity\LineItem', 2);
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
                    'quantity' => 15.1119,
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
                    'quantity' => 15.1119,
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
        $lineItem2 = $this->getEntity('OroB2B\Bundle\ShoppingListBundle\Entity\LineItem', 1);

        $this->assertEquals(self::DATA_CLASS, $resolvedOptions['data_class']);
        $this->assertEquals(['create'], $resolvedOptions['validation_groups']($this->getForm($lineItem)));
        $this->assertEquals(['update'], $resolvedOptions['validation_groups']($this->getForm($lineItem2)));
    }

    public function testGetName()
    {
        $this->assertEquals(LineItemType::NAME, $this->type->getName());
    }

    /**
     * @param LineItem $lineItem
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected function getForm(LineItem $lineItem)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
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
