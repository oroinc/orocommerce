<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Converter;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\PromotionBundle\Discount\Converter\LineItemsToDiscountLineItemsConverter;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LineItemsToDiscountLineItemsConverterTest extends TestCase
{
    use EntityTrait;

    private FrontendProductPricesDataProvider|MockObject $productPricesDataProvider;

    private ProductLineItemPriceProviderInterface|MockObject $productLineItemsPriceProvider;

    private LineItemsToDiscountLineItemsConverter $converter;

    protected function setUp(): void
    {
        $this->productPricesDataProvider = $this->createMock(FrontendProductPricesDataProvider::class);
        $this->productLineItemsPriceProvider = $this->createMock(ProductLineItemPriceProviderInterface::class);

        $this->converter = new LineItemsToDiscountLineItemsConverter($this->productPricesDataProvider);
        $this->converter->setProductLineItemsPriceProvider($this->productLineItemsPriceProvider);
    }

    /**
     * @dataProvider converterDataProvider
     */
    public function testConvert(array $lineItems, array $productLineItemsPrices, array $expected): void
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

        $price = Price::create(100, 'USD');

        $productUnit = new ProductUnit();
        $productUnit->setCode($unitCode);

        $lineItem = new LineItem();
        $lineItem->setUnit($productUnit);
        $lineItem->setProduct($product);
        $lineItem->setQuantity(10);

        $lineItemPrice = new ProductLineItemPrice($lineItem, Price::create(100, 'USD'), 1000);

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
                        ->setQuantity(10)
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
                        ->setQuantity(10)
                        ->setProduct($product)
                        ->setProductUnit($productUnit)
                        ->setSourceLineItem($lineItem)
                        ->setSubtotal(0),
                ],
            ],
        ];
    }

    /**
     * @dataProvider converterWhenNoProductLineItemPriceProviderDataProvider
     */
    public function testConvertWhenNoProductLineItemPriceProvider(
        array $lineItems,
        array $matchedPrices,
        array $expected
    ): void {
        $this->productPricesDataProvider->expects(self::once())
            ->method('getProductsMatchedPrice')
            ->with($lineItems)
            ->willReturn($matchedPrices);

        $this->converter->setProductLineItemsPriceProvider(null);
        self::assertEquals($expected, $this->converter->convert($lineItems));
    }

    public function converterWhenNoProductLineItemPriceProviderDataProvider(): array
    {
        $productId = 42;
        $unitCode = 'item';

        $product = $this->getEntity(Product::class, ['id' => $productId]);

        $price = Price::create(100, 'USD');

        $productUnit = new ProductUnit();
        $productUnit->setCode($unitCode);

        $lineItem = new LineItem();
        $lineItem->setUnit($productUnit);
        $lineItem->setProduct($product);
        $lineItem->setQuantity(10);

        $lineItemWithoutProduct = new LineItem();
        $lineItemWithoutProduct->setUnit($productUnit);
        $lineItemWithoutProduct->setQuantity(10);

        return [
            'with matched prices' => [
                'lineItems' => [$lineItem, $lineItemWithoutProduct],
                'matchedPrices' => [
                    $productId => [
                        $unitCode => $price,
                    ],
                ],
                'expected' => [
                    (new DiscountLineItem())
                        ->setQuantity(10)
                        ->setProduct($product)
                        ->setProductUnit($productUnit)
                        ->setSourceLineItem($lineItem)
                        ->setPrice($price)
                        ->setSubtotal($price->getValue() * $lineItem->getQuantity()),
                ],
            ],
            'without matched prices' => [
                'lineItems' => [$lineItem, $lineItemWithoutProduct],
                'matchedPrices' => [
                    $productId => [
                        'box' => $price,
                    ],
                ],
                'expected' => [
                    (new DiscountLineItem())
                        ->setQuantity(10)
                        ->setProduct($product)
                        ->setProductUnit($productUnit)
                        ->setSourceLineItem($lineItem)
                        ->setSubtotal(0),
                ],
            ],
        ];
    }
}
