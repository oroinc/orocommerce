<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DataProvider\LineItem;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\DataProvider\LineItem\CheckoutLineItemsDataProvider;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CheckoutLineItemsDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var FrontendProductPricesDataProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $frontendProductPricesDataProvider;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var CheckoutLineItemsDataProvider */
    protected $provider;

    /** @var AuthorizationCheckerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $authorizationChecker;

    /** @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $productAvailabilityCache;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->frontendProductPricesDataProvider = $this->createMock(FrontendProductPricesDataProvider::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->productAvailabilityCache = $this->createMock(CacheProvider::class);
        $this->provider = new CheckoutLineItemsDataProvider(
            $this->frontendProductPricesDataProvider,
            $this->doctrine
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
        $product = $this->getEntity(
            Product::class,
            ['id' => 2, 'sku' => 'PRODUCT_SKU', 'status' => Product::STATUS_ENABLED]
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
            ->willReturn([2 => ['code' => $expectedPrice]]);

        /** @var CheckoutLineItem $lineItem */
        $lineItem = $this->getEntity(
            CheckoutLineItem::class,
            [
                'product' => $product,
                'parentProduct' => $parentProduct,
                'freeFormProduct' => $freeFormProduct,
                'quantity' => 10,
                'productUnit' => $productUnit,
                'price' => $price,
                'fromExternalSource' => true,
            ]
        );
        $lineItem->setPriceFixed($isPriceFixed)
            ->preSave();

        $checkout = $this->getEntity(Checkout::class, ['lineItems' => new ArrayCollection([$lineItem])]);

        $expected = [
            [
                'product' => $product,
                'parentProduct' => $parentProduct,
                'productSku' => 'PRODUCT_SKU',
                'freeFormProduct' => $freeFormProduct,
                'quantity' => 10,
                'productUnit' => $productUnit,
                'productUnitCode' => 'code',
                'price' => $expectedPrice,
                'fromExternalSource' => true,
            ],
        ];

        $this->provider->setProductAvailabilityCache($this->productAvailabilityCache);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);
        $this->provider->setAuthorizationChecker($this->authorizationChecker);

        $this->assertEquals($expected, $this->provider->getData($checkout));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetDataWithoutCacheProvider()
    {
        /** @var CheckoutLineItem $lineItem */
        $lineItem = $this->getEntity(CheckoutLineItem::class);
        $checkout = $this->getEntity(Checkout::class, ['lineItems' => new ArrayCollection([$lineItem])]);
        $this->provider->getData($checkout);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetDataWithoutAuthorizationChecker()
    {
        /** @var CheckoutLineItem $lineItem */
        $lineItem = $this->getEntity(CheckoutLineItem::class);
        $checkout = $this->getEntity(Checkout::class, ['lineItems' => new ArrayCollection([$lineItem])]);
        $this->provider->setProductAvailabilityCache($this->productAvailabilityCache);
        $this->provider->getData($checkout);
    }

    public function testGetDataWithoutProduct()
    {
        /** @var CheckoutLineItem $lineItem */
        $lineItem = $this->getEntity(CheckoutLineItem::class);
        $checkout = $this->getEntity(Checkout::class, ['lineItems' => new ArrayCollection([$lineItem])]);
        
        $this->provider->setProductAvailabilityCache($this->productAvailabilityCache);
        $this->provider->setAuthorizationChecker($this->authorizationChecker);

        $expected = [
            [
                'product' => null,
                'parentProduct' => null,
                'productSku' => null,
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
        $this->provider->setAuthorizationChecker($this->authorizationChecker);

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
        $this->provider->setProductAvailabilityCache($this->productAvailabilityCache);

        $expected = [
            [
                'productSku' => null,
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
