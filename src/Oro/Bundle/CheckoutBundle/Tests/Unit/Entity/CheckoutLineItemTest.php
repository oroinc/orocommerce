<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Entity;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class CheckoutLineItemTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testProperties()
    {
        $id = 123;
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
            ['priceType', CheckoutLineItem::PRICE_TYPE_BUNDLED],
            ['fromExternalSource', true],
            ['comment', 'comment'],
        ];

        $entity = new CheckoutLineItem();
        /** Assert Default Values */
        $this->assertFalse($entity->isFromExternalSource());
        $this->assertFalse($entity->isPriceFixed());

        $this->assertPropertyAccessors($entity, $properties);
        $this->setValue($entity, 'id', $id);
        $this->assertSame($id, $entity->getEntityIdentifier());
        $this->assertSame($entity, $entity->getProductHolder());
    }

    public function testToString()
    {
        $entity = new CheckoutLineItem();
        $entity->setProductSku('SKU');
        $this->assertEquals('SKU', sprintf('%s', $entity));
    }

    public function testPrice()
    {
        $entity = new CheckoutLineItem();
        $this->assertNull($entity->getPrice());
        $this->assertNull($entity->getCurrency());
        $this->assertNull($entity->getValue());

        $price = Price::create(1, 'USD');
        $entity->setPrice($price);
        $this->assertSame($price->getCurrency(), $entity->getCurrency());
        $this->assertSame((float)$price->getValue(), $entity->getValue());
    }
}
