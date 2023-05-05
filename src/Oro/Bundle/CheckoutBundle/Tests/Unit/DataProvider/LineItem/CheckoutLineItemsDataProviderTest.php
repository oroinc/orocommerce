<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DataProvider\LineItem;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\DataProvider\LineItem\CheckoutLineItemsDataProvider;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CheckoutLineItemsDataProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var FrontendProductPricesDataProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $frontendProductPricesDataProvider;

    /** @var CheckoutLineItemsDataProvider */
    private $provider;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productAvailabilityCache;

    /** @var ResolvedProductVisibilityProvider */
    private $resolvedProductVisibilityProvider;

    protected function setUp(): void
    {
        $this->frontendProductPricesDataProvider = $this->createMock(FrontendProductPricesDataProvider::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->productAvailabilityCache = $this->createMock(CacheInterface::class);
        $this->resolvedProductVisibilityProvider = $this->createMock(ResolvedProductVisibilityProvider::class);

        $this->provider = new CheckoutLineItemsDataProvider(
            $this->frontendProductPricesDataProvider,
            $this->authorizationChecker,
            $this->productAvailabilityCache,
            $this->resolvedProductVisibilityProvider
        );
    }

    /**
     * @dataProvider isEntitySupportedProvider
     */
    public function testIsEntitySupported(bool $expected, object $entity)
    {
        $this->assertEquals($expected, $this->provider->isEntitySupported($entity));
    }

    public function isEntitySupportedProvider(): array
    {
        return [
            ['expected' => false, 'data' => new \stdClass(),],
            ['expected' => true, 'entity' => new Checkout(),],
        ];
    }

    /**
     * @dataProvider priceDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetData(?Price $price, bool $isPriceFixed)
    {
        $freeFormProduct = 'freeFromProduct';
        $product1 = $this->getEntity(
            Product::class,
            ['id' => 1001, 'sku' => 'PRODUCT_SKU1', 'status' => Product::STATUS_ENABLED]
        );
        $product2 = $this->getEntity(
            Product::class,
            ['id' => 2002, 'sku' => 'PRODUCT_SKU2', 'status' => Product::STATUS_ENABLED]
        );
        $parentProduct = $this->getEntity(Product::class, ['id' => 1, 'sku' => 'PARENT_SKU']);
        $productUnit = $this->getEntity(ProductUnit::class, ['code' => 'code']);
        if (null === $price && !$isPriceFixed) {
            $expectedPrice = Price::create(13, 'USD');
        } else {
            $expectedPrice = $price;
        }

        $this->frontendProductPricesDataProvider->expects($this->atMost(1))
            ->method('getProductsMatchedPrice')
            ->willReturn([1001 => ['code' => $expectedPrice]]);

        $this->resolvedProductVisibilityProvider->expects($this->once())
            ->method('prefetch')
            ->with([$product1->getId(), $product2->getId()]);

        $lineItem1 = $this->getEntity(
            CheckoutLineItem::class,
            [
                'product' => $product1,
                'parentProduct' => $parentProduct,
                'freeFormProduct' => $freeFormProduct,
                'quantity' => 10,
                'productUnit' => $productUnit,
                'price' => $price,
                'fromExternalSource' => true,
                'comment' => 'line item comment',
                'shippingMethod' => 'flat_rate_1',
                'shippingMethodType' => 'primary',
                'shippingEstimateAmount' => 5.0,
            ]
        );
        $lineItem1->setPriceFixed($isPriceFixed)
            ->preSave();

        $lineItem2 = $this->getEntity(
            CheckoutLineItem::class,
            [
                'product' => $product2,
                'parentProduct' => $parentProduct,
                'freeFormProduct' => $freeFormProduct,
                'quantity' => 10,
                'productUnit' => $productUnit,
                'price' => $price,
                'fromExternalSource' => true,
                'comment' => 'line item comment',
                'shippingMethod' => 'flat_rate_2',
                'shippingMethodType' => 'primary',
                'shippingEstimateAmount' => 3.0,
            ]
        );
        $lineItem2->setPriceFixed($isPriceFixed)
            ->preSave();

        $checkout = $this->getEntity(Checkout::class, ['lineItems' => new ArrayCollection([$lineItem1, $lineItem2])]);

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturn(true);
        $this->productAvailabilityCache->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive([$product1->getId()], [$product2->getId()])
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->assertEquals(
            [
                [
                    'product' => $product1,
                    'parentProduct' => $parentProduct,
                    'productSku' => 'PRODUCT_SKU1',
                    'comment' => 'line item comment',
                    'freeFormProduct' => $freeFormProduct,
                    'quantity' => 10,
                    'productUnit' => $productUnit,
                    'productUnitCode' => 'code',
                    'price' => $expectedPrice,
                    'fromExternalSource' => true,
                    'shippingMethod' => 'flat_rate_1',
                    'shippingMethodType' => 'primary',
                    'shippingEstimateAmount' => 5.0,
                ],
                [
                    'product' => $product2,
                    'parentProduct' => $parentProduct,
                    'productSku' => 'PRODUCT_SKU2',
                    'comment' => 'line item comment',
                    'freeFormProduct' => $freeFormProduct,
                    'quantity' => 10,
                    'productUnit' => $productUnit,
                    'productUnitCode' => 'code',
                    'price' => $price,
                    'fromExternalSource' => true,
                    'shippingMethod' => 'flat_rate_2',
                    'shippingMethodType' => 'primary',
                    'shippingEstimateAmount' => 3.0,
                ],
            ],
            $this->provider->getData($checkout)
        );
    }

    public function testGetDataWithoutProduct()
    {
        $lineItem = $this->getEntity(CheckoutLineItem::class);
        $checkout = $this->getEntity(Checkout::class, ['lineItems' => new ArrayCollection([$lineItem])]);

        $expected = [
            [
                'product' => null,
                'parentProduct' => null,
                'productSku' => null,
                'comment' => null,
                'freeFormProduct' => null,
                'quantity' => null,
                'productUnit' => null,
                'productUnitCode' => null,
                'price' => null,
                'fromExternalSource' => false,
                'shippingMethod' => null,
                'shippingMethodType' => null,
                'shippingEstimateAmount' => null,
            ]
        ];
        $this->assertEquals($expected, $this->provider->getData($checkout));
    }

    public function testGetDataFromCache()
    {
        $this->frontendProductPricesDataProvider->expects($this->atMost(2))
            ->method('getProductsMatchedPrice')
            ->willReturn([]);

        $enabledProduct = $this->getEntity(
            Product::class,
            ['id' => 3, 'sku' => 'PRODUCT_SKU', 'status' => Product::STATUS_ENABLED]
        );
        $lineItem = $this->getEntity(CheckoutLineItem::class, ['product' => $enabledProduct]);
        $firstCheckout = $this->getEntity(
            Checkout::class,
            ['id' => 1, 'lineItems' => new ArrayCollection([$lineItem])]
        );
        $secondCheckout = $this->getEntity(
            Checkout::class,
            ['id' => 2, 'lineItems' => new ArrayCollection([$lineItem])]
        );

        $this->productAvailabilityCache->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [$enabledProduct->getId()],
                [$enabledProduct->getId()]
            )
            ->willReturnOnConsecutiveCalls(true, true);

        $expected = [
            [
                'productSku' => null,
                'comment' => null,
                'quantity' => null,
                'productUnit' => null,
                'productUnitCode' => null,
                'product' => $enabledProduct,
                'parentProduct' => null,
                'freeFormProduct' => null,
                'fromExternalSource' => false,
                'price' => null,
                'shippingMethod' => null,
                'shippingMethodType' => null,
                'shippingEstimateAmount' => null,
            ]
        ];

        // Save product availability status to the cache
        $this->assertEquals($expected, $this->provider->getData($firstCheckout));
        // Load product availability status from the cache
        $this->assertEquals($expected, $this->provider->getData($secondCheckout));
    }

    public function priceDataProvider(): array
    {
        return [
            'positive' => [Price::create(10, 'EUR'), false],
            'negative & auto-discovery prices' => [null, false],
            'negative & price is fixed' => [null, true],
        ];
    }
}
