<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\Context\LineItem\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Quote\Shipping\Context\LineItem\Factory\ShippingLineItemFromQuoteProductDemandFactory;
use Oro\Bundle\ShippingBundle\Context\LineItem\Factory\ShippingKitItemLineItemFromProductKitItemLineItemFactory;
use Oro\Bundle\ShippingBundle\Context\LineItem\Factory\ShippingLineItemFromProductLineItemFactory;
use Oro\Bundle\ShippingBundle\Context\LineItem\ShippingLineItemOptionsModifier;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingLineItemFromQuoteProductDemandFactoryTest extends TestCase
{
    private const TEST_QUANTITY = 15;

    private ProductUnit|MockObject $productUnit;

    private ProductShippingOptionsRepository|MockObject $repository;

    private ShippingLineItemFromProductLineItemFactory $factory;

    protected function setUp(): void
    {
        $this->productUnit = $this->createMock(ProductUnit::class);
        $this->productUnit->method('getCode')->willReturn('someCode');

        $this->repository = $this->createMock(ProductShippingOptionsRepository::class);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getRepository')
            ->with(ProductShippingOptions::class)
            ->willReturn($this->repository);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->withConsecutive([WeightUnit::class], [LengthUnit::class])
            ->willReturn($entityManager);

        $entityManager
            ->expects(self::any())
            ->method('getReference')
            ->willReturnCallback(
                static function (string $className, ?string $unitCode) {
                    $unit = new $className();
                    $unit->setCode($unitCode);

                    return $unit;
                }
            );

        $modifier = new ShippingLineItemOptionsModifier($managerRegistry);
        $this->factory = new ShippingLineItemFromQuoteProductDemandFactory(
            new ShippingKitItemLineItemFromProductKitItemLineItemFactory($modifier),
            $modifier,
        );
    }

    public function testCreateByProductLineItemInterfaceOnly(): void
    {
        $quoteProductDemand = $this->createMock(ProductLineItemInterface::class);

        $this->expectExceptionObject(
            new \InvalidArgumentException(sprintf(
                '"%s" expected, "%s" given',
                QuoteProductDemand::class,
                get_debug_type($quoteProductDemand)
            ))
        );

        $this->repository->expects(self::never())
            ->method('findIndexedByProductsAndUnits')
            ->withAnyParameters();

        $this->factory->create($quoteProductDemand);
    }

    public function testCreate(): void
    {
        $product = $this->getProduct(1001);

        $quoteProduct = (new QuoteProduct())
            ->setProduct($product);

        $quoteProductOffer = $this->createQuoteProductOffer(
            self::TEST_QUANTITY,
            $this->productUnit,
            Price::create(99.9, 'USD'),
            $quoteProduct
        );

        $quoteProductDemand = new QuoteProductDemand(new QuoteDemand(), $quoteProductOffer, self::TEST_QUANTITY);

        $this->repository->expects(self::once())
            ->method('findIndexedByProductsAndUnits')
            ->willReturn(
                [
                    $product->getId() => [
                        'someCode' => [
                            'dimensionsHeight' => 3.0,
                            'dimensionsLength' => 1.0,
                            'dimensionsWidth' => 2.0,
                            'dimensionsUnit' => 'in',
                            'weightUnit' => 'kilo',
                            'weightValue' => 42.0,
                        ]
                    ],
                ]
            );

        $expectedShippingLineItem = $this->createShippingLineItem(
            $quoteProductOffer->getQuantity(),
            $quoteProductOffer->getProductUnit(),
            $quoteProductOffer->getProductUnitCode(),
            $quoteProductOffer,
            $quoteProductOffer->getPrice(),
            $quoteProductOffer->getProduct(),
            Dimensions::create(1, 2, 3, (new LengthUnit())->setCode('in')),
            Weight::create(42, (new WeightUnit())->setCode('kilo'))
        );

        self::assertEquals(
            $expectedShippingLineItem,
            $this->factory->create($quoteProductDemand)
        );
    }

    public function testCreateCollectionByProductLineItemInterfaceOnly(): void
    {
        $quoteProductDemand = $this->createMock(ProductLineItemInterface::class);

        $this->expectExceptionObject(
            new \InvalidArgumentException(sprintf(
                '"%s" expected, "%s" given',
                QuoteProductDemand::class,
                get_debug_type($quoteProductDemand)
            ))
        );

        $this->repository->expects(self::never())
            ->method('findIndexedByProductsAndUnits')
            ->withAnyParameters();

        $this->factory->createCollection([$quoteProductDemand]);
    }

    public function testCreateCollection(): void
    {
        $product1 = $this->getProduct(1001);
        $product2 = $this->getProduct(2002);

        $quoteProduct1 = (new QuoteProduct())
            ->setProduct($product1);
        $quoteProduct2 = (new QuoteProduct())
            ->setProduct($product2);

        $quoteProductOffer1 = $this->createQuoteProductOffer(
            1,
            $this->productUnit,
            Price::create(99.9, 'USD'),
            $quoteProduct1
        );
        $quoteProductOffer2 = $this->createQuoteProductOffer(
            2,
            $this->productUnit,
            Price::create(199.9, 'USD'),
            $quoteProduct2
        );

        $quoteProductDemand1 = new QuoteProductDemand(new QuoteDemand(), $quoteProductOffer1, 100);
        $quoteProductDemand2 = new QuoteProductDemand(new QuoteDemand(), $quoteProductOffer2, 200);

        $quoteProductDemands = [$quoteProductDemand1, $quoteProductDemand2];

        $expectedShippingLineItem1 = $this->createShippingLineItem(
            $quoteProductDemand1->getQuantity(),
            $quoteProductOffer1->getProductUnit(),
            $quoteProductOffer1->getProductUnitCode(),
            $quoteProductOffer1,
            $quoteProductOffer1->getPrice(),
            $quoteProductOffer1->getProduct(),
            Dimensions::create(1, 2, 3, (new LengthUnit())->setCode('in')),
            Weight::create(42, (new WeightUnit())->setCode('kilo')),
        );

        $expectedShippingLineItem2 = $this->createShippingLineItem(
            $quoteProductDemand2->getQuantity(),
            $quoteProductOffer2->getProductUnit(),
            $quoteProductOffer2->getProductUnitCode(),
            $quoteProductOffer2,
            $quoteProductOffer2->getPrice(),
            $quoteProductOffer2->getProduct(),
            Dimensions::create(11, 12, 13, (new LengthUnit())->setCode('meter')),
            Weight::create(142, (new WeightUnit())->setCode('lbs')),
        );

        $this->repository
            ->expects(self::once())
            ->method('findIndexedByProductsAndUnits')
            ->willReturn(
                [
                    $product1->getId() => [
                        'someCode' => [
                            'dimensionsHeight' => 3.0,
                            'dimensionsLength' => 1.0,
                            'dimensionsWidth' => 2.0,
                            'dimensionsUnit' => 'in',
                            'weightUnit' => 'kilo',
                            'weightValue' => 42.0,
                        ]
                    ],
                    $product2->getId() => [
                        'someCode' => [
                            'dimensionsHeight' => 13.0,
                            'dimensionsLength' => 11.0,
                            'dimensionsWidth' => 12.0,
                            'dimensionsUnit' => 'meter',
                            'weightUnit' => 'lbs',
                            'weightValue' => 142.0,
                        ]
                    ]
                ]
            );

        self::assertEquals(
            new ArrayCollection([$expectedShippingLineItem1, $expectedShippingLineItem2]),
            $this->factory->createCollection($quoteProductDemands)
        );
    }

    public function testCreateCollectionEmpty(): void
    {
        $this->repository
            ->method('findIndexedByProductsAndUnits')
            ->willReturn([]);

        self::assertEquals(new ArrayCollection([]), $this->factory->createCollection([]));
    }

    private function createQuoteProductOffer(
        float|int $quantity,
        ?ProductUnit $productUnit,
        ?Price $price,
        QuoteProduct $quoteProduct
    ): QuoteProductOffer {
        $quoteProductOffer = new QuoteProductOffer();
        $quoteProductOffer->setQuantity($quantity);
        $quoteProductOffer->setProductUnit($productUnit);
        $quoteProductOffer->setQuoteProduct($quoteProduct);
        $quoteProductOffer->setPrice($price);
        $quoteProductOffer->setProductUnitCode($productUnit->getCode());

        return $quoteProductOffer;
    }

    private function getProduct(int $id): Product
    {
        return (new ProductStub())
            ->setId($id);
    }

    private function createShippingLineItem(
        float|int $quantity,
        ?ProductUnit $productUnit,
        string $unitCode,
        ProductLineItemInterface $productHolder,
        ?Price $price,
        ?Product $product,
        ?Dimensions $dimensions,
        ?Weight $weight
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

        return $shippingLineItem;
    }
}
