<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\Factory\ProductLineItemPriceFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\MatchedProductPriceProviderInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProvider;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemsHolderCurrencyProvider;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItem;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderDTO;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductLineItemPriceProviderTest extends TestCase
{
    private MatchedProductPriceProviderInterface|MockObject $matchedProductPriceProvider;

    private ProductPriceScopeCriteriaFactoryInterface|MockObject $priceScopeCriteriaFactory;

    private ProductPriceScopeCriteriaRequestHandler|MockObject $priceScopeCriteriaRequestHandler;

    private ProductPriceCriteriaFactoryInterface|MockObject $productPriceCriteriaFactory;

    private ProductLineItemsHolderCurrencyProvider|MockObject $productLineItemsHolderCurrencyProvider;

    private UserCurrencyManager|MockObject $userCurrencyManager;

    private ProductLineItemPriceFactoryInterface|MockObject $productLineItemPriceFactory;

    private ProductLineItemPriceProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->matchedProductPriceProvider = $this->createMock(MatchedProductPriceProviderInterface::class);
        $this->priceScopeCriteriaFactory = $this->createMock(ProductPriceScopeCriteriaFactoryInterface::class);
        $this->priceScopeCriteriaRequestHandler = $this->createMock(ProductPriceScopeCriteriaRequestHandler::class);
        $this->productPriceCriteriaFactory = $this->createMock(ProductPriceCriteriaFactoryInterface::class);
        $this->productLineItemsHolderCurrencyProvider = $this->createMock(
            ProductLineItemsHolderCurrencyProvider::class
        );
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->productLineItemPriceFactory = $this->createMock(ProductLineItemPriceFactoryInterface::class);

        $this->provider = new ProductLineItemPriceProvider(
            $this->matchedProductPriceProvider,
            $this->priceScopeCriteriaFactory,
            $this->priceScopeCriteriaRequestHandler,
            $this->productPriceCriteriaFactory,
            $this->productLineItemsHolderCurrencyProvider,
            $this->userCurrencyManager,
            $this->productLineItemPriceFactory
        );
    }

    public function testGetProductLineItemsPricesWhenNoProductPriceCriteria(): void
    {
        $lineItem1 = new ProductLineItem(1);
        $lineItem2 = new ProductLineItem(2);
        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $currency = 'USD';

        $this->userCurrencyManager
            ->expects(self::never())
            ->method(self::anything());

        $this->priceScopeCriteriaRequestHandler
            ->expects(self::never())
            ->method(self::anything());

        $this->productPriceCriteriaFactory
            ->expects(self::once())
            ->method('createListFromProductLineItems')
            ->with([10 => $lineItem1, 20 => $lineItem2], $currency)
            ->willReturn([]);

        self::assertEquals(
            [],
            $this->provider->getProductLineItemsPrices(
                [10 => $lineItem1, 20 => $lineItem2],
                $priceScopeCriteria,
                $currency
            )
        );
    }

    public function testGetProductLineItemsPricesWhenNoSomeProductPriceCriteria(): void
    {
        $lineItem1 = new ProductLineItem(1);
        $lineItem2 = new ProductLineItem(2);
        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $currency = 'USD';

        $this->userCurrencyManager
            ->expects(self::never())
            ->method(self::anything());

        $this->priceScopeCriteriaRequestHandler
            ->expects(self::never())
            ->method(self::anything());

        $productLineItem2PriceCriteria = $this->createMock(ProductPriceCriteria::class);
        $this->productPriceCriteriaFactory
            ->expects(self::once())
            ->method('createListFromProductLineItems')
            ->with([10 => $lineItem1, 20 => $lineItem2], $currency)
            ->willReturn([20 => $productLineItem2PriceCriteria]);

        $this->matchedProductPriceProvider
            ->expects(self::once())
            ->method('getMatchedProductPrices')
            ->with([20 => $productLineItem2PriceCriteria], $priceScopeCriteria)
            ->willReturn([]);

        self::assertEquals(
            [],
            $this->provider->getProductLineItemsPrices(
                [10 => $lineItem1, 20 => $lineItem2],
                $priceScopeCriteria,
                $currency
            )
        );
    }

    public function testGetProductLineItemsPricesWhenNoMatchedProductPrices(): void
    {
        $lineItem1 = new ProductLineItem(1);
        $lineItem2 = new ProductLineItem(2);
        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $currency = 'USD';

        $this->userCurrencyManager
            ->expects(self::never())
            ->method(self::anything());

        $this->priceScopeCriteriaRequestHandler
            ->expects(self::never())
            ->method(self::anything());

        $productLineItem1PriceCriteria = $this->createMock(ProductPriceCriteria::class);
        $productLineItem2PriceCriteria = $this->createMock(ProductPriceCriteria::class);
        $this->productPriceCriteriaFactory
            ->expects(self::once())
            ->method('createListFromProductLineItems')
            ->with([10 => $lineItem1, 20 => $lineItem2], $currency)
            ->willReturn([10 => $productLineItem1PriceCriteria, 20 => $productLineItem2PriceCriteria]);

        $this->matchedProductPriceProvider
            ->expects(self::once())
            ->method('getMatchedProductPrices')
            ->with([10 => $productLineItem1PriceCriteria, 20 => $productLineItem2PriceCriteria], $priceScopeCriteria)
            ->willReturn([]);

        self::assertEquals(
            [],
            $this->provider->getProductLineItemsPrices(
                [10 => $lineItem1, 20 => $lineItem2],
                $priceScopeCriteria,
                $currency
            )
        );
    }

    public function testGetProductLineItemsPrices(): void
    {
        $lineItem1 = new ProductLineItem(1);
        $lineItem2 = new ProductLineItem(2);
        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $currency = 'USD';

        $this->userCurrencyManager
            ->expects(self::never())
            ->method(self::anything());

        $this->priceScopeCriteriaRequestHandler
            ->expects(self::never())
            ->method(self::anything());

        $lineItem1Product = (new ProductStub())->setId(42);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productLineItem1PriceCriteria = new ProductPriceCriteria(
            $lineItem1Product,
            $productUnitItem,
            12.3456,
            $currency
        );
        $lineItem2Product = (new ProductStub())->setId(43);
        $productUnitEach = (new ProductUnit())->setCode('each');
        $productLineItem2PriceCriteria = new ProductPriceCriteria(
            $lineItem2Product,
            $productUnitEach,
            34.5678,
            $currency
        );
        $this->productPriceCriteriaFactory
            ->expects(self::once())
            ->method('createListFromProductLineItems')
            ->with([10 => $lineItem1, 20 => $lineItem2], $currency)
            ->willReturn([10 => $productLineItem1PriceCriteria, 20 => $productLineItem2PriceCriteria]);

        $product1Price = $this->createMock(ProductPriceInterface::class);
        $product2Price = $this->createMock(ProductPriceInterface::class);
        $this->matchedProductPriceProvider
            ->expects(self::once())
            ->method('getMatchedProductPrices')
            ->with([10 => $productLineItem1PriceCriteria, 20 => $productLineItem2PriceCriteria], $priceScopeCriteria)
            ->willReturn(
                [
                    $productLineItem1PriceCriteria->getIdentifier() => $product1Price,
                    $productLineItem2PriceCriteria->getIdentifier() => $product2Price,
                ]
            );

        $productLineItem1Price = $this->createMock(ProductLineItemPrice::class);
        $productLineItem2Price = $this->createMock(ProductLineItemPrice::class);

        $this->productLineItemPriceFactory
            ->expects(self::exactly(2))
            ->method('createForProductLineItem')
            ->willReturnMap([
                [$lineItem1, $product1Price, $productLineItem1Price],
                [$lineItem2, $product2Price, $productLineItem2Price],
            ]);

        self::assertSame(
            [10 => $productLineItem1Price, 20 => $productLineItem2Price],
            $this->provider->getProductLineItemsPrices(
                [10 => $lineItem1, 20 => $lineItem2],
                $priceScopeCriteria,
                $currency
            )
        );
    }

    public function testGetProductLineItemsPricesWhenNoPriceScopeCriteria(): void
    {
        $lineItem1 = new ProductLineItem(1);
        $lineItem2 = new ProductLineItem(2);
        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $currency = 'USD';

        $this->userCurrencyManager
            ->expects(self::never())
            ->method(self::anything());

        $this->priceScopeCriteriaRequestHandler
            ->expects(self::once())
            ->method('getPriceScopeCriteria')
            ->willReturn($priceScopeCriteria);

        $lineItem1Product = (new ProductStub())->setId(42);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productLineItem1PriceCriteria = new ProductPriceCriteria(
            $lineItem1Product,
            $productUnitItem,
            12.3456,
            $currency
        );
        $lineItem2Product = (new ProductStub())->setId(43);
        $productUnitEach = (new ProductUnit())->setCode('each');
        $productLineItem2PriceCriteria = new ProductPriceCriteria(
            $lineItem2Product,
            $productUnitEach,
            34.5678,
            $currency
        );
        $this->productPriceCriteriaFactory
            ->expects(self::once())
            ->method('createListFromProductLineItems')
            ->with([10 => $lineItem1, 20 => $lineItem2], $currency)
            ->willReturn([10 => $productLineItem1PriceCriteria, 20 => $productLineItem2PriceCriteria]);

        $product1Price = $this->createMock(ProductPriceInterface::class);
        $product2Price = $this->createMock(ProductPriceInterface::class);
        $this->matchedProductPriceProvider
            ->expects(self::once())
            ->method('getMatchedProductPrices')
            ->with([10 => $productLineItem1PriceCriteria, 20 => $productLineItem2PriceCriteria], $priceScopeCriteria)
            ->willReturn(
                [
                    $productLineItem1PriceCriteria->getIdentifier() => $product1Price,
                    $productLineItem2PriceCriteria->getIdentifier() => $product2Price,
                ]
            );

        $productLineItem1Price = $this->createMock(ProductLineItemPrice::class);
        $productLineItem2Price = $this->createMock(ProductLineItemPrice::class);

        $this->productLineItemPriceFactory
            ->expects(self::exactly(2))
            ->method('createForProductLineItem')
            ->willReturnMap([
                [$lineItem1, $product1Price, $productLineItem1Price],
                [$lineItem2, $product2Price, $productLineItem2Price],
            ]);

        self::assertSame(
            [10 => $productLineItem1Price, 20 => $productLineItem2Price],
            $this->provider->getProductLineItemsPrices(
                [10 => $lineItem1, 20 => $lineItem2],
                null,
                $currency
            )
        );
    }

    public function testGetProductLineItemsPricesWhenFallbackToUserCurrency(): void
    {
        $lineItem1 = new ProductLineItem(1);
        $lineItem2 = new ProductLineItem(2);
        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $currency = 'EUR';

        $this->userCurrencyManager
            ->expects(self::once())
            ->method('getUserCurrency')
            ->willReturn($currency);

        $this->priceScopeCriteriaRequestHandler
            ->expects(self::once())
            ->method('getPriceScopeCriteria')
            ->willReturn($priceScopeCriteria);

        $lineItem1Product = (new ProductStub())->setId(42);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productLineItem1PriceCriteria = new ProductPriceCriteria(
            $lineItem1Product,
            $productUnitItem,
            12.3456,
            $currency
        );
        $lineItem2Product = (new ProductStub())->setId(43);
        $productUnitEach = (new ProductUnit())->setCode('each');
        $productLineItem2PriceCriteria = new ProductPriceCriteria(
            $lineItem2Product,
            $productUnitEach,
            34.5678,
            $currency
        );
        $this->productPriceCriteriaFactory
            ->expects(self::once())
            ->method('createListFromProductLineItems')
            ->with([10 => $lineItem1, 20 => $lineItem2], $currency)
            ->willReturn([10 => $productLineItem1PriceCriteria, 20 => $productLineItem2PriceCriteria]);

        $product1Price = $this->createMock(ProductPriceInterface::class);
        $product2Price = $this->createMock(ProductPriceInterface::class);
        $this->matchedProductPriceProvider
            ->expects(self::once())
            ->method('getMatchedProductPrices')
            ->with([10 => $productLineItem1PriceCriteria, 20 => $productLineItem2PriceCriteria], $priceScopeCriteria)
            ->willReturn(
                [
                    $productLineItem1PriceCriteria->getIdentifier() => $product1Price,
                    $productLineItem2PriceCriteria->getIdentifier() => $product2Price,
                ]
            );

        $productLineItem1Price = $this->createMock(ProductLineItemPrice::class);
        $productLineItem2Price = $this->createMock(ProductLineItemPrice::class);

        $this->productLineItemPriceFactory
            ->expects(self::exactly(2))
            ->method('createForProductLineItem')
            ->willReturnMap([
                [$lineItem1, $product1Price, $productLineItem1Price],
                [$lineItem2, $product2Price, $productLineItem2Price],
            ]);

        self::assertSame(
            [10 => $productLineItem1Price, 20 => $productLineItem2Price],
            $this->provider->getProductLineItemsPrices([10 => $lineItem1, 20 => $lineItem2])
        );
    }

    public function testGetProductLineItemsPricesWhenFallbackToDefaultCurrency(): void
    {
        $lineItem1 = new ProductLineItem(1);
        $lineItem2 = new ProductLineItem(2);
        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $currency = 'CAD';

        $this->userCurrencyManager
            ->expects(self::once())
            ->method('getUserCurrency')
            ->willReturn(null);

        $this->userCurrencyManager
            ->expects(self::once())
            ->method('getDefaultCurrency')
            ->willReturn($currency);

        $this->priceScopeCriteriaRequestHandler
            ->expects(self::once())
            ->method('getPriceScopeCriteria')
            ->willReturn($priceScopeCriteria);

        $lineItem1Product = (new ProductStub())->setId(42);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productLineItem1PriceCriteria = new ProductPriceCriteria(
            $lineItem1Product,
            $productUnitItem,
            12.3456,
            $currency
        );
        $lineItem2Product = (new ProductStub())->setId(43);
        $productUnitEach = (new ProductUnit())->setCode('each');
        $productLineItem2PriceCriteria = new ProductPriceCriteria(
            $lineItem2Product,
            $productUnitEach,
            34.5678,
            $currency
        );
        $this->productPriceCriteriaFactory
            ->expects(self::once())
            ->method('createListFromProductLineItems')
            ->with([10 => $lineItem1, 20 => $lineItem2], $currency)
            ->willReturn([10 => $productLineItem1PriceCriteria, 20 => $productLineItem2PriceCriteria]);

        $product1Price = $this->createMock(ProductPriceInterface::class);
        $product2Price = $this->createMock(ProductPriceInterface::class);
        $this->matchedProductPriceProvider
            ->expects(self::once())
            ->method('getMatchedProductPrices')
            ->with([10 => $productLineItem1PriceCriteria, 20 => $productLineItem2PriceCriteria], $priceScopeCriteria)
            ->willReturn(
                [
                    $productLineItem1PriceCriteria->getIdentifier() => $product1Price,
                    $productLineItem2PriceCriteria->getIdentifier() => $product2Price,
                ]
            );

        $productLineItem1Price = $this->createMock(ProductLineItemPrice::class);
        $productLineItem2Price = $this->createMock(ProductLineItemPrice::class);

        $this->productLineItemPriceFactory
            ->expects(self::exactly(2))
            ->method('createForProductLineItem')
            ->willReturnMap([
                [$lineItem1, $product1Price, $productLineItem1Price],
                [$lineItem2, $product2Price, $productLineItem2Price],
            ]);

        self::assertSame(
            [10 => $productLineItem1Price, 20 => $productLineItem2Price],
            $this->provider->getProductLineItemsPrices([10 => $lineItem1, 20 => $lineItem2])
        );
    }

    public function testGetProductLineItemsPricesForLineItemsHolder(): void
    {
        $lineItem1 = new ProductLineItem(1);
        $lineItem2 = new ProductLineItem(2);
        $lineItemsHolder = (new ProductLineItemsHolderDTO())
            ->setLineItems(new ArrayCollection([10 => $lineItem1, 20 => $lineItem2]));
        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $currency = 'CAD';

        $this->productLineItemsHolderCurrencyProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->userCurrencyManager
            ->expects(self::never())
            ->method('getUserCurrency');

        $this->priceScopeCriteriaRequestHandler
            ->expects(self::never())
            ->method('getPriceScopeCriteria');

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($lineItemsHolder)
            ->willReturn($priceScopeCriteria);

        $lineItem1Product = (new ProductStub())->setId(42);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productLineItem1PriceCriteria = new ProductPriceCriteria(
            $lineItem1Product,
            $productUnitItem,
            12.3456,
            $currency
        );
        $lineItem2Product = (new ProductStub())->setId(43);
        $productUnitEach = (new ProductUnit())->setCode('each');
        $productLineItem2PriceCriteria = new ProductPriceCriteria(
            $lineItem2Product,
            $productUnitEach,
            34.5678,
            $currency
        );
        $this->productPriceCriteriaFactory
            ->expects(self::once())
            ->method('createListFromProductLineItems')
            ->with(new ArrayCollection([10 => $lineItem1, 20 => $lineItem2]), $currency)
            ->willReturn([10 => $productLineItem1PriceCriteria, 20 => $productLineItem2PriceCriteria]);

        $product1Price = $this->createMock(ProductPriceInterface::class);
        $product2Price = $this->createMock(ProductPriceInterface::class);
        $this->matchedProductPriceProvider
            ->expects(self::once())
            ->method('getMatchedProductPrices')
            ->with([10 => $productLineItem1PriceCriteria, 20 => $productLineItem2PriceCriteria], $priceScopeCriteria)
            ->willReturn(
                [
                    $productLineItem1PriceCriteria->getIdentifier() => $product1Price,
                    $productLineItem2PriceCriteria->getIdentifier() => $product2Price,
                ]
            );

        $productLineItem1Price = $this->createMock(ProductLineItemPrice::class);
        $productLineItem2Price = $this->createMock(ProductLineItemPrice::class);

        $this->productLineItemPriceFactory
            ->expects(self::exactly(2))
            ->method('createForProductLineItem')
            ->willReturnMap([
                [$lineItem1, $product1Price, $productLineItem1Price],
                [$lineItem2, $product2Price, $productLineItem2Price],
            ]);

        self::assertSame(
            [10 => $productLineItem1Price, 20 => $productLineItem2Price],
            $this->provider->getProductLineItemsPricesForLineItemsHolder($lineItemsHolder, $currency)
        );
    }

    public function testGetProductLineItemsPricesForLineItemsHolderWhenNoCurrency(): void
    {
        $lineItem1 = new ProductLineItem(1);
        $lineItem2 = new ProductLineItem(2);
        $lineItemsHolder = (new ProductLineItemsHolderDTO())
            ->setLineItems(new ArrayCollection([10 => $lineItem1, 20 => $lineItem2]));
        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $currency = 'USD';

        $this->productLineItemsHolderCurrencyProvider
            ->expects(self::once())
            ->method('getCurrencyForLineItemsHolder')
            ->with($lineItemsHolder)
            ->willReturn($currency);

        $this->userCurrencyManager
            ->expects(self::never())
            ->method('getUserCurrency');

        $this->priceScopeCriteriaRequestHandler
            ->expects(self::never())
            ->method('getPriceScopeCriteria');

        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($lineItemsHolder)
            ->willReturn($priceScopeCriteria);

        $lineItem1Product = (new ProductStub())->setId(42);
        $productUnitItem = (new ProductUnit())->setCode('item');
        $productLineItem1PriceCriteria = new ProductPriceCriteria(
            $lineItem1Product,
            $productUnitItem,
            12.3456,
            $currency
        );
        $lineItem2Product = (new ProductStub())->setId(43);
        $productUnitEach = (new ProductUnit())->setCode('each');
        $productLineItem2PriceCriteria = new ProductPriceCriteria(
            $lineItem2Product,
            $productUnitEach,
            34.5678,
            $currency
        );
        $this->productPriceCriteriaFactory
            ->expects(self::once())
            ->method('createListFromProductLineItems')
            ->with(new ArrayCollection([10 => $lineItem1, 20 => $lineItem2]), $currency)
            ->willReturn([10 => $productLineItem1PriceCriteria, 20 => $productLineItem2PriceCriteria]);

        $product1Price = $this->createMock(ProductPriceInterface::class);
        $product2Price = $this->createMock(ProductPriceInterface::class);
        $this->matchedProductPriceProvider
            ->expects(self::once())
            ->method('getMatchedProductPrices')
            ->with([10 => $productLineItem1PriceCriteria, 20 => $productLineItem2PriceCriteria], $priceScopeCriteria)
            ->willReturn(
                [
                    $productLineItem1PriceCriteria->getIdentifier() => $product1Price,
                    $productLineItem2PriceCriteria->getIdentifier() => $product2Price,
                ]
            );

        $productLineItem1Price = $this->createMock(ProductLineItemPrice::class);
        $productLineItem2Price = $this->createMock(ProductLineItemPrice::class);

        $this->productLineItemPriceFactory
            ->expects(self::exactly(2))
            ->method('createForProductLineItem')
            ->willReturnMap([
                [$lineItem1, $product1Price, $productLineItem1Price],
                [$lineItem2, $product2Price, $productLineItem2Price],
            ]);

        self::assertSame(
            [10 => $productLineItem1Price, 20 => $productLineItem2Price],
            $this->provider->getProductLineItemsPricesForLineItemsHolder($lineItemsHolder)
        );
    }
}
