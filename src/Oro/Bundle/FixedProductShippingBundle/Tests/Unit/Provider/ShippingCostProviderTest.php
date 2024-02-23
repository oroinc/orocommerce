<?php

namespace Oro\Bundle\FixedProductShippingBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FixedProductShippingBundle\Provider\ShippingCostProvider;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Provider\PriceAttributePricesProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Tests\Unit\Context\ShippingLineItemTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingCostProviderTest extends TestCase
{
    use ShippingLineItemTrait;

    private PriceAttributePricesProvider|MockObject $priceProvider;

    private ObjectRepository|MockObject $manager;

    private ShippingCostProvider $provider;

    protected function setUp(): void
    {
        $this->priceProvider = $this->createMock(PriceAttributePricesProvider::class);
        $this->manager = $this->createMock(ObjectRepository::class);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::once())
            ->method('getRepository')
            ->willReturn($this->manager);

        $this->provider = new ShippingCostProvider($this->priceProvider, $registry);
    }

    public function testCannotFoundPriceListShippingCostAttribute(): void
    {
        $this->manager->expects(self::once())
            ->method('findOneBy')
            ->willReturn(null);

        self::assertEquals(0.0, $this->provider->getCalculatedProductShippingCost(
            new ArrayCollection([]),
            'USD'
        ));
    }

    public function testCannotFoundProduct(): void
    {
        $priceAttribute = new PriceAttributePriceList();
        $this->manager->expects(self::once())
            ->method('findOneBy')
            ->willReturn($priceAttribute);

        $this->priceProvider->expects(self::never())
            ->method('getPricesWithUnitAndCurrencies');

        $lineItem = $this->getShippingLineItem(unitCode: 'piece');

        $lineItems = new ArrayCollection([$lineItem]);

        self::assertEquals(0.0, $this->provider->getCalculatedProductShippingCost($lineItems, 'USD'));
    }

    public function testCannotFoundProductUnitCode(): void
    {
        $priceAttribute = new PriceAttributePriceList();
        $this->manager->expects(self::once())
            ->method('findOneBy')
            ->willReturn($priceAttribute);

        $this->priceProvider->expects(self::once())
            ->method('getPricesWithUnitAndCurrencies')
            ->with($priceAttribute, new Product())
            ->willReturn([]);

        $lineItem = $this->getShippingLineItem(unitCode: 'piece')
            ->setProduct(new Product());
        $lineItems = new ArrayCollection([$lineItem]);

        self::assertEquals(0.0, $this->provider->getCalculatedProductShippingCost(
            $lineItems,
            'USD'
        ));
    }

    public function testCannotFoundCurrency(): void
    {
        $priceAttribute = new PriceAttributePriceList();
        $this->manager->expects(self::once())
            ->method('findOneBy')
            ->willReturn($priceAttribute);

        $this->priceProvider->expects(self::once())
            ->method('getPricesWithUnitAndCurrencies')
            ->with($priceAttribute, new Product())
            ->willReturn(['piece' => []]);

        $lineItem = $this->getShippingLineItem(unitCode: 'piece')
            ->setProduct(new Product());
        $lineItems = new ArrayCollection([$lineItem]);

        self::assertEquals(0.0, $this->provider->getCalculatedProductShippingCost(
            $lineItems,
            'USD'
        ));
    }

    public function testGetCalculatedProductShippingCost(): void
    {
        $priceAttribute = new PriceAttributePriceList();
        $this->manager->expects(self::once())
            ->method('findOneBy')
            ->willReturn($priceAttribute);

        $productPrice = new PriceAttributeProductPrice();
        $productPrice->setPrice(Price::create(11.22, 'USD'));

        $this->priceProvider->expects(self::any())
            ->method('getPricesWithUnitAndCurrencies')
            ->with($priceAttribute, new Product())
            ->willReturn(['piece' => ['USD' => $productPrice]]);

        $lineItem = $this->getShippingLineItem(quantity: 3, unitCode: 'piece')
            ->setProduct(new Product());
        $lineItem2 = $this->getShippingLineItem(quantity: 2, unitCode: 'set')
            ->setProduct(new Product());
        $lineItem3 = $this->getShippingLineItem(quantity: 4, unitCode: 'piece')
            ->setProduct(new Product());
        $lineItems = new ArrayCollection([$lineItem, $lineItem2, $lineItem3]);

        self::assertEquals(78.54, $this->provider->getCalculatedProductShippingCost(
            $lineItems,
            'USD'
        ));
    }
}
