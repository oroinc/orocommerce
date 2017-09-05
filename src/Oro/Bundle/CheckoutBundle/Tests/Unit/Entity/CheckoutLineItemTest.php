<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Entity;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;

class CheckoutLineItemTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['checkout', new Checkout()],
            ['product', new Product()],
            ['productSku', 'SKU'],
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
        $this->assertFalse($entity->isRequirePriceRecalculation());

        $this->assertPropertyAccessors($entity, $properties);
    }

    public function testToString()
    {
        $entity = new CheckoutLineItem();
        $entity->setProductSku('SKU');
        $this->assertEquals('SKU', sprintf('%s', $entity));
    }

    /**
     * @param bool $expected
     * @param Product $product
     *
     * @param Product $currentProduct
     *
     * @dataProvider setProductDataProvider
     */
    public function testSetProduct($expected, Product $product = null, Product $currentProduct = null)
    {
        $entity = new CheckoutLineItem();
        $this->setProperty($entity, 'product', $currentProduct);
        $entity->setProduct($product);
        $this->assertEquals($product, $entity->getProduct());
        $this->assertEquals($expected, $entity->isRequirePriceRecalculation());
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

    /**
     * @return array
     */
    public function setProductDataProvider()
    {
        return [
            'positive' => ['expected' => true, 'product' => $this->getEntity(Product::class, ['id' => 1])],
            'negative' => ['expected' => false, 'product' => null],
            'custom with same value' => [
                'expected' => false,
                'product' => $this->getEntity(Product::class, ['id' => 1]),
                'currentProduct' => $this->getEntity(Product::class, ['id' => 1]),
            ],
            'custom with different values' => [
                'expected' => true,
                'product' => $this->getEntity(Product::class, ['id' => 1]),
                'currentProduct' => $this->getEntity(Product::class, ['id' => 2]),
            ],
        ];
    }

    /**
     * @param bool $expected
     * @param int $quantity
     * @param int $currentQuantity
     *
     * @dataProvider setQuantityDataProvider
     */
    public function testSetQuantity($expected, $quantity, $currentQuantity = null)
    {
        $entity = new CheckoutLineItem();
        $this->setProperty($entity, 'quantity', $currentQuantity);
        $entity->setQuantity($quantity);
        $this->assertEquals($quantity, $entity->getQuantity());
        $this->assertEquals($expected, $entity->isRequirePriceRecalculation());
    }

    /**
     * @return array
     */
    public function setQuantityDataProvider()
    {
        return [
            'positive' => ['expected' => true, 'quantity' => 1],
            'negative' => ['expected' => false, 'quantity' => null],
            'custom with same value' => ['expected' => false, 'quantity' => 1, 'currentQuantity' => 1],
            'custom with different values' => ['expected' => true, 'quantity' => 1, 'currentQuantity' => 2],
        ];
    }

    /**
     * @param bool $expected
     * @param ProductUnit|null $productUnit
     * @param ProductUnit|null $currentProductUnit
     *
     * @dataProvider setProductUnitDataProvider
     */
    public function testSetProductUnit(
        $expected,
        ProductUnit $productUnit = null,
        ProductUnit $currentProductUnit = null
    ) {
        $entity = new CheckoutLineItem();
        $this->setProperty($entity, 'productUnit', $currentProductUnit);
        $entity->setProductUnit($productUnit);
        $this->assertEquals($productUnit, $entity->getProductUnit());
        $this->assertEquals($expected, $entity->isRequirePriceRecalculation());
    }

    /**
     * @return array
     */
    public function setProductUnitDataProvider()
    {
        return [
            'positive' => [
                'expected' => true,
                'productUnit' => $this->getEntity(ProductUnit::class, ['code' => 'unit1']),
            ],
            'negative' => ['expected' => false, 'productUnit' => null],
            'custom with same product' => [
                'expected' => false,
                'productUnit' => $this->getEntity(ProductUnit::class, ['code' => 'unit1']),
                'currentProductUnit' => $this->getEntity(ProductUnit::class, ['code' => 'unit1']),
            ],
            'custom with different values' => [
                'expected' => true,
                'productUnit' => $this->getEntity(ProductUnit::class, ['code' => 'unit1']),
                'currentProductUnit' => $this->getEntity(ProductUnit::class, ['code' => 'unit2']),
            ],
        ];
    }

    /**
     * @param CheckoutLineItem $entity
     * @param string $propertyName
     * @param mixed $value
     */
    protected function setProperty(CheckoutLineItem $entity, $propertyName, $value)
    {
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($entity, $value);
    }
}
