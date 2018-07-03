<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Pricing;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Quote\Pricing\QuotePriceComparator;

class QuotePriceComparatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider isPriceChangedDataProvider
     *
     * @param Quote $quote
     * @param bool $expected
     */
    public function testIsQuoteProductOfferPriceChanged(Quote $quote, $expected)
    {
        $comparator = new QuotePriceComparator($quote);

        $this->assertEquals($expected, $comparator->isQuoteProductOfferPriceChanged('psku', 'punit', 42, 'USD', 100));
    }

    /**
     * @return \Generator
     */
    public function isPriceChangedDataProvider()
    {
        yield 'no data' => [
            'quote' => $this->getQuote(null, null, null, null, null),
            'expected' => true
        ];

        yield 'empty sku' => [
            'quote' => $this->getQuote(null, 'punit', 42, 'USD', 100),
            'expected' => true
        ];

        yield 'empty unit' => [
            'quote' => $this->getQuote('psku', null, 42, 'USD', 100),
            'expected' => true
        ];

        yield 'empty quantity' => [
            'quote' => $this->getQuote('psku', 'punit', null, 'USD', 100),
            'expected' => true
        ];

        yield 'empty currency' => [
            'quote' => $this->getQuote('psku', 'punit', 42, null, 100),
            'expected' => true
        ];

        yield 'empty price' => [
            'quote' => $this->getQuote('psku', 'punit', 42, 'USD', null),
            'expected' => true
        ];

        yield 'price changed' => [
            'quote' => $this->getQuote('psku', 'punit', 42, 'USD', 100.5),
            'expected' => true
        ];

        yield 'the same data' => [
            'quote' => $this->getQuote('psku', 'punit', 42, 'USD', 100),
            'expected' => false
        ];
    }

    /**
     * @param string $sku
     * @param string $unit
     * @param int $qty
     * @param string $currency
     * @param float $price
     *
     * @return Quote|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getQuote($sku, $unit, $qty, $currency, $price)
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($unit);

        $product = $this->createMock(Product::class);
        $product->expects($this->any())->method('getSku')->willReturn($sku);

        $quoteProductOffer = new QuoteProductOffer();
        $quoteProductOffer->setPrice(Price::create($price, $currency))
            ->setProductUnit($productUnit)
            ->setQuantity($qty);

        $quoteProduct = new QuoteProduct();
        $quoteProduct->setProduct($product)->addQuoteProductOffer($quoteProductOffer);

        $quote = new Quote();
        $quote->addQuoteProduct($quoteProduct);

        return $quote;
    }
}
