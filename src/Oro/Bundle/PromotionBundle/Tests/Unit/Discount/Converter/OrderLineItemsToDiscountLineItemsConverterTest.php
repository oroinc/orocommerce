<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Converter;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\PromotionBundle\Discount\Converter\OrderLineItemsToDiscountLineItemsConverter;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Component\Testing\Unit\EntityTrait;

class OrderLineItemsToDiscountLineItemsConverterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private OrderLineItemsToDiscountLineItemsConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new OrderLineItemsToDiscountLineItemsConverter();
    }

    /**
     * @dataProvider converterDataProvider
     */
    public function testConvert(array $lineItems, array $expected)
    {
        $this->assertEquals($expected, $this->converter->convert($lineItems));
    }

    public function converterDataProvider(): array
    {
        $productId = 42;
        $unitCode = 'item';

        $product = $this->getEntity(Product::class, ['id' => $productId]);

        $price = Price::create(100, 'USD');

        $productUnit = new ProductUnit();
        $productUnit->setCode($unitCode);

        $lineItem = new OrderLineItem();
        $lineItem->setProductUnit($productUnit);
        $lineItem->setProduct($product);
        $lineItem->setQuantity(10);
        $lineItem->setPrice($price);

        $lineItemWithoutProduct = new OrderLineItem();
        $lineItemWithoutProduct->setProductUnit($productUnit);
        $lineItemWithoutProduct->setQuantity(10);

        return [
            [
                'lineItems' => [$lineItem, $lineItemWithoutProduct],
                'expected' => [
                    (new DiscountLineItem())
                        ->setQuantity(10)
                        ->setProduct($product)
                        ->setProductUnit($productUnit)
                        ->setSourceLineItem($lineItem)
                        ->setPrice($price)
                        ->setSubtotal($price->getValue() * $lineItem->getQuantity())
                ]
            ]
         ];
    }
}
