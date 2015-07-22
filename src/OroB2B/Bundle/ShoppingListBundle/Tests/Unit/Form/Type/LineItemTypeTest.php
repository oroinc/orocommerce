<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\LineItemType;
use OroB2B\Bundle\ShoppingListBundle\Manager\LineItemManager;
use OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;

class LineItemTypeTest extends FormIntegrationTestCase
{
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

        /** @var \PHPUnit_Framework_MockObject_MockObject|LineItemManager $lineItemManager */
        $lineItemManager = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Manager\LineItemManager')
            ->disableOriginalConstructor()
            ->getMock();
        $lineItemManager->expects($this->any())
            ->method('roundProductQuantity')
            ->willReturnCallback(
                function ($product, $unit, $quantity) {
                    /** @var \PHPUnit_Framework_MockObject_MockObject|Product $product */
                    return round($quantity, $product->getUnitPrecision($unit)->getPrecision());
                }
            );

        $this->type = new LineItemType($this->getRegistry(), $lineItemManager);
        $this->type->setDataClass(self::DATA_CLASS);
        $this->type->setProductClass(self::PRODUCT_CLASS);
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

        if ($isExisting) {
            $repo = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository')
                ->disableOriginalConstructor()
                ->getMock();
            $repo->expects($this->once())
                ->method('getProductUnitsQueryBuilder')
                ->will($this->returnValue(null));

            $closure = $form->get('unit')->getConfig()->getOptions()['query_builder'];
            $this->assertNull($closure($repo));
        }

        $form->submit($submittedData);

        $this->assertEquals([], $form->getErrors());
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
            ->setQuantity('15.1119')
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

    public function testSetDefaultOptions()
    {
        $resolver = new OptionsResolver();
        $this->type->setDefaultOptions($resolver);
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

    /**
     * @return ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRegistry()
    {
        $repo = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->any())
            ->method('find')
            ->willReturn($this->getProductEntityWithPrecision(1, 'kg', 3));

        /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $registry */
        $registry = $this->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $registry->expects($this->any())
            ->method('getRepository')
            ->with($this->isType('string'))
            ->willReturn($repo);

        return $registry;
    }

    /**
     * @param integer $productId
     * @param string  $unitCode
     * @param integer $precision
     *
     * @return Product
     */
    protected function getProductEntityWithPrecision($productId, $unitCode, $precision = 0)
    {
        /** @var \OroB2B\Bundle\ProductBundle\Entity\Product $product */
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', $productId);

        $unit = new ProductUnit();
        $unit->setCode($unitCode);

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision
            ->setPrecision($precision)
            ->setUnit($unit)
            ->setProduct($product);

        return $product->addUnitPrecision($unitPrecision);
    }

    /**
     * @param string $className
     * @param int    $id
     *
     * @return object
     */
    protected function getEntity($className, $id)
    {
        $entity = new $className;

        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);

        return $entity;
    }
}
