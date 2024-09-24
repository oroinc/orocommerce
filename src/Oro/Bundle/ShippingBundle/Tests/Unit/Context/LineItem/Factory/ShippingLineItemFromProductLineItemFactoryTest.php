<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context\LineItem\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShippingBundle\Context\LineItem\Factory\ShippingKitItemLineItemFromProductKitItemLineItemFactory;
use Oro\Bundle\ShippingBundle\Context\LineItem\Factory\ShippingLineItemFromProductLineItemFactory;
use Oro\Bundle\ShippingBundle\Context\LineItem\ShippingLineItemOptionsModifier;
use Oro\Bundle\ShippingBundle\Context\ShippingKitItemLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingLineItemFromProductLineItemFactoryTest extends TestCase
{
    private const TEST_QUANTITY = 15;

    private ManagerRegistry|MockObject $managerRegistry;
    private ProductShippingOptionsRepository|MockObject $repository;

    private ShippingLineItemFromProductLineItemFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProductShippingOptionsRepository::class);

        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->with(ProductShippingOptions::class)
            ->willReturn($this->repository);

        $modifier = new ShippingLineItemOptionsModifier($this->managerRegistry);

        $this->factory = new ShippingLineItemFromProductLineItemFactory(
            new ShippingKitItemLineItemFromProductKitItemLineItemFactory($modifier),
            $modifier
        );
    }

    public function testCreateByProductLineItemInterfaceOnly(): void
    {
        $product = $this->getProduct(1001);
        $unit = $this->getProductUnit('item');
        $productLineItem = $this->createProductLineItemInterfaceMock(
            $unit,
            $unit->getCode(),
            self::TEST_QUANTITY,
            $product,
            $product->getSku()
        );

        $this->repository->expects(self::once())
            ->method('findIndexedByProductsAndUnits')
            ->willReturn([]);

        $expectedShippingLineItem = $this->createShippingLineItem(
            $productLineItem->getQuantity(),
            $productLineItem->getProductUnit(),
            $productLineItem->getProductUnitCode(),
            $productLineItem,
            null,
            $productLineItem->getProduct(),
            null,
            null,
            null
        );

        self::assertEquals(
            $expectedShippingLineItem,
            $this->factory->create($productLineItem)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreate(): void
    {
        $lengthUnit = (new LengthUnit())->setCode('in');
        $weightUnit = (new WeightUnit())->setCode('kilo');

        $product = $this->getProduct(1001)
            ->setType(Product::TYPE_KIT);
        $unit = $this->getProductUnit('item');
        $kitItemLineItem = $this->createKitItemLineItem(
            self::TEST_QUANTITY,
            $unit,
            Price::create(13, 'USD'),
            $this->getProduct(1)
        );
        $orderLineItem = $this->createOrderLineItem(
            self::TEST_QUANTITY,
            $unit,
            Price::create(99.9, 'USD'),
            $product,
            [$kitItemLineItem]
        );

        $manager = $this->createMock(EntityManager::class);
        $this->managerRegistry->expects(self::exactly(2))
            ->method('getManagerForClass')
            ->willReturn($manager);

        $manager->expects(self::exactly(2))
            ->method('getReference')
            ->willReturnOnConsecutiveCalls($weightUnit, $lengthUnit);

        $this->repository->expects(self::once())
            ->method('findIndexedByProductsAndUnits')
            ->willReturn(
                [
                    $product->getId() => [
                        'item' => [
                            'dimensionsHeight' => 3.0,
                            'dimensionsLength' => 1.0,
                            'dimensionsWidth' => 2.0,
                            'dimensionsUnit' => 'in',
                            'weightUnit' => 'kilo',
                            'weightValue' => 42.0,
                        ],
                    ],
                    1 => [
                        'item' => [
                            'dimensionsHeight' => 1.0,
                            'dimensionsLength' => 2.0,
                            'dimensionsWidth' => 3.0,
                            'dimensionsUnit' => 'in',
                            'weightUnit' => 'kilo',
                            'weightValue' => 23.0,
                        ]
                    ]
                ]
            );

        $expectedShippingKitItemLineItem = $this->createShippingKitItemLineItem(
            $kitItemLineItem->getProductUnit(),
            $kitItemLineItem->getQuantity(),
            $kitItemLineItem->getPrice(),
            $kitItemLineItem->getProduct(),
            $kitItemLineItem,
            $kitItemLineItem->getSortOrder(),
            $kitItemLineItem->getKitItem(),
            Dimensions::create(2, 3, 1, $lengthUnit),
            Weight::create(23, $weightUnit),
        );
        $expectedShippingLineItem = $this->createShippingLineItem(
            $orderLineItem->getQuantity(),
            $orderLineItem->getProductUnit(),
            $orderLineItem->getProductUnitCode(),
            $orderLineItem,
            $orderLineItem->getPrice(),
            $orderLineItem->getProduct(),
            Dimensions::create(1, 2, 3, $lengthUnit),
            Weight::create(42, $weightUnit),
            new ArrayCollection([$expectedShippingKitItemLineItem])
        );

        self::assertEquals(
            $expectedShippingLineItem,
            $this->factory->create($orderLineItem)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateCollection(): void
    {
        $lengthUnitIn = (new LengthUnit())->setCode('in');
        $lengthUnitMeter = (new LengthUnit())->setCode('meter');
        $weightUnitKilo = (new WeightUnit())->setCode('kilo');
        $weightUnitLbs = (new WeightUnit())->setCode('lbs');

        $product1 = $this->getProduct(1001)
            ->setType(Product::TYPE_KIT);
        $product2 = $this->getProduct(2002)
            ->setType(Product::TYPE_KIT);

        $unit = $this->getProductUnit('item');
        $kitItemLineItem1 = $this->createKitItemLineItem(
            self::TEST_QUANTITY,
            $unit,
            Price::create(1, 'USD'),
            $this->getProduct(1)
        );
        $orderLineItem1 = $this->createOrderLineItem(
            self::TEST_QUANTITY,
            $unit,
            Price::create(99.9, 'USD'),
            $product1,
            [$kitItemLineItem1]
        );

        $kitItemLineItem2 = $this->createKitItemLineItem(
            self::TEST_QUANTITY,
            $unit,
            Price::create(2, 'USD'),
            $this->getProduct(2)
        );
        $orderLineItem2 = $this->createOrderLineItem(
            self::TEST_QUANTITY,
            $unit,
            Price::create(99.9, 'USD'),
            $product2,
            [$kitItemLineItem2]
        );

        $productLineItems = [$orderLineItem1, $orderLineItem2];

        $expectedShippingKitItemLineItem1 = $this->createShippingKitItemLineItem(
            $kitItemLineItem1->getProductUnit(),
            $kitItemLineItem1->getQuantity(),
            $kitItemLineItem1->getPrice(),
            $kitItemLineItem1->getProduct(),
            $kitItemLineItem1,
            $kitItemLineItem1->getSortOrder(),
            $kitItemLineItem1->getKitItem(),
            Dimensions::create(2, 4, 6, $lengthUnitIn),
            Weight::create(25, $weightUnitKilo),
        );
        $expectedShippingLineItem1 = $this->createShippingLineItem(
            $orderLineItem1->getQuantity(),
            $orderLineItem1->getProductUnit(),
            $orderLineItem1->getProductUnitCode(),
            $orderLineItem1,
            $orderLineItem1->getPrice(),
            $orderLineItem1->getProduct(),
            Dimensions::create(1, 2, 3, $lengthUnitIn),
            Weight::create(42, $weightUnitKilo),
            new ArrayCollection([$expectedShippingKitItemLineItem1])
        );

        $expectedShippingKitItemLineItem2 = $this->createShippingKitItemLineItem(
            $kitItemLineItem2->getProductUnit(),
            $kitItemLineItem2->getQuantity(),
            $kitItemLineItem2->getPrice(),
            $kitItemLineItem2->getProduct(),
            $kitItemLineItem2,
            $kitItemLineItem2->getSortOrder(),
            $kitItemLineItem2->getKitItem(),
            Dimensions::create(7, 9, 13, $lengthUnitMeter),
            Weight::create(75, $weightUnitLbs),
        );
        $expectedShippingLineItem2 = $this->createShippingLineItem(
            $orderLineItem2->getQuantity(),
            $orderLineItem2->getProductUnit(),
            $orderLineItem2->getProductUnitCode(),
            $orderLineItem2,
            $orderLineItem2->getPrice(),
            $orderLineItem2->getProduct(),
            Dimensions::create(11, 12, 13, $lengthUnitMeter),
            Weight::create(142, $weightUnitLbs),
            new ArrayCollection([$expectedShippingKitItemLineItem2])
        );

        $manager = $this->createMock(EntityManager::class);
        $this->managerRegistry->expects(self::exactly(4))
            ->method('getManagerForClass')
            ->willReturn($manager);

        $manager->expects(self::exactly(4))
            ->method('getReference')
            ->willReturnOnConsecutiveCalls(
                $weightUnitKilo,
                $lengthUnitIn,
                $weightUnitLbs,
                $lengthUnitMeter,
            );

        $this->repository->expects(self::once())
            ->method('findIndexedByProductsAndUnits')
            ->willReturn(
                [
                    $product1->getId() => [
                        'item' => [
                            'dimensionsHeight' => 3.0,
                            'dimensionsLength' => 1.0,
                            'dimensionsWidth' => 2.0,
                            'dimensionsUnit' => 'in',
                            'weightUnit' => 'kilo',
                            'weightValue' => 42.0,
                        ],
                    ],
                    $product2->getId() => [
                        'item' => [
                            'dimensionsHeight' => 13.0,
                            'dimensionsLength' => 11.0,
                            'dimensionsWidth' => 12.0,
                            'dimensionsUnit' => 'meter',
                            'weightUnit' => 'lbs',
                            'weightValue' => 142.0,
                        ],
                    ],
                    1 => [
                        'item' => [
                            'dimensionsHeight' => 6.0,
                            'dimensionsLength' => 2.0,
                            'dimensionsWidth' => 4.0,
                            'dimensionsUnit' => 'in',
                            'weightUnit' => 'kilo',
                            'weightValue' => 25.0,
                        ]
                    ],
                    2 => [
                        'item' => [
                            'dimensionsHeight' => 13.0,
                            'dimensionsLength' => 7.0,
                            'dimensionsWidth' => 9.0,
                            'dimensionsUnit' => 'meter',
                            'weightUnit' => 'lbs',
                            'weightValue' => 75.0,
                        ]
                    ]
                ],
            );

        self::assertEquals(
            new ArrayCollection([$expectedShippingLineItem1, $expectedShippingLineItem2]),
            $this->factory->createCollection($productLineItems)
        );
    }

    public function testCreateCollectionEmpty(): void
    {
        $this->repository
            ->method('findIndexedByProductsAndUnits')
            ->willReturn([]);

        self::assertEquals(new ArrayCollection([]), $this->factory->createCollection([]));
    }

    private function createOrderLineItem(
        float|int $quantity,
        ?ProductUnit $productUnit,
        ?Price $price,
        ?Product $product,
        array $kitItemLineItems = []
    ): OrderLineItem {
        $lineItem = new OrderLineItem();
        $lineItem->setQuantity($quantity);
        $lineItem->setProductUnit($productUnit);
        $lineItem->setPrice($price);
        $lineItem->setProduct($product);
        $lineItem->setProductUnitCode($productUnit->getCode());
        foreach ($kitItemLineItems as $kitItemLineItem) {
            $lineItem->addKitItemLineItem($kitItemLineItem);
        }

        return $lineItem;
    }

    private function createProductLineItemInterfaceMock(
        ProductUnit $productUnit,
        string $unitCode,
        float|int $quantity,
        ?Product $product,
        ?string $productSku
    ): ProductLineItemInterface {
        $lineItem = $this->createMock(ProductLineItemInterface::class);
        $lineItem->expects(self::any())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $lineItem->expects(self::any())
            ->method('getProductUnitCode')
            ->willReturn($unitCode);
        $lineItem->expects(self::any())
            ->method('getQuantity')
            ->willReturn($quantity);
        $lineItem->expects(self::any())
            ->method('getProduct')
            ->willReturn($product);
        $lineItem->expects(self::any())
            ->method('getProductSku')
            ->willReturn($productSku);

        return $lineItem;
    }

    private function getProduct(int $id): Product
    {
        return (new ProductStub())
            ->setId($id);
    }

    private function getProductUnit(string $code): ProductUnit
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);

        return $productUnit;
    }

    private function createKitItemLineItem(
        float|int $quantity,
        ?ProductUnit $productUnit,
        ?Price $price,
        ?Product $product
    ): OrderProductKitItemLineItem {
        return (new OrderProductKitItemLineItem())
            ->setProduct($product)
            ->setProductUnit($productUnit)
            ->setQuantity($quantity)
            ->setPrice($price)
            ->setSortOrder(1);
    }

    private function createShippingLineItem(
        float|int $quantity,
        ?ProductUnit $productUnit,
        string $unitCode,
        ProductLineItemInterface $productHolder,
        ?Price $price,
        ?Product $product,
        ?Dimensions $dimensions,
        ?Weight $weight,
        ?Collection $kitItemLineItems
    ): ShippingLineItem {
        $shippingLineItem = (new ShippingLineItem(
            $productUnit,
            $quantity,
            $productHolder
        ))
            ->setProductUnitCode($unitCode)
            ->setProduct($product)
            ->setProductSku($product->getSku());
        if ($price) {
            $shippingLineItem->setPrice($price);
        }
        if ($dimensions) {
            $shippingLineItem->setDimensions($dimensions);
        }
        if ($weight) {
            $shippingLineItem->setWeight($weight);
        }
        if ($kitItemLineItems) {
            $shippingLineItem->setKitItemLineItems($kitItemLineItems);
        }

        return $shippingLineItem;
    }

    private function createShippingKitItemLineItem(
        ?ProductUnit $productUnit,
        float $quantity,
        ?Price $price,
        ?Product $product,
        ?ProductHolderInterface $productHolder,
        int $sortOrder,
        ?ProductKitItem $kitItem,
        ?Dimensions $dimensions,
        ?Weight $weight,
    ): ShippingKitItemLineItem {
        return (new ShippingKitItemLineItem($productHolder))
            ->setProduct($product)
            ->setProductSku($product->getSku())
            ->setProductUnit($productUnit)
            ->setProductUnitCode($productUnit->getCode())
            ->setQuantity($quantity)
            ->setPrice($price)
            ->setKitItem($kitItem)
            ->setSortOrder($sortOrder)
            ->setDimensions($dimensions)
            ->setWeight($weight);
    }
}
