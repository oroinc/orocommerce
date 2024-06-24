<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Entity;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class CheckoutLineItemTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $id = 123;
        $checksum = sha1('sample-line-item');
        $properties = [
            ['id', $id],
            ['checkout', new Checkout()],
            ['product', new Product()],
            //Allow null as product, required for Quote
            ['product', null],
            ['productSku', 'SKU'],
            //Allow null as parent product, required for Quote
            ['parentProduct', null],
            ['parentProduct', new Product()],
            ['freeFormProduct', 'FREE_FORM_PRODUCT'],
            ['quantity', 1],
            ['productUnit', new ProductUnit()],
            ['productUnitCode', 'PRODUCT_UNIT_CODE'],
            ['value', 2.0],
            ['currency', 'USD'],
            ['priceType', PriceTypeAwareInterface::PRICE_TYPE_BUNDLED],
            ['fromExternalSource', true],
            ['comment', 'comment'],
            ['shippingMethod', 'SHIPPING_METHOD'],
            ['shippingMethodType', 'SHIPPING_METHOD_TYPE'],
            ['shippingEstimateAmount', 3.0],
            ['checksum', $checksum],
        ];

        $entity = new CheckoutLineItem();
        /** Assert Default Values */
        self::assertFalse($entity->isFromExternalSource());
        self::assertFalse($entity->isPriceFixed());

        self::assertPropertyAccessors($entity, $properties);

        ReflectionUtil::setId($entity, $id);
        self::assertSame($id, $entity->getEntityIdentifier());
        self::assertSame($entity, $entity->getProductHolder());

        self::assertPropertyCollection($entity, 'kitItemLineItems', new CheckoutProductKitItemLineItem());
    }

    public function testToString(): void
    {
        $entity = new CheckoutLineItem();
        $entity->setProductSku('SKU');
        self::assertEquals('SKU', sprintf('%s', $entity));
    }

    public function testPrice(): void
    {
        $entity = new CheckoutLineItem();
        self::assertNull($entity->getPrice());
        self::assertNull($entity->getCurrency());
        self::assertNull($entity->getValue());

        $price = Price::create(1, 'USD');
        $entity->setPrice($price);
        self::assertSame($price->getCurrency(), $entity->getCurrency());
        self::assertSame((float)$price->getValue(), $entity->getValue());
    }

    public function testShippingCost(): void
    {
        $entity = new CheckoutLineItem();
        self::assertNull($entity->getShippingCost());
        $entity->setCurrency('USD');
        $entity->setShippingEstimateAmount(5.00);

        $shippingCost = $entity->getShippingCost();
        self::assertInstanceOf(Price::class, $shippingCost);
        self::assertSame($shippingCost->getCurrency(), $entity->getCurrency());
        self::assertSame((float)$shippingCost->getValue(), $entity->getShippingEstimateAmount());
    }

    /**
     * @dataProvider getDataToTestShippingMethods
     */
    public function testHasShippingMethod(?string $shippingMethod, ?string $shippingMethodType, bool $expected): void
    {
        $lineItem = new CheckoutLineItem();
        $lineItem->setShippingMethod($shippingMethod)
            ->setShippingMethodType($shippingMethodType);

        self::assertEquals($expected, $lineItem->hasShippingMethodData());
    }

    public function getDataToTestShippingMethods(): array
    {
        return [
            [
                'shippingMethod' => 'METHOD',
                'shippingMethodType' => 'TYPE',
                'expected' => true
            ],
            [
                'shippingMethod' => null,
                'shippingMethodType' => 'TYPE',
                'expected' => false
            ],
            [
                'shippingMethod' => 'METHOD',
                'shippingMethodType' => null,
                'expected' => false
            ],
            [
                'shippingMethod' => null,
                'shippingMethodType' => null,
                'expected' => false
            ]
        ];
    }
}
