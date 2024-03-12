<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context\LineItem;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Context\LineItem\ShippingLineItemOptionsModifier;
use Oro\Bundle\ShippingBundle\Context\ShippingKitItemLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingLineItemOptionsModifierTest extends TestCase
{
    private ProductShippingOptionsRepository|MockObject $repository;
    private ManagerRegistry|MockObject $managerRegistry;
    private ShippingLineItemOptionsModifier $modifier;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProductShippingOptionsRepository::class);

        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->with(ProductShippingOptions::class)
            ->willReturn($this->repository);

        $this->modifier = new ShippingLineItemOptionsModifier($this->managerRegistry);
    }

    public function testModifyLineItemWithShippingOptionsWithoutProduct(): void
    {
        $this->repository->expects(self::never())
            ->method('findIndexedByProductsAndUnits')
            ->withAnyParameters();

        $lineItem = new ShippingKitItemLineItem(new OrderLineItem());
        $lineItem->setProductUnit(new ProductUnit());
        $this->modifier->modifyLineItemWithShippingOptions($lineItem);

        self::assertNull($lineItem->getWeight());
        self::assertNull($lineItem->getDimensions());
    }

    public function testModifyLineItemWithShippingOptionsWithoutProductUnit(): void
    {
        $this->repository->expects(self::never())
            ->method('findIndexedByProductsAndUnits')
            ->withAnyParameters();

        $lineItem = new ShippingKitItemLineItem(new OrderLineItem());
        $lineItem->setProduct(new Product());
        $this->modifier->modifyLineItemWithShippingOptions($lineItem);

        self::assertNull($lineItem->getWeight());
        self::assertNull($lineItem->getDimensions());
    }

    public function testModifyLineItemWithShippingOptionsWithLoadedOptions(): void
    {
        $lengthUnitIn = (new LengthUnit())->setCode('in');
        $weightUnitLbs = (new WeightUnit())->setCode('lbs');

        ReflectionUtil::setPropertyValue(
            $this->modifier,
            'shippingOptions',
            [
                // Indexed shipping options for Product Kit
                1 => [
                    'item' => [
                        'dimensionsHeight' => 1.0,
                        'dimensionsLength' => 2.0,
                        'dimensionsWidth' => 3.0,
                        'dimensionsUnit' => 'in',
                        'weightUnit' => 'lbs',
                        'weightValue' => 4.0,
                        'code' => 'item',
                    ]
                ]
            ]
        );

        $product = new Product();
        ReflectionUtil::setPropertyValue($product, 'id', 1);

        $lineItem = new ShippingKitItemLineItem(new OrderLineItem());
        $lineItem->setProductUnit((new ProductUnit())->setCode('item'));
        $lineItem->setProduct($product);

        $manager = $this->createMock(EntityManager::class);
        $this->managerRegistry->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturn($manager);

        $manager->expects(self::exactly(2))
            ->method('getReference')
            ->willReturnOnConsecutiveCalls(
                $weightUnitLbs,
                $lengthUnitIn,
            );

        $this->modifier->modifyLineItemWithShippingOptions($lineItem);

        // Loaded shipping options for Kit Line Item
        self::assertEquals(Weight::create(4.0, $weightUnitLbs), $lineItem->getWeight());
        self::assertEquals(Dimensions::create(2.0, 3.0, 1.0, $lengthUnitIn), $lineItem->getDimensions());
    }

    public function testModifyLineItemWithShippingOptionsWithoutLoadedOptions(): void
    {
        $lengthUnitIn = (new LengthUnit())->setCode('in');
        $weightUnitLbs = (new WeightUnit())->setCode('lbs');

        $product = new Product();
        ReflectionUtil::setPropertyValue($product, 'id', 1);
        $product->setType(Product::TYPE_KIT);

        $productSimple = new Product();
        ReflectionUtil::setPropertyValue($productSimple, 'id', 2);

        $kitItemLineItem = new ShippingKitItemLineItem(new OrderLineItem());
        $kitItemLineItem->setProductUnit((new ProductUnit())->setCode('item'));
        $kitItemLineItem->setProduct($productSimple);

        $lineItem = new ShippingLineItem((new ProductUnit())->setCode('item'), 2, new OrderLineItem());
        $lineItem->setProduct($product);
        $lineItem->setKitItemLineItems(new ArrayCollection([$kitItemLineItem]));

        $manager = $this->createMock(EntityManager::class);
        $this->managerRegistry->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturn($manager);

        $manager->expects(self::exactly(2))
            ->method('getReference')
            ->willReturnOnConsecutiveCalls(
                $weightUnitLbs,
                $lengthUnitIn,
            );

        $this->repository->expects(self::once())
            ->method('findIndexedByProductsAndUnits')
            ->willReturn(
                [
                    // Indexed shipping options for Product Kit
                    1 => [
                        'item' => [
                            'dimensionsHeight' => 1.0,
                            'dimensionsLength' => 2.0,
                            'dimensionsWidth' => 3.0,
                            'dimensionsUnit' => 'in',
                            'weightUnit' => 'lbs',
                            'weightValue' => 4.0,
                            'code' => 'item',
                        ]
                    ],
                    // Indexed shipping options for Kit Item Product
                    2 => [
                        'item' => [
                            'dimensionsHeight' => 13.0,
                            'dimensionsLength' => 11.0,
                            'dimensionsWidth' => 12.0,
                            'dimensionsUnit' => 'in',
                            'weightUnit' => 'lbs',
                            'weightValue' => 142.0,
                        ],
                    ],
                ],
            );

        $this->modifier->modifyLineItemWithShippingOptions($lineItem);

        // Loaded shipping options for Kit Line Item
        self::assertEquals(Weight::create(4.0, $weightUnitLbs), $lineItem->getWeight());
        self::assertEquals(Dimensions::create(2.0, 3.0, 1.0, $lengthUnitIn), $lineItem->getDimensions());

        $modifiedKitItemLineItems = $lineItem->getKitItemLineItems()->toArray();

        // Loaded shipping options for Kit Item Line Item
        self::assertEquals(Weight::create(142.0, $weightUnitLbs), $modifiedKitItemLineItems[0]->getWeight());
        self::assertEquals(
            Dimensions::create(11.0, 12.0, 13.0, $lengthUnitIn),
            $modifiedKitItemLineItems[0]->getDimensions()
        );
    }

    public function testModifyLineItemWithShippingOptionsLoadKitShippingOptionsOnlyOnce(): void
    {
        $lengthUnitIn = (new LengthUnit())->setCode('in');
        $weightUnitLbs = (new WeightUnit())->setCode('lbs');

        $product = new Product();
        ReflectionUtil::setPropertyValue($product, 'id', 1);
        $product->setType(Product::TYPE_KIT);

        $productSimple = new Product();
        ReflectionUtil::setPropertyValue($productSimple, 'id', 2);

        $kitItemLineItem = new ShippingKitItemLineItem(new OrderLineItem());
        $kitItemLineItem->setProductUnit((new ProductUnit())->setCode('item'));
        $kitItemLineItem->setProduct($productSimple);

        $lineItem = new ShippingLineItem((new ProductUnit())->setCode('item'), 2, new OrderLineItem());
        $lineItem->setProduct($product);
        $lineItem->setKitItemLineItems(new ArrayCollection([$kitItemLineItem]));

        $manager = $this->createMock(EntityManager::class);
        $this->managerRegistry->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturn($manager);

        $manager->expects(self::exactly(2))
            ->method('getReference')
            ->willReturnOnConsecutiveCalls(
                $weightUnitLbs,
                $lengthUnitIn,
            );

        $this->repository->expects(self::once())
            ->method('findIndexedByProductsAndUnits')
            ->willReturn(
                [
                    // Indexed shipping options for Product Kit
                    1 => [
                        'item' => [
                            'dimensionsHeight' => 1.0,
                            'dimensionsLength' => 2.0,
                            'dimensionsWidth' => 3.0,
                            'dimensionsUnit' => 'in',
                            'weightUnit' => 'lbs',
                            'weightValue' => 4.0,
                            'code' => 'item',
                        ]
                    ],
                ],
            );

        $this->modifier->modifyLineItemWithShippingOptions($lineItem);

        // Loaded shipping options for Kit Line Item
        self::assertEquals(Weight::create(4.0, $weightUnitLbs), $lineItem->getWeight());
        self::assertEquals(Dimensions::create(2.0, 3.0, 1.0, $lengthUnitIn), $lineItem->getDimensions());

        $modifiedKitItemLineItems = $lineItem->getKitItemLineItems()->toArray();

        // No shipping options for Kit Item Line Item
        self::assertNull($modifiedKitItemLineItems[0]->getWeight());
        self::assertNull($modifiedKitItemLineItems[0]->getDimensions());
    }

    public function testClear(): void
    {
        ReflectionUtil::setPropertyValue($this->modifier, 'shippingOptions', ['shippingOptions']);
        ReflectionUtil::setPropertyValue($this->modifier, 'dimensionsUnits', ['dimensionsUnits']);
        ReflectionUtil::setPropertyValue($this->modifier, 'weightUnits', ['weightUnits']);

        $this->modifier->clear();

        self::assertEmpty(ReflectionUtil::getPropertyValue($this->modifier, 'shippingOptions'));
        self::assertEmpty(ReflectionUtil::getPropertyValue($this->modifier, 'dimensionsUnits'));
        self::assertEmpty(ReflectionUtil::getPropertyValue($this->modifier, 'weightUnits'));
    }
}
