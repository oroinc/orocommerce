<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DataProvider\LineItem;

use Oro\Bundle\CheckoutBundle\DataProvider\LineItem\CheckoutLineItemsDataProvider;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutLineItemsDataProviderTest extends TestCase
{
    private ProductLineItemPriceProviderInterface|MockObject $productLineItemPriceProvider;

    private CheckoutLineItemsDataProvider $provider;

    private AuthorizationCheckerInterface|MockObject $authorizationChecker;

    private ResolvedProductVisibilityProvider $resolvedProductVisibilityProvider;

    protected function setUp(): void
    {
        $this->productLineItemPriceProvider = $this->createMock(ProductLineItemPriceProviderInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $productAvailabilityCache = $this->createMock(CacheInterface::class);
        $this->resolvedProductVisibilityProvider = $this->createMock(ResolvedProductVisibilityProvider::class);

        $this->provider = new CheckoutLineItemsDataProvider(
            $this->productLineItemPriceProvider,
            $this->authorizationChecker,
            $productAvailabilityCache,
            $this->resolvedProductVisibilityProvider
        );

        $productAvailabilityCache
            ->method('get')
            ->willReturnCallback(fn (string $key, callable $callback) => $callback());
    }

    /**
     * @dataProvider isEntitySupportedProvider
     */
    public function testIsEntitySupported(bool $expected, object $entity): void
    {
        self::assertEquals($expected, $this->provider->isEntitySupported($entity));
    }

    public function isEntitySupportedProvider(): array
    {
        return [
            ['expected' => false, 'data' => new \stdClass()],
            ['expected' => true, 'entity' => new Checkout()],
        ];
    }

    public function testGetDataWhenNoLineItems(): void
    {
        $checkout = new Checkout();

        $this->resolvedProductVisibilityProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->productLineItemPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->authorizationChecker
            ->expects(self::never())
            ->method(self::anything());

        self::assertEquals([], $this->provider->getData($checkout));
    }

    public function testGetDataWhenEmptyLineItem(): void
    {
        $checkout = new Checkout();
        $emptyLineItem = new CheckoutLineItem();
        $checkout->addLineItem($emptyLineItem);

        $this->resolvedProductVisibilityProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->productLineItemPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->authorizationChecker
            ->expects(self::never())
            ->method(self::anything());

        self::assertEquals([
            [
                'productSku' => null,
                'comment' => null,
                'quantity' => null,
                'productUnit' => null,
                'productUnitCode' => null,
                'product' => null,
                'parentProduct' => null,
                'freeFormProduct' => null,
                'fromExternalSource' => false,
                'price' => null,
                'shippingMethod' => null,
                'shippingMethodType' => null,
                'shippingEstimateAmount' => null,
                'checksum' => '',
                'kitItemLineItems' => [],
            ],
        ], $this->provider->getData($checkout));
    }

    public function testGetDataWhenFreeFormProductWithoutPrice(): void
    {
        $checkout = new Checkout();
        $lineItemWithFreeFormProduct = (new CheckoutLineItem())
            ->setFreeFormProduct('Sample free form product');
        $checkout->addLineItem($lineItemWithFreeFormProduct);

        $this->resolvedProductVisibilityProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->productLineItemPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->authorizationChecker
            ->expects(self::never())
            ->method(self::anything());

        self::assertEquals([
            [
                'productSku' => null,
                'comment' => null,
                'quantity' => null,
                'productUnit' => null,
                'productUnitCode' => null,
                'product' => null,
                'parentProduct' => null,
                'freeFormProduct' => $lineItemWithFreeFormProduct->getFreeFormProduct(),
                'fromExternalSource' => false,
                'price' => null,
                'shippingMethod' => null,
                'shippingMethodType' => null,
                'shippingEstimateAmount' => null,
                'checksum' => '',
                'kitItemLineItems' => [],
            ],
        ], $this->provider->getData($checkout));
    }

    public function testGetDataWhenFreeFormProductWithPrice(): void
    {
        $checkout = new Checkout();
        $lineItemWithFreeFormProduct = (new CheckoutLineItem())
            ->setFreeFormProduct('Sample free form product')
            ->setPriceFixed(true)
            ->setPrice(Price::create(12.3456, 'USD'))
            ->setQuantity(3);
        $checkout->addLineItem($lineItemWithFreeFormProduct);

        $this->resolvedProductVisibilityProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->productLineItemPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->authorizationChecker
            ->expects(self::never())
            ->method(self::anything());

        self::assertEquals([
            [
                'productSku' => null,
                'comment' => null,
                'quantity' => 3,
                'productUnit' => null,
                'productUnitCode' => null,
                'product' => null,
                'parentProduct' => null,
                'freeFormProduct' => $lineItemWithFreeFormProduct->getFreeFormProduct(),
                'fromExternalSource' => false,
                'price' => $lineItemWithFreeFormProduct->getPrice(),
                'shippingMethod' => null,
                'shippingMethodType' => null,
                'shippingEstimateAmount' => null,
                'checksum' => '',
                'kitItemLineItems' => [],
            ],
        ], $this->provider->getData($checkout));
    }

    public function testGetDataWhenRegularProductWithFixedPrice(): void
    {
        $checkout = new Checkout();
        $product = (new ProductStub())->setId(42)->setSku('SKU1')->setStatus(Product::STATUS_ENABLED);
        $unitItem = (new ProductUnit())->setCode('item');
        $lineItemWithRegularProductWithFixedPrice = (new CheckoutLineItem())
            ->setProduct($product)
            ->setProductUnit($unitItem)
            ->setPriceFixed(true)
            ->setPrice(Price::create(12.3456, 'USD'))
            ->setQuantity(3);
        $lineItemWithRegularProductWithFixedPrice->preSave();

        $checkout->addLineItem($lineItemWithRegularProductWithFixedPrice);

        $this->resolvedProductVisibilityProvider
            ->expects(self::once())
            ->method('prefetch')
            ->with([$product->getId()]);

        $this->productLineItemPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $product)
            ->willReturn(true);

        self::assertEquals([
            [
                'productSku' => $product->getSku(),
                'comment' => null,
                'quantity' => 3,
                'productUnit' => $unitItem,
                'productUnitCode' => $lineItemWithRegularProductWithFixedPrice->getProductUnitCode(),
                'product' => $product,
                'parentProduct' => null,
                'freeFormProduct' => $lineItemWithRegularProductWithFixedPrice->getFreeFormProduct(),
                'fromExternalSource' => false,
                'price' => $lineItemWithRegularProductWithFixedPrice->getPrice(),
                'shippingMethod' => null,
                'shippingMethodType' => null,
                'shippingEstimateAmount' => null,
                'checksum' => '',
                'kitItemLineItems' => [],
            ],
        ], $this->provider->getData($checkout));
    }

    public function testGetDataWhenRegularProductWithFixedPriceButWithoutPrice(): void
    {
        $checkout = new Checkout();
        $product = (new ProductStub())->setId(42)->setSku('SKU1')->setStatus(Product::STATUS_ENABLED);
        $unitItem = (new ProductUnit())->setCode('item');
        $lineItemWithRegularProductWithFixedPrice = (new CheckoutLineItem())
            ->setProduct($product)
            ->setProductUnit($unitItem)
            ->setPriceFixed(true)
            ->setQuantity(3);
        $lineItemWithRegularProductWithFixedPrice->preSave();

        $checkout->addLineItem($lineItemWithRegularProductWithFixedPrice);

        $this->resolvedProductVisibilityProvider
            ->expects(self::once())
            ->method('prefetch')
            ->with([$product->getId()]);

        $this->productLineItemPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $product)
            ->willReturn(true);

        self::assertEquals([
            [
                'productSku' => $product->getSku(),
                'comment' => null,
                'quantity' => 3,
                'productUnit' => $unitItem,
                'productUnitCode' => $lineItemWithRegularProductWithFixedPrice->getProductUnitCode(),
                'product' => $product,
                'parentProduct' => null,
                'freeFormProduct' => $lineItemWithRegularProductWithFixedPrice->getFreeFormProduct(),
                'fromExternalSource' => false,
                'price' => null,
                'shippingMethod' => null,
                'shippingMethodType' => null,
                'shippingEstimateAmount' => null,
                'checksum' => '',
                'kitItemLineItems' => [],
            ],
        ], $this->provider->getData($checkout));
    }

    public function testGetDataWhenRegularProductWithNotFixedPrice(): void
    {
        $checkout = new Checkout();
        $product = (new ProductStub())->setId(42)->setSku('SKU1')->setStatus(Product::STATUS_ENABLED);
        $unitItem = (new ProductUnit())->setCode('item');
        $lineItemWithRegularProductWithNotFixedPrice = (new CheckoutLineItem())
            ->setProduct($product)
            ->setProductUnit($unitItem)
            ->setQuantity(3);
        $lineItemWithRegularProductWithNotFixedPrice->preSave();

        // Just to make sure keys are preserved when passed to getProductLineItemsPrices.
        $checkout->addLineItem($lineItemWithRegularProductWithNotFixedPrice);
        $checkout->removeLineItem($lineItemWithRegularProductWithNotFixedPrice);
        $checkout->addLineItem($lineItemWithRegularProductWithNotFixedPrice);

        $this->resolvedProductVisibilityProvider
            ->expects(self::once())
            ->method('prefetch')
            ->with([$product->getId()]);

        $lineItemPrice = new ProductLineItemPrice(
            $lineItemWithRegularProductWithNotFixedPrice,
            Price::create(123.4567, 'USD'),
            123.4567 * 3
        );
        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([1 => $lineItemWithRegularProductWithNotFixedPrice])
            ->willReturn([1 => $lineItemPrice]);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $product)
            ->willReturn(true);

        $expected = [
            [
                'productSku' => $product->getSku(),
                'comment' => null,
                'quantity' => 3,
                'productUnit' => $unitItem,
                'productUnitCode' => $lineItemWithRegularProductWithNotFixedPrice->getProductUnitCode(),
                'product' => $product,
                'parentProduct' => null,
                'freeFormProduct' => $lineItemWithRegularProductWithNotFixedPrice->getFreeFormProduct(),
                'fromExternalSource' => false,
                'price' => $lineItemPrice->getPrice(),
                'shippingMethod' => null,
                'shippingMethodType' => null,
                'shippingEstimateAmount' => null,
                'checksum' => '',
                'kitItemLineItems' => [],
            ],
        ];
        self::assertEquals($expected, $this->provider->getData($checkout));

        // Checks local cache in AbstractCheckoutProvider::getData.
        self::assertEquals($expected, $this->provider->getData($checkout));
    }

    public function testGetDataWhenRegularProductWithNotFixedPriceWhenNoPrice(): void
    {
        $checkout = new Checkout();
        $product = (new ProductStub())->setId(42)->setSku('SKU1')->setStatus(Product::STATUS_ENABLED);
        $unitItem = (new ProductUnit())->setCode('item');
        $lineItemWithRegularProductWithNotFixedPrice = (new CheckoutLineItem())
            ->setProduct($product)
            ->setProductUnit($unitItem)
            ->setQuantity(3);
        $lineItemWithRegularProductWithNotFixedPrice->preSave();

        $checkout->addLineItem($lineItemWithRegularProductWithNotFixedPrice);

        $this->resolvedProductVisibilityProvider
            ->expects(self::once())
            ->method('prefetch')
            ->with([$product->getId()]);

        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItemWithRegularProductWithNotFixedPrice])
            ->willReturn([]);

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $product)
            ->willReturn(true);

        self::assertEquals([
            [
                'productSku' => $product->getSku(),
                'comment' => null,
                'quantity' => 3,
                'productUnit' => $unitItem,
                'productUnitCode' => $lineItemWithRegularProductWithNotFixedPrice->getProductUnitCode(),
                'product' => $product,
                'parentProduct' => null,
                'freeFormProduct' => $lineItemWithRegularProductWithNotFixedPrice->getFreeFormProduct(),
                'fromExternalSource' => false,
                'price' => null,
                'shippingMethod' => null,
                'shippingMethodType' => null,
                'shippingEstimateAmount' => null,
                'checksum' => '',
                'kitItemLineItems' => [],
            ],
        ], $this->provider->getData($checkout));
    }

    public function testGetDataWhenRegularProductWhenNotEnabled(): void
    {
        $checkout = new Checkout();
        $product = (new ProductStub())->setId(42)->setSku('SKU1')->setStatus(Product::STATUS_DISABLED);
        $unitItem = (new ProductUnit())->setCode('item');
        $lineItemWithRegularProductWithNotFixedPrice = (new CheckoutLineItem())
            ->setProduct($product)
            ->setProductUnit($unitItem)
            ->setQuantity(3);
        $lineItemWithRegularProductWithNotFixedPrice->preSave();

        $checkout->addLineItem($lineItemWithRegularProductWithNotFixedPrice);

        $this->resolvedProductVisibilityProvider
            ->expects(self::once())
            ->method('prefetch')
            ->with([$product->getId()]);

        $this->productLineItemPriceProvider
            ->expects(self::never())
            ->method('getProductLineItemsPrices');

        $this->authorizationChecker
            ->expects(self::never())
            ->method('isGranted');

        self::assertEquals([], $this->provider->getData($checkout));
    }

    public function testGetDataWhenRegularProductWhenNotGranted(): void
    {
        $checkout = new Checkout();
        $product = (new ProductStub())->setId(42)->setSku('SKU1')->setStatus(Product::STATUS_ENABLED);
        $unitItem = (new ProductUnit())->setCode('item');
        $lineItemWithRegularProductWithNotFixedPrice = (new CheckoutLineItem())
            ->setProduct($product)
            ->setProductUnit($unitItem)
            ->setQuantity(3);
        $lineItemWithRegularProductWithNotFixedPrice->preSave();

        $checkout->addLineItem($lineItemWithRegularProductWithNotFixedPrice);

        $this->resolvedProductVisibilityProvider
            ->expects(self::once())
            ->method('prefetch')
            ->with([$product->getId()]);

        $this->productLineItemPriceProvider
            ->expects(self::never())
            ->method('getProductLineItemsPrices');

        $this->authorizationChecker
            ->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::VIEW, $product)
            ->willReturn(false);

        self::assertEquals([], $this->provider->getData($checkout));
    }
}
