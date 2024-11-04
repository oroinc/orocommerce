<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Converter;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\PromotionBundle\Discount\Converter\LineItemsToDiscountLineItemsConverter;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

class LineItemsToDiscountLineItemsConverterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private ProductLineItemPriceProviderInterface|MockObject $productLineItemsPriceProvider;

    private LineItemsToDiscountLineItemsConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $this->productLineItemsPriceProvider = $this->createMock(ProductLineItemPriceProviderInterface::class);
        $this->converter = new LineItemsToDiscountLineItemsConverter($this->productLineItemsPriceProvider);
    }

    /**
     * @dataProvider converterDataProvider
     */
    public function testConvert(array $lineItems, array $productLineItemsPrices, array $expected)
    {
        $this->productLineItemsPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with($lineItems)
            ->willReturn($productLineItemsPrices);

        self::assertEquals($expected, $this->converter->convert($lineItems));
    }

    public function converterDataProvider(): array
    {
        $productId = 42;
        $unitCode = 'item';

        $product = $this->getEntity(Product::class, ['id' => $productId]);

        $price = Price::create(12.3456, 'USD');

        $productUnit = new ProductUnit();
        $productUnit->setCode($unitCode);

        $lineItem = new LineItem();
        $lineItem->setUnit($productUnit);
        $lineItem->setProduct($product);
        $lineItem->setQuantity(2);

        $lineItemPrice = new ProductLineItemPrice($lineItem, $price, round(12.3456 * 2, 2));

        $lineItemWithoutProduct = new LineItem();
        $lineItemWithoutProduct->setUnit($productUnit);
        $lineItemWithoutProduct->setQuantity(10);

        return [
            'with prices' => [
                'lineItems' => [$lineItem, $lineItemWithoutProduct],
                'lineItemsPrices' => [
                    $lineItemPrice,
                ],
                'expected' => [
                    (new DiscountLineItem())
                        ->setQuantity(2)
                        ->setProduct($product)
                        ->setProductUnit($productUnit)
                        ->setSourceLineItem($lineItem)
                        ->setPrice($price)
                        ->setSubtotal($price->getValue() * $lineItem->getQuantity()),
                ],
            ],
            'without prices' => [
                'lineItems' => [$lineItem, $lineItemWithoutProduct],
                'lineItemsPrices' => [],
                'expected' => [
                    (new DiscountLineItem())
                        ->setQuantity(2)
                        ->setProduct($product)
                        ->setProductUnit($productUnit)
                        ->setSourceLineItem($lineItem)
                        ->setSubtotal(0),
                ],
            ],
        ];
    }
}
