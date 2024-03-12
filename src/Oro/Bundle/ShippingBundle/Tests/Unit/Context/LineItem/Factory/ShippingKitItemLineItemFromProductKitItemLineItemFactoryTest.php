<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context\LineItem\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShippingBundle\Context\LineItem\Factory\ShippingKitItemLineItemFromProductKitItemLineItemFactory;
use Oro\Bundle\ShippingBundle\Context\LineItem\ShippingLineItemOptionsModifier;
use Oro\Bundle\ShippingBundle\Context\ShippingKitItemLineItem;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingKitItemLineItemFromProductKitItemLineItemFactoryTest extends TestCase
{
    private ManagerRegistry|MockObject $managerRegistry;
    private ProductShippingOptionsRepository|MockObject $repository;

    private ShippingKitItemLineItemFromProductKitItemLineItemFactory $factory;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProductShippingOptionsRepository::class);

        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->with(ProductShippingOptions::class)
            ->willReturn($this->repository);

        $this->factory = new ShippingKitItemLineItemFromProductKitItemLineItemFactory(
            new ShippingLineItemOptionsModifier($this->managerRegistry)
        );
    }

    public function testCreate(): void
    {
        $lengthUnit = (new LengthUnit())->setCode('in');
        $weightUnit = (new WeightUnit())->setCode('kilo');

        $manager = $this->createMock(EntityManager::class);
        $this->managerRegistry->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $manager->expects(self::exactly(2))
            ->method('getReference')
            ->willReturnOnConsecutiveCalls($weightUnit, $lengthUnit);

        $this->repository->expects(self::once())
            ->method('findIndexedByProductsAndUnits')
            ->willReturn(
                [
                    1 => [
                        'item' => [
                            'dimensionsHeight' => 1.0,
                            'dimensionsLength' => 2.0,
                            'dimensionsWidth' => 3.0,
                            'dimensionsUnit' => 'in',
                            'weightUnit' => 'kilo',
                            'weightValue' => 23.0,
                        ],
                    ],
                ]
            );

        $productKitItemLineItem = $this->getKitItemLineItem(
            12.3456,
            (new ProductUnit())->setCode('item'),
            Price::create(1, 'USD'),
            (new ProductStub())->setId(1)->setSku('sku1')
        );

        $expectedShippingKitItemLineItem = (new ShippingKitItemLineItem($productKitItemLineItem))
            ->setProduct($productKitItemLineItem->getProduct())
            ->setProductSku($productKitItemLineItem->getProductSku())
            ->setProductUnit($productKitItemLineItem->getProductUnit())
            ->setProductUnitCode($productKitItemLineItem->getProductUnitCode())
            ->setQuantity($productKitItemLineItem->getQuantity())
            ->setPrice($productKitItemLineItem->getPrice())
            ->setKitItem($productKitItemLineItem->getKitItem())
            ->setSortOrder($productKitItemLineItem->getSortOrder())
            ->setDimensions(Dimensions::create(2, 3, 1, $lengthUnit))
            ->setWeight(Weight::create(23, $weightUnit));

        self::assertEquals(
            $expectedShippingKitItemLineItem,
            $this->factory->create($productKitItemLineItem)
        );
    }

    public function testCreateWhenNoProductUnit(): void
    {
        $productKitItemLineItem = $this->getKitItemLineItem(
            1,
            (new ProductUnit())->setCode('item'),
            Price::create(1, 'USD'),
            (new ProductStub())->setId(1)
        );

        $productKitItemLineItem->setProductUnit(null);
        $productKitItemLineItem->setProductUnitCode('set');

        $expectedShippingKitItemLineItem = (new ShippingKitItemLineItem($productKitItemLineItem))
            ->setProduct($productKitItemLineItem->getProduct())
            ->setProductSku($productKitItemLineItem->getProductSku())
            ->setProductUnit(null)
            ->setProductUnitCode($productKitItemLineItem->getProductUnitCode())
            ->setQuantity($productKitItemLineItem->getQuantity())
            ->setPrice($productKitItemLineItem->getPrice())
            ->setKitItem($productKitItemLineItem->getKitItem())
            ->setSortOrder($productKitItemLineItem->getSortOrder());

        self::assertEquals(
            $expectedShippingKitItemLineItem,
            $this->factory->create($productKitItemLineItem)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateCollection(): void
    {
        $lengthUnit = (new LengthUnit())->setCode('in');
        $weightUnit = (new WeightUnit())->setCode('kilo');

        $manager = $this->createMock(EntityManager::class);
        $this->managerRegistry->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $manager->expects(self::exactly(2))
            ->method('getReference')
            ->willReturnOnConsecutiveCalls($weightUnit, $lengthUnit);

        $this->repository->expects(self::once())
            ->method('findIndexedByProductsAndUnits')
            ->willReturn(
                [
                    1 => [
                        'item' => [
                            'dimensionsHeight' => 3.0,
                            'dimensionsLength' => 1.0,
                            'dimensionsWidth' => 2.0,
                            'dimensionsUnit' => 'in',
                            'weightUnit' => 'kilo',
                            'weightValue' => 66.0,
                        ],
                    ],
                    2 => [
                        'set' => [
                            'dimensionsHeight' => 6.0,
                            'dimensionsLength' => 2.0,
                            'dimensionsWidth' => 4.0,
                            'dimensionsUnit' => 'in',
                            'weightUnit' => 'kilo',
                            'weightValue' => 132.0,
                        ],
                    ],
                ]
            );

        $productKitItemLineItem1 = $this->getKitItemLineItem(
            12.3456,
            (new ProductUnit())->setCode('item'),
            Price::create(1, 'USD'),
            (new ProductStub())->setId(1)
        );
        $productKitItemLineItem2 = $this->getKitItemLineItem(
            23.4567,
            (new ProductUnit())->setCode('set'),
            Price::create(2, 'USD'),
            (new ProductStub())->setId(2)
        );

        $productKitItemLineItems = new ArrayCollection([
            $productKitItemLineItem1,
            $productKitItemLineItem2,
        ]);

        $expectedShippingKitItemLineItems = [
            (new ShippingKitItemLineItem($productKitItemLineItem1))
                ->setProduct($productKitItemLineItem1->getProduct())
                ->setProductSku($productKitItemLineItem1->getProductSku())
                ->setProductUnit($productKitItemLineItem1->getProductUnit())
                ->setProductUnitCode($productKitItemLineItem1->getProductUnitCode())
                ->setQuantity($productKitItemLineItem1->getQuantity())
                ->setPrice($productKitItemLineItem1->getPrice())
                ->setKitItem($productKitItemLineItem1->getKitItem())
                ->setSortOrder($productKitItemLineItem1->getSortOrder())
                ->setDimensions(Dimensions::create(1, 2, 3, $lengthUnit))
                ->setWeight(Weight::create(66, $weightUnit)),
            (new ShippingKitItemLineItem($productKitItemLineItem2))
                ->setProduct($productKitItemLineItem2->getProduct())
                ->setProductSku($productKitItemLineItem2->getProductSku())
                ->setProductUnit($productKitItemLineItem2->getProductUnit())
                ->setProductUnitCode($productKitItemLineItem2->getProductUnitCode())
                ->setQuantity($productKitItemLineItem2->getQuantity())
                ->setPrice($productKitItemLineItem2->getPrice())
                ->setKitItem($productKitItemLineItem2->getKitItem())
                ->setSortOrder($productKitItemLineItem2->getSortOrder())
                ->setDimensions(Dimensions::create(2, 4, 6, $lengthUnit))
                ->setWeight(Weight::create(132, $weightUnit)),
        ];

        self::assertEquals(
            new ArrayCollection($expectedShippingKitItemLineItems),
            $this->factory->createCollection($productKitItemLineItems)
        );
    }

    private function getKitItemLineItem(
        float $quantity,
        ?ProductUnit $productUnit,
        ?Price $price,
        ?Product $product,
    ): OrderProductKitItemLineItem {
        return (new OrderProductKitItemLineItem())
            ->setProduct($product)
            ->setProductUnit($productUnit)
            ->setQuantity($quantity)
            ->setPrice($price)
            ->setSortOrder(1)
            ->setKitItem(new ProductKitItemStub());
    }
}
