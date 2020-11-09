<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DataProvider\LineItem;

use Doctrine\Common\Cache\CacheProvider;
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

class CheckoutLineItemsDataProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var FrontendProductPricesDataProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $frontendProductPricesDataProvider;

    /** @var CheckoutLineItemsDataProvider */
    protected $provider;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productAvailabilityCache;

    /** @var ResolvedProductVisibilityProvider */
    private $resolvedProductVisibilityProvider;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->frontendProductPricesDataProvider = $this->createMock(FrontendProductPricesDataProvider::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->productAvailabilityCache = $this->createMock(CacheProvider::class);
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
     *
     * @param bool $expected
     * @param object $entity
     */
    public function testIsEntitySupported($expected, $entity)
    {
        $this->assertEquals($expected, $this->provider->isEntitySupported($entity));
    }

    /**
     * @return array
     */
    public function isEntitySupportedProvider()
    {
        return [
            ['expected' => false, 'data' => new \stdClass(),],
            ['expected' => true, 'entity' => new Checkout(),],
        ];
    }

    /**
     * @param Price|null $price
     * @param bool $isPriceFixed
     *
     * @dataProvider priceDataProvider
     */
    public function testGetData(Price $price = null, $isPriceFixed = false)
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

        /** @var CheckoutLineItem $lineItem */
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
            ]
        );
        $lineItem2->setPriceFixed($isPriceFixed)
            ->preSave();

        $checkout = $this->getEntity(Checkout::class, ['lineItems' => new ArrayCollection([$lineItem1, $lineItem2])]);

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturn(true);

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
                ],
            ],
            $this->provider->getData($checkout)
        );
    }

    public function testGetDataWithoutProduct()
    {
        /** @var CheckoutLineItem $lineItem */
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
                'fromExternalSource' => false
            ]
        ];
        $this->assertEquals($expected, $this->provider->getData($checkout));
    }

    public function testGetDataFromCache()
    {
        $this->frontendProductPricesDataProvider
            ->expects($this->atMost(2))
            ->method('getProductsMatchedPrice')
            ->willReturn([]);

        $enabledProduct = $this->getEntity(
            Product::class,
            ['id' => 3, 'sku' => 'PRODUCT_SKU', 'status' => Product::STATUS_ENABLED]
        );
        /** @var CheckoutLineItem $lineItem */
        $lineItem = $this->getEntity(CheckoutLineItem::class, ['product' => $enabledProduct]);
        $firstCheckout = $this->getEntity(
            Checkout::class,
            ['id' => 1, 'lineItems' => new ArrayCollection([$lineItem])]
        );
        $secondCheckout = $this->getEntity(
            Checkout::class,
            ['id' => 2, 'lineItems' => new ArrayCollection([$lineItem])]
        );

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', $enabledProduct)
            ->willReturn(true);

        $this->productAvailabilityCache->expects($this->exactly(2))
            ->method('contains')
            ->withConsecutive(
                [$enabledProduct->getId()],
                [$enabledProduct->getId()]
            )
            ->willReturnOnConsecutiveCalls([
                [$enabledProduct->getId(), false],
                [$enabledProduct->getId(), true]
            ]);
        $this->productAvailabilityCache->expects($this->once())
            ->method('save')
            ->with($enabledProduct->getId())
            ->willReturn(true);
        $this->productAvailabilityCache->expects($this->once())
            ->method('fetch')
            ->with($enabledProduct->getId())
            ->willReturn(true);

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
                'price' => null
            ]
        ];

        // Save product availability status to the cache
        $this->assertEquals($expected, $this->provider->getData($firstCheckout));
        // Load product availability status from the cache
        $this->assertEquals($expected, $this->provider->getData($secondCheckout));
    }

    /**
     * @return array
     */
    public function priceDataProvider()
    {
        return [
            'positive' => ['price' => Price::create(10, 'EUR')],
            'negative & auto-discovery prices' => ['price' => null],
            'negative & price is fixed' => ['price' => null, 'isPriceFixed' => true],
        ];
    }
}
