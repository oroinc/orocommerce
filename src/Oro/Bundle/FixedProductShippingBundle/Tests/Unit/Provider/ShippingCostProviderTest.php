<?php

namespace Oro\Bundle\FixedProductShippingBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FixedProductShippingBundle\Provider\ShippingCostProvider;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Provider\PriceAttributePricesProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingCostProviderTest extends TestCase
{
    private PriceAttributePricesProvider|MockObject $priceProvider;

    private ObjectRepository|MockObject $manager;

    private ShippingCostProvider $provider;

    /**
     * {@inheritDoc}
     */
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

        self::assertEquals(
            0.0,
            $this->provider->getCalculatedProductShippingCost(
                new DoctrineShippingLineItemCollection([]),
                'USD'
            )
        );
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

        $lineItem = new ShippingLineItem([
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'piece',
            ShippingLineItem::FIELD_PRODUCT => new Product(),
        ]);
        $lineItems = new DoctrineShippingLineItemCollection([$lineItem]);

        self::assertEquals(
            0.0,
            $this->provider->getCalculatedProductShippingCost(
                $lineItems,
                'USD'
            )
        );
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

        $lineItem = new ShippingLineItem([
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'piece',
            ShippingLineItem::FIELD_PRODUCT => new Product(),
        ]);
        $lineItems = new DoctrineShippingLineItemCollection([$lineItem]);

        self::assertEquals(
            0.0,
            $this->provider->getCalculatedProductShippingCost(
                $lineItems,
                'USD'
            )
        );
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

        $lineItem = new ShippingLineItem([
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'piece',
            ShippingLineItem::FIELD_QUANTITY => 3,
            ShippingLineItem::FIELD_PRODUCT => new Product(),
        ]);
        $lineItem2 = new ShippingLineItem([
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'set',
            ShippingLineItem::FIELD_QUANTITY => 2,
            ShippingLineItem::FIELD_PRODUCT => new Product(),
        ]);
        $lineItem3 = new ShippingLineItem([
            ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'piece',
            ShippingLineItem::FIELD_QUANTITY => 4,
            ShippingLineItem::FIELD_PRODUCT => new Product(),
        ]);
        $lineItems = new DoctrineShippingLineItemCollection([$lineItem, $lineItem2, $lineItem3]);

        self::assertEquals(
            78.54,
            $this->provider->getCalculatedProductShippingCost(
                $lineItems,
                'USD'
            )
        );
    }
}
