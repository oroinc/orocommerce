<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\Provider;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Bundle\RFPBundle\Provider\RequestProductPriceProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestProductPriceProviderTest extends TestCase
{
    private ProductPriceProviderInterface|MockObject $productPriceProvider;

    private ProductPriceScopeCriteriaFactoryInterface|MockObject $priceScopeCriteriaFactory;

    private UserCurrencyManager|MockObject $userCurrencyManager;

    private RequestProductPriceProvider $provider;

    protected function setUp(): void
    {
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->priceScopeCriteriaFactory = $this->createMock(ProductPriceScopeCriteriaFactoryInterface::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);

        $this->provider = new RequestProductPriceProvider(
            $this->productPriceProvider,
            $this->priceScopeCriteriaFactory,
            $this->userCurrencyManager
        );
    }

    public function testGetProductPricesWhenNoRequestProducts(): void
    {
        $this->productPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->priceScopeCriteriaFactory
            ->expects(self::never())
            ->method(self::anything());

        self::assertSame([], $this->provider->getProductPrices(new Request()));
    }

    public function testGetProductPricesWhenNoRequestProductsWithProducts(): void
    {
        $this->productPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->priceScopeCriteriaFactory
            ->expects(self::never())
            ->method(self::anything());

        $requestProduct = (new RequestProduct())
            ->addRequestProductItem(new RequestProductItem());

        self::assertSame([], $this->provider->getProductPrices((new Request())->addRequestProduct($requestProduct)));
    }

    public function testGetProductPricesWhenHasRequestProductWithProduct(): void
    {
        $product = (new ProductStub())->setId(10);
        $website = new Website();
        $requestProduct = (new RequestProduct())
            ->setProduct($product);
        $request = (new Request())
            ->addRequestProduct($requestProduct)
            ->setWebsite($website);
        $currency = 'USD';
        $this->userCurrencyManager
            ->expects(self::once())
            ->method('getUserCurrency')
            ->with($website)
            ->willReturn($currency);

        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($request)
            ->willReturn($priceScopeCriteria);

        $productPrice1 = $this->createMock(ProductPriceInterface::class);
        $productPrice2 = $this->createMock(ProductPriceInterface::class);
        $productPrices = [$product->getId() => [$productPrice1, $productPrice2]];
        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with($priceScopeCriteria, [$product], [$currency])
            ->willReturn($productPrices);

        self::assertSame($productPrices, $this->provider->getProductPrices($request));
    }

    public function testGetProductPricesWhenHasRequestProductWithProductKitAndNoKitItemLineItems(): void
    {
        $kitItem1Product1 = (new ProductStub())->setId(100);
        $kitItem1Product2 = (new ProductStub())->setId(101);
        $kitItem1 = (new ProductKitItemStub())
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product1))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product2));
        $kitItem2Product1 = (new ProductStub())->setId(200);
        $kitItem2 = (new ProductKitItemStub())
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem2Product1));
        $productKit = (new ProductStub())
            ->setId(10)
            ->setType(Product::TYPE_KIT)
            ->addKitItem($kitItem1)
            ->addKitItem($kitItem2);
        $website = new Website();
        $requestProduct = (new RequestProduct())
            ->setProduct($productKit);
        $request = (new Request())
            ->addRequestProduct($requestProduct)
            ->setWebsite($website);

        $currency = 'USD';
        $this->userCurrencyManager
            ->expects(self::once())
            ->method('getUserCurrency')
            ->with($website)
            ->willReturn($currency);

        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($request)
            ->willReturn($priceScopeCriteria);

        $productPrice1 = $this->createMock(ProductPriceInterface::class);
        $productPrice2 = $this->createMock(ProductPriceInterface::class);
        $productPrices = [$productKit->getId() => [$productPrice1, $productPrice2]];
        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with(
                $priceScopeCriteria,
                [$productKit, $kitItem1Product1, $kitItem1Product2, $kitItem2Product1],
                [$currency]
            )
            ->willReturn($productPrices);

        self::assertSame($productPrices, $this->provider->getProductPrices($request));
    }

    public function testGetProductPricesWhenHasRequestProductWithProductKitAndKitItemLineItem(): void
    {
        $kitItem1Product1 = (new ProductStub())->setId(100);
        $kitItem1Product2 = (new ProductStub())->setId(101);
        $kitItem1 = (new ProductKitItemStub())
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product1))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product2));
        $kitItem2Product1 = (new ProductStub())->setId(200);
        $kitItem2 = (new ProductKitItemStub())
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem2Product1));
        $productKit = (new ProductStub())
            ->setId(10)
            ->setType(Product::TYPE_KIT)
            ->addKitItem($kitItem1)
            ->addKitItem($kitItem2);
        $kitItemLineItem1Product = new Product();
        $kitItemLineItem1 = (new RequestProductKitItemLineItem())
            ->setProduct($kitItemLineItem1Product);
        $website = new Website();
        $requestProduct = (new RequestProduct())
            ->setProduct($productKit)
            ->addKitItemLineItem($kitItemLineItem1);
        $request = (new Request())
            ->addRequestProduct($requestProduct)
            ->setWebsite($website);

        $currency = 'USD';
        $this->userCurrencyManager
            ->expects(self::once())
            ->method('getUserCurrency')
            ->with($website)
            ->willReturn($currency);

        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($request)
            ->willReturn($priceScopeCriteria);

        $productPrice1 = $this->createMock(ProductPriceInterface::class);
        $productPrice2 = $this->createMock(ProductPriceInterface::class);
        $productPrices = [$productKit->getId() => [$productPrice1, $productPrice2]];
        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with(
                $priceScopeCriteria,
                [$productKit, $kitItemLineItem1Product, $kitItem1Product1, $kitItem1Product2, $kitItem2Product1],
                [$currency]
            )
            ->willReturn($productPrices);

        self::assertSame($productPrices, $this->provider->getProductPrices($request));
    }

    public function testGetProductPricesWhenHasRequestProductWithProductKitAndNoKitItemLineItemProduct(): void
    {
        $kitItem1Product1 = (new ProductStub())->setId(100);
        $kitItem1Product2 = (new ProductStub())->setId(101);
        $kitItem1 = (new ProductKitItemStub())
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product1))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product2));
        $kitItem2Product1 = (new ProductStub())->setId(200);
        $kitItem2 = (new ProductKitItemStub())
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem2Product1));
        $productKit = (new ProductStub())
            ->setId(10)
            ->setType(Product::TYPE_KIT)
            ->addKitItem($kitItem1)
            ->addKitItem($kitItem2);
        $kitItemLineItem1 = new RequestProductKitItemLineItem();
        $website = new Website();
        $requestProduct = (new RequestProduct())
            ->setProduct($productKit)
            ->addKitItemLineItem($kitItemLineItem1);
        $request = (new Request())
            ->addRequestProduct($requestProduct)
            ->setWebsite($website);

        $currency = 'USD';
        $this->userCurrencyManager
            ->expects(self::once())
            ->method('getUserCurrency')
            ->with($website)
            ->willReturn($currency);

        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($request)
            ->willReturn($priceScopeCriteria);

        $productPrice1 = $this->createMock(ProductPriceInterface::class);
        $productPrice2 = $this->createMock(ProductPriceInterface::class);
        $productPrices = [$productKit->getId() => [$productPrice1, $productPrice2]];
        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with(
                $priceScopeCriteria,
                [$productKit, $kitItem1Product1, $kitItem1Product2, $kitItem2Product1],
                [$currency]
            )
            ->willReturn($productPrices);

        self::assertSame($productPrices, $this->provider->getProductPrices($request));
    }
}
