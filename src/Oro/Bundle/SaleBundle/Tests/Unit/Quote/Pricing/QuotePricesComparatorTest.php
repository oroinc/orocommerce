<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Pricing;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Quote\Pricing\QuotePricesComparator;

class QuotePricesComparatorTest extends \PHPUnit\Framework\TestCase
{
    private QuotePricesComparator $comparator;

    protected function setUp(): void
    {
        $this->comparator = new QuotePricesComparator();
    }

    public function testIsPriceEqualsToMatchingListedPriceWhenNoTierPrices(): void
    {
        self::assertFalse($this->comparator->isPriceEqualsToMatchingListedPrice(new QuoteProductOffer(), []));
    }

    public function testIsPriceEqualsToMatchingListedPriceWhenNoItemPrice(): void
    {
        $quoteProductItem = new QuoteProductOffer();
        $product = new Product();
        $price1 = Price::create(12.3456, 'USD');
        $unitItem = (new ProductUnit())->setCode('item');
        $tierPrices = [new ProductPriceDTO($product, $price1, 1, $unitItem)];

        self::assertFalse($this->comparator->isPriceEqualsToMatchingListedPrice($quoteProductItem, $tierPrices));
    }

    public function testIsPriceEqualsToMatchingListedPriceWhenNoMatchingPrice(): void
    {
        $unitEach = (new ProductUnit())->setCode('each');
        $quoteProductItem = (new QuoteProductOffer())
            ->setQuantity(1.2345)
            ->setProductUnit($unitEach);

        $product = new Product();
        $price1 = Price::create(12.3456, 'USD');
        $unitItem = (new ProductUnit())->setCode('item');
        $tierPrices = [new ProductPriceDTO($product, $price1, 1, $unitItem)];

        self::assertFalse($this->comparator->isPriceEqualsToMatchingListedPrice($quoteProductItem, $tierPrices));
    }

    public function testIsPriceEqualsToMatchingListedPriceWhenNotMatch(): void
    {
        $unitItem = (new ProductUnit())->setCode('item');
        $quoteProductItem = (new QuoteProductOffer())
            ->setQuantity(2.3456)
            ->setProductUnit($unitItem)
            ->setPrice(Price::create(12.34561, 'USD'));

        $product = new Product();
        $price1 = Price::create(12.345601, 'USD');
        $tierPrices = [new ProductPriceDTO($product, $price1, 1, $unitItem)];

        self::assertFalse($this->comparator->isPriceEqualsToMatchingListedPrice($quoteProductItem, $tierPrices));
    }

    public function testIsPriceEqualsToMatchingListedPriceWhenMatch(): void
    {
        $unitItem = (new ProductUnit())->setCode('item');
        $quoteProductItem = (new QuoteProductOffer())
            ->setQuantity(2.3456)
            ->setProductUnit($unitItem)
            ->setPrice(Price::create(12.3456, 'USD'));

        $product = new Product();
        $price1 = Price::create(12.3456001, 'USD');
        $tierPrices = [new ProductPriceDTO($product, $price1, 1, $unitItem)];

        self::assertTrue($this->comparator->isPriceEqualsToMatchingListedPrice($quoteProductItem, $tierPrices));
    }

    public function testIsPriceOneOfListedPricesWhenNoItemPrice(): void
    {
        $quoteProductItem = new QuoteProductOffer();
        $product = new Product();
        $price1 = Price::create(12.3456, 'USD');
        $unitItem = (new ProductUnit())->setCode('item');
        $tierPrices = [new ProductPriceDTO($product, $price1, 1, $unitItem)];

        self::assertFalse($this->comparator->isPriceOneOfListedPrices($quoteProductItem, $tierPrices));
    }

    public function testIsPriceOneOfListedPricesWhenNoMatchingPrice(): void
    {
        $unitEach = (new ProductUnit())->setCode('each');
        $quoteProductItem = (new QuoteProductOffer())
            ->setQuantity(1.2345)
            ->setProductUnit($unitEach);

        $product = new Product();
        $price1 = Price::create(12.3456, 'USD');
        $unitItem = (new ProductUnit())->setCode('item');
        $tierPrices = [new ProductPriceDTO($product, $price1, 1, $unitItem)];

        self::assertFalse($this->comparator->isPriceOneOfListedPrices($quoteProductItem, $tierPrices));
    }

    public function testIsPriceOneOfListedPricesWhenNotMatch(): void
    {
        $unitItem = (new ProductUnit())->setCode('item');
        $quoteProductItem = (new QuoteProductOffer())
            ->setQuantity(2.3456)
            ->setProductUnit($unitItem)
            ->setPrice(Price::create(12.34561, 'USD'));

        $product = new Product();
        $price1 = Price::create(12.345601, 'USD');
        $price2 = Price::create(10.345601, 'USD');
        $tierPrices = [
            new ProductPriceDTO($product, $price1, 1, $unitItem),
            new ProductPriceDTO($product, $price2, 10, $unitItem),
        ];

        self::assertFalse($this->comparator->isPriceOneOfListedPrices($quoteProductItem, $tierPrices));
    }

    public function testIsPriceOneOfListedPricesWhenMatch(): void
    {
        $unitItem = (new ProductUnit())->setCode('item');
        $quoteProductItem = (new QuoteProductOffer())
            ->setQuantity(2.3456)
            ->setProductUnit($unitItem)
            ->setPrice(Price::create(12.3456, 'USD'));

        $product = new Product();
        $price1 = Price::create(12.3456001, 'USD');
        $price2 = Price::create(10.3456001, 'USD');

        $tierPrices = [
            new ProductPriceDTO($product, $price1, 1, $unitItem),
            new ProductPriceDTO($product, $price2, 10, $unitItem),
        ];

        self::assertTrue($this->comparator->isPriceOneOfListedPrices($quoteProductItem, $tierPrices));
    }
}
