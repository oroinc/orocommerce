<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Pricing;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Provider\QuoteProductPriceProvider;
use Oro\Bundle\SaleBundle\Quote\Pricing\QuotePriceComparator;
use Oro\Component\Testing\Unit\EntityTrait;

class QuotePriceComparatorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var QuotePriceComparator */
    private $comparator;

    /** @var Quote */
    private $quote;

    /** @var QuoteProductPriceProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    protected function setUp(): void
    {
        $this->quote = new Quote();
        $this->provider = $this->createMock(QuoteProductPriceProvider::class);
        $this->comparator = new QuotePriceComparator($this->quote, $this->provider);
    }

    public function testIsQuoteProductOfferPriceChangedNotChanged()
    {
        $matchedPrice = $this->getEntity(Price::class, ['value' => 99.99]);

        $this->provider->expects($this->once())
            ->method('getMatchedProductPrice')
            ->with(
                $this->quote,
                'psku',
                'punit',
                42,
                'USD'
            )
            ->willReturn($matchedPrice);

        $this->assertFalse($this->comparator->isQuoteProductOfferPriceChanged('psku', 'punit', 42, 'USD', 99.99));
    }

    public function testIsQuoteProductOfferPriceChangedChanged()
    {
        $matchedPrice = $this->getEntity(Price::class, ['value' => 50.00]);

        $this->provider->expects($this->once())
            ->method('getMatchedProductPrice')
            ->with(
                $this->quote,
                'psku',
                'punit',
                42,
                'USD'
            )
            ->willReturn($matchedPrice);

        $this->assertTrue($this->comparator->isQuoteProductOfferPriceChanged('psku', 'punit', 42, 'USD', 99.99));
    }

    public function testIsQuoteProductOfferPriceChangedNoMatchedPrice()
    {
        $this->provider->expects($this->once())
            ->method('getMatchedProductPrice')
            ->with(
                $this->quote,
                'psku',
                'punit',
                42,
                'USD'
            )
            ->willReturn(null);

        $this->assertTrue($this->comparator->isQuoteProductOfferPriceChanged('psku', 'punit', 42, 'USD', 99.99));
    }
}
