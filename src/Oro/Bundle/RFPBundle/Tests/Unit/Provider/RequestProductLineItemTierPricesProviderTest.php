<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Provider;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Provider\RequestProductLineItemTierPricesProvider;
use Oro\Bundle\RFPBundle\Provider\RequestProductPriceProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestProductLineItemTierPricesProviderTest extends TestCase
{
    private RequestProductPriceProvider|MockObject $requestProductPriceProvider;

    private ProductLineItemProductPriceProviderInterface|MockObject $productLineItemProductPriceProvider;

    private UserCurrencyManager|MockObject $userCurrencyManager;

    private RequestProductLineItemTierPricesProvider $provider;

    protected function setUp(): void
    {
        $this->requestProductPriceProvider = $this->createMock(RequestProductPriceProvider::class);
        $this->productLineItemProductPriceProvider = $this->createMock(
            ProductLineItemProductPriceProviderInterface::class
        );
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);

        $this->provider = new RequestProductLineItemTierPricesProvider(
            $this->requestProductPriceProvider,
            $this->productLineItemProductPriceProvider,
            $this->userCurrencyManager
        );
    }

    public function testGetTierPricesWhenNoLineItems(): void
    {
        $request = new Request();

        $this->requestProductPriceProvider
            ->expects(self::once())
            ->method('getProductPrices')
            ->with($request)
            ->willReturn([]);

        $this->productLineItemProductPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        self::assertSame([], $this->provider->getTierPrices($request));
    }

    public function testGetTierPricesWhenNoLineItemsWithProduct(): void
    {
        $requestProduct = new RequestProduct();
        $request = (new Request())
            ->addRequestProduct($requestProduct);

        $this->requestProductPriceProvider
            ->expects(self::once())
            ->method('getProductPrices')
            ->with($request)
            ->willReturn([]);

        $this->productLineItemProductPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        self::assertSame([], $this->provider->getTierPrices($request));
    }

    public function testGetTierPricesWhenHasLineItemWithProductButNoPrices(): void
    {
        $product = (new ProductStub())->setId(42);
        $website = new Website();
        $requestProductItem = new RequestProductItem();
        $requestProduct = (new RequestProduct())
            ->setProduct($product)
            ->addRequestProductItem($requestProductItem);
        $request = (new Request())
            ->addRequestProduct($requestProduct)
            ->setWebsite($website);

        $currency = 'USD';
        $this->userCurrencyManager
            ->expects(self::once())
            ->method('getUserCurrency')
            ->with($website)
            ->willReturn($currency);

        $this->requestProductPriceProvider
            ->expects(self::once())
            ->method('getProductPrices')
            ->with($request)
            ->willReturn([]);

        $this->productLineItemProductPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemProductPrices')
            ->with($requestProductItem, new ProductPriceCollectionDTO(), $currency)
            ->willReturn([]);

        self::assertSame(
            [],
            $this->provider->getTierPrices($request)
        );
    }

    public function testGetTierPricesWhenHasLineItemWithProductAndHasPrices(): void
    {
        $product = (new ProductStub())->setId(42);
        $website = new Website();
        $requestProductItem = new RequestProductItem();
        $requestProduct = (new RequestProduct())
            ->setProduct($product)
            ->addRequestProductItem($requestProductItem);
        $request = (new Request())
            ->addRequestProduct($requestProduct)
            ->setWebsite($website);

        $currency = 'USD';
        $this->userCurrencyManager
            ->expects(self::once())
            ->method('getUserCurrency')
            ->with($website)
            ->willReturn($currency);

        $productPrice1 = $this->createMock(ProductPriceInterface::class);
        $productPrice2 = $this->createMock(ProductPriceInterface::class);
        $this->requestProductPriceProvider
            ->expects(self::once())
            ->method('getProductPrices')
            ->with($request)
            ->willReturn([$product->getId() => [$productPrice1, $productPrice2]]);

        $this->productLineItemProductPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemProductPrices')
            ->with(
                $requestProductItem,
                new ProductPriceCollectionDTO([$productPrice1, $productPrice2]),
                $currency
            )
            ->willReturn([$productPrice1, $productPrice2]);

        self::assertSame(
            [
                [
                    [$productPrice1, $productPrice2],
                ],
            ],
            $this->provider->getTierPrices($request)
        );
    }

    public function testGetTierPricesWhenHasLineItemWithProductKit(): void
    {
        $product = (new ProductStub())
            ->setId(42)
            ->setType(Product::TYPE_KIT);
        $website = new Website();
        $requestProduct1Item = new RequestProductItem();
        $requestProduct1 = (new RequestProduct())
            ->setProduct($product)
            ->addRequestProductItem($requestProduct1Item);
        $requestProduct2Item = new RequestProductItem();
        $requestProduct2 = (new RequestProduct())
            ->setProduct($product)
            ->addRequestProductItem($requestProduct2Item);
        $request = (new Request())
            ->addRequestProduct($requestProduct1)
            ->addRequestProduct($requestProduct2)
            ->setWebsite($website);

        $currency = 'USD';
        $this->userCurrencyManager
            ->expects(self::once())
            ->method('getUserCurrency')
            ->with($website)
            ->willReturn($currency);

        $productPrice1 = $this->createMock(ProductPriceInterface::class);
        $productPrice2 = $this->createMock(ProductPriceInterface::class);
        $productPrice3 = $this->createMock(ProductPriceInterface::class);
        $this->requestProductPriceProvider
            ->expects(self::once())
            ->method('getProductPrices')
            ->with($request)
            ->willReturn([$product->getId() => [$productPrice1, $productPrice2, $productPrice3]]);

        $productPriceCollection = new ProductPriceCollectionDTO([$productPrice1, $productPrice2, $productPrice3]);
        $this->productLineItemProductPriceProvider
            ->expects(self::exactly(2))
            ->method('getProductLineItemProductPrices')
            ->withConsecutive(
                [$requestProduct1Item, $productPriceCollection, $currency],
                [$requestProduct2Item, $productPriceCollection, $currency],
            )
            ->willReturnOnConsecutiveCalls(
                [$productPrice1, $productPrice2],
                [$productPrice3],
            );

        self::assertSame(
            [
                [
                    [$productPrice1, $productPrice2],
                ],
                [
                    [$productPrice3],
                ],
            ],
            $this->provider->getTierPrices($request)
        );
    }
}
