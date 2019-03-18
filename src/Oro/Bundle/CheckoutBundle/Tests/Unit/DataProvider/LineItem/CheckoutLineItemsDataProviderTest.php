<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DataProvider\LineItem;

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

class CheckoutLineItemsDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var FrontendProductPricesDataProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $frontendProductPricesDataProvider;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var CheckoutLineItemsDataProvider */
    protected $provider;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->frontendProductPricesDataProvider = $this->createMock(FrontendProductPricesDataProvider::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
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
        $product = $this->getEntity(Product::class, ['id' => 2, 'sku' => 'PRODUCT_SKU']);
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
                'comment' => 'line item comment',
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
                'comment' => 'line item comment',
                'freeFormProduct' => $freeFormProduct,
                'quantity' => 10,
                'productUnit' => $productUnit,
                'productUnitCode' => 'code',
                'price' => $expectedPrice,
                'fromExternalSource' => true,
            ],
        ];

        $this->assertEquals($expected, $this->provider->getData($checkout));
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
